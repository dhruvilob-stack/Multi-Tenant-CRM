<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MailComposerController extends Controller
{
    public function send(Request $request, OrganizationMailService $mailService): JsonResponse
    {
        $sender = Auth::user();

        if (! $sender) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'to' => ['required', 'string'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $to = $this->parseEmails((string) $validated['to']);
        $cc = $this->parseEmails((string) ($validated['cc'] ?? ''));
        $bcc = $this->parseEmails((string) ($validated['bcc'] ?? ''));

        $storedPaths = [];
        foreach ((array) $request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            $stored = $file->store('mail-media', 'local');
            $storedPaths[] = storage_path('app/'.$stored);
        }

        $body = $this->sanitizeBody((string) $validated['body']);
        $signature = $this->organizationSignature();

        $mailService->send($sender, [
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => (string) $validated['subject'],
            'body' => $body.$signature,
            'template_key' => 'custom',
            'media_attachments' => $storedPaths,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Mail sent successfully.',
        ]);
    }

    public function assist(Request $request, GeminiMailAssistantService $assistant): JsonResponse
    {
        $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'mode' => ['nullable', 'string', 'in:draft,improve,formal,shorten'],
        ]);

        try {
            $text = $assistant->assist(
                subject: (string) $request->input('subject', ''),
                body: (string) $request->input('body', ''),
                mode: (string) $request->input('mode', 'improve'),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gemini assist failed.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'body' => $text,
        ]);
    }

    public function records(Request $request): JsonResponse
    {
        $type = strtolower(trim((string) $request->input('type', 'product')));
        $search = trim((string) $request->input('search', ''));

        $items = match ($type) {
            'inventory' => $this->inventoryRecords($search),
            'order' => $this->orderRecords($search),
            'category' => $this->categoryRecords($search),
            default => $this->productRecords($search),
        };

        return response()->json([
            'ok' => true,
            'items' => $items,
        ]);
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

    private function organizationSignature(): string
    {
        $organization = auth()->user()?->organization;
        $name = e((string) ($organization?->name ?? 'Organization'));
        $email = e((string) (auth()->user()?->email ?? ''));
        $logo = $organization?->logo ? asset('storage/'.$organization->logo) : null;
        $logoHtml = $logo ? '<p><img src="'.e($logo).'" style="max-height:48px;" alt="logo"></p>' : '';

        return '<br><br><hr><p><strong>'.$name.'</strong><br>Email: '.$email.'</p>'.$logoHtml;
    }

    private function sanitizeBody(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><div><span>';
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

    /**
     * @return array<int, array{id:int,label:string,url:string}>
     */
    private function productRecords(string $search): array
    {
        $query = Product::query()->select(['id', 'name', 'sku'])->latest('id')->limit(100);
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $base = ShopProductResource::getUrl('index');

        return $query->get()->map(fn (Product $r): array => [
            'id' => $r->id,
            'label' => trim($r->name.' (SKU: '.($r->sku ?: '-').')'),
            'url' => $base.'?highlight_type=product&highlight_id='.$r->id,
        ])->all();
    }

    /**
     * @return array<int, array{id:int,label:string,url:string}>
     */
    private function inventoryRecords(string $search): array
    {
        $query = Inventory::query()->select(['id', 'sku', 'barcode', 'quantity_available'])->latest('id')->limit(100);
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $base = InventoryResource::getUrl('index');

        return $query->get()->map(fn (Inventory $r): array => [
            'id' => $r->id,
            'label' => 'SKU: '.($r->sku ?: '-').' | Qty: '.(string) $r->quantity_available,
            'url' => $base.'?highlight_type=inventory&highlight_id='.$r->id,
        ])->all();
    }

    /**
     * @return array<int, array{id:int,label:string,url:string}>
     */
    private function orderRecords(string $search): array
    {
        $query = Order::query()->select(['id', 'order_number', 'status'])->latest('id')->limit(100);
        if ($search !== '') {
            $query->where('order_number', 'like', "%{$search}%");
        }

        $base = OrderResource::getUrl('index');

        return $query->get()->map(fn (Order $r): array => [
            'id' => $r->id,
            'label' => ($r->order_number ?: ('Order #'.$r->id)).' | '.strtoupper((string) $r->status),
            'url' => $base.'?highlight_type=order&highlight_id='.$r->id,
        ])->all();
    }

    /**
     * @return array<int, array{id:int,label:string,url:string}>
     */
    private function categoryRecords(string $search): array
    {
        $query = Category::query()->select(['id', 'name', 'slug'])->latest('id')->limit(100);
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $base = ShopCategoryResource::getUrl('index');

        return $query->get()->map(fn (Category $r): array => [
            'id' => $r->id,
            'label' => $r->name ?: ('Category #'.$r->id),
            'url' => $base.'?highlight_type=category&highlight_id='.$r->id,
        ])->all();
    }
}
