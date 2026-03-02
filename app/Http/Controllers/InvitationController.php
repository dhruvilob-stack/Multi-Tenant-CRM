<?php

namespace App\Http\Controllers;

use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitationService)
    {
    }

    public function showAccept(string $token): View
    {
        return $this->showAcceptByRole(null, $token);
    }

    public function showAcceptByRole(?string $role, string $token): View
    {
        try {
            ['invitation' => $invitation] = $this->invitationService->verifyToken($token);
            $this->ensureRoleMatches($role, $invitation->role);

            $roleSegment = $role ?? $invitation->role;

            return view('auth.invitation-accept', [
                'invitation' => $invitation,
                'token' => $token,
                'roleSegment' => $roleSegment,
                'formAction' => $this->invitationFormAction($roleSegment, $token),
            ]);
        } catch (ValidationException $exception) {
            return view('auth.invitation-invalid', [
                'token' => $token,
                'roleSegment' => $role ?? '',
                'errors' => $exception->errors(),
            ]);
        }
    }

    public function setPassword(Request $request, string $token): RedirectResponse
    {
        return $this->setPasswordByRole($request, null, $token);
    }

    public function setPasswordByRole(Request $request, ?string $role, string $token): RedirectResponse
    {
        try {
            $result = $this->invitationService->verifyToken($token);
            $this->ensureRoleMatches($role, $result['invitation']->role);
        } catch (ValidationException $exception) {
            return redirect($this->invitationViewUrl($role, $token))
                ->withErrors($exception->errors())
                ->withInput();
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->invitationService->acceptInvitation(
            $token,
            $request->input('name', $result['invitation']->invitee_email),
            $validated['password'],
        );

        return redirect()->to(url('/admin/login'))->with([
            'status' => 'Your account is ready. '
                .'Sign in to continue.',
            'email' => $user->email,
        ]);
    }

    public function verify(string $token): JsonResponse
    {
        return $this->verifyByRole(null, $token);
    }

    public function verifyByRole(?string $role, string $token): JsonResponse
    {
        try {
            $result = $this->invitationService->verifyToken($token);
            $this->ensureRoleMatches($role, $result['invitation']->role);

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

    private function ensureRoleMatches(?string $roleInUrl, string $roleInToken): void
    {
        if ($roleInUrl !== null && strtolower($roleInUrl) !== strtolower($roleInToken)) {
            throw ValidationException::withMessages([
                'role' => 'Invitation role mismatch.',
            ]);
        }
    }

    private function invitationViewUrl(?string $role, string $token): string
    {
        $base = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
        $segment = trim($role ?? '', '/');

        if ($segment === '') {
            return "{$base}/invitation/{$token}";
        }

        return "{$base}/{$segment}/invitation/{$token}";
    }

    private function invitationFormAction(string $role, string $token): string
    {
        return $this->invitationViewUrl($role, $token).'/set-password';
    }
}
