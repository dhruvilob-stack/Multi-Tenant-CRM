<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MailComposerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PanelLoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\Admin\InvoicePdfController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\UserPanelAccessController;
use App\Http\Controllers\ProfileLocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationSectionController;
use App\Http\Controllers\OrganizationMailAttachmentController;
use App\Http\Controllers\SuperAdmin\TenantPanelAccessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/', function () {
    return view('home', [
        'superAdminUrl' => url('/super-admin/login'),
        'tenantDemoUrl' => url('/blueorbit/login') . '?' . http_build_query([
            'email' => 'blueorbit@gmail.com',
            'password' => 'password',
        ]),
    ]);
})->name('landing');

Route::get('/demo-image/{file}', function (string $file): BinaryFileResponse {
    abort_unless(in_array($file, ['1.png', '2.png', '3.png'], true), 404);
    $path = base_path($file);
    abort_unless(file_exists($path), 404);

    return response()->file($path, [
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('demo.image');

Route::get('/platform/login', function () {
    return redirect('/super-admin/login');
})->name('platform.login');

Route::get('/platform/switch', function (Request $request) {
    // Invalidate session directly to avoid resolving tenant guard user when tenant DB is not initialized.
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/super-admin/login');
})->name('platform.switch');

Route::get('/login', function () {
    return redirect('/super-admin/login');
})->name('login');

Route::post('/super-admin/login', [PanelLoginController::class, 'loginSuperAdmin'])
    ->name('super-admin.login.submit');

Route::post('/{tenant}/login', [PanelLoginController::class, 'loginTenant'])
    ->where(['tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+'])
    ->name('tenant.login.submit');

Route::get('/forgot-password', [ForgotPasswordController::class, 'show']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'send']);
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

Route::prefix('invitation/{token}')->group(function (): void {
    Route::get('/', [InvitationController::class, 'showAccept']);
    Route::post('/set-password', [InvitationController::class, 'setPassword']);
    Route::get('/verify', [InvitationController::class, 'verify']);
});

Route::prefix('{role}/invitation/{token}')->group(function (): void {
    Route::get('/', [InvitationController::class, 'showAcceptByRole']);
    Route::post('/set-password', [InvitationController::class, 'setPasswordByRole']);
    Route::get('/verify', [InvitationController::class, 'verifyByRole']);
});


Route::get('/demo/flow', [DemoController::class, 'flow']);
Route::get('/demo/navigation', [DemoController::class, 'navigation']);

Route::prefix('{tenant}')
    ->where(['tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+'])
    ->middleware('auth:tenant')
    ->group(function (): void {
        Route::post('/organizations/{id}/activate', [WorkflowController::class, 'activateOrganization']);
        Route::post('/organizations/{id}/suspend', [WorkflowController::class, 'suspendOrganization']);

        Route::post('/manufacturers/{id}/send-invitation', [WorkflowController::class, 'sendManufacturerInvitation']);
        Route::post('/distributors/{id}/invite-vendor', [WorkflowController::class, 'inviteVendor']);
        Route::post('/vendors/{id}/invite-consumer', [WorkflowController::class, 'inviteConsumer']);

        Route::post('/products/{id}/duplicate', [WorkflowController::class, 'duplicateProduct']);

        Route::post('/quotations/{id}/send', [WorkflowController::class, 'sendQuotation']);
        Route::post('/quotations/{id}/negotiate', [WorkflowController::class, 'negotiateQuotation']);
        Route::post('/quotations/{id}/confirm', [WorkflowController::class, 'confirmQuotation']);
        Route::post('/quotations/{id}/reject', [WorkflowController::class, 'rejectQuotation']);
        Route::post('/quotations/{id}/convert-to-invoice', [WorkflowController::class, 'convertQuotationToInvoice']);

        Route::post('/invoices/{id}/approve', [WorkflowController::class, 'approveInvoice']);
        Route::post('/invoices/{id}/send', [WorkflowController::class, 'sendInvoice']);
        Route::post('/invoices/{id}/mark-paid', [WorkflowController::class, 'markInvoicePaid']);
        Route::post('/invoices/{id}/cancel', [WorkflowController::class, 'cancelInvoice']);
        Route::post('/invoices/{id}/credit', [WorkflowController::class, 'creditInvoice']);
        Route::get('/workflows/invoices/{id}/pdf', [InvoicePdfController::class, 'download'])->name('admin.invoices.pdf');

        Route::post('/orders/{id}/confirm', [WorkflowController::class, 'confirmOrder']);
        Route::post('/orders/{id}/process', [WorkflowController::class, 'processOrder']);
        Route::post('/orders/{id}/ship', [WorkflowController::class, 'shipOrder']);
        Route::post('/orders/{id}/deliver', [WorkflowController::class, 'deliverOrder']);
        Route::post('/orders/{id}/generate-quotation-invoice', [WorkflowController::class, 'generateQuotationAndInvoiceForOrder']);
    });

Route::post('/profile/locale', [ProfileLocaleController::class, 'update'])
    ->middleware('auth:tenant')
    ->name('filament.profile.locale');

Route::post('/profile/update', [ProfileController::class, 'update'])
    ->middleware('auth:tenant')
    ->name('filament.profile.update');

Route::middleware('auth:super_admin')->get(
    '/super-admin/tenants/{organization}/open-admin',
    [TenantPanelAccessController::class, 'openAdmin']
)->name('super-admin.tenants.open-admin');

Route::get(
    '/{tenant}/org-admin/users/{user}/open-panel',
    [UserPanelAccessController::class, 'open']
)->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'user' => '[0-9]+',
])->name('org-admin.users.open-panel');

Route::get(
    '/{tenant}/impersonate/{user}',
    [ImpersonationController::class, 'start']
)->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'user' => '[0-9]+',
])->name('tenant.impersonate.start');

