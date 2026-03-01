<?php

namespace App\Http\Controllers;

use App\Services\DemoFlowService;
use Illuminate\Http\JsonResponse;

class DemoController extends Controller
{
    public function __construct(private readonly DemoFlowService $demoFlowService)
    {
    }

    public function flow(): JsonResponse
    {
        return response()->json([
            'message' => 'Demo flow executed successfully.',
            'data' => $this->demoFlowService->runFlow(),
        ]);
    }

    public function navigation(): JsonResponse
    {
        return response()->json([
            'panels' => config('crm_navigation.panels', []),
            'important_routes' => [
                'public_invitation_accept' => '/invitation/{token}',
                'public_invitation_verify' => '/invitation/{token}/verify',
                'public_invitation_set_password' => '/invitation/{token}/set-password',
                'super_admin' => '/super-admin',
                'tenant_panel' => '/admin/{tenant}',
            ],
        ]);
    }
}
