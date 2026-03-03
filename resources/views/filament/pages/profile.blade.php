<x-filament-panels::page>
    <style>
        .profile-shell {
            background:
                radial-gradient(circle at 6% 6%, rgba(56, 189, 248, .18), transparent 30%),
                radial-gradient(circle at 95% 0%, rgba(52, 211, 153, .16), transparent 33%),
                linear-gradient(145deg, #f8fbff 0%, #f0fdfa 58%, #fff7ed 100%);
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 15px 35px rgba(2, 132, 199, .08);
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 14px;
        }
        @media (min-width: 960px) {
            .profile-grid { grid-template-columns: 1.1fr 1fr; }
        }
        .profile-card {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 8px 22px rgba(30, 64, 175, .07);
        }
        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            background: #ecfeff;
            color: #155e75;
            border: 1px solid #a5f3fc;
        }
        .info-row {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            margin-top: 10px;
        }
    </style>

    <div class="profile-shell">
        <x-filament::section :heading="__('profile.heading')" :description="__('profile.description')">
            <div class="profile-grid">
                <div class="profile-card">
                    <div class="fi-flex fi-items-center fi-justify-between">
                        <h3 class="fi-text-base fi-font-semibold fi-text-slate-900">{{ __('profile.heading') }}</h3>
                        <span class="pill">{{ strtoupper(auth()->user()?->locale ?: app()->getLocale()) }}</span>
                    </div>

                    <div class="fi-mt-3 fi-flex fi-items-center fi-gap-3">
                        @if(auth()->user()?->profile_photo)
                            <img src="{{ asset('storage/'.auth()->user()->profile_photo) }}" alt="Profile Photo" class="h-16 w-16 rounded-full object-cover border border-slate-200" />
                        @else
                            <div class="h-16 w-16 rounded-full bg-sky-100 text-sky-700 fi-flex fi-items-center fi-justify-center fi-font-semibold">
                                {{ strtoupper(substr((string) auth()->user()?->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="fi-text-sm fi-font-semibold fi-text-slate-900">{{ auth()->user()?->name }}</div>
                            <div class="fi-text-xs fi-text-slate-500">Email is locked for security.</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <p class="text-sm text-gray-700"><strong>{{ __('profile.name') }}:</strong> {{ auth()->user()?->name }}</p>
                        <p class="text-sm text-gray-700"><strong>{{ __('profile.email') }}:</strong> {{ auth()->user()?->email }}</p>
                        <p class="text-sm text-gray-700"><strong>{{ __('profile.role') }}:</strong> {{ auth()->user()?->role }}</p>
                    </div>

                    <div class="info-row">
                        <p class="text-sm text-gray-700">
                            <strong>Current Locale:</strong> {{ app()->getLocale() }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <strong>Localized Date:</strong> {{ now()->translatedFormat('l, d F Y') }}
                        </p>
                    </div>
                </div>

                <div class="profile-card">
                    <h3 class="fi-text-base fi-font-semibold fi-text-slate-900">{{ __('profile.preferred_language') }}</h3>
                    <p class="fi-text-sm fi-text-gray-600 fi-mt-1">Update your personal details and language preference.</p>

                    <form method="POST" action="{{ route('filament.profile.update') }}" enctype="multipart/form-data" class="fi-mt-4">
                        @csrf

                        <div class="fi-grid fi-grid-cols-1 md:fi-grid-cols-2 fi-gap-3">
                            <div>
                                <label class="fi-block fi-text-sm fi-font-semibold fi-text-slate-900">First Name</label>
                                <input
                                    type="text"
                                    name="first_name"
                                    value="{{ old('first_name', auth()->user()?->first_name) }}"
                                    class="fi-mt-1 fi-block fi-w-full fi-rounded-lg fi-border fi-border-gray-300 fi-bg-white fi-px-3 fi-py-2"
                                />
                            </div>
                            <div>
                                <label class="fi-block fi-text-sm fi-font-semibold fi-text-slate-900">Last Name</label>
                                <input
                                    type="text"
                                    name="last_name"
                                    value="{{ old('last_name', auth()->user()?->last_name) }}"
                                    class="fi-mt-1 fi-block fi-w-full fi-rounded-lg fi-border fi-border-gray-300 fi-bg-white fi-px-3 fi-py-2"
                                />
                            </div>
                        </div>

                        <div class="fi-mt-3">
                            <label class="fi-block fi-text-sm fi-font-semibold fi-text-slate-900">Email (Locked)</label>
                            <input
                                type="email"
                                value="{{ auth()->user()?->email }}"
                                readonly
                                disabled
                                class="fi-mt-1 fi-block fi-w-full fi-rounded-lg fi-border fi-border-gray-200 fi-bg-gray-100 fi-px-3 fi-py-2 fi-text-slate-500"
                            />
                        </div>

                        <div class="fi-mt-3">
                            <label class="fi-block fi-text-sm fi-font-semibold fi-text-slate-900">Profile Image</label>
                            <input
                                type="file"
                                name="profile_photo"
                                accept="image/*"
                                class="fi-mt-1 fi-block fi-w-full fi-rounded-lg fi-border fi-border-gray-300 fi-bg-white fi-px-3 fi-py-2"
                            />
                        </div>

                        <button
                            type="submit"
                            class="fi-mt-4 fi-inline-flex fi-items-center fi-rounded-lg fi-bg-sky-600 fi-py-2 fi-px-4 fi-text-xs fi-font-semibold fi-text-white hover:fi-bg-sky-700"
                        >
                            Save Profile
                        </button>
                    </form>

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
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