Route::get(
    '/{tenant}/stop-impersonation',
    [ImpersonationController::class, 'stop']
)->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
])->name('tenant.impersonate.stop');

Route::middleware('auth:tenant')->prefix('filament/notifications/sections')->group(function (): void {
    Route::get('/counts', [NotificationSectionController::class, 'counts'])
        ->name('filament.notifications.sections.counts');
    Route::post('/read', [NotificationSectionController::class, 'markRead'])
        ->name('filament.notifications.sections.read');
});

Route::middleware('auth:tenant')->prefix('mail/compose')->group(function (): void {
    Route::post('/send', [MailComposerController::class, 'send'])
        ->name('mail.compose.send');
    Route::post('/assist', [MailComposerController::class, 'assist'])
        ->name('mail.compose.assist');
    Route::get('/records', [MailComposerController::class, 'records'])
        ->name('mail.compose.records');
});

Route::middleware('auth:tenant')->get(
    '/{tenant}/inbox-mail/attachments/{recipient}/{index}',
    [OrganizationMailAttachmentController::class, 'download']
)->whereNumber('recipient')->whereNumber('index')->name('mail.attachments.download');

Route::get('/{tenant}/dashboard', function (string $tenant) {
    return redirect('/' . $tenant);
})->where(['tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+'])
    ->name('tenant.dashboard');

Route::get('/{tenant}/{role}/login', function (string $tenant, string $role) {
    $normalizedRole = match (strtolower(trim($role))) {
        'manufacturer', 'manufacturers' => 'manufacturer',
        'distributor', 'distributors' => 'distributor',
        'vendor', 'vendors' => 'vendor',
        'consumer', 'consumers' => 'consumer',
        'organization-admin', 'organization-admins', 'org_admin' => 'org_admin',
        default => null,
    };

    abort_if($normalizedRole === null, 404, 'Invalid role.');

    $landing = match ($normalizedRole) {
        'manufacturer' => "/{$tenant}/manufacturer",
        'distributor' => "/{$tenant}/distributor",
        'vendor' => "/{$tenant}/vendor",
        'consumer' => "/{$tenant}/consumer",
        default => "/{$tenant}",
    };

    request()->session()->put('tenant_expected_role', $normalizedRole);
    request()->session()->put('url.intended', $landing);

    $token = trim((string) request()->query('oa_prefill', request()->query('sa_prefill', '')));
    $prefillEmail = (string) request()->query('email', '');
    $prefillPassword = (string) request()->query('password', '');

    if ($token !== '') {
        try {
            $raw = Crypt::decryptString($token);
            $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            $issuedAt = (int) ($payload['issued_at'] ?? 0);
            $sameTenant = (string) ($payload['tenant'] ?? '') === $tenant;
            $isFresh = $issuedAt > 0 && (time() - $issuedAt) <= 300;

            if ($sameTenant && $isFresh) {
                $prefillEmail = (string) ($payload['email'] ?? $prefillEmail);
                $prefillPassword = (string) ($payload['password'] ?? $prefillPassword);
            }
        } catch (\Throwable) {
            // Ignore invalid token and fall back to plain query prefill.
        }
    }

    return response()->view('auth.tenant-login', [
        'tenant' => $tenant,
        'role' => $normalizedRole,
        'action' => url("/{$tenant}/login"),
        'prefillEmail' => $prefillEmail,
        'prefillPassword' => $prefillPassword,
    ]);
})->where([
            'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
            'role' => 'organization-admin|org_admin|organization-admins|manufacturer|manufacturers|distributor|distributors|vendor|vendors|consumer|consumers',
        ])->name('tenant.role.login');

Route::get('/{tenant}/{role}/dashboard', function (string $tenant, string $role) {
    $normalizedRole = match (strtolower(trim($role))) {
        'manufacturer', 'manufacturers' => 'manufacturer',
        'distributor', 'distributors' => 'distributor',
        'vendor', 'vendors' => 'vendor',
        'consumer', 'consumers' => 'consumer',
        'organization-admin', 'organization-admins', 'org_admin' => 'org_admin',
        default => null,
    };

    abort_if($normalizedRole === null, 404, 'Invalid role.');

    return redirect(match ($normalizedRole) {
        'manufacturer' => "/{$tenant}/manufacturer",
        'distributor' => "/{$tenant}/distributor",
        'vendor' => "/{$tenant}/vendor",
        'consumer' => "/{$tenant}/consumer",
        default => "/{$tenant}",
    });
})->where([
            'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
            'role' => 'organization-admin|org_admin|organization-admins|manufacturer|manufacturers|distributor|distributors|vendor|vendors|consumer|consumers',
        ])->name('tenant.role.dashboard');

Route::get('/{tenant}/distributors/{path?}', function (string $tenant, ?string $path = null) {
    $target = "/{$tenant}/distributor";
    if (filled($path)) {
        $target .= '/'.ltrim($path, '/');
    }

    return redirect($target);
})->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'path' => '.*',
]);

Route::get('/{tenant}/vendors/{path?}', function (string $tenant, ?string $path = null) {
    $target = "/{$tenant}/vendor";
    if (filled($path)) {
        $target .= '/'.ltrim($path, '/');
    }

    return redirect($target);
})->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'path' => '.*',
]);

Route::get('/{tenant}/consumers/{path?}', function (string $tenant, ?string $path = null) {
    $target = "/{$tenant}/consumer";
    if (filled($path)) {
        $target .= '/'.ltrim($path, '/');
    }

    return redirect($target);
})->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'path' => '.*',
]);
