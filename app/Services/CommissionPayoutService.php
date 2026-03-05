<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Models\CommissionPayoutItem;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionPayoutService
{
    public function create(array $data, User $actor): CommissionPayout
    {
        $partnerId = (int) ($data['user_id'] ?? 0);
        $partner = User::query()->find($partnerId);

        if (! $partner || ! in_array((string) $partner->role, [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true)) {
            throw ValidationException::withMessages(['user_id' => 'Select a valid partner user.']);
        }

        $targetAmount = isset($data['amount']) && is_numeric($data['amount']) ? max((float) $data['amount'], 0) : 0.0;

        return DB::transaction(function () use ($data, $actor, $partner, $targetAmount): CommissionPayout {
            $entries = CommissionLedger::query()
                ->where('from_user_id', $partner->id)
                ->whereNotIn('status', ['rejected'])
                ->whereColumn('commission_amount', '>', 'paid_amount')
                ->whereDoesntHave('payoutItems.payout', fn ($q) => $q->where('status', 'processing'))
                ->orderBy('id')
                ->get(['id', 'commission_amount', 'paid_amount', 'status']);

            if ($entries->isEmpty()) {
                throw ValidationException::withMessages(['user_id' => 'No pending commission entries available for this partner.']);
            }

            $selected = collect();
            $running = 0.0;
            $remainingTarget = $targetAmount > 0 ? $targetAmount : null;

            foreach ($entries as $entry) {
                $amount = max((float) $entry->commission_amount - (float) $entry->paid_amount, 0);
                if ($amount <= 0) {
                    continue;
                }

                $allocated = $amount;
                if ($remainingTarget !== null) {
                    if ($remainingTarget <= 0) {
                        break;
                    }
                    $allocated = min($allocated, $remainingTarget);
                    $remainingTarget -= $allocated;
                }

                if ($allocated <= 0) {
                    continue;
                }

                $selected->push([
                    'entry' => $entry,
                    'amount' => round($allocated, 2),
                ]);
                $running += $allocated;
            }

            if ($selected->isEmpty()) {
                throw ValidationException::withMessages(['amount' => 'Requested amount is not available in pending commission entries.']);
            }

            $currency = trim((string) ($data['currency'] ?? 'USD')) ?: 'USD';
            $payout = CommissionPayout::query()->create([
                'organization_id' => $actor->organization_id,
                'payout_number' => $this->nextPayoutNumber(),
                'user_id' => $partner->id,
                'amount' => round($running, 2),
                'status' => (string) ($data['status'] ?? 'processing'),
                'payment_method' => (string) ($data['payment_method'] ?? 'bank_transfer'),
                'currency' => $currency,
                'created_by' => $actor->id,
                'reference' => (string) ($data['reference'] ?? ''),
                'notes' => (string) ($data['notes'] ?? ''),
                'paid_at' => null,
                'processed_at' => null,
            ]);

            foreach ($selected as $row) {
                /** @var CommissionLedger $entry */
                $entry = $row['entry'];
                $itemAmount = (float) $row['amount'];
                CommissionPayoutItem::query()->create([
                    'payout_id' => $payout->id,
                    'commission_ledger_id' => (int) $entry->id,
                    'amount' => round($itemAmount, 2),
                ]);

                if ((string) $entry->status === 'accrued') {
                    $entry->update(['status' => 'approved']);
                }
            }

            if ((string) $payout->status === 'completed') {
                $this->markCompleted($payout);
            } else {
                app(PartnerWalletService::class)->syncForUser($partner->id);
            }

            return $payout->fresh(['items']);
        });
    }

    public function markCompleted(CommissionPayout $payout): CommissionPayout
    {
        return DB::transaction(function () use ($payout): CommissionPayout {
            $payout->loadMissing('items.ledger');

            foreach ($payout->items as $item) {
                $ledger = $item->ledger;
                if ($ledger && (string) $ledger->status !== 'paid') {
                    $newPaid = min(
                        (float) $ledger->commission_amount,
                        (float) $ledger->paid_amount + (float) $item->amount
                    );

                    $newStatus = $newPaid >= (float) $ledger->commission_amount ? 'paid' : 'approved';

                    $ledger->update([
                        'paid_amount' => round($newPaid, 2),
                        'status' => $newStatus,
                    ]);
                }
            }

            $payout->update([
                'status' => 'completed',
                'paid_at' => $payout->paid_at ?: now()->toDateString(),
                'processed_at' => now(),
            ]);

            app(PartnerWalletService::class)->syncForUser((int) $payout->user_id);

            return $payout->fresh(['items']);
        });
    }

    private function nextPayoutNumber(): string
    {
        $lastId = (int) (CommissionPayout::query()->max('id') ?? 0);

        return 'PO-'.str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
