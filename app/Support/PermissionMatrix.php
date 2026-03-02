<?php

namespace App\Support;

use Filament\Resources\Resource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PermissionMatrix
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::roleGroups() as $role => $label) {
            $options = array_merge($options, self::optionsForRole($role));
        }

        ksort($options);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function roleGroups(): array
    {
        return [
            UserRole::ORG_ADMIN => 'Organization',
            UserRole::MANUFACTURER => 'Manufacturer',
            UserRole::DISTRIBUTOR => 'Distributor',
            UserRole::VENDOR => 'Vendor',
            UserRole::CONSUMER => 'Consumer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForRole(string $role): array
    {
        $options = [];

        foreach (self::resourceClasses() as $resourceClass) {
            if (! is_subclass_of($resourceClass, Resource::class)) {
                continue;
            }

            $label = (string) ($resourceClass::getNavigationLabel() ?: $resourceClass::getPluralModelLabel());
            $slug = Str::of(class_basename($resourceClass))
                ->beforeLast('Resource')
                ->snake()
                ->toString();

            foreach (['view_any', 'view', 'create', 'update', 'delete'] as $action) {
                $key = "{$role}.{$slug}.{$action}";
                $options[$key] = "{$label}: " . Str::headline(str_replace('_', ' ', $action));
            }
        }

        ksort($options);

        return $options;
    }

    /**
     * @return array<int, class-string>
     */
    protected static function resourceClasses(): array
    {
        $maps = [
            [app_path('Filament/Resources'), 'App\\Filament\\Resources\\'],
            [app_path('Filament/SuperAdmin/Resources'), 'App\\Filament\\SuperAdmin\\Resources\\'],
        ];

        $classes = [];

        foreach ($maps as [$path, $namespace]) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if (! Str::endsWith($file->getFilename(), 'Resource.php')) {
                    continue;
                }

                $relative = str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $file->getRelativePathname(),
                );

                $class = $namespace . $relative;

                if (class_exists($class)) {
                    $classes[] = $class;
                }
            }
        }

        return array_values(array_unique($classes));
    }
}
