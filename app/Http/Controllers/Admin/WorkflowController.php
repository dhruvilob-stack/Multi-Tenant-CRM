<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\InvoiceWorkflowService;
use App\Services\OrderWorkflowService;
use App\Services\QuotationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function activateOrganization(int $id): JsonResponse
    {
        $organization = Organization::query()->findOrFail($id);
        $organization->update(['status' => 'active']);

        return response()->json(['message' => 'Organization activated']);
    }

    public function suspendOrganization(int $id): JsonResponse
    {
        $organization = Organization::query()->findOrFail($id);
        $organization->update(['status' => 'suspended']);

        return response()->json(['message' => 'Organization suspended']);
    }

    public function sendManufacturerInvitation(Request $request, int $id, InvitationService $service): JsonResponse
    {
        $manufacturer = User::query()->where('role', 'manufacturer')->findOrFail($id);

        $request->validate(['email' => ['required', 'email']]);

        $inv = $service->sendInvitation($manufacturer->id, $request->string('email')->toString(), 'distributor', (int) $manufacturer->organization_id);

        return response()->json(['message' => 'Invitation sent', 'token' => $inv->token]);
    }

    public function inviteVendor(Request $request, int $id, InvitationService $service): JsonResponse
    {
        $distributor = User::query()->where('role', 'distributor')->findOrFail($id);

        $request->validate(['email' => ['required', 'email']]);
        $inv = $service->sendInvitation($distributor->id, $request->string('email')->toString(), 'vendor', (int) $distributor->organization_id);

        return response()->json(['message' => 'Vendor invitation sent', 'token' => $inv->token]);
    }

    public function inviteConsumer(Request $request, int $id, InvitationService $service): JsonResponse
    {
        $vendor = User::query()->where('role', 'vendor')->findOrFail($id);

        $request->validate(['email' => ['required', 'email']]);
        $inv = $service->sendInvitation($vendor->id, $request->string('email')->toString(), 'consumer', (int) $vendor->organization_id);

        return response()->json(['message' => 'Consumer invitation sent', 'token' => $inv->token]);
    }

    public function duplicateProduct(int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        $copy = $product->replicate();
        $copy->sku = $product->sku.'-COPY-'.now()->format('His');
        $copy->name = $product->name.' (Copy)';
        $copy->save();

        return response()->json(['message' => 'Product duplicated', 'product_id' => $copy->id]);
    }

    public function sendQuotation(int $id, QuotationWorkflowService $service): JsonResponse
    {
        $service->send(Quotation::query()->findOrFail($id));
        return response()->json(['message' => 'Quotation sent']);
    }

    public function negotiateQuotation(int $id, QuotationWorkflowService $service): JsonResponse
    {
        $service->negotiate(Quotation::query()->findOrFail($id));
        return response()->json(['message' => 'Quotation negotiated']);
    }

    public function confirmQuotation(int $id, QuotationWorkflowService $service): JsonResponse
    {
        $order = $service->convertToOrder(Quotation::query()->findOrFail($id));

        return response()->json([
            'message' => 'Quotation confirmed and converted to order',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    public function rejectQuotation(int $id, QuotationWorkflowService $service): JsonResponse
    {
        $service->reject(Quotation::query()->findOrFail($id));
        return response()->json(['message' => 'Quotation rejected']);
    }

    public function convertQuotationToInvoice(int $id, QuotationWorkflowService $service): JsonResponse
    {
        $invoice = $service->confirm(Quotation::query()->findOrFail($id));
        return response()->json(['message' => 'Converted to invoice', 'invoice_id' => $invoice->id]);
    }

    public function approveInvoice(int $id, InvoiceWorkflowService $service): JsonResponse
    {
        $service->approve(Invoice::query()->findOrFail($id));
        return response()->json(['message' => 'Invoice approved']);
    }

    public function markInvoicePaid(int $id, InvoiceWorkflowService $service): JsonResponse
    {
        $service->markPaid(Invoice::query()->findOrFail($id));
        return response()->json(['message' => 'Invoice paid']);
    }

    public function sendInvoice(int $id): JsonResponse
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->update(['status' => 'sent']);

        return response()->json(['message' => 'Invoice sent']);
    }

    public function cancelInvoice(int $id): JsonResponse
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Invoice cancelled']);
    }

    public function creditInvoice(int $id): JsonResponse
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->update(['status' => 'credit_invoice']);

        return response()->json(['message' => 'Credit invoice created']);
    }

    public function confirmOrder(int $id, OrderWorkflowService $service): JsonResponse
    {
        $service->confirm(Order::query()->findOrFail($id));

        return response()->json(['message' => 'Order confirmed']);
    }

    public function processOrder(int $id, OrderWorkflowService $service): JsonResponse
    {
        $service->process(Order::query()->findOrFail($id));

        return response()->json(['message' => 'Order moved to processing']);
    }

    public function shipOrder(int $id, OrderWorkflowService $service): JsonResponse
    {
        $service->ship(Order::query()->findOrFail($id));

        return response()->json(['message' => 'Order shipped']);
    }

    public function deliverOrder(int $id, OrderWorkflowService $service): JsonResponse
    {
        $service->deliver(Order::query()->findOrFail($id));

        return response()->json(['message' => 'Order delivered']);
    }

    public function generateQuotationAndInvoiceForOrder(int $id, OrderWorkflowService $service): JsonResponse
    {
        $invoice = $service->markPaidAndGenerateInvoice(Order::query()->findOrFail($id));

        return response()->json([
            'message' => 'Payment captured and invoice generated for order',
            'invoice_id' => $invoice->id,
        ]);
    }
}
