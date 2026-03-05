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
use App\Services\OrganizationMailService;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class MailComposerPanel extends Component implements HasForms
{
    use InteractsWithForms;

    public string $to = '';
    public string $cc = '';
    public string $bcc = '';
    public string $subject = '';
    public ?array $data = [];

    public string $recordType = 'product';
    public string $recordSearch = '';
    public ?string $recordSelection = null;
    public array $recordOptions = [];
    public array $recordUrls = [];

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
        if (filled($payload['to'] ?? null)) {
            $this->to = (string) $payload['to'];
        }
        if (filled($payload['subject'] ?? null)) {
            $this->subject = (string) $payload['subject'];
        }
        if (filled($payload['body'] ?? null)) {
            $quoted = nl2br(e((string) $payload['body']));
            $current = (string) ($this->data['content'] ?? '');
            $this->data['content'] = trim($current).'<br><br><blockquote>'.$quoted.'</blockquote>';
            $this->form->fill(['content' => $this->data['content']]);
        }
    }

    public function resetComposer(): void
    {
        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
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
        $current = (string) ($this->data['content'] ?? '');
        $this->data['content'] = $current.'<p><a href="'.$url.'" target="_blank" rel="noopener">'.$label.'</a></p>';
        $this->form->fill(['content' => $this->data['content']]);
    }

    public function sendMail(): void
    {
        $this->validate([
            'to' => ['required', 'string'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'data.content' => ['required', 'string'],
        ]);

        $sender = auth()->user();
        if (! $sender) {
            return;
        }

        app(OrganizationMailService::class)->send($sender, [
            'to' => $this->parseEmails($this->to),
            'cc' => $this->parseEmails($this->cc),
            'bcc' => $this->parseEmails($this->bcc),
            'subject' => $this->subject,
            'body' => $this->sanitizeBody((string) ($this->data['content'] ?? '')).$this->organizationSignature(),
            'template_key' => 'custom',
        ]);

        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->data['content'] = '';
        $this->form->fill(['content' => '']);

        $this->dispatch('mail-counts-updated');

        Notification::make()
            ->success()
            ->title('Mail sent')
            ->send();
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

        return $clean;
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

    /**
     * @return array<int, string>
     */
    private function parseEmails(string $input): array
    {
        return collect(preg_split('/[,\n;]+/', $input) ?: [])
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
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
