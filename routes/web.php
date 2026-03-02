<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\WorkflowController;
use App\Http\Controllers\ProfileLocaleController;
use App\Http\Controllers\NotificationSectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
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

Route::prefix('admin')->middleware('auth')->group(function (): void {
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

    Route::post('/orders/{id}/confirm', [WorkflowController::class, 'confirmOrder']);
    Route::post('/orders/{id}/ship', [WorkflowController::class, 'shipOrder']);
    Route::post('/orders/{id}/deliver', [WorkflowController::class, 'deliverOrder']);
});

Route::post('/profile/locale', [ProfileLocaleController::class, 'update'])
    ->middleware('auth')
    ->name('filament.profile.locale');

Route::middleware('auth')->prefix('filament/notifications/sections')->group(function (): void {
    Route::get('/counts', [NotificationSectionController::class, 'counts'])
        ->name('filament.notifications.sections.counts');
    Route::post('/read', [NotificationSectionController::class, 'markRead'])
        ->name('filament.notifications.sections.read');
});
