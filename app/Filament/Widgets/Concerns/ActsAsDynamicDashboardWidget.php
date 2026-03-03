<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;

trait ActsAsDynamicDashboardWidget
{
    public static function getWidgetLabel(): string
    {
        return Str::headline(class_basename(static::class));
    }

    /**
     * @return array<Component>
     */
    public static function getSettingsFormSchema(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public static function getSettingsCasts(): array
    {
        return [];
    }
}
