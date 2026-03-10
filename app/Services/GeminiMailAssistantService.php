<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use function Laravel\Ai\agent;

class GeminiMailAssistantService
{
    /**
     * @return array{suggestion_html?: string, full_email_html?: string}
     */
    public function generateWithContext(
        User $sender,
        string $subject,
        string $body,
        string $mode = 'suggestion',
        array $recipientEmails = [],
    ): array
    {
        $providerKey = (string) config('ai.providers.gemini.key', '');

        if ($providerKey === '') {
            throw ValidationException::withMessages([
                'gemini' => 'GEMINI_API_KEY is not configured in .env.',
            ]);
        }

        $mode = in_array($mode, ['suggestion', 'full', 'autocomplete'], true) ? $mode : 'suggestion';
        $outputKey = $mode === 'full' ? 'full_email_html' : 'suggestion_html';

        $context = $this->buildCrmContext($sender, $recipientEmails);
        $instructions = $this->baseInstructions($mode, $outputKey);
        $prompt = $this->buildPrompt($mode, $subject, $body, $context);

        $schema = function (JsonSchema $schema) use ($outputKey): array {
            return [
                $outputKey => $schema->string()
                    ->required()
                    ->description('Valid HTML string only, directly insertable into rich text editor.'),
            ];
        };

        $response = agent(
            instructions: $instructions,
            schema: $schema,
        )->prompt(
            $prompt,
            provider: 'gemini',
            model: $this->resolveGeminiModel(),
            timeout: 45,
        );

        $structured = method_exists($response, 'toArray') ? (array) $response->toArray() : [];
        $html = trim((string) ($structured[$outputKey] ?? ''));

        if ($html === '') {
            $parsed = json_decode((string) $response, true);
            if (is_array($parsed)) {
                $html = trim((string) ($parsed[$outputKey] ?? ''));
            }
        }

        if ($html === '') {
            throw ValidationException::withMessages([
                'gemini' => 'Gemini returned an empty response.',
            ]);
        }

        $html = $this->normalizeHtmlOutput($html);

        return [$outputKey => $html];
    }

    public function assist(string $subject, string $body, string $mode = 'improve'): string
    {
        /** @var User|null $sender */
        $sender = auth('tenant')->user();
        if (! $sender) {
            throw ValidationException::withMessages([
                'auth' => 'Unauthorized.',
            ]);
        }

        $normalizedMode = $mode === 'draft' ? 'full' : 'suggestion';
        $result = $this->generateWithContext($sender, $subject, $body, $normalizedMode);

        return (string) ($result['full_email_html'] ?? $result['suggestion_html'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCrmContext(User $sender, array $recipientEmails): array
    {
        $organization = $sender->organization;
        $organizationId = (int) ($sender->organization_id ?? 0);
        $recipientEmails = collect($recipientEmails)
            ->map(fn ($email): string => mb_strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $recipientUsers = User::query()
            ->where('organization_id', $organizationId)
            ->when($recipientEmails !== [], fn ($query) => $query->whereIn('email', $recipientEmails))
            ->get(['name', 'email', 'role'])
            ->map(fn (User $user): array => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) $user->role,
            ])
            ->values()
            ->all();

        $products = Product::query()
            ->whereHas('manufacturer', fn ($q) => $q->where('organization_id', $organizationId))
            ->latest('id')
            ->limit(5)
            ->get(['name', 'sku', 'price', 'qty'])
            ->map(fn (Product $product): array => [
                'name' => (string) $product->name,
                'sku' => (string) ($product->sku ?? ''),
                'price' => (float) ($product->price ?? 0),
                'qty' => (float) ($product->qty ?? 0),
            ])
            ->values()
            ->all();

        $orders = Order::query()
            ->whereHas('vendor', fn ($q) => $q->where('organization_id', $organizationId))
            ->latest('id')
            ->limit(5)
            ->get(['order_number', 'status', 'currency', 'total_amount'])
            ->map(fn (Order $order): array => [
                'order_number' => (string) ($order->order_number ?? ''),
                'status' => (string) ($order->status ?? ''),
                'currency' => (string) ($order->currency ?? ''),
                'total_amount' => (float) ($order->total_amount ?? 0),
            ])
            ->values()
            ->all();

        $quotations = Quotation::query()
            ->whereHas('vendor', fn ($q) => $q->where('organization_id', $organizationId))
            ->latest('id')
            ->limit(5)
            ->get(['quotation_number', 'status', 'subject', 'grand_total'])
            ->map(fn (Quotation $quotation): array => [
                'quotation_number' => (string) ($quotation->quotation_number ?? ''),
                'status' => (string) ($quotation->status ?? ''),
                'subject' => (string) ($quotation->subject ?? ''),
                'grand_total' => (float) ($quotation->grand_total ?? 0),
            ])
            ->values()
            ->all();

        $partners = User::query()
            ->where('organization_id', $organizationId)
            ->whereIn('role', ['manufacturer', 'distributor', 'vendor', 'consumer'])
            ->latest('id')
            ->limit(8)
            ->get(['name', 'email', 'role'])
            ->map(fn (User $user): array => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) $user->role,
            ])
            ->values()
            ->all();

        return [
            'organization' => [
                'name' => (string) ($organization?->name ?? ''),
                'slug' => (string) ($organization?->slug ?? ''),
                'email' => (string) ($organization?->email ?? ''),
            ],
            'sender' => [
                'name' => (string) $sender->name,
                'email' => (string) $sender->email,
                'role' => (string) $sender->role,
            ],
            'recipients' => $recipientUsers,
            'products' => $products,
            'orders' => $orders,
            'quotations' => $quotations,
            'partners' => $partners,
        ];
    }

