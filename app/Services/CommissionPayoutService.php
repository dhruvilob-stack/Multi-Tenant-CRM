<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Models\CommissionPayoutItem;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CommissionPayoutService
{
    public function create(array $data, User $actor): CommissionPayout
    {
        if (! Schema::hasTable('commission_payout_items') || ! Schema::hasColumn('commission_ledger', 'paid_amount')) {
            throw ValidationException::withMessages([
                'user_id' => 'Payout system migration is pending. Please run: php artisan migrate',
            ]);
        }

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
                ->whereDoesntHave('payoutItems.payout', fn ($q) => $q->where('status', 'processing'))
                ->orderBy('id')
                ->get(['id', 'commission_amount', 'paid_amount', 'status']);

            if ($entries->isEmpty()) {
                throw ValidationException::withMessages(['user_id' => 'No pending commission entries available for this partner.']);
            }

            $earned = (float) CommissionLedger::query()
                ->where('from_user_id', $partner->id)
                ->whereNotIn('status', ['rejected'])
                ->sum('commission_amount');

            $alreadyPaidToPartner = (float) CommissionPayout::query()
                ->where('user_id', $partner->id)
                ->when(
                    Schema::hasColumn('commission_payouts', 'status'),
                    fn ($q) => $q->where('status', 'completed')
                )
                ->sum('amount');

            $partnerPending = max($earned - $alreadyPaidToPartner, 0);
            if ($partnerPending <= 0) {
                throw ValidationException::withMessages(['user_id' => 'No pending commission entries available for this partner.']);
            }

            $selected = collect();
            $running = 0.0;
            $remainingTarget = $targetAmount > 0 ? min($targetAmount, $partnerPending) : $partnerPending;

            foreach ($entries as $entry) {
                $amount = max((float) $entry->commission_amount - (float) $entry->paid_amount, 0);
                if ($amount <= 0) {
                    continue;
                }

                $allocated = $amount;
                if ($remainingTarget <= 0) {
                    break;
                }
                $allocated = min($allocated, $remainingTarget);
                $remainingTarget -= $allocated;

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
            $payload = [
                'user_id' => $partner->id,
                'amount' => round($running, 2),
                'reference' => (string) ($data['reference'] ?? ''),
                'notes' => (string) ($data['notes'] ?? ''),
                'paid_at' => null,
            ];

            if (Schema::hasColumn('commission_payouts', 'organization_id')) {
                $payload['organization_id'] = $actor->organization_id;
            }
            if (Schema::hasColumn('commission_payouts', 'payout_number')) {
                $payload['payout_number'] = $this->nextPayoutNumber();
            }
            if (Schema::hasColumn('commission_payouts', 'status')) {
                $payload['status'] = (string) ($data['status'] ?? 'processing');
            }
            if (Schema::hasColumn('commission_payouts', 'payment_method')) {
                $payload['payment_method'] = (string) ($data['payment_method'] ?? 'bank_transfer');
            }
            if (Schema::hasColumn('commission_payouts', 'currency')) {
                $payload['currency'] = $currency;
            }
            if (Schema::hasColumn('commission_payouts', 'created_by')) {
                $payload['created_by'] = $actor->id;
            }
            if (Schema::hasColumn('commission_payouts', 'processed_at')) {
                $payload['processed_at'] = null;
            }

            $payout = CommissionPayout::query()->create($payload);

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

            if (Schema::hasColumn('commission_payouts', 'status') && (string) $payout->status === 'completed') {
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
