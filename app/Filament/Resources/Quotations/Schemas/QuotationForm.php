<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation')
                    ->schema([
                        TextInput::make('quotation_number')
                            ->required()
                            ->default(fn (): string => sprintf('QUO-%s-%04d', now()->format('Y'), random_int(1, 9999)))
                            ->unique(ignoreRecord: true),
                        Select::make('vendor_id')
                            ->options(User::query()->where('role', 'vendor')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('distributor_id')
                            ->options(User::query()->where('role', 'distributor')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'negotiated' => 'Negotiated',
                                'confirmed' => 'Confirmed',
                                'rejected' => 'Rejected',
                                'converted' => 'Converted',
                            ])
                            ->default('draft')
                            ->required(),
                        TextInput::make('subject'),
                        DatePicker::make('valid_until'),
                        Textarea::make('terms_conditions')->columnSpanFull(),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Amounts')
                    ->schema([
                        TextInput::make('subtotal')->numeric()->default(0),
                        TextInput::make('discount_amount')->numeric()->default(0),
                        TextInput::make('tax_amount')->numeric()->default(0),
                        TextInput::make('grand_total')->numeric()->default(0),
                    ])
                    ->columns(4),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('item_name')->required(),
                        TextInput::make('qty')->numeric()->required()->default(1),
                        TextInput::make('selling_price')->numeric()->required()->default(0),
                        TextInput::make('discount_percent')->numeric()->default(0),
                        TextInput::make('net_price')->numeric()->default(0),
                        TextInput::make('tax_rate')->numeric()->default(0),
                        TextInput::make('tax_amount')->numeric()->default(0),
                        TextInput::make('total')->numeric()->default(0),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
            ]);
    }
}
