<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('status'),
                TextEntry::make('latestSubscription.plan_name')->label('Plan')->placeholder('-'),
                TextEntry::make('latestSubscription.status')->label('Subscription')->placeholder('-'),
                TextEntry::make('latestSubscription.ends_at')->label('Renews/Ends')->dateTime()->placeholder('-'),
                TextEntry::make('latestSubscription.total_amount')->label('Amount')->placeholder('-'),
                TextEntry::make('latestSubscription.payment_method')->label('Payment Method')->placeholder('-'),
                TextEntry::make('latestSubscription.payment_reference')->label('Payment Ref')->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
            ])
            ->columns(2);
    }
}
