<x-filament-panels::page>
    <x-filament::section :heading="__('profile.heading')" :description="__('profile.description')">
        <div class="fi-flex fi-flex-col fi-gap-3">
            <p class="text-sm text-gray-600">{{ __('profile.name') }}: {{ auth()->user()?->name }}</p>
            <p class="text-sm text-gray-600">{{ __('profile.email') }}: {{ auth()->user()?->email }}</p>
            <p class="text-sm text-gray-600">{{ __('profile.role') }}: {{ auth()->user()?->role }}</p>
        </div>

        <form method="POST" action="{{ route('filament.profile.locale') }}" class="fi-mt-4">
            @csrf

            @if(session('status'))
                <div class="fi-mb-4 fi-rounded-lg fi-bg-emerald-50 fi-p-3 fi-text-sm fi-text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <label class="fi-block fi-text-sm fi-font-semibold fi-text-slate-900" for="locale">{{ __('profile.preferred_language') }}</label>
            <select
                id="locale"
                name="locale"
                class="fi-mt-1 fi-block fi-w-full fi-rounded-lg fi-border fi-border-gray-300 fi-bg-white fi-px-3 fi-py-2"
            >
                @foreach(config('localization.supported', []) as $locale => $name)
                    <option value="{{ $locale }}" @selected(auth()->user()?->locale === $locale)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <button
                type="submit"
                class="fi-mt-4 fi-inline-flex fi-items-center fi-rounded-lg fi-bg-emerald-500 fi-py-2 fi-px-4 fi-text-xs fi-font-semibold fi-text-white hover:fi-bg-emerald-600"
            >
                {{ __('profile.save_language') }}
            </button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
