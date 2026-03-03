<?php

namespace App\Http\Controllers;

use App\Models\OrganizationMailRecipient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationMailAttachmentController extends Controller
{
    public function download(Request $request, int $recipient, int $index): BinaryFileResponse
    {
        $recipientRow = OrganizationMailRecipient::query()
            ->with('mail')
            ->where('id', $recipient)
            ->where('recipient_id', auth()->id())
            ->firstOrFail();

        $meta = (array) ($recipientRow->mail?->meta ?? []);
        $attachments = array_values(array_filter((array) ($meta['attachments'] ?? []), fn ($value): bool => is_array($value)));
        if ($attachments === []) {
            $legacy = array_values(array_filter((array) ($meta['media_attachments'] ?? []), fn ($value): bool => is_string($value) && $value !== ''));
            $attachments = array_map(fn (string $path): array => [
                'path' => $path,
                'name' => basename($path),
                'mime' => mime_content_type($path) ?: 'application/octet-stream',
                'size' => (int) @filesize($path),
            ], $legacy);
        }
        $attachment = $attachments[$index] ?? null;

        abort_if(! is_array($attachment), 404);

        $path = (string) ($attachment['path'] ?? '');
        $name = (string) ($attachment['name'] ?? basename($path));
        $mime = (string) ($attachment['mime'] ?? 'application/octet-stream');

        abort_if($path === '' || ! is_file($path), 404);

        if ((int) $request->query('inline', 0) === 1) {
            return response()->file($path, ['Content-Type' => $mime]);
        }

        return response()->download($path, $name, ['Content-Type' => $mime]);
    }
}
