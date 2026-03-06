<?php

namespace App\Services;

use App\Models\CommissionPayout;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Quotation;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;

class OrganizationCurrencyService
{
    public function conversionFactor(string $fromCurrency, string $toCurrency): float
    {
        $from = strtoupper($fromCurrency);
        $to = strtoupper($toCurrency);

        if ($from === $to) {
            return 1.0;
        }

        $rates = SystemSettings::currencyRatesPerUsd();
        $fromRate = (float) ($rates[$from] ?? 1.0);
        $toRate = (float) ($rates[$to] ?? 1.0);

        if ($fromRate <= 0 || $toRate <= 0) {
            return 1.0;
        }

        return $toRate / $fromRate;
    }

    public function convertOrganizationMonetaryData(Organization $organization, string $fromCurrency, string $toCurrency): void
    {
        $factor = $this->conversionFactor($fromCurrency, $toCurrency);

        if (abs($factor - 1.0) < 0.000001) {
            return;
        }

        DB::transaction(function () use ($organization, $toCurrency, $factor): void {
            $orgId = (int) $organization->id;
            $currency = strtoupper($toCurrency);

            $this->convertQuotations($orgId, $factor);
            $this->convertInvoices($orgId, $currency, $factor);
            $this->convertOrders($orgId, $currency, $factor);
            $this->convertProductsAndInventory($orgId, $factor);
            $this->convertCommissionPayouts($orgId, $currency, $factor);
        });
    }

    private function convertQuotations(int $organizationId, float $factor): void
    {
        Quotation::query()
            ->whereHas('vendor', fn ($query) => $query->where('organization_id', $organizationId))
            ->select('id')
            ->chunkById(200, function ($rows) use ($factor): void {
                $ids = $rows->pluck('id')->all();

                DB::table('quotations')
                    ->whereIn('id', $ids)
                    ->update([
                        'subtotal' => DB::raw("ROUND(subtotal * {$factor}, 2)"),
                        'discount_amount' => DB::raw("ROUND(discount_amount * {$factor}, 2)"),
                        'tax_amount' => DB::raw("ROUND(tax_amount * {$factor}, 2)"),
                        'grand_total' => DB::raw("ROUND(grand_total * {$factor}, 2)"),
                    ]);

                DB::table('quotation_items')
                    ->whereIn('quotation_id', $ids)
                    ->update([
                        'selling_price' => DB::raw("ROUND(selling_price * {$factor}, 2)"),
                        'net_price' => DB::raw("ROUND(net_price * {$factor}, 2)"),
                        'total' => DB::raw("ROUND(total * {$factor}, 2)"),
                        'tax_amount' => DB::raw("ROUND(tax_amount * {$factor}, 2)"),
                    ]);
            });
    }

