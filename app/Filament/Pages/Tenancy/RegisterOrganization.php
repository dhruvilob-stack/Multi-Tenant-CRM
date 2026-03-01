<?php
namespace App\Filament\Pages\Tenancy;

use App\Models\Organization;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterOrganization extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Organization';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('slug')->unique(Organization::class, 'slug'),
            ]);
    }

    protected function handleRegistration(array $data): Organization
    {
        $Organization = Organization::create($data);

        $Organization->users()->attach(auth()->user());

        return $Organization;
    }
}