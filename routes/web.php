<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MailComposerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PanelLoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\Admin\InvoicePdfController;
use App\Http\Controllers\ProfileLocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationSectionController;
use App\Http\Controllers\OrganizationMailAttachmentController;
use App\Http\Controllers\SuperAdmin\TenantPanelAccessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/super-admin/login');

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
    return redirect("/{$tenant}/login");
})->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'role' => 'organization-admin|org_admin|manufacturer|distributor|vendor|consumer',
])->name('tenant.role.login');

Route::get('/{tenant}/{role}/dashboard', function (string $tenant, string $role) {
    return redirect('/' . $tenant);
})->where([
    'tenant' => '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|filament$|livewire.*|up$).+',
    'role' => 'organization-admin|org_admin|manufacturer|distributor|vendor|consumer',
])->name('tenant.role.dashboard');
