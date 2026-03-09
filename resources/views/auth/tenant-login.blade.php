<x-filament-panels::layout.simple>
    <div class="crm-login-wrap">
        <x-filament-panels::header.simple
            heading="Tenant Sign in"
            subheading="Sign in to your organization workspace."
        />

        <x-filament::section>
            <div class="crm-tenant-meta">
                <x-filament::badge color="info">
                    Tenant: {{ $tenant }}
                </x-filament::badge>

                @if ($role)
                    <x-filament::badge color="gray">
                        Role: {{ $role }}
                    </x-filament::badge>
                @endif
            </div>

            <form method="post" action="{{ $action }}" class="crm-login-form">
                @csrf

                @php($errorBag = session('errors'))
                @php($prefillEmail = old('email', (string) ($prefillEmail ?? request()->query('email', ''))))
                @php($prefillPassword = old('password', (string) ($prefillPassword ?? request()->query('password', ''))))
                @if ($errorBag && $errorBag->any())
                    <x-filament::callout
                        color="danger"
                        heading="Login failed"
                        :description="$errorBag->first()"
                    />
                @endif

                <div class="crm-login-field">
                    <label for="email" class="crm-login-label">Email</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            :value="$prefillEmail"
                            required
                            autofocus
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="crm-login-field">
                    <label for="password" class="crm-login-label">Password</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            :value="$prefillPassword"
                            required
                        />
                    </x-filament::input.wrapper>
                </div>

                <label class="crm-login-remember">
                    <x-filament::input.checkbox name="remember" value="1" />
                    <span>Remember me</span>
                </label>

                <x-filament::button type="submit" size="lg" class="crm-login-submit">
                    Sign in
                </x-filament::button>
            </form>
        </x-filament::section>
    </div>

    <style>
        .crm-login-wrap {
            width: min(100%, 30rem);
            margin-inline: auto;
            display: grid;
            gap: 1rem;
        }

        .crm-tenant-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.875rem;
        }

        .crm-login-form {
            display: grid;
            gap: 1rem;
        }

        .crm-login-field {
            display: grid;
            gap: 0.45rem;
        }

        .crm-login-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: rgb(51 65 85);
        }

        .dark .crm-login-label {
            color: rgb(226 232 240);
        }

        .crm-login-remember {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.875rem;
            color: rgb(71 85 105);
        }

        .dark .crm-login-remember {
            color: rgb(203 213 225);
        }

        .crm-login-submit {
            width: 100%;
            justify-content: center;
        }
    </style>
</x-filament-panels::layout.simple>
