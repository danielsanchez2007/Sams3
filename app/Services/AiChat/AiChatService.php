<?php

namespace App\Services\AiChat;

class AiChatService
{
    /** @var array<int, AiChatProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        $order = config('ai-chat.providers', ['gemini', 'groq', 'openrouter']);
        foreach ($order as $name) {
            $p = $this->makeProvider($name);
            if ($p !== null) {
                $this->providers[] = $p;
            }
        }
    }

    private function makeProvider(string $name): ?AiChatProviderInterface
    {
        return match (strtolower($name)) {
            'gemini' => $this->gemini(),
            'groq' => $this->groq(),
            'openrouter' => $this->openrouter(),
            default => null,
        };
    }

    private function gemini(): ?GeminiProvider
    {
        $key = config('ai-chat.gemini.api_key', config('gemini.api_key', ''));
        if ($key === '') {
            return null;
        }
        return new GeminiProvider($key, config('ai-chat.gemini.model', 'gemini-2.0-flash'));
    }

    private function groq(): ?GroqProvider
    {
        $key = config('ai-chat.groq.api_key', '');
        if ($key === '') {
            return null;
        }
        return new GroqProvider($key);
    }

    private function openrouter(): ?OpenRouterProvider
    {
        $key = config('ai-chat.openrouter.api_key', '');
        if ($key === '') {
            return null;
        }
        return new OpenRouterProvider($key);
    }

    /**
     * Intenta obtener respuesta de la primera IA que responda.
     * @return array{reply: string|null, provider: string|null}
     */
    public function chat(array $contents, string $systemInstruction): array
    {
        $deadline = microtime(true) + (float) config('ai-chat.max_total_seconds', 38);
        $perCallCap = (int) config('ai-chat.per_provider_timeout_cap', 18);

        foreach ($this->providers as $provider) {
            $remaining = (int) ceil($deadline - microtime(true));
            if ($remaining < 4) {
                break;
            }
            $budget = min($perCallCap, max(6, $remaining));
            $reply = $provider->chat($contents, $systemInstruction, $budget);
            if ($reply !== null && trim($reply) !== '') {
                return ['reply' => trim($reply), 'provider' => $provider->name()];
            }
        }

        return ['reply' => null, 'provider' => null];
    }

    /** @return string[] */
    public function configuredProviderNames(): array
    {
        return array_map(fn (AiChatProviderInterface $p) => $p->name(), $this->providers);
    }

    public function hasAnyProvider(): bool
    {
        return count($this->providers) > 0;
    }
}
