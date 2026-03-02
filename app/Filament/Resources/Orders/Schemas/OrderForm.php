<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Invoice;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required()
                    ->default(fn (): string => sprintf('ORD-%s-%04d', now()->format('Y'), random_int(1, 9999)))
                    ->unique(ignoreRecord: true),
                Select::make('consumer_id')
                    ->options(User::query()->where('role', 'consumer')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('vendor_id')
                    ->options(User::query()->where('role', 'vendor')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('invoice_id')
                    ->options(Invoice::query()->pluck('invoice_number', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('total_amount')->numeric()->default(0),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
