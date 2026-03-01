<?php

namespace App\Http\Controllers;

use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitationService)
    {
    }

    public function showAccept(string $token): JsonResponse
    {
        try {
            ['invitation' => $invitation] = $this->invitationService->verifyToken($token);

            return response()->json([
                'valid' => true,
                'invitee_email' => $invitation->invitee_email,
                'role' => $invitation->role,
                'organization_id' => $invitation->organization_id,
                'expires_at' => optional($invitation->expires_at)->toIso8601String(),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'valid' => false,
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    public function setPassword(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->invitationService->acceptInvitation(
            $token,
            $validated['name'],
            $validated['password'],
        );

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'user_id' => $user->id,
        ]);
    }

    public function verify(string $token): JsonResponse
    {
        try {
            $result = $this->invitationService->verifyToken($token);

            return response()->json([
                'valid' => true,
                'invitee_email' => $result['invitation']->invitee_email,
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'valid' => false,
                'errors' => $exception->errors(),
            ], 422);
        }
    }
}
