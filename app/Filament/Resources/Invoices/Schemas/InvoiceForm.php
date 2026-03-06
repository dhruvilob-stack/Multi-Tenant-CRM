<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\User;
use App\Support\SystemSettings;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Information')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->required()
                            ->default(fn (): string => sprintf('INV-%s-%04d', now()->format('Y'), random_int(1, 9999)))
                            ->unique(ignoreRecord: true),
                        TextInput::make('subject')->required()->default('Sales Order')->maxLength(255),
                        TextInput::make('customer_no')->maxLength(100),
                        TextInput::make('contact_name')->maxLength(255),
                        DatePicker::make('invoice_date')->required()->default(now()),
                        DatePicker::make('due_date')->required()->after('invoice_date'),
                        TextInput::make('purchase_order')->maxLength(100),
                        TextInput::make('excise_duty')->numeric()->default(0),
                        TextInput::make('sales_commission')->numeric()->default(0),
                        TextInput::make('organisation_name')->maxLength(255),
                        Select::make('status')
                            ->options([
                                'auto_created' => 'Auto Created',
                                'created' => 'Created',
                                'cancelled' => 'Cancel',
                                'sent' => 'Sent',
                                'credit_invoice' => 'Credit Invoice',
                                'paid' => 'Paid',
                            ])
                            ->default('created')
                            ->required(),
                        Select::make('assigned_to')
                            ->options(User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('opportunity_name')->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('Address Details')
                    ->schema([
                        Radio::make('copy_billing_from')
                            ->options(['organization' => 'Organization', 'contact' => 'Contact'])
                            ->default('organization')
                            ->live()
                            ->dehydrated(false),
                        Radio::make('copy_shipping_from')
                            ->options(['organization' => 'Organization', 'contact' => 'Contact', 'invoice' => 'Invoice Address'])
                            ->default('organization')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                if ($state === 'invoice') {
                                    $set('shipping_address.street', $get('billing_address.street'));
                                    $set('shipping_address.po_box', $get('billing_address.po_box'));
                                    $set('shipping_address.city', $get('billing_address.city'));
                                    $set('shipping_address.state', $get('billing_address.state'));
                                    $set('shipping_address.postal_code', $get('billing_address.postal_code'));
                                    $set('shipping_address.country', $get('billing_address.country'));
                                }
                            })
                            ->dehydrated(false),
                        Textarea::make('billing_address.street')->label('Invoice Address')->required(),
                        Textarea::make('shipping_address.street')->label('Delivery Address'),
                        TextInput::make('billing_address.po_box')->label('Billing P.O. Box'),
                        TextInput::make('shipping_address.po_box')->label('Shipping P.O. Box'),
                        TextInput::make('billing_address.city')->label('Billing City'),
                        TextInput::make('shipping_address.city')->label('Shipping City'),
                        TextInput::make('billing_address.state')->label('Billing State'),
                        TextInput::make('shipping_address.state')->label('Shipping State'),
                        TextInput::make('billing_address.postal_code')->label('Billing Postal Code'),
                        TextInput::make('shipping_address.postal_code')->label('Shipping Postal Code'),
                        TextInput::make('billing_address.country')->label('Billing Country'),
                        TextInput::make('shipping_address.country')->label('Shipping Country'),
                    ])
                    ->columns(2),

                Section::make('Terms & Description')
                    ->schema([
                        RichEditor::make('terms_conditions')->columnSpanFull(),
                        RichEditor::make('description')->columnSpanFull(),
                    ]),

                Section::make('Item Details')
                    ->schema([
                        Select::make('tax_region')
                            ->options([
                                'us' => 'United States',
                                'eu' => 'Europe',
                                'in' => 'India',
                            ]),
                        Select::make('currency')
                            ->options(SystemSettings::currencyOptions())
                            ->default(fn (): string => SystemSettings::currencyForCurrentUser())
                            ->required(),
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Card',
                                'bank_transfer' => 'Bank Transfer',
                                'upi' => 'UPI',
                                'wallet' => 'Wallet',
                            ]),
                        Radio::make('tax_mode')
                            ->options(['individual' => 'Individual', 'group' => 'Group'])
                            ->default('individual')
                            ->required(),
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                TextInput::make('item_name')->required(),
                                TextInput::make('qty')->numeric()->required()->default(1),
                                TextInput::make('selling_price')->numeric()->required()->default(0),
                                TextInput::make('discount_percent')->numeric()->default(0),
                                TextInput::make('net_price')->numeric()->default(0),
                                TextInput::make('total')->numeric()->default(0),
                                Select::make('tax_type')
                                    ->options(['vat' => 'VAT', 'sales' => 'Sales', 'service' => 'Service']),
                                TextInput::make('tax_rate')->numeric()->default(0),
                                TextInput::make('tax_amount')->numeric()->default(0),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                        Select::make('overall_discount_type')
                            ->options(['zero' => 'Zero', 'percentage' => '% of Price', 'direct' => 'Direct Reduction'])
                            ->default('zero'),
                        TextInput::make('overall_discount_value')->numeric()->default(0),
                        TextInput::make('shipping_handling')->numeric()->default(0),
                        TextInput::make('pre_tax_total')->numeric()->default(0),
                        TextInput::make('group_tax_vat')->numeric()->default(0),
                        TextInput::make('group_tax_sales')->numeric()->default(0),
                        TextInput::make('group_tax_service')->numeric()->default(0),
                        TextInput::make('tax_amount')->numeric()->default(0),
                        TextInput::make('tax_on_charges')->numeric()->default(0),
                        TextInput::make('deducted_taxes')->numeric()->default(0),
                        Radio::make('adjustment_type')
                            ->options(['add' => 'Add', 'deduct' => 'Deduct'])
                            ->default('add'),
                        TextInput::make('adjustment_amount')->numeric()->default(0),
                        TextInput::make('grand_total')->numeric()->default(0),
                    ])
                    ->columns(3),
            ]);
    }
}
