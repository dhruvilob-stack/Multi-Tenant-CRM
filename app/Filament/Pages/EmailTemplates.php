<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class EmailTemplates extends Page
{
    protected string $view = 'filament.pages.email-templates';
    protected static ?string $slug = 'inbox-mail/templates';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedEnvelopeOpen;
    protected static ?string $navigationLabel = 'Templates';
    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    public array $templates = [];

    public function mount(): void
    {
        $this->loadTemplates();
    }

    public function saveTemplates(): void
    {
        $validated = validator(
            ['templates' => $this->templates],
            [
                'templates.invitation.subject' => ['required', 'string', 'max:255'],
                'templates.invitation.body' => ['required', 'string'],
                'templates.quotation.subject' => ['required', 'string', 'max:255'],
                'templates.quotation.body' => ['required', 'string'],
                'templates.invoice.subject' => ['required', 'string', 'max:255'],
                'templates.invoice.body' => ['required', 'string'],
                'templates.payment.subject' => ['required', 'string', 'max:255'],
                'templates.payment.body' => ['required', 'string'],
            ]
        )->validate();

        $organization = auth()->user()?->organization;

        if (! $organization) {
            Notification::make()->danger()->title('Organization not found')->send();
            return;
        }

        $settings = (array) ($organization->settings ?? []);
        $settings['email_templates'] = $validated['templates'];

        $organization->forceFill(['settings' => $settings])->saveQuietly();

        Notification::make()->success()->title('Email templates saved')->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return 'Templates';
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ], true);
    }

    private function loadTemplates(): void
    {
        $defaults = [
            'invitation' => [
                'subject' => 'You are invited to join our CRM network',
                'body' => 'Hello {{name}}, you have been invited as {{role}}. Click {{accept_url}} to activate your account.',
            ],
            'quotation' => [
                'subject' => 'Quotation {{quotation_number}} is ready',
                'body' => 'Hello {{name}}, quotation {{quotation_number}} totaling {{amount}} is ready for your review.',
            ],
            'invoice' => [
                'subject' => 'Invoice {{invoice_number}} generated',
                'body' => 'Invoice {{invoice_number}} has been generated with due date {{due_date}}.',
            ],
            'payment' => [
                'subject' => 'Payment received for {{invoice_number}}',
                'body' => 'Payment of {{amount}} has been received for invoice {{invoice_number}}.',
            ],
        ];

        $organization = auth()->user()?->organization;
        $settings = (array) ($organization?->settings ?? []);
        $saved = (array) ($settings['email_templates'] ?? []);

        $this->templates = array_replace_recursive($defaults, $saved);
    }
}
