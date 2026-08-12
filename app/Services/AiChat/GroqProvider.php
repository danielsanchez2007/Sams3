<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;

class GroqProvider implements AiChatProviderInterface
{
    /** Modelos gratuitos: llama3-70b, llama3-8b, mixtral-8x7b, etc. */
    private const MODELS = ['llama-3.3-70b-versatile', 'llama3-70b-8192', 'llama3-8b-8192', 'mixtral-8x7b-32768'];

    public function __construct(private string $apiKey) {}

    public function name(): string
    {
        return 'Groq';
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
        $models = array_slice(self::MODELS, 0, 2);
        $perModel = max(6, (int) ceil($t / count($models)));

        foreach ($models as $model) {
            try {
                $response = Http::connectTimeout($connect)
                    ->timeout($perModel)
                    ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 2048,
                    ]);
            } catch (\Throwable) {
                continue;
            }
            if (! $response->successful()) {
                continue;
            }
            $content = $response->json('choices.0.message.content');
            if ($content !== null && trim((string) $content) !== '') {
                return trim((string) $content);
            }
        }

        return null;
    }
}
