<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->columnSpan('full')
                    ->required(),

                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->required(),

                Select::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'INR' => 'INR',
                        'EUR' => 'EUR',
                    ])
                    ->searchable()
                    ->required(),

                ToggleButtons::make('provider')
                    ->inline()
                    ->grouped()
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                    ])
                    ->required(),

                ToggleButtons::make('method')
                    ->inline()
                    ->options([
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'upi' => 'UPI',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Details')
                    ->columns([
                        TextColumn::make('reference')
                            ->searchable()
                            ->weight(FontWeight::Medium),

                        TextColumn::make('amount')
                            ->sortable()
                            ->money(fn ($record) => (string) ($record->currency ?? 'USD')),
                    ]),

                ColumnGroup::make('Context')
                    ->columns([
                        TextColumn::make('provider')
                            ->formatStateUsing(fn ($state) => Str::headline((string) $state))
                            ->sortable(),

                        TextColumn::make('method')
                            ->formatStateUsing(fn ($state) => Str::headline((string) $state))
                            ->sortable(),
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
