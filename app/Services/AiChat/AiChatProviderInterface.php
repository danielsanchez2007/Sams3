<?php

namespace App\Services\AiChat;

interface AiChatProviderInterface
{
    /**
     * Envía la conversación a la IA y devuelve la respuesta en texto, o null si falla.
     *
     * @param  array<int, array{role: string, parts: array<int, array{text: string}>}>  $contents  Historial + mensaje actual (formato Gemini: role + parts)
     * @param  string  $systemInstruction  Instrucción de sistema (contexto SAMS, etc.)
     */
    /**
     * @param  int  $timeoutSeconds  Tiempo máximo de espera HTTP para esta llamada (evita cuelgues largos).
     */
    public function chat(array $contents, string $systemInstruction, int $timeoutSeconds = 25): ?string;

    /**
     * Nombre del proveedor para logs y mensajes (ej: "Gemini", "Groq").
     */
    public function name(): string;
}
