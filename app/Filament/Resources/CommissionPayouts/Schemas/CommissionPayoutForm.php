<?php

namespace App\Filament\Resources\CommissionPayouts\Schemas;

use App\Models\CommissionLedger;
use App\Models\User;
use App\Support\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CommissionPayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Partner')
                    ->options(function (): array {
                        $user = auth()->user();

                        $query = User::query()
                            ->whereIn('role', [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR]);

                        if ($user?->role !== UserRole::SUPER_ADMIN) {
                            $query->where(function ($scoped) use ($user): void {
                                $scoped
                                    ->where('organization_id', $user?->organization_id)
                                    ->orWhereHas('parent', fn ($q) => $q->where('organization_id', $user?->organization_id));
                            });
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->required(),
                Placeholder::make('pending_commission')
                    ->label('Pending Commission')
                    ->content(function (Get $get): string {
                        $partnerId = (int) ($get('user_id') ?? 0);
                        if ($partnerId <= 0) {
                            return '$0.00';
                        }

                        $pending = (float) CommissionLedger::query()
                            ->where('from_user_id', $partnerId)
                            ->whereNotIn('status', ['rejected'])
                            ->whereColumn('commission_amount', '>', 'paid_amount')
                            ->whereDoesntHave('payoutItems.payout', fn ($q) => $q->where('status', 'processing'))
                            ->selectRaw('COALESCE(SUM(commission_amount - paid_amount), 0) as pending_total')
                            ->value('pending_total');

                        return '$'.number_format($pending, 2);
                    }),
                TextInput::make('amount')
                    ->label('Amount (Optional)')
                    ->numeric()
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->helperText('Leave empty to payout all pending entries.'),
                Select::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'stripe' => 'Stripe',
                        'razorpay' => 'Razorpay',
                        'paypal' => 'PayPal',
                    ])
                    ->default('bank_transfer')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->required(),
                Select::make('status')
                    ->options([
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('processing')
                    ->required(),
                TextInput::make('currency')
                    ->default('USD')
                    ->maxLength(10)
                    ->required(),
                TextInput::make('reference'),
                DatePicker::make('paid_at'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
