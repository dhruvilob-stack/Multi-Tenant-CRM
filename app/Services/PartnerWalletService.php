<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Models\PartnerWallet;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PartnerWalletService
{
    public function syncForUser(int $userId): void
    {
        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }

        $totalEarned = (float) CommissionLedger::query()
            ->where('from_user_id', $userId)
            ->whereNotIn('status', ['rejected'])
            ->sum('commission_amount');

        $paid = (float) CommissionPayout::query()
            ->where('user_id', $userId)
            ->when(
                Schema::hasColumn('commission_payouts', 'status'),
                fn ($q) => $q->where('status', 'completed')
            )
            ->sum('amount');

        $pending = max($totalEarned - $paid, 0);

        PartnerWallet::query()->updateOrCreate(
            [
                'organization_id' => $user->organization_id,
                'user_id' => $user->id,
            ],
            [
                'role' => (string) $user->role,
                'available_balance' => round($pending, 2),
                'pending_balance' => round($pending, 2),
                'total_earned' => round($totalEarned, 2),
                'total_paid' => round($paid, 2),
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * @param array<int, int>|Collection<int, int> $userIds
     */
    public function syncForUsers(array|Collection $userIds): void
    {
        collect($userIds)
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->each(fn (int $id): mixed => $this->syncForUser($id));
    }
}