    private function convertInvoices(int $organizationId, string $toCurrency, float $factor): void
    {
        Invoice::query()
            ->where(function ($query) use ($organizationId): void {
                $query
                    ->whereHas('quotation.vendor', fn ($q) => $q->where('organization_id', $organizationId))
                    ->orWhereHas('order.vendor', fn ($q) => $q->where('organization_id', $organizationId));
            })
            ->select('id')
            ->chunkById(200, function ($rows) use ($toCurrency, $factor): void {
                $ids = $rows->pluck('id')->all();

                DB::table('invoices')
                    ->whereIn('id', $ids)
                    ->update([
                        'currency' => $toCurrency,
                        'excise_duty' => DB::raw("ROUND(excise_duty * {$factor}, 2)"),
                        'sales_commission' => DB::raw("ROUND(sales_commission * {$factor}, 2)"),
                        'overall_discount_value' => DB::raw("ROUND(overall_discount_value * {$factor}, 2)"),
                        'shipping_handling' => DB::raw("ROUND(shipping_handling * {$factor}, 2)"),
                        'pre_tax_total' => DB::raw("ROUND(pre_tax_total * {$factor}, 2)"),
                        'group_tax_vat' => DB::raw("ROUND(group_tax_vat * {$factor}, 2)"),
                        'group_tax_sales' => DB::raw("ROUND(group_tax_sales * {$factor}, 2)"),
                        'group_tax_service' => DB::raw("ROUND(group_tax_service * {$factor}, 2)"),
                        'tax_amount' => DB::raw("ROUND(tax_amount * {$factor}, 2)"),
                        'tax_on_charges' => DB::raw("ROUND(tax_on_charges * {$factor}, 2)"),
                        'deducted_taxes' => DB::raw("ROUND(deducted_taxes * {$factor}, 2)"),
                        'adjustment_amount' => DB::raw("ROUND(adjustment_amount * {$factor}, 2)"),
                        'grand_total' => DB::raw("ROUND(grand_total * {$factor}, 2)"),
                        'received_amount' => DB::raw("ROUND(received_amount * {$factor}, 2)"),
                        'balance' => DB::raw("ROUND(balance * {$factor}, 2)"),
                    ]);

                DB::table('invoice_items')
                    ->whereIn('invoice_id', $ids)
                    ->update([
                        'selling_price' => DB::raw("ROUND(selling_price * {$factor}, 2)"),
                        'net_price' => DB::raw("ROUND(net_price * {$factor}, 2)"),
                        'total' => DB::raw("ROUND(total * {$factor}, 2)"),
                        'tax_amount' => DB::raw("ROUND(tax_amount * {$factor}, 2)"),
                    ]);
            });
    }

    private function convertOrders(int $organizationId, string $toCurrency, float $factor): void
    {
        Order::query()
            ->whereHas('vendor', fn ($query) => $query->where('organization_id', $organizationId))
            ->select('id')
            ->chunkById(200, function ($rows) use ($toCurrency, $factor): void {
                $ids = $rows->pluck('id')->all();

                DB::table('orders')
                    ->whereIn('id', $ids)
                    ->update([
                        'currency' => $toCurrency,
                        'total_amount' => DB::raw("ROUND(total_amount * {$factor}, 2)"),
                        'total_amount_billed' => DB::raw("ROUND(total_amount_billed * {$factor}, 2)"),
                    ]);

                DB::table('order_items')
                    ->whereIn('order_id', $ids)
                    ->update([
                        'unit_price' => DB::raw("ROUND(unit_price * {$factor}, 2)"),
                        'line_total' => DB::raw("ROUND(line_total * {$factor}, 2)"),
                    ]);
            });
    }

    private function convertProductsAndInventory(int $organizationId, float $factor): void
    {
        Product::query()
            ->whereHas('manufacturer', fn ($query) => $query->where('organization_id', $organizationId))
            ->select('id')
            ->chunkById(200, function ($rows) use ($factor): void {
                $productIds = $rows->pluck('id')->all();

                DB::table('products')
                    ->whereIn('id', $productIds)
                    ->update([
                        'base_price' => DB::raw("ROUND(base_price * {$factor}, 2)"),
                        'price' => DB::raw("ROUND(price * {$factor}, 2)"),
                        'old_price' => DB::raw("ROUND(old_price * {$factor}, 2)"),
                        'cost' => DB::raw("ROUND(cost * {$factor}, 2)"),
                    ]);

                DB::table('inventories')
                    ->whereIn('product_id', $productIds)
                    ->update([
                        'unit_price' => DB::raw("ROUND(unit_price * {$factor}, 2)"),
                    ]);
            });
    }

    private function convertCommissionPayouts(int $organizationId, string $toCurrency, float $factor): void
    {
        CommissionPayout::query()
            ->where('organization_id', $organizationId)
            ->select('id')
            ->chunkById(200, function ($rows) use ($toCurrency, $factor): void {
                $ids = $rows->pluck('id')->all();

                DB::table('commission_payouts')
                    ->whereIn('id', $ids)
                    ->update([
                        'currency' => $toCurrency,
                        'amount' => DB::raw("ROUND(amount * {$factor}, 2)"),
                    ]);
            });
    }
}

