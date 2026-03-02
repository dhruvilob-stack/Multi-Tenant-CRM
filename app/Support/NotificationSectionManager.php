<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NotificationSectionManager
{
    public static function unreadCount(string $section): int
    {
        $user = Auth::user();

        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->where('data->viewData->section', $section)
            ->count();
    }

    public static function markSectionAsRead(string $section): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $user->unreadNotifications()
            ->where('data->viewData->section', $section)
            ->update(['read_at' => now()]);
    }

    /**
     * @return array<string, int>
     */
    public static function unreadCountsBySection(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        /** @var Collection<int, \Illuminate\Notifications\DatabaseNotification> $notifications */
        $notifications = $user->unreadNotifications()->get();

        return $notifications
            ->map(function ($notification): ?string {
                $section = data_get($notification->data, 'viewData.section');

                return is_string($section) && $section !== '' ? $section : null;
            })
            ->filter()
            ->countBy()
            ->map(fn (int $count): int => $count)
            ->all();
    }
}
