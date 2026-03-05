<?php

namespace App\Filament\Resources\CommissionLedgers\Pages;

use App\Filament\Resources\CommissionLedgers\CommissionLedgerResource;
use App\Support\UserRole;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Alignment;

class ViewCommissionLedger extends ViewRecord
{
    protected static string $resource = CommissionLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->role === UserRole::ORG_ADMIN),
        ];
    }

    public function getTitle(): string
    {
        return 'Commission Ledger Details';
    }

    public function getHeading(): string
    {
        $commission = $this->record;
        return "Commission for Invoice {$commission?->invoice?->invoice_number}";
    }

    public function getSubheading(): string|null
    {
        $commission = $this->record;
        $amount = $commission?->commission_amount ?? 0;
        return "Commission Amount: $" . number_format($amount, 2);
    }

    public function getHeaderActionsAlignment(): ?Alignment
    {
        return Alignment::End;
    }
}
