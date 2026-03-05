<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">

        {{-- Topbar --}}
        <div class="
            flex justify-between items-center gap-4
            rounded-2xl p-4
            bg-white dark:bg-gray-900
            border border-gray-200 dark:border-gray-800
            shadow-sm
        ">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('profile.heading') }}
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Manage your account details, locale, and security from one place.
                </p>
            </div>

            <x-filament::badge color="success">
                {{ strtoupper(auth()->user()?->locale ?: app()->getLocale()) }}
            </x-filament::badge>
        </div>

        {{-- Profile Card --}}
        <div class="
            rounded-2xl p-6
            bg-white dark:bg-gray-900
            border border-gray-200 dark:border-gray-800
            shadow-sm
        ">
            {{ $this->form }}
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end">
            <x-filament::button type="submit" size="lg">
                Save Profile
            </x-filament::button>
        </div>

    </form>
</x-filament-panels::page>