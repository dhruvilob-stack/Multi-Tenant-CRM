<?php

namespace App\Filament\Resources\CommissionPayouts\Schemas;

use App\Models\User;
use App\Support\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CommissionPayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->options(function (): array {
                        $user = auth()->user();

                        $query = User::query()
                            ->whereIn('role', [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR, UserRole::CONSUMER]);

                        if ($user?->role !== UserRole::SUPER_ADMIN) {
                            $query->where('organization_id', $user?->organization_id);
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('amount')->numeric()->required(),
                TextInput::make('reference'),
                DatePicker::make('paid_at'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
