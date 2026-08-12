<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;

class OpenRouterProvider implements AiChatProviderInterface
{
    /** Modelo "free" elige un modelo gratuito automáticamente */
    private const FREE_MODEL = 'openrouter/free';

    public function __construct(private string $apiKey) {}

    public function name(): string
    {
        return 'OpenRouter';
    }

    public function chat(array $contents, string $systemInstruction, int $timeoutSeconds = 25): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $messages = [['role' => 'system', 'content' => $systemInstruction]];
        foreach (array_slice($contents, 1, null, true) as $item) {
            $role = ($item['role'] ?? '') === 'model' ? 'assistant' : 'user';
            $text = $item['parts'][0]['text'] ?? '';
            $messages[] = ['role' => $role, 'content' => $text];
        }

        $t = max(6, min(60, $timeoutSeconds));
        $connect = min(8, max(3, (int) ($t / 4)));

        try {
            $response = Http::connectTimeout($connect)
                ->timeout($t)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => self::FREE_MODEL,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ]);
        } catch (\Throwable) {
            return null;
        }
        if (! $response->successful()) {
            return null;
        }
        $content = $response->json('choices.0.message.content');
        if ($content === null || trim((string) $content) === '') {
            return null;
        }
        return trim((string) $content);
    }
}
