@php
    $tenant = (string) (request()->route('tenant') ?? session('tenant_slug') ?? '');
    $roleLabel = auth()->check()
        ? \Illuminate\Support\Str::of(auth()->user()?->role ?? 'guest')->replace('_', ' ')->upper()->value()
        : null;
    $isImpersonating = session()->has('impersonator_id') && $tenant !== '';
@endphp

@if (filled($roleLabel))
    <div class="fi-topbar-role-label-wrap">
        <x-filament::badge color="primary" class="fi-topbar-role-label">
            {{ $roleLabel }}
        </x-filament::badge>
        @if ($isImpersonating)
            <a href="{{ route('tenant.impersonate.stop', ['tenant' => $tenant]) }}" class="fi-impersonation-stop">
                Return to Admin
            </a>
        @endif
    </div>

    <style>
        .fi-topbar-role-label-wrap {
            display: inline-flex;
            align-items: center;
            margin-inline-end: 0.5rem;
        }

        .fi-topbar-role-label {
            border-radius: 0.4rem !important;
            letter-spacing: 0.08em;
            font-size: 0.68rem !important;
            line-height: 1rem !important;
            animation: fiRoleBlink 1.4s ease-in-out infinite;
            box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.35);
        }

        @keyframes fiRoleBlink {
            0%, 100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.38);
            }

            50% {
                opacity: 0.55;
                box-shadow: 0 0 0 6px rgba(20, 184, 166, 0);
            }
        }

        .fi-impersonation-stop {
            margin-inline-start: 0.6rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #0f766e;
            text-decoration: underline;
        }

        .dark .fi-impersonation-stop {
            color: #5eead4;
        }

    </style>
@endif
