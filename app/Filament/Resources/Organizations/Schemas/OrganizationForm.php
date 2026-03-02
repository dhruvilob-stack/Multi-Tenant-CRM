<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Details')
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(Tenant::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true),
                        TextInput::make('email')->email(),
                        TextInput::make('phone'),
                        TextInput::make('logo')->label('Logo URL'),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                            ])
                            ->default('active'),
                        Textarea::make('address')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
