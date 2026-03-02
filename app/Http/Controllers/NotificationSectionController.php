<?php

namespace App\Http\Controllers;

use App\Support\NotificationSectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSectionController extends Controller
{
    public function counts(): JsonResponse
    {
        return response()->json([
            'counts' => NotificationSectionManager::unreadCountsBySection(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $section = trim((string) $request->input('section', ''));

        if ($section !== '') {
            NotificationSectionManager::markSectionAsRead($section);
        }

        return response()->json([
            'ok' => true,
            'counts' => NotificationSectionManager::unreadCountsBySection(),
        ]);
    }
}

