<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSubscriptionInvoice;
use App\Models\User;
use App\Services\SubscriptionInvoicePdfService;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Support\UserRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubscriptionInvoiceController extends Controller
{
    public function download(Request $request, $invoice): BinaryFileResponse
    {
        $tenant = app(TenantResolver::class)->resolveFromRequest($request);
        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        $this->activateTenantConnection($request, $tenant);
        $user = $request->user('tenant');
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            abort(403);
        }

        $record = OrganizationSubscriptionInvoice::query()
            ->findOrFail((int) $invoice);

        $recordOrg = $record->organization;
        if (! $recordOrg) {
            abort(404, 'Organization not found.');
        }

        $tenantKey = (string) ($tenant->slug ?: $tenant->id);
        $routeTenant = (string) ($request->route('tenant') ?? '');

        if (filled($recordOrg->tenant_id)) {
            if ((string) $recordOrg->tenant_id !== (string) $tenant->id
                && (string) $recordOrg->tenant_id !== $tenantKey
                && (string) $recordOrg->tenant_id !== $routeTenant) {
                abort(404);
            }
        } else {
            if (filled($recordOrg->slug)
                && $recordOrg->slug !== $tenantKey
                && $recordOrg->slug !== $routeTenant) {
                abort(404);
            }
        }

        if ($user && (int) $user->organization_id !== (int) $record->organization_id) {
            abort(403);
        }

        $generated = app(SubscriptionInvoicePdfService::class)->generate($record);
        if (isset($generated['path'])) {
            $record->update(['pdf_path' => $generated['path']]);
        }

        return response()->download(
            $generated['path'],
            $generated['filename'] ?? ($record->invoice_number.'.pdf'),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.($generated['filename'] ?? ($record->invoice_number.'.pdf')).'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        )->deleteFileAfterSend(true);
    }

    private function activateTenantConnection(Request $request, \App\Models\Tenant $tenant): void
    {
        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_slug', $tenant->slug ?: $tenant->id);
    }
}