    private function baseInstructions(string $mode, string $outputKey): string
    {
        $modeRules = match ($mode) {
            'full' => 'FULL EMAIL MODE: Generate a complete professional email.',
            'autocomplete' => 'AUTOCOMPLETE MODE: Generate ONLY the next 1 to 3 words that naturally continue the last sentence. Do not repeat existing text. No greeting/closing. Keep it minimal.',
            default => 'SUGGESTION MODE: Generate ONLY continuation based on the current partial email body. Do not repeat already written text.',
        };

        return <<<TXT
You are an AI email assistant for a multi-tenant CRM.

CRITICAL OUTPUT RULES:
1. Always return content in valid HTML format.
2. NEVER return Markdown.
3. NEVER wrap content inside quotation marks.
4. NEVER return code blocks.
5. Use proper HTML tags such as <p>, <br>, <b>, <strong>, <ul>, <li>.
6. Output must be directly insertable into a rich text editor.
7. Maintain a professional business communication tone.
8. Avoid overly long paragraphs.
9. Use natural email formatting.

{$modeRules}

Return strictly valid JSON with exactly this key:
{
  "{$outputKey}": "..."
}
TXT;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildPrompt(string $mode, string $subject, string $body, array $context): string
    {
        $modeLabel = $mode === 'full' ? 'full' : ($mode === 'autocomplete' ? 'autocomplete' : 'suggestion');
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

        return <<<PROMPT
Mode: {$modeLabel}
Subject: {$subject}
Current Email Body (HTML or plain):
{$body}

CRM Context JSON:
{$contextJson}

Use context intelligently (organization, sender, recipient role, products, quotations, orders).
Generate a business-appropriate response now.
PROMPT;
    }

    private function resolveGeminiModel(): ?string
    {
        $model = trim((string) env('GEMINI_MODEL', ''));

        return $model !== '' ? $model : null;
    }

    private function normalizeHtmlOutput(string $html): string
    {
        $html = trim($html);

        if (Str::startsWith($html, ['```html', '```'])) {
            $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
            $html = preg_replace('/\s*```$/', '', $html) ?? $html;
            $html = trim($html);
        }

        $html = trim($html, "\"' \n\r\t");

        return $html;
    }
}
