<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\Quotation;
use App\Support\QuotationStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation')
                    ->schema([
                        TextEntry::make('quotation_number')->label('Quotation #'),
                        TextEntry::make('vendor.name')->label('Vendor'),
                        TextEntry::make('distributor.name')->label('Distributor'),
                        TextEntry::make('status')
                            ->label('Current Stage')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state)),
                        TextEntry::make('valid_until')->label('Valid Until')->date(),
                        TextEntry::make('grand_total')->label('Grand Total')->money('USD'),
                        TextEntry::make('order.order_number')
                            ->label('Converted Order')
                            ->placeholder('Not converted yet'),
                    ])
                    ->columns(2),
                Section::make('Workflow Guide')
                    ->description('Simple process: Vendor creates and sends, Distributor negotiates/accepts, then system generates order.')
                    ->schema([
                        TextEntry::make('workflow_stage')
                            ->label('What this status means')
                            ->state(fn (Quotation $record): string => self::statusDescription((string) $record->status)),
                        TextEntry::make('next_step')
                            ->label('What should happen next')
                            ->state(fn (Quotation $record): string => self::nextAction((string) $record->status)),
                    ])
                    ->columns(1),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('item_name')->label('Product'),
                                TextEntry::make('qty')->label('Quantity')->numeric(),
                                TextEntry::make('selling_price')->label('Price/Unit')->money('USD'),
                                TextEntry::make('discount_percent')->label('Discount')->suffix('%'),
                                TextEntry::make('total')->label('Total')->money('USD'),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('subject')->label('Subject')->placeholder('-'),
                        TextEntry::make('terms_conditions')->label('Terms')->placeholder('-'),
                        TextEntry::make('notes')->label('Internal Notes')->placeholder('-'),
                    ])
                    ->columns(1),
            ]);
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'Draft',
            QuotationStatus::SENT => 'Sent',
            QuotationStatus::NEGOTIATED => 'Negotiating',
            QuotationStatus::CONFIRMED => 'Confirmed',
            QuotationStatus::REJECTED => 'Rejected',
            QuotationStatus::CONVERTED => 'Converted',
            default => Str::headline((string) $status),
        };
    }

    private static function statusDescription(string $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'Vendor created the quotation but has not sent it yet.',
            QuotationStatus::SENT => 'Distributor received the quotation and can review it.',
            QuotationStatus::NEGOTIATED => 'Distributor requested changes. Vendor should review and respond.',
            QuotationStatus::CONFIRMED => 'Quotation accepted and ready for order creation.',
            QuotationStatus::REJECTED => 'Quotation has been declined or cancelled.',
            QuotationStatus::CONVERTED => 'Quotation has been converted into a sales order.',
            default => 'Status updated.',
        };
    }

    private static function nextAction(string $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'Vendor should click Send to share the quotation.',
            QuotationStatus::SENT => 'Distributor can Negotiate, Confirm and Convert, or Reject.',
            QuotationStatus::NEGOTIATED => 'Vendor can edit and send a counter-offer.',
            QuotationStatus::CONFIRMED => 'Convert this quotation into a sales order.',
            QuotationStatus::REJECTED => 'No further action required.',
            QuotationStatus::CONVERTED => 'Continue order processing in Orders module.',
            default => 'Review quotation details.',
        };
    }
}
