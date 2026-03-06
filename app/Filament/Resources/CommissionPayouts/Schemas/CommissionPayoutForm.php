<?php

namespace App\Filament\Resources\CommissionPayouts\Schemas;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Models\User;
use App\Support\SystemSettings;
use App\Support\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class CommissionPayoutForm
{
    private static function pendingForPartner(int $partnerId): float
    {
        if ($partnerId <= 0) {
            return 0.0;
        }

        $pending = (float) CommissionLedger::query()
            ->where('from_user_id', $partnerId)
            ->whereNotIn('status', ['rejected'])
            ->sum('commission_amount');

        $paid = (float) CommissionPayout::query()
            ->where('user_id', $partnerId)
            ->when(
                SchemaFacade::hasColumn('commission_payouts', 'status'),
                fn ($q) => $q->where('status', 'completed')
            )
            ->sum('amount');

        return round(max($pending - $paid, 0), 2);
    }

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
                        $pending = self::pendingForPartner($partnerId);
                        $currency = SystemSettings::currencyForCurrentUser();

                        return number_format($pending, 2).' '.$currency;
                    }),
                TextInput::make('amount')
                    ->label('Amount (Optional)')
                    ->numeric()
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->maxValue(fn (Get $get): float => self::pendingForPartner((int) ($get('user_id') ?? 0)))
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
                    ->default(fn (): string => SystemSettings::currencyForCurrentUser())
                    ->maxLength(10)
                    ->required(),
                TextInput::make('reference'),
                DatePicker::make('paid_at'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
