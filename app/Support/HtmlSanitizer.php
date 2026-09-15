<?php

namespace App\Support;

/**
 * Sanitización de HTML generado por el usuario (inspección, hoja de vida, formatos).
 * Dos pases: regex + DOM para reducir bypasses anidados (p. ej. <scr<script>ipt>).
 */
class HtmlSanitizer
{
    /** @var list<string> */
    private const FORBIDDEN_TAGS = [
        'script', 'iframe', 'object', 'embed', 'link', 'meta', 'base',
        'applet', 'form', 'input', 'button', 'textarea', 'select',
        'svg', 'math', 'style',
    ];

    /** @var list<string> */
    private const FORBIDDEN_INTERACTIVE_TAGS = [
        'script', 'iframe', 'object', 'embed', 'link', 'meta', 'base',
        'applet', 'form', 'input', 'button', 'textarea', 'select',
        'svg', 'math',
    ];

    public static function sanitizeUserHtml(string $html): string
    {
        $html = self::stripWithRegex($html, true);
        $html = self::stripWithDom($html, 'strict');
        $html = self::stripWithRegex($html, true);

        return $html;
    }

    /**
     * Conserva estilos e imágenes del Excel; elimina scripts y handlers peligrosos.
     */
    public static function sanitizeTemplateHtml(string $html): string
    {
        $html = self::stripWithRegex($html, false);
        $html = self::stripWithDom($html, 'interactive');
        $html = self::stripWithRegex($html, false);

        return $html;
    }

    private static function stripWithRegex(string $html, bool $stripStyleTags): string
    {
        $tags = 'script|iframe|object|embed|link|meta|base|applet';
        $html = (string) preg_replace('/<(' . $tags . ')\b[^>]*>.*?<\/\1>/is', '', $html);
        $html = (string) preg_replace('/<(' . $tags . ')\b[^>]*\/?>/is', '', $html);

        if ($stripStyleTags) {
            $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        }

        $html = (string) preg_replace('/\s(href|src|action|formaction|xlink:href|srcdoc)\s*=\s*["\']?\s*(javascript|vbscript|data):[^"\']*/i', '', $html);
        $html = (string) preg_replace('/\s(on\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = (string) preg_replace('/javascript\s*:/i', '', $html);
        $html = (string) preg_replace('/vbscript\s*:/i', '', $html);
        $html = (string) preg_replace('/expression\s*\(/i', '', $html);
        $html = (string) preg_replace('/-moz-binding/i', '', $html);

        return $html;
    }

    /**
     * @param  'strict'|'interactive'|'basic'  $mode
     */
    private static function stripWithDom(string $html, string $mode): string
    {
        if (trim($html) === '' || !class_exists(\DOMDocument::class)) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $wrapped = '<div id="sams-sanitize-root">' . $html . '</div>';
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $root = $dom->getElementById('sams-sanitize-root');
        if (!$root) {
            return $html;
        }

        $forbidden = match ($mode) {
            'strict' => self::FORBIDDEN_TAGS,
            'interactive' => self::FORBIDDEN_INTERACTIVE_TAGS,
            default => ['script', 'iframe', 'object', 'embed', 'link', 'meta', 'base', 'applet'],
        };

        $nodes = [];
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*') ?: [] as $node) {
            $nodes[] = $node;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, $forbidden, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }

            if (!$node->hasAttributes()) {
                continue;
            }

            $toRemove = [];
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $value = trim((string) $attr->value);
                $valueLower = strtolower($value);

                if (str_starts_with($name, 'on')) {
                    $toRemove[] = $attr->name;
                    continue;
                }

                if (in_array($name, ['href', 'src', 'action', 'formaction', 'xlink:href', 'srcdoc', 'srcset'], true)) {
                    if (
                        str_starts_with($valueLower, 'javascript:')
                        || str_starts_with($valueLower, 'vbscript:')
                        || str_starts_with($valueLower, 'data:text/html')
                        || (str_starts_with($valueLower, 'data:') && ! str_starts_with($valueLower, 'data:image/'))
                    ) {
                        $toRemove[] = $attr->name;
                    }
                }
            }

            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out !== '' ? $out : $html;
    }
}
