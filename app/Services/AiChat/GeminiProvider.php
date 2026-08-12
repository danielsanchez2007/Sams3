<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiChatProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-2.0-flash'
    ) {}

    public function name(): string
    {
        return 'Gemini';
    }

    public function chat(array $contents, string $systemInstruction, int $timeoutSeconds = 25): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $primary = trim($this->model) !== '' ? $this->model : 'gemini-2.0-flash';
        $fallbackModel = 'gemini-1.5-flash';
        if (strtolower($primary) === strtolower($fallbackModel)) {
            $fallbackModel = 'gemini-2.0-flash';
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            ],
        ];

        $t = max(6, min(60, $timeoutSeconds));
        $connect = min(8, max(3, (int) ($t / 4)));
        $half = max(6, (int) ceil($t / 2));

        $attempts = [
            ['v1beta', $primary, $half],
            ['v1beta', $fallbackModel, max(6, $t - $half)],
        ];

        foreach ($attempts as [$version, $model, $sec]) {
            $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . $this->apiKey;
            try {
                $response = Http::connectTimeout($connect)->timeout((int) $sec)->post($url, $body);
            } catch (\Throwable) {
                continue;
            }
            if (! $response->successful()) {
                continue;
            }
            $data = $response->json();
            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            $text = '';
            foreach ($parts as $part) {
                $text .= $part['text'] ?? '';
            }
            $reply = trim($text);
            if ($reply !== '') {
                return $reply;
            }
        }

        return null;
    }
}
