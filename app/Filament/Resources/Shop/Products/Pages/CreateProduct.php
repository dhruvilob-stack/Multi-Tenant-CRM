<?php

namespace App\Filament\Resources\Shop\Products\Pages;

use App\Filament\Resources\Shop\Products\ProductResource;
use App\Models\Inventory;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected ?int $inventoryReferenceId = null;
    protected ?Inventory $inventoryReference = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->inventoryReferenceId = filled($data['inventory_reference_id'] ?? null)
            ? (int) $data['inventory_reference_id']
            : null;

        $reference = $this->inventoryReferenceId
            ? Inventory::query()->find($this->inventoryReferenceId)
            : null;

        if (! $reference) {
            throw ValidationException::withMessages([
                'inventory_reference_id' => 'Please select a valid inventory reference record.',
            ]);
        }
        if (filled($reference->product_id)) {
            throw ValidationException::withMessages([
                'inventory_reference_id' => 'This inventory record is already mapped to another product.',
            ]);
        }

        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug((string) ($data['name'] ?? ''));

        $basePrice = (float) $reference->unit_price;
        $discount = max(0, min(100, (float) ($data['discount_percent'] ?? 0)));
        $calculatedPrice = round($basePrice * (1 - ($discount / 100)), 2);

        $data['sku'] = (string) ($reference->sku ?? $data['sku'] ?? '');
        $data['barcode'] = $reference->barcode ?? ($data['barcode'] ?? null);
        $data['qty'] = (int) round((float) $reference->quantity_available);
        $data['security_stock'] = (int) $reference->security_stock;
        $data['base_price'] = $basePrice;
        $data['old_price'] = $basePrice;
        $data['price'] = $calculatedPrice;

        $this->inventoryReference = $reference;
        $this->inventoryReference->discount_percent = $discount;

        unset($data['inventory_reference_id'], $data['discount_percent'], $data['calculated_price']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Product $product */
        $product = $this->record;
        $reference = $this->inventoryReference
            ?? ($this->inventoryReferenceId ? Inventory::query()->find($this->inventoryReferenceId) : null);

        if (! $reference) {
            return;
        }

        $reference->forceFill([
            'product_id' => $product->id,
            'discount_percent' => $reference->discount_percent ?? 0,
        ])->save();
    }
}
