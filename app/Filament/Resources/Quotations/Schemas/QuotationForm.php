<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\User;
use App\Support\QuotationStatus;
use App\Support\SystemSettings;
use App\Support\UserRole;
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
        $user = auth('tenant')->user();
        $system = SystemSettings::forOrganization($user?->organization);
        $defaultTaxPercent = (float) ($system['default_tax_percent'] ?? 0);
        $defaultDiscountPercent = (float) ($system['default_discount_percent'] ?? 0);

        return $schema
            ->components([
                Section::make('Quotation')
                    ->description('Create a quote, send it to distributor, then complete negotiation through workflow actions.')
                    ->schema([
                        TextInput::make('quotation_number')
                            ->required()
                            ->default(fn (): string => sprintf('QUO-%s-%04d', now()->format('Y'), ((int) \App\Models\Quotation::query()->max('id')) + 1))
                            ->unique(ignoreRecord: true),
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(function () use ($user): array {
                                $query = User::query()
                                    ->where('role', UserRole::VENDOR)
                                    ->orderBy('name');

                                if (($user?->role ?? null) === UserRole::ORG_ADMIN) {
                                    $query->where('organization_id', $user?->organization_id);
                                }

                                if (($user?->role ?? null) === UserRole::VENDOR) {
                                    $query->whereKey($user?->id);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->default(($user?->role ?? null) === UserRole::VENDOR ? $user?->id : null)
                            ->disabled(($user?->role ?? null) === UserRole::VENDOR)
                            ->dehydrated()
                            ->required(),
                        Select::make('distributor_id')
                            ->label('Distributor')
                            ->options(function () use ($user): array {
                                $query = User::query()
                                    ->where('role', UserRole::DISTRIBUTOR)
                                    ->orderBy('name');

                                if (filled($user?->organization_id)) {
                                    $query->where('organization_id', $user?->organization_id);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->options([
                                QuotationStatus::DRAFT => 'Draft',
                                QuotationStatus::SENT => 'Sent',
                                QuotationStatus::NEGOTIATED => 'Negotiating',
                                QuotationStatus::CONFIRMED => 'Confirmed',
                                QuotationStatus::REJECTED => 'Rejected',
                                QuotationStatus::CONVERTED => 'Converted',
                            ])
                            ->default(QuotationStatus::DRAFT)
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->helperText('Status changes automatically when users click Send, Negotiate, Confirm, or Reject.'),
                        TextInput::make('subject'),
                        DatePicker::make('valid_until')
                            ->label('Valid Until')
                            ->default(now()->addDays(7))
                            ->minDate(now()->toDateString()),
                        Textarea::make('terms_conditions')->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->helperText('Use simple language for negotiation notes and follow-ups.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Amounts')
                    ->schema([
                        TextInput::make('subtotal')->numeric()->default(0),
                        TextInput::make('discount_amount')->numeric()->default($defaultDiscountPercent),
                        TextInput::make('tax_amount')->numeric()->default($defaultTaxPercent),
                        TextInput::make('grand_total')->numeric()->default(0),
                    ])
                    ->columns(4),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('item_name')->required(),
                        TextInput::make('qty')->numeric()->required()->default(1),
                        TextInput::make('selling_price')->numeric()->required()->default(0),
                        TextInput::make('discount_percent')->numeric()->default($defaultDiscountPercent),
                        TextInput::make('net_price')->numeric()->default(0),
                        TextInput::make('tax_rate')->numeric()->default($defaultTaxPercent),
                        TextInput::make('tax_amount')->numeric()->default(0),
                        TextInput::make('total')->numeric()->default(0),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
            ]);
    }
}
