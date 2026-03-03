<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrganizationMail;
use App\Models\User;
use App\Services\OrganizationMailService;
use App\Support\UserRole;
use Throwable;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\WithFileUploads;

class InboxMailSend extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected string $view = 'filament.pages.inbox-mail-send';
    protected static ?string $slug = 'inbox-mail/send';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedPaperAirplane;
    protected static ?string $navigationLabel = 'Send Mail';
    protected static string|\UnitEnum|null $navigationGroup = 'Inbox Mail';

    public array $to = [];
    public string $toQuery = '';
    public array $toSuggestions = [];
    public string $subject = '';
    public ?array $data = [];
    public string $template_key = '';
    public ?int $order_id = null;
    public ?int $invoice_id = null;
    public bool $attach_invoice_pdf = false;
    public bool $attach_sales_report = false;

    /**
     * @var array<int, mixed>
     */
    public array $media_files = [];

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

    public function mount(): void
    {
        $initialBody = '';
        $replyMailId = (int) request()->query('reply', 0);

        if ($replyMailId > 0) {
            $mail = OrganizationMail::query()->find($replyMailId);

            if ($mail) {
                $this->subject = str_starts_with(strtolower($mail->subject), 're:') ? $mail->subject : 'Re: '.$mail->subject;
                if ($mail->sender_email !== '' && $mail->sender_email !== auth()->user()?->email) {
                    $this->to[] = $mail->sender_email;
                }

                $initialBody = '<p><br></p><blockquote>'.$mail->body.'</blockquote>';
            }
        }

        $this->form->fill(['body' => $initialBody]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Terms & Description')
                    ->schema([
                        RichEditor::make('body')
                            ->label('Mail Body')
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function updatedToQuery(string $value): void
    {
        $query = trim($value);

        if (mb_strlen($query) < 1) {
            $this->toSuggestions = [];
            return;
        }

        $orgId = auth()->user()?->organization_id;

        $this->toSuggestions = User::query()
            ->where('organization_id', $orgId)
            ->where(function ($q) use ($query): void {
                $q->where('email', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->whereNotIn('email', $this->to)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'email' => $user->email,
                'label' => $user->name.' <'.$user->email.'>',
            ])
            ->all();
    }

    public function addRecipient(string $email): void
    {
        $email = trim($email);

        if ($email === '' || in_array($email, $this->to, true)) {
            return;
        }

        $this->to[] = $email;
        $this->toQuery = '';
        $this->toSuggestions = [];
    }

    public function addRecipientFromQuery(): void
    {
        $this->addRecipient($this->toQuery);
    }

    public function removeRecipient(string $email): void
    {
        $this->to = array_values(array_filter($this->to, fn (string $x): bool => $x !== $email));
    }

    public function orderOptions(): array
    {
        $orgId = auth()->user()?->organization_id;

        return Order::query()
            ->whereHas('vendor', fn ($q) => $q->where('organization_id', $orgId))
            ->latest('id')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (Order $order) => [$order->id => $order->order_number])
            ->all();
    }

    public function invoiceOptions(): array
    {
        $orgId = auth()->user()?->organization_id;

        return Invoice::query()
            ->whereHas('quotation.vendor', fn ($q) => $q->where('organization_id', $orgId))
            ->latest('id')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice) => [$invoice->id => $invoice->invoice_number])
            ->all();
    }

    public function templateOptions(): array
    {
        return [
            'order_status_update' => 'Order Status Update',
            'invoice_ready' => 'Invoice Ready',
            'sales_report' => 'Sales Report',
            'custom' => 'Custom',
        ];
    }

    public function applyTemplate(): void
    {
        $orderText = $this->order_id ? ('#'.$this->order_id) : '[Order ID]';
        $invoiceText = $this->invoice_id ? ('#'.$this->invoice_id) : '[Invoice ID]';

        if ($this->template_key === 'order_status_update') {
            $this->subject = 'Order '.$orderText.' status update';
            $this->form->fill([
                'body' => '<p>Hello,</p><p>Your order '.$orderText.' has a new status update.</p><p>Regards.</p>',
            ]);
        } elseif ($this->template_key === 'invoice_ready') {
            $this->subject = 'Invoice '.$invoiceText.' is ready';
            $this->form->fill([
                'body' => '<p>Hello,</p><p>Your invoice '.$invoiceText.' is ready.</p><p>Regards.</p>',
            ]);
            $this->attach_invoice_pdf = true;
        } elseif ($this->template_key === 'sales_report') {
            $this->subject = 'Organization sales report';
            $this->form->fill([
                'body' => '<p>Hello,</p><p>Please find attached sales dashboard report.</p><p>Regards.</p>',
            ]);
            $this->attach_sales_report = true;
        }
    }

    public function sendMail(): void
    {
        $state = $this->form->getState();

        $this->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['email'],
            'subject' => ['required', 'string', 'max:255'],
            'data.body' => ['required'],
            'media_files.*' => ['file', 'max:10240'],
        ]);

        $sender = auth()->user();

        if (! $sender) {
            return;
        }

        $storedPaths = [];
        foreach ($this->media_files as $file) {
            if ($file) {
                $stored = $file->store('mail-media', 'local');
                $storedPaths[] = storage_path('app/'.$stored);
            }
        }

        $mailBody = $this->normalizeMailBody($state['body'] ?? null);
        if ($mailBody === '') {
            throw ValidationException::withMessages([
                'data.body' => 'Mail Body is required.',
            ]);
        }

        $signature = $this->organizationSignature();

        try {
            app(OrganizationMailService::class)->send($sender, [
                'to' => $this->to,
                'cc' => [],
                'bcc' => [],
                'subject' => $this->subject,
                'body' => $mailBody.$signature,
                'template_key' => $this->template_key ?: null,
                'order_id' => $this->order_id,
                'invoice_id' => $this->invoice_id,
                'attach_invoice_pdf' => $this->attach_invoice_pdf,
                'attach_sales_report' => $this->attach_sales_report,
                'media_attachments' => $storedPaths,
            ]);
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->danger()
                ->title('Mail could not be sent')
                ->body($e->getMessage())
                ->send();
            return;
        }

        Notification::make()->success()->title('Mail sent')->send();

        $this->to = [];
        $this->toQuery = '';
        $this->toSuggestions = [];
        $this->subject = '';
        $this->form->fill(['body' => '']);
        $this->template_key = '';
        $this->order_id = null;
        $this->invoice_id = null;
        $this->attach_invoice_pdf = false;
        $this->attach_sales_report = false;
        $this->media_files = [];
    }

    private function organizationSignature(): string
    {
        $organization = auth()->user()?->organization;
        $name = e((string) ($organization?->name ?? 'Organization'));
        $email = e((string) (auth()->user()?->email ?? ''));
        $logo = $organization?->logo ? asset('storage/'.$organization->logo) : null;
        $logoHtml = $logo ? '<p><img src="'.e($logo).'" style="max-height:48px;" alt="logo"></p>' : '';

        return '<br><br><hr><p><strong>'.$name.'</strong><br>Email: '.$email.'</p>'.$logoHtml;
    }

    private function normalizeMailBody(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            if (isset($value['html']) && is_string($value['html'])) {
                return trim($value['html']);
            }

            if (($value['type'] ?? null) === 'doc' && isset($value['content']) && is_array($value['content'])) {
                return trim($this->tiptapNodesToHtml($value['content']));
            }

            if (isset($value['content']) && is_string($value['content'])) {
                return trim($value['content']);
            }
        }

        return trim((string) $value);
    }

    /**
     * @param array<int, mixed> $nodes
     */
    private function tiptapNodesToHtml(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = (string) ($node['type'] ?? '');
            $content = is_array($node['content'] ?? null) ? $node['content'] : [];

            if ($type === 'text') {
                $text = e((string) ($node['text'] ?? ''));
                $marks = is_array($node['marks'] ?? null) ? $node['marks'] : [];

                foreach ($marks as $mark) {
                    $markType = (string) ($mark['type'] ?? '');
                    if ($markType === 'bold') {
                        $text = '<strong>'.$text.'</strong>';
                    } elseif ($markType === 'italic') {
                        $text = '<em>'.$text.'</em>';
                    } elseif ($markType === 'underline') {
                        $text = '<u>'.$text.'</u>';
                    } elseif ($markType === 'strike') {
                        $text = '<s>'.$text.'</s>';
                    } elseif ($markType === 'link') {
                        $href = e((string) data_get($mark, 'attrs.href', '#'));
                        $text = '<a href="'.$href.'">'.$text.'</a>';
                    }
                }

                $html .= $text;
                continue;
            }

            if ($type === 'hardBreak') {
                $html .= '<br>';
                continue;
            }

            if ($type === 'paragraph') {
                $html .= '<p>'.$this->tiptapNodesToHtml($content).'</p>';
                continue;
            }

            if (str_starts_with($type, 'heading')) {
                $level = (int) data_get($node, 'attrs.level', 3);
                $level = max(1, min(6, $level));
                $html .= '<h'.$level.'>'.$this->tiptapNodesToHtml($content).'</h'.$level.'>';
                continue;
            }

            if ($type === 'bulletList') {
                $html .= '<ul>'.$this->tiptapNodesToHtml($content).'</ul>';
                continue;
            }

            if ($type === 'orderedList') {
                $html .= '<ol>'.$this->tiptapNodesToHtml($content).'</ol>';
                continue;
            }

            if ($type === 'listItem') {
                $html .= '<li>'.$this->tiptapNodesToHtml($content).'</li>';
                continue;
            }

            if ($type === 'blockquote') {
                $html .= '<blockquote>'.$this->tiptapNodesToHtml($content).'</blockquote>';
                continue;
            }

            $html .= $this->tiptapNodesToHtml($content);
        }

        return $html;
    }
}
