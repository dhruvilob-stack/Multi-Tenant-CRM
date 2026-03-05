<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GeminiMailAssistantService
{
    public function assist(string $subject, string $body, string $mode = 'improve'): string
    {
        $apiKey = (string) env('GEMINI_API_KEY', '');

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'gemini' => 'GEMINI_API_KEY is not configured in .env.',
            ]);
        }

        $instruction = match ($mode) {
            'draft' => 'Write a complete professional email based on the provided context.',
            'formal' => 'Rewrite this email in a more formal and professional tone.',
            'shorten' => 'Rewrite this email to be concise while preserving meaning.',
            default => 'Improve clarity, grammar, and structure while preserving intent.',
        };

        $prompt = <<<PROMPT
{$instruction}

Subject: {$subject}
Body:
{$body}

Return plain text only (no markdown).
PROMPT;

        $models = array_values(array_unique(array_filter([
            (string) env('GEMINI_MODEL', ''),
            'gemini-2.0-flash',
            'gemini-1.5-flash',
        ])));

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        $lastError = 'Gemini API request failed.';

        foreach ($models as $model) {
            $response = Http::timeout(45)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

            if (! $response->successful()) {
                $lastError = (string) data_get($response->json(), 'error.message', $response->body() ?: $lastError);
                continue;
            }

            $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

            if (trim($text) !== '') {
                return trim($text);
            }

            $lastError = 'Gemini returned an empty response.';
        }

        throw ValidationException::withMessages([
            'gemini' => $lastError,
        ]);
    }
}
