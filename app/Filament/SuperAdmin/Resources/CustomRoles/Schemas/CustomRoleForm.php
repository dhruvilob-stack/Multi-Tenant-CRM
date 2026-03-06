<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles\Schemas;

use App\Support\PermissionMatrix;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true),
                Toggle::make('is_active')
                    ->default(true),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                ...self::permissionSections(),
            ])
            ->columns(2);
    }

    /**
     * @return array<int, Section>
     */
    protected static function permissionSections(): array
    {
        $sections = [];

        foreach (PermissionMatrix::roleGroups() as $role => $label) {
            $sections[] = Section::make($label . ' Permissions')
                ->schema([
                    CheckboxList::make("permission_groups.{$role}")
                        ->label('Select permissions')
                        ->options(fn () => PermissionMatrix::optionsForRole($role))
                        ->columns(2)
                        ->searchable()
                        ->bulkToggleable(),
                ])
                ->columnSpanFull();
        }

        return $sections;
    }
}
