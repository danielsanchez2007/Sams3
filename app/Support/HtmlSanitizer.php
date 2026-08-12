<?php

namespace App\Support;

/**
 * Sanitización básica de HTML generado por el usuario (inspección, hoja de vida).
 */
class HtmlSanitizer
{
    public static function sanitizeUserHtml(string $html): string
    {
        $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = (string) preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
        $html = (string) preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
        $html = (string) preg_replace('/<embed\b[^>]*\/?>/is', '', $html);
        $html = (string) preg_replace('/<link\b[^>]*>/is', '', $html);
        $html = (string) preg_replace('/<meta\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // javascript: / data: en href y src
        $html = (string) preg_replace('/\s(href|src|action|formaction|xlink:href)\s*=\s*["\']?\s*(javascript|data|vbscript):[^"\']*["\']?/i', '', $html);

        // Atributos de eventos (onerror, onclick, etc.)
        $html = (string) preg_replace('/\s(on\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return $html;
    }

    /**
     * Conserva estilos e imágenes del Excel; elimina scripts y handlers peligrosos.
     */
    public static function sanitizeTemplateHtml(string $html): string
    {
        $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = (string) preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
        $html = (string) preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
        $html = (string) preg_replace('/<embed\b[^>]*\/?>/is', '', $html);
        $html = (string) preg_replace('/<link\b[^>]*>/is', '', $html);
        $html = (string) preg_replace('/<meta\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/\s(href|src|action|formaction|xlink:href)\s*=\s*["\']?\s*(javascript|vbscript):[^"\']*["\']?/i', '', $html);
        $html = (string) preg_replace('/\s(on\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return $html;
    }
}
