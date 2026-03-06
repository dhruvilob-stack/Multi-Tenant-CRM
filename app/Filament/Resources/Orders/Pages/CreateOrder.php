<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use App\Models\User;
use App\Support\SystemSettings;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;

class CreateOrder extends CreateRecord
{
    use HasWizard;

    protected static string $resource = OrderResource::class;

    /**
     * @return array<Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make('Order Details')
                ->schema([
                    Section::make()
                        ->schema(OrderForm::getDetailsComponents())
                        ->columns(),
                ]),
            Step::make('Order Items')
                ->schema([
                    Section::make()
                        ->schema([OrderForm::getItemsRepeater()]),
                ]),
            Step::make('Payment Info')
                ->schema([
                    Section::make()
                        ->schema(OrderForm::getPaymentComponents())
                        ->columns(2),
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data = OrderForm::normalizeItemsAndTotals($data);

        $data['status'] = 'new';
        $data['payment_status'] = (string) ($data['payment_status'] ?? 'pending');
        $data['currency'] = (string) ($data['currency'] ?? SystemSettings::currencyForCurrentUser());
        $data['payment_reference_number'] = filled($data['payment_reference_number'] ?? null)
            ? (string) $data['payment_reference_number']
            : null;
        $data['total_amount_billed'] = (float) ($data['total_amount'] ?? 0);

        if ($user && $user->role !== UserRole::SUPER_ADMIN && ! isset($data['vendor_id']) && $user->role === UserRole::VENDOR) {
            $data['vendor_id'] = $user->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Order|null $order */
        $order = $this->record;
        /** @var User|null $user */
        $user = auth()->user();

        if (! $order || ! $user) {
            return;
        }

        Notification::make()
            ->title('New order')
            ->icon(Heroicon::ShoppingBag)
            ->body("**{$order->consumer?->name} ordered {$order->items()->count()} products.**")
            ->actions([
                Action::make('View')
                    ->url(OrderResource::getUrl('edit', ['record' => $order])),
            ])
            ->sendToDatabase($user);
    }
}
