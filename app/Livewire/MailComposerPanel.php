<?php

namespace App\Livewire;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Shop\Categories\CategoryResource as ShopCategoryResource;
use App\Filament\Resources\Shop\Products\ProductResource as ShopProductResource;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Services\GeminiMailAssistantService;
use App\Services\OrganizationMailService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Tiptap\Editor;

class MailComposerPanel extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public string $to = '';
    public string $cc = '';
    public string $bcc = '';
    public string $subject = '';
    public ?array $data = [];
    public string $templateKey = '';
    public array $templateOptions = [];
    public string $selectedPlaceholder = '';

    public string $recordType = 'product';
    public string $recordSearch = '';
    public ?string $recordSelection = null;
    public array $recordOptions = [];
    public array $recordUrls = [];
    public bool $aiBusy = false;

    protected $listeners = [
        'mail-compose-prefill' => 'prefill',
        'mail-compose-reset' => 'resetComposer',
    ];

    public function mount(): void
    {
        $this->form->fill([
            'content' => '',
        ]);

        $this->loadRecordOptions();
        $this->loadTemplateOptions();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('content')
                    ->label('Mail Body')
                    ->live(debounce: 1500)
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike'],
                        ['h2', 'h3', 'blockquote'],
                        ['orderedList', 'bulletList'],
                        ['link', 'redo', 'undo'],
                    ])
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function updatedRecordType(): void
    {
        $this->recordSelection = null;
        $this->loadRecordOptions();
    }

    public function updatedRecordSearch(): void
    {
        $this->recordSelection = null;
        $this->loadRecordOptions();
    }

    public function prefill(array $payload = []): void
    {
        $payload = $this->normalizePrefillPayload($payload);

        if (filled($payload['to'] ?? null)) {
            $to = $payload['to'];
            if (is_array($to)) {
                $to = collect($to)->flatten()->map(fn ($v): string => trim((string) $v))->filter()->implode(', ');
            }
            $this->to = trim((string) $to);
        }
        if (filled($payload['subject'] ?? null)) {
            $this->subject = (string) $payload['subject'];
        }
        if (filled($payload['body'] ?? null)) {
            $quoted = nl2br(e((string) $payload['body']));
            $current = $this->normalizeEditorContent($this->data['content'] ?? null);
            $this->data['content'] = trim($current).'<br><br><blockquote>'.$quoted.'</blockquote>';
            $this->form->fill(['content' => $this->data['content']]);
        }
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePrefillPayload(array $payload): array
    {
        if (array_key_exists(0, $payload) && is_array($payload[0])) {
            return (array) $payload[0];
        }

        if (array_key_exists('detail', $payload) && is_array($payload['detail'])) {
            return (array) $payload['detail'];
        }

        return $payload;
    }

    public function resetComposer(): void
    {
        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->templateKey = '';
        $this->selectedPlaceholder = '';
        $this->loadTemplateOptions();
        $this->data['content'] = '';
        $this->form->fill(['content' => '']);
    }

    public function insertRecordLink(): void
    {
        $id = (string) ($this->recordSelection ?? '');
        if ($id === '' || ! isset($this->recordUrls[$id])) {
            return;
        }

        $label = e((string) ($this->recordOptions[$id] ?? 'Open record'));
        $url = e((string) $this->recordUrls[$id]);
        $anchor = '<a href="'.$url.'" target="_blank" rel="noopener">'.$label.'</a>';
        $current = $this->normalizeEditorContent($this->data['content'] ?? null);

        $selected = trim((string) $this->selectedPlaceholder);
        $tokenKey = null;

        if (preg_match('/^\{\{\s*([a-zA-Z0-9_.\-]+)\s*\}\}$/', $selected, $m) === 1) {
            $tokenKey = (string) $m[1];
        } else {
            $tokenKey = $this->extractFirstPlaceholderToken($current) ?? $this->extractFirstPlaceholderToken((string) $this->subject);
        }

        if (filled($tokenKey)) {
            $tokenPattern = '/\{\{\s*' . preg_quote((string) $tokenKey, '/') . '\s*\}\}/';
            $replaced = preg_replace($tokenPattern, $anchor, $current, 1);

            if (is_string($replaced) && $replaced !== $current) {
                $this->data['content'] = $replaced;
                $this->subject = preg_replace($tokenPattern, $label, (string) $this->subject, 1) ?? $this->subject;
            } else {
                $this->data['content'] = $current.'<p>'.$anchor.'</p>';
            }
        } else {
            $this->data['content'] = $current.'<p>'.$anchor.'</p>';
        }

        $this->form->fill(['content' => $this->data['content']]);
        $this->selectedPlaceholder = '';
    }

    private function extractFirstPlaceholderToken(string $text): ?string
    {
        if (preg_match('/\{\{\s*([a-zA-Z0-9_.\-]+)\s*\}\}/', $text, $m) !== 1) {
            return null;
        }

        $token = trim((string) ($m[1] ?? ''));

        return $token !== '' ? $token : null;
    }

    public function sendMail(): void
    {
        $this->data['content'] = $this->normalizeEditorContent($this->data['content'] ?? null);

        $this->validate([
            'to' => ['required', 'string'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'data.content' => ['required', 'string'],
        ]);

        $sender = auth('tenant')->user();
        if (! $sender) {
            return;
        }

        app(OrganizationMailService::class)->send($sender, [
            'to' => $this->parseEmails($this->to),
            'cc' => $this->parseEmails($this->cc),
            'bcc' => $this->parseEmails($this->bcc),
            'subject' => $this->subject,
            'body' => $this->sanitizeBody($this->normalizeEditorContent($this->data['content'] ?? null)).$this->organizationSignature(),
            'template_key' => $this->templateKey !== '' ? $this->templateKey : 'custom',
        ]);

        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->templateKey = '';
        $this->data['content'] = '';
        $this->form->fill(['content' => '']);

        $this->dispatch('mail-counts-updated');
        $this->dispatch('mail-compose-sent');

        Notification::make()
            ->success()
            ->title('Mail sent')
            ->send();
    }

    public function generateAiFullDraft(): void
    {
        $sender = auth('tenant')->user();
        if (! $sender) {
            return;
        }

        $this->aiBusy = true;

        try {
            $result = app(GeminiMailAssistantService::class)->generateWithContext(
                sender: $sender,
                subject: (string) $this->subject,
                body: $this->normalizeEditorContent($this->data['content'] ?? null),
                mode: 'full',
                recipientEmails: $this->parseEmails($this->to),
            );

            $draft = trim((string) ($result['full_email_html'] ?? ''));
            if ($draft === '') {
                throw ValidationException::withMessages([
                    'gemini' => 'AI returned an empty draft.',
                ]);
            }

            $this->data['content'] = $draft;
            $this->form->fill(['content' => $draft]);

            Notification::make()
                ->success()
                ->title('AI draft generated')
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('AI draft failed')
                ->body((string) collect($e->errors())->flatten()->first())
                ->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->danger()
                ->title('AI draft failed')
                ->body('Please try again.')
                ->send();
        } finally {
            $this->aiBusy = false;
        }
    }


    public function applySelectedTemplate(): void
    {
        if ($this->templateKey === '') {
            return;
        }

        $templates = $this->templatesFromSettings();
        $template = (array) ($templates[$this->templateKey] ?? []);

        $subject = trim((string) ($template['subject'] ?? ''));
        $body = trim((string) ($template['body'] ?? ''));

        if ($subject === '' && $body === '') {
            return;
        }

        if ($subject !== '') {
            $this->subject = $subject;
        }

        if ($body !== '') {
            $this->data['content'] = $body;
            $this->form->fill(['content' => $body]);
        }
    }

    private function loadTemplateOptions(): void
    {
        $templates = $this->templatesFromSettings();

        $this->templateOptions = collect($templates)
            ->mapWithKeys(function (array $template, string $key): array {
                $name = trim((string) ($template['name'] ?? ''));
                return [$key => ($name !== '' ? $name : str_replace(['_', '-'], ' ', ucfirst($key)))];
            })
            ->all();

        if ($this->templateKey !== '' && ! isset($this->templateOptions[$this->templateKey])) {
            $this->templateKey = '';
        }
    }

    /**
     * @return array<string, array{name?:string,subject?:string,body?:string}>
     */
    private function templatesFromSettings(): array
    {
        $defaults = [
            'invitation' => [
                'name' => 'Invitation',
                'subject' => 'You are invited to join our CRM network',
                'body' => '<p>Hello {{name}}, you have been invited as {{role}}. Click {{accept_url}} to activate your account.</p>',
            ],
            'quotation' => [
                'name' => 'Quotation',
                'subject' => 'Quotation {{quotation_number}} is ready',
                'body' => '<p>Hello {{name}}, quotation {{quotation_number}} totaling {{amount}} is ready for your review.</p>',
            ],
            'invoice' => [
                'name' => 'Invoice',
                'subject' => 'Invoice {{invoice_number}} generated',
                'body' => '<p>Invoice {{invoice_number}} has been generated with due date {{due_date}}.</p>',
            ],
            'payment' => [
                'name' => 'Payment',
                'subject' => 'Payment received for {{invoice_number}}',
                'body' => '<p>Payment of {{amount}} has been received for invoice {{invoice_number}}.</p>',
            ],
        ];

        $saved = (array) (auth()->user()?->organization?->settings['email_templates'] ?? []);
        $saved = array_replace_recursive($defaults, $saved);

        $templates = [];
        foreach ($saved as $key => $template) {
            if (! is_array($template)) {
                continue;
            }

            $templateKey = trim((string) $key);
            if ($templateKey === '') {
                continue;
            }

            $templates[$templateKey] = [
                'name' => (string) ($template['name'] ?? ''),
                'subject' => (string) ($template['subject'] ?? ''),
                'body' => (string) ($template['body'] ?? ''),
            ];
        }

        return $templates;
    }

    public function render()
    {
        return view('livewire.mail-composer-panel');
    }

    private function sanitizeBody(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><div><span><h2><h3><s>';
        $clean = strip_tags(trim($html), $allowed);
        $clean = preg_replace('/href\s*=\s*([\"\'])\s*javascript:[^\"\']*\1/i', 'href="#"', $clean) ?? $clean;
        $clean = preg_replace('/on\w+\s*=\s*([\"\']).*?\1/i', '', $clean) ?? $clean;
        $clean = $this->linkifyPlainUrls($clean);
        $clean = $this->forceLinksToOpenInNewTab($clean);

        return $clean;
    }

    private function forceLinksToOpenInNewTab(string $html): string
    {
        return preg_replace_callback('/<a\b([^>]*)>/i', function (array $matches): string {
            $attrs = (string) ($matches[1] ?? '');

            if (preg_match('/\bhref\s*=\s*([\"\'])(.*?)\1/i', $attrs, $hrefMatch) !== 1) {
                return $matches[0];
            }

            $href = trim((string) ($hrefMatch[2] ?? ''));
            if ($href === '') {
                return $matches[0];
            }

            if (str_starts_with(mb_strtolower($href), 'javascript:')) {
                $href = '#';
            }

            $attrs = preg_replace('/\s+target\s*=\s*([\"\']).*?\1/i', '', $attrs) ?? $attrs;
            $attrs = preg_replace('/\s+rel\s*=\s*([\"\']).*?\1/i', '', $attrs) ?? $attrs;
            $attrs = preg_replace('/\bhref\s*=\s*([\"\']).*?\1/i', 'href="'.e($href).'"', $attrs, 1) ?? $attrs;

            return '<a '.trim($attrs).' target="_blank" rel="noopener noreferrer">';
        }, $html) ?? $html;
    }

    private function linkifyPlainUrls(string $html): string
    {
        $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            return $html;
        }

        foreach ($parts as $index => $part) {
            if ($part === '' || str_starts_with($part, '<')) {
                continue;
            }

            $parts[$index] = preg_replace_callback('/(?<!["\'=])((?:https?:\/\/|www\.)[^\s<]+)/i', function (array $matches): string {
                $url = trim((string) ($matches[1] ?? ''));
                if ($url === '') {
                    return '';
                }

                $href = str_starts_with(mb_strtolower($url), 'www.') ? ('https://'.$url) : $url;
                $safeUrl = e($url);
                $safeHref = e($href);

                return '<a href="'.$safeHref.'" target="_blank" rel="noopener noreferrer">'.$safeUrl.'</a>';
            }, $part) ?? $part;
        }

        return implode('', $parts);
    }

    private function organizationSignature(): string
    {
        $tenantUser = auth('tenant')->user();
        $organization = $tenantUser?->organization;
        $name = e((string) ($organization?->name ?? 'Organization'));
        $email = e((string) ($tenantUser?->email ?? ''));
        $logo = $organization?->logo ? asset('storage/'.$organization->logo) : null;
        $logoHtml = $logo ? '<p><img src="'.e($logo).'" style="max-height:48px;" alt="logo"></p>' : '';

        return '<br><br><hr><p><strong>'.$name.'</strong><br>Email: '.$email.'</p>'.$logoHtml;
    }

    /**
     * @return array<int, string>
     */
    private function parseEmails(mixed $input): array
    {
        $flat = is_array($input)
            ? collect($input)->flatten()->map(fn ($v): string => (string) $v)->implode(',')
            : (string) $input;

        return collect(preg_split('/[,\n;]+/', $flat) ?: [])
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeEditorContent(mixed $value): string
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return '';
            }

            if ((str_starts_with($trimmed, '{') || str_starts_with($trimmed, '['))) {
                try {
                    $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded) && (($decoded['type'] ?? null) === 'doc' || isset($decoded['content']))) {
                        return $this->tiptapDocumentToHtml($decoded);
                    }
                } catch (\Throwable) {
                    // Keep original HTML/text when value is not valid JSON.
                }
            }

            return $value;
        }

        if (is_null($value)) {
            return '';
        }

        if (is_array($value)) {
            if (isset($value['content']) && is_string($value['content'])) {
                return $value['content'];
            }

            if (isset($value['html']) && is_string($value['html'])) {
                return $value['html'];
            }

            if (($value['type'] ?? null) === 'doc' || (isset($value['content']) && is_array($value['content']))) {
                return $this->tiptapDocumentToHtml($value);
            }

            return '';
        }

        return (string) $value;
    }

    private function tiptapDocumentToHtml(array $document): string
    {
        try {
            return (new Editor)->setContent($document)->getHTML();
        } catch (\Throwable) {
            return '';
        }
    }

    private function loadRecordOptions(): void
    {
        $search = trim($this->recordSearch);

        $items = match ($this->recordType) {
            'inventory' => Inventory::query()
                ->select(['id', 'sku', 'barcode', 'quantity_available'])
                ->when($search !== '', fn ($q) => $q->where('sku', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%"))
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (Inventory $r): array => [
                    'id' => (string) $r->id,
                    'label' => 'SKU: '.($r->sku ?: '-').' | Qty: '.(string) $r->quantity_available,
                    'url' => InventoryResource::getUrl('index').'?highlight_type=inventory&highlight_id='.$r->id,
                ])->all(),
            'order' => Order::query()
                ->select(['id', 'order_number', 'status'])
                ->when($search !== '', fn ($q) => $q->where('order_number', 'like', "%{$search}%"))
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (Order $r): array => [
                    'id' => (string) $r->id,
                    'label' => ($r->order_number ?: ('Order #'.$r->id)).' | '.Str::upper((string) $r->status),
                    'url' => OrderResource::getUrl('index').'?highlight_type=order&highlight_id='.$r->id,
                ])->all(),
            'category' => Category::query()
                ->select(['id', 'name', 'slug'])
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (Category $r): array => [
                    'id' => (string) $r->id,
                    'label' => $r->name ?: ('Category #'.$r->id),
                    'url' => ShopCategoryResource::getUrl('index').'?highlight_type=category&highlight_id='.$r->id,
                ])->all(),
            default => Product::query()
                ->select(['id', 'name', 'sku'])
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (Product $r): array => [
                    'id' => (string) $r->id,
                    'label' => trim($r->name.' (SKU: '.($r->sku ?: '-').')'),
                    'url' => ShopProductResource::getUrl('index').'?highlight_type=product&highlight_id='.$r->id,
                ])->all(),
        };

        $this->recordOptions = [];
        $this->recordUrls = [];
        foreach ($items as $item) {
            $id = (string) $item['id'];
            $this->recordOptions[$id] = (string) $item['label'];
            $this->recordUrls[$id] = (string) $item['url'];
        }

        if ($this->recordSelection === null && $this->recordOptions !== []) {
            $this->recordSelection = (string) array_key_first($this->recordOptions);
        }
    }
}
