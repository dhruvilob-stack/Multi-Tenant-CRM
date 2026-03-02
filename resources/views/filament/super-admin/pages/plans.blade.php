<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section :heading="__('filament.super_admin.pages.plans.starter.heading')" :description="__('filament.super_admin.pages.plans.starter.description')">{{ __('filament.super_admin.pages.plans.starter.price') }}</x-filament::section>
        <x-filament::section :heading="__('filament.super_admin.pages.plans.growth.heading')" :description="__('filament.super_admin.pages.plans.growth.description')">{{ __('filament.super_admin.pages.plans.growth.price') }}</x-filament::section>
        <x-filament::section :heading="__('filament.super_admin.pages.plans.enterprise.heading')" :description="__('filament.super_admin.pages.plans.enterprise.description')">{{ __('filament.super_admin.pages.plans.enterprise.price') }}</x-filament::section>
    </div>
</x-filament-panels::page>
