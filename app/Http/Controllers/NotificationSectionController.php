<?php

namespace App\Http\Controllers;

use App\Models\OrganizationMailRecipient;
use App\Support\NotificationSectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSectionController extends Controller
{
    public function counts(): JsonResponse
    {
        $counts = NotificationSectionManager::unreadCountsBySection();
        $mailUnread = $this->mailUnreadCount();
        $counts['mail'] = $mailUnread;
        $counts['inbox-mail'] = $mailUnread;
        $counts['mail/inbox-mail'] = $mailUnread;

        return response()->json([
            'counts' => $counts,
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $section = trim((string) $request->input('section', ''));

        if ($section !== '') {
            NotificationSectionManager::markSectionAsRead($section);
        }

        $counts = NotificationSectionManager::unreadCountsBySection();
        $mailUnread = $this->mailUnreadCount();
        $counts['mail'] = $mailUnread;
        $counts['inbox-mail'] = $mailUnread;
        $counts['mail/inbox-mail'] = $mailUnread;

        return response()->json([
            'ok' => true,
            'counts' => $counts,
        ]);
    }

    private function mailUnreadCount(): int
    {
        $userId = auth()->id();

        if (! $userId) {
            return 0;
        }

        return OrganizationMailRecipient::query()
            ->where('recipient_id', $userId)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->count();
    }
}
