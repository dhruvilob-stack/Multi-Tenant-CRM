<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.profile';
    protected static ?string $slug = 'profile';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUserCircle;

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'email' => $user?->email,
            'role' => $user?->role,
            'locale' => $user?->locale ?: app()->getLocale(),
            'profile_photo' => $user?->profile_photo,
            'current_password' => null,
            'new_password' => null,
            'new_password_confirmation' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Details')
                    ->description('Update your name, profile picture, and language.')
                    ->schema([
                        FileUpload::make('profile_photo')
                            ->label('Profile Photo')
                            ->image()
                            ->avatar()
                            ->directory('profile-photos')
                            ->disk('public')
                            ->visibility('public'),

                        TextInput::make('first_name')
                            ->label('First Name')
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(255),

                        TextInput::make('email')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('role')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('locale')
                            ->label('Preferred Language')
                            ->options((array) config('localization.supported', []))
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Security')
                    ->description('Set a new password if you need to rotate credentials.')
                    ->schema([
                        TextInput::make('current_password')
                            ->password()
                            ->revealable(),

                        TextInput::make('new_password')
                            ->password()
                            ->revealable(),

                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $state = $this->form->getState();
        $supportedLocales = array_keys((array) config('localization.supported', []));

        $validated = validator($state, [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', 'string', Rule::in($supportedLocales)],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'string', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Current password is incorrect.',
        ])->validate();

        $newPhoto = $validated['profile_photo'] ?? null;
        $oldPhoto = $user->profile_photo;

        if (filled($oldPhoto) && filled($newPhoto) && $oldPhoto !== $newPhoto && Storage::disk('public')->exists($oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $firstName = trim((string) ($validated['first_name'] ?? ''));
        $lastName = trim((string) ($validated['last_name'] ?? ''));
        $displayName = trim($firstName.' '.$lastName);

        $user->first_name = $firstName !== '' ? $firstName : null;
        $user->last_name = $lastName !== '' ? $lastName : null;
        $user->locale = (string) $validated['locale'];
        $user->profile_photo = $newPhoto ?: null;

        if ($displayName !== '') {
            $user->name = $displayName;
        }

        if (filled($validated['new_password'] ?? null)) {
            $user->password = (string) $validated['new_password'];
        }

        $user->save();

        App::setLocale($user->locale ?: app()->getLocale());
        session()->put('locale', $user->locale ?: app()->getLocale());

        $this->form->fill([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'locale' => $user->locale ?: app()->getLocale(),
            'profile_photo' => $user->profile_photo,
            'current_password' => null,
            'new_password' => null,
            'new_password_confirmation' => null,
        ]);

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.profile.nav');
    }
}
