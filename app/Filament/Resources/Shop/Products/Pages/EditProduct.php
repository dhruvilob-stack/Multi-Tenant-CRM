<?php

namespace App\Filament\Resources\Shop\Products\Pages;

use App\Filament\Resources\Shop\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug((string) ($data['name'] ?? ''));

        $basePrice = (float) ($this->record->base_price ?? $data['price'] ?? 0);
        $discount = max(0, min(100, (float) ($data['discount_percent'] ?? 0)));
        $calculatedPrice = round($basePrice * (1 - ($discount / 100)), 2);

        $data['base_price'] = $basePrice;
        $data['old_price'] = $basePrice;
        $data['price'] = $calculatedPrice;

        unset(
            $data['inventory_reference_id'],
            $data['discount_percent'],
            $data['calculated_price'],
            $data['sku'],
            $data['barcode'],
            $data['qty'],
            $data['security_stock']
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }
}
