<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAMS - Sistema de Gestión')</title>
    <x-sams-assets />
    @php
        $samsLightweightUi = (bool) config('sams.lightweight_ui', true);
        $uiColors = [
            'system' => [
                'type' => 'gradient',
                'color' => '#ffffff',
                'gradient_from' => '#f8fbff',
                'gradient_to' => '#edf3fb',
                'image_path' => null,
            ],
            'header' => [
                'type' => 'gradient',
                'color' => '#eff6ff',
                'gradient_from' => '#dbeafe',
                'gradient_to' => '#bfdbfe',
                'image_path' => null,
                'text_color' => '#1e3a8a',
                'subtitle_color' => '#334155',
            ],
            'cards' => [
            'radius' => 'xl',
            'shadow' => 'soft',
            'style' => 'solid',
            'bg_type' => 'solid',
            'bg_color' => '#ffffff',
            'bg_opacity' => 0.85,
            'bg_gradient_from' => '#e0f2fe',
            'bg_gradient_to' => '#bae6fd',
        ],
        ];
        $systemCfg = $uiColors['system'] ?? [];
        $headerCfg = $uiColors['header'] ?? [];
        $headerTextColor = $headerCfg['text_color'] ?? '#1f2937';
        $headerSubtitleColor = $headerCfg['subtitle_color'] ?? '#4b5563';
        $cardsCfg = $uiColors['cards'] ?? [];
        $systemType = $systemCfg['type'] ?? 'gradient';
        $headerType = $headerCfg['type'] ?? 'solid';
        $systemImageUrl = !empty($systemCfg['image_path']) ? asset('storage/' . $systemCfg['image_path']) : null;
        $headerImageUrl = !empty($headerCfg['image_path']) ? asset('storage/' . $headerCfg['image_path']) : null;
        $menuCfg = [
            'layout' => 'sidebar',
            'bg' => '#1e3a8a',
            'text' => '#eff6ff',
            'subtext' => '#cbd5e1',
            'icon' => '#93c5fd',
            'icon_colors' => [],
            'icon_bg_colors' => [],
            'active_bg' => '#2563eb',
            'active_text' => '#ffffff',
            'item_size' => 'md',
        ];
        $menuLayout = $menuCfg['layout'] ?? 'sidebar';
        $menuIconColors = $menuCfg['icon_colors'] ?? [];
        $menuIconBgColors = $menuCfg['icon_bg_colors'] ?? [];
        $defaultIconColor = $menuCfg['icon'] ?? '#000000';
        $tablesCfg = [
            'header_size' => 'md',
            'header_bg' => '#f9fafb',
            'header_text' => '#374151',
            'border_style' => 'solid',
            'border_color' => '#e5e7eb',
            'row_lines' => 'subtle',
        ];
        $typographyCfg = [
            'size' => 'md',
            'color' => '#0f172a',
            'bold' => false,
            'font_family' => 'Inter',
        ];
        $modalsCfg = [
            'size' => 'md',
            'bg_type' => 'solid',
            'bg_color' => '#ffffff',
            'bg_opacity' => 0.95,
            'bg_gradient_from' => '#ffffff',
            'bg_gradient_to' => '#f3f4f6',
        ];
        $logosCfg = [
            'primario' => 'images/logo principal .png',
            'secundario' => 'images/LOGO-INSTITUTO-PREVENTION-WORLD.png',
        ];
        $btnsCfg = [
            'primary' => ['bg' => '#2563eb', 'text' => '#ffffff'],
            'danger' => ['bg' => '#dc2626', 'text' => '#ffffff'],
            'success' => ['bg' => '#16a34a', 'text' => '#ffffff'],
            'secondary' => ['bg' => '#f3f4f6', 'text' => '#374151'],
            'dark' => ['bg' => '#111827', 'text' => '#ffffff'],
            'login' => ['bg' => '#4f46e5', 'text' => '#ffffff'],
            'toggle_active' => ['bg' => '#16a34a', 'text' => '#ffffff'],
            'toggle_inactive' => ['bg' => '#9ca3af', 'text' => '#ffffff'],
            'view' => ['bg' => '#7c3aed', 'text' => '#ffffff'],
            'reemplazar' => ['bg' => '#2563eb', 'text' => '#ffffff'],
            'dar_baja' => ['bg' => '#dc2626', 'text' => '#ffffff'],
            'accent' => ['bg' => '#7c3aed', 'text' => '#ffffff'],
            'apartado_exportar' => ['bg' => '#2563eb', 'text' => '#ffffff'],
            'apartado_inspeccion' => ['bg' => '#2563eb', 'text' => '#ffffff'],
            'apartado_formatos' => ['bg' => '#2563eb', 'text' => '#ffffff'],
            'apartado_hoja_vida' => ['bg' => '#2563eb', 'text' => '#ffffff'],
            'apartado_empresa' => ['bg' => '#7c3aed', 'text' => '#ffffff'],
        ];
        $empresaActiva = \App\Services\EmpresaContext::empresaActiva();
        $empresaColor = $empresaActiva?->color_primario;
        $empresaColorSec1 = $empresaActiva?->color_secundario_1;
        $empresaColorSec2 = $empresaActiva?->color_secundario_2;
        $empresaColorExtra4 = $empresaActiva?->color_extra_4;
        $empresaColorExtra5 = $empresaActiva?->color_extra_5;

        $hexToRgb = static function (?string $hex): ?array {
            if (!$hex) {
                return null;
            }
            $h = strtoupper(ltrim(trim($hex), '#'));
            if (strlen($h) === 3 && preg_match('/^[0-9A-F]{3}$/', $h)) {
                $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
            }
            if (strlen($h) !== 6 || !preg_match('/^[0-9A-F]{6}$/', $h)) {
                return null;
            }
            return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
        };
        $rgbToRgba = static function (?array $rgb, float $a): string {
            if (!$rgb) {
                return 'rgba(15,23,42,' . $a . ')';
            }
            return 'rgba(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ',' . $a . ')';
        };
        $rgbToHex = static function (?array $rgb): string {
            if (!$rgb) {
                return '#0f172a';
            }
            return sprintf('#%02X%02X%02X', max(0, min(255, (int) $rgb[0])), max(0, min(255, (int) $rgb[1])), max(0, min(255, (int) $rgb[2])));
        };
        $mixRgb = static function (?array $a, ?array $b, float $ratioA = 0.5): ?array {
            if (!$a && !$b) return null;
            if (!$a) return $b;
            if (!$b) return $a;
            $r = max(0, min(1, $ratioA));
            return [
                (int) round(($a[0] * $r) + ($b[0] * (1 - $r))),
                (int) round(($a[1] * $r) + ($b[1] * (1 - $r))),
                (int) round(($a[2] * $r) + ($b[2] * (1 - $r))),
            ];
        };
        $isHarshColor = static function (?array $rgb): bool {
            if (!$rgb) return false;
            $max = max($rgb);
            $min = min($rgb);
            $sat = $max > 0 ? (($max - $min) / $max) : 0;
            $lum = ((0.2126 * $rgb[0]) + (0.7152 * $rgb[1]) + (0.0722 * $rgb[2])) / 255;
            return $sat > 0.62 && $lum > 0.52;
        };
        $normalizeAccentHex = static function (?string $hex) use ($hexToRgb, $mixRgb, $rgbToHex, $isHarshColor): ?string {
            $rgb = $hexToRgb($hex);
            if (!$rgb) return $hex;
            if (!$isHarshColor($rgb)) return $rgbToHex($rgb);
            return $rgbToHex($mixRgb($rgb, [30, 41, 59], 0.38));
        };
        $normalizeSurfaceHex = static function (?string $hex) use ($hexToRgb, $mixRgb, $rgbToHex, $isHarshColor): ?string {
            $rgb = $hexToRgb($hex);
            if (!$rgb) return $hex;
            return $rgbToHex($isHarshColor($rgb) ? $mixRgb($rgb, [15, 23, 42], 0.18) : $mixRgb($rgb, [15, 23, 42], 0.55));
        };

        $pwBrandedTheme = (bool) ($empresaActiva && $empresaColor);
        $pwParticlesColors = ['#42A0D7', '#67e8f9', '#ffffff'];
        $pwParticlesLine = '#42A0D7';
        $pwBgGradient = 'linear-gradient(165deg, #f8fbff 0%, #eef4ff 45%, #e2e8f0 100%)';

        $primaryRgb = $hexToRgb($empresaColor);
        $primaryIsHarsh = $isHarshColor($primaryRgb);
        $accentRgb = $primaryRgb ? $mixRgb($primaryRgb, [255, 255, 255], 0.74) : [59, 130, 246];
        $accentHex = $rgbToHex($accentRgb);

        // Regla global anti-neón: base oscura/neutra + color empresa solo como acento.
        $uiPrimary = $empresaColor
            ? ($primaryIsHarsh ? $rgbToHex($mixRgb($primaryRgb, [17, 24, 39], 0.16)) : ($normalizeAccentHex($empresaColor) ?: $empresaColor))
            : '#42A0D7';
        $uiSecondary1 = $empresaColorSec1
            ? ($normalizeAccentHex($empresaColorSec1) ?: $empresaColorSec1)
            : ($primaryIsHarsh ? $rgbToHex($mixRgb($primaryRgb, [107, 114, 128], 0.20)) : ($uiPrimary ?: '#67e8f9'));
        $uiSecondary2 = $empresaColorSec2
            ? ($normalizeSurfaceHex($empresaColorSec2) ?: $empresaColorSec2)
            : ($primaryIsHarsh
                ? $rgbToHex($mixRgb($primaryRgb, [15, 23, 42], 0.10))
                : ($normalizeSurfaceHex($uiPrimary) ?: '#075479'));

        if ($empresaColor) {
            $menuCfg['active_bg'] = $primaryIsHarsh ? $accentHex : $uiPrimary;
            $btnsCfg['primary']['bg'] = $primaryIsHarsh ? $accentHex : $uiPrimary;
            $btnsCfg['accent']['bg'] = $primaryIsHarsh ? $accentHex : $uiPrimary;
            $headerCfg['gradient_from'] = $uiSecondary1 ?: $uiPrimary;
            $headerCfg['gradient_to'] = $uiSecondary2 ?: $uiPrimary;
            $headerCfg['color'] = $uiPrimary;

            $sec1Rgb = $hexToRgb($empresaColorSec1);
            $sec2Rgb = $hexToRgb($empresaColorSec2);
            $primRgb = $hexToRgb($empresaColor);

            $menuCfg['bg'] = $normalizeSurfaceHex($uiSecondary2 ?: $uiPrimary) ?: '#1a3a6b';
            if ($hexToRgb($menuCfg['bg']) && (($hexToRgb($menuCfg['bg'])[0] + $hexToRgb($menuCfg['bg'])[1] + $hexToRgb($menuCfg['bg'])[2]) / 3) > 100) {
                $menuCfg['bg'] = '#1a3a6b';
            }
            $menuCfg['text'] = '#eff6ff';
            $menuCfg['subtext'] = '#cbd5e1';
            $defaultIconColor = $primaryIsHarsh ? $accentHex : ($uiSecondary1 ?: $uiPrimary);
            $headerTextColor = '#1e3a8a';
            $headerSubtitleColor = '#334155';

            $pwParticlesColors = array_values(array_unique(array_filter([
                $uiPrimary,
                $uiSecondary1 ?: null,
                $uiSecondary2 ?: null,
                '#e7e5e4',
            ])));
            $pwParticlesLine = $uiPrimary;

            $darkRgb = $sec2Rgb ?: [28, 25, 23];
            $midRgb = $hexToRgb($uiPrimary) ?: ($primRgb ?: [59, 130, 246]);
            $pwBgGradient = 'linear-gradient(165deg, '
                . $rgbToRgba([248, 251, 255], 1) . ' 0%, '
                . $rgbToRgba($midRgb, 0.08) . ' 38%, '
                . $rgbToRgba([239, 246, 255], 1) . ' 72%, '
                . $rgbToRgba([226, 232, 240], 1) . ' 100%)';

            $btnsCfg['success']['bg'] = $primaryIsHarsh ? $accentHex : $uiPrimary;
            $btnsCfg['success']['text'] = '#ffffff';
            $btnsCfg['dark']['bg'] = $uiSecondary2 ?: '#3f3f46';
            $btnsCfg['dark']['text'] = '#fafaf9';
            $btnsCfg['apartado_empresa']['bg'] = $primaryIsHarsh ? $accentHex : ($uiSecondary1 ?: $uiPrimary);
        }

        $iconAccentHex = $pwBrandedTheme ? (string) (($primaryIsHarsh ? $accentHex : null) ?: ($uiSecondary1 ?: $uiPrimary)) : '#67e8f9';
        $iconRgb = $hexToRgb($iconAccentHex) ?: [103, 232, 249];
        $iconBgSoft = $rgbToRgba($iconRgb, 0.22);
        $iconBorderSoft = $rgbToRgba($iconRgb, 0.38);
        $pendingAccessCount = 0;
        $pendingAccessRequests = collect();
        if (auth()->check()) {
            $pendingCacheKey = 'sams_pending_access_' . (int) auth()->id() . '_' . (int) ($empresaActiva?->id ?? 0);
            $pendingPayload = \Illuminate\Support\Facades\Cache::remember($pendingCacheKey, 20, function () use ($empresaActiva) {
                $pendingQ = \App\Models\User::query()
                    ->select(['id', 'name', 'last_name', 'email', 'empresa_id'])
                    ->with('empresa:id,nombre')
                    ->where('active', false)
                    ->whereNull('role_id')
                    ->whereNotNull('empresa_id');
                if ($empresaActiva) {
                    $pendingQ->where('empresa_id', $empresaActiva->id);
                }
                if (auth()->user()?->empresa_id) {
                    $pendingQ->where('empresa_id', auth()->user()->empresa_id);
                }

                return [
                    'count' => (int) (clone $pendingQ)->count(),
                    'items' => (clone $pendingQ)->latest('id')->limit(6)->get(),
                ];
            });
            $pendingAccessCount = (int) ($pendingPayload['count'] ?? 0);
            $pendingAccessRequests = collect($pendingPayload['items'] ?? []);
        }
    @endphp
    <style>
        :root {
            --sams-brand-primary: {{ $uiPrimary ?: '#42A0D7' }};
            --sams-brand-secondary-1: {{ $uiSecondary1 ?: '#67e8f9' }};
            --sams-brand-secondary-2: {{ $uiSecondary2 ?: '#075479' }};
            --sams-brand-white: {{ $empresaColorExtra4 ?: '#FFFFFF' }};
            --sams-brand-black: {{ $empresaColorExtra5 ?: '#000000' }};
            --sams-empresa-color: {{ $empresaColor ?: '#42A0D7' }};
            --sams-menu-bg: {{ $menuCfg['bg'] ?? '#1e3a8a' }};
            --sams-menu-text: {{ $menuCfg['text'] ?? '#eff6ff' }};
            --sams-menu-subtext: {{ $menuCfg['subtext'] ?? '#cbd5e1' }};
            --sams-menu-icon: {{ $defaultIconColor ?: '#93c5fd' }};
            --sams-menu-active-bg: {{ $menuCfg['active_bg'] ?? '#2563eb' }};
            --sams-menu-active-text: {{ $menuCfg['active_text'] ?? '#ffffff' }};
            --pw-bubble-fill: {{ $pwBrandedTheme ? $rgbToRgba($hexToRgb($empresaColor) ?: [59,130,246], 0.12) : 'rgba(147, 197, 253, 0.16)' }};
            --pw-bubble-border: {{ $pwBrandedTheme ? $rgbToRgba($hexToRgb($empresaColor) ?: [59,130,246], 0.4) : 'rgba(147, 197, 253, 0.45)' }};
            --pw-bubble-glow: {{ $pwBrandedTheme ? $rgbToRgba($hexToRgb($empresaColor) ?: [59,130,246], 0.35) : 'rgba(59, 130, 246, 0.46)' }};
            --sams-menu-icon-box-bg: {{ $iconBgSoft }};
            --sams-menu-icon-box-border: {{ $iconBorderSoft }};
            @foreach(['message-square','home','menu','users','shield','briefcase','layers','factory','monitor','server','book-open','archive','clipboard-check','building','settings','file-text','download','user-check','repeat','package-check','file-check-2','user','log-out'] as $ik)
            --sams-icon-{{ $ik }}: {{ $menuIconColors[$ik] ?? $iconAccentHex }};
            --sams-icon-bg-{{ $ik }}: {{ $menuIconBgColors[$ik] ?? $iconBgSoft }};
            @endforeach
            @php
                $cardsBgType = $cardsCfg['bg_type'] ?? 'solid';
                $cardsBgColor = $cardsCfg['bg_color'] ?? '#ffffff';
                $cardsBgOpacity = (float)($cardsCfg['bg_opacity'] ?? 0.85);
                $cardsBgFrom = $cardsCfg['bg_gradient_from'] ?? '#e0f2fe';
                $cardsBgTo = $cardsCfg['bg_gradient_to'] ?? '#bae6fd';
                $hex = ltrim($cardsBgColor, '#');
                if (strlen($hex) < 6) { $hex = 'ffffff'; }
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $cardsBgRgba = "rgba($r, $g, $b, $cardsBgOpacity)";
                $hexFrom = ltrim($cardsBgFrom, '#');
                $hexTo = ltrim($cardsBgTo, '#');
                if (strlen($hexFrom) < 6) { $hexFrom = 'e0f2fe'; }
                if (strlen($hexTo) < 6) { $hexTo = 'bae6fd'; }
                $r1 = hexdec(substr($hexFrom, 0, 2)); $g1 = hexdec(substr($hexFrom, 2, 2)); $b1 = hexdec(substr($hexFrom, 4, 2));
                $r2 = hexdec(substr($hexTo, 0, 2)); $g2 = hexdec(substr($hexTo, 2, 2)); $b2 = hexdec(substr($hexTo, 4, 2));
                $cardsGradientRgba = "linear-gradient(135deg, rgba($r1,$g1,$b1,$cardsBgOpacity), rgba($r2,$g2,$b2,$cardsBgOpacity))";
            @endphp
            --sams-cards-bg: {{ $cardsBgColor }};
            --sams-cards-bg-rgba: {{ $cardsBgRgba }};
            --sams-cards-opacity: {{ $cardsBgOpacity }};
            --sams-cards-gradient-from: {{ $cardsBgFrom }};
            --sams-cards-gradient-to: {{ $cardsBgTo }};
            --sams-cards-gradient-rgba: {{ $cardsGradientRgba }};
            --sams-table-header-size: {{ $tablesCfg['header_size'] ?? 'md' }};
            --sams-table-header-bg: {{ $tablesCfg['header_bg'] ?? '#f9fafb' }};
            --sams-table-header-text: {{ $tablesCfg['header_text'] ?? '#374151' }};
            --sams-table-border-style: {{ $tablesCfg['border_style'] ?? 'solid' }};
            --sams-table-border-color: {{ $tablesCfg['border_color'] ?? '#e5e7eb' }};
            --sams-table-row-lines: {{ $tablesCfg['row_lines'] ?? 'subtle' }};
            --sams-btn-primary-bg: {{ $btnsCfg['primary']['bg'] ?? '#2563eb' }};
            --sams-btn-primary-text: {{ $btnsCfg['primary']['text'] ?? '#ffffff' }};
            --sams-btn-danger-bg: {{ $btnsCfg['danger']['bg'] ?? '#dc2626' }};
            --sams-btn-danger-text: {{ $btnsCfg['danger']['text'] ?? '#ffffff' }};
            --sams-btn-success-bg: {{ $btnsCfg['success']['bg'] ?? '#16a34a' }};
            --sams-btn-success-text: {{ $btnsCfg['success']['text'] ?? '#ffffff' }};
            --sams-btn-secondary-bg: {{ $btnsCfg['secondary']['bg'] ?? '#f3f4f6' }};
            --sams-btn-secondary-text: {{ $btnsCfg['secondary']['text'] ?? '#374151' }};
            --sams-btn-dark-bg: {{ $btnsCfg['dark']['bg'] ?? '#111827' }};
            --sams-btn-dark-text: {{ $btnsCfg['dark']['text'] ?? '#ffffff' }};
            --sams-btn-login-bg: {{ $btnsCfg['login']['bg'] ?? '#4f46e5' }};
            --sams-btn-login-text: {{ $btnsCfg['login']['text'] ?? '#ffffff' }};
            --sams-btn-toggle-active-bg: {{ $btnsCfg['toggle_active']['bg'] ?? '#16a34a' }};
            --sams-btn-toggle-active-text: {{ $btnsCfg['toggle_active']['text'] ?? '#ffffff' }};
            --sams-btn-toggle-inactive-bg: {{ $btnsCfg['toggle_inactive']['bg'] ?? '#9ca3af' }};
            --sams-btn-toggle-inactive-text: {{ $btnsCfg['toggle_inactive']['text'] ?? '#ffffff' }};
            --sams-btn-view-bg: {{ $btnsCfg['view']['bg'] ?? '#7c3aed' }};
            --sams-btn-view-text: {{ $btnsCfg['view']['text'] ?? '#ffffff' }};
            --sams-btn-reemplazar-bg: {{ $btnsCfg['reemplazar']['bg'] ?? '#2563eb' }};
            --sams-btn-reemplazar-text: {{ $btnsCfg['reemplazar']['text'] ?? '#ffffff' }};
            --sams-btn-dar-baja-bg: {{ $btnsCfg['dar_baja']['bg'] ?? '#dc2626' }};
            --sams-btn-dar-baja-text: {{ $btnsCfg['dar_baja']['text'] ?? '#ffffff' }};
            --sams-btn-accent-bg: {{ $btnsCfg['accent']['bg'] ?? '#7c3aed' }};
            --sams-btn-accent-text: {{ $btnsCfg['accent']['text'] ?? '#ffffff' }};
            --sams-btn-apartado-exportar-bg: {{ $btnsCfg['apartado_exportar']['bg'] ?? '#111827' }};
            --sams-btn-apartado-exportar-text: {{ $btnsCfg['apartado_exportar']['text'] ?? '#ffffff' }};
            --sams-btn-apartado-inspeccion-bg: {{ $btnsCfg['apartado_inspeccion']['bg'] ?? '#2563eb' }};
            --sams-btn-apartado-inspeccion-text: {{ $btnsCfg['apartado_inspeccion']['text'] ?? '#ffffff' }};
            --sams-btn-apartado-formatos-bg: {{ $btnsCfg['apartado_formatos']['bg'] ?? '#2563eb' }};
            --sams-btn-apartado-formatos-text: {{ $btnsCfg['apartado_formatos']['text'] ?? '#ffffff' }};
            --sams-btn-apartado-hoja_vida-bg: {{ $btnsCfg['apartado_hoja_vida']['bg'] ?? '#2563eb' }};
            --sams-btn-apartado-hoja_vida-text: {{ $btnsCfg['apartado_hoja_vida']['text'] ?? '#ffffff' }};
            --sams-btn-apartado-empresa-bg: {{ $btnsCfg['apartado_empresa']['bg'] ?? '#7c3aed' }};
            --sams-btn-apartado-empresa-text: {{ $btnsCfg['apartado_empresa']['text'] ?? '#ffffff' }};
            --pw-bg-gradient: {{ $pwBgGradient }};
            /* Superficies blancas/azul/gris */
            --pw-surface-card: color-mix(in srgb, #ffffff 92%, var(--sams-brand-secondary-1) 8%);
            --pw-surface-card-deep: color-mix(in srgb, #ffffff 86%, var(--sams-brand-secondary-2) 14%);
            --pw-surface-gradient-from: color-mix(in srgb, #ffffff 88%, var(--sams-brand-secondary-1) 12%);
            --pw-surface-gradient-to: color-mix(in srgb, #f1f5f9 82%, var(--sams-brand-secondary-2) 18%);
            --pw-surface-table: color-mix(in srgb, #ffffff 90%, var(--sams-brand-secondary-2) 10%);
            --pw-surface-table-header: color-mix(in srgb, #eff6ff 76%, var(--sams-brand-primary) 24%);
            --pw-surface-table-row-hover: color-mix(in srgb, #f8fafc 74%, var(--sams-brand-primary) 26%);
            --pw-border-ui: color-mix(in srgb, var(--sams-brand-secondary-1) 30%, #cbd5e1 70%);
            --pw-border-ui-strong: color-mix(in srgb, var(--sams-brand-primary) 36%, #94a3b8 64%);
            --pw-modal-surface-from: color-mix(in srgb, #ffffff 90%, var(--sams-brand-secondary-1) 10%);
            --pw-modal-surface-to: color-mix(in srgb, #f1f5f9 82%, var(--sams-brand-secondary-2) 18%);
            --sams-header-title-color: {{ $headerTextColor }};
            --sams-header-subtitle-color: {{ $headerSubtitleColor }};
            --sams-sidebar-bg: #1a3a6b;
        }
    </style>
    <style>
        /* Sidebar – estilos críticos (no dependen de caché Vite) */
        aside#sidebar.pw-sidebar {
            background: var(--sams-sidebar-bg, #1a3a6b) !important;
            background-image: none !important;
        }

        aside#sidebar.pw-sidebar .sidebar-user-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 1rem !important;
            padding: 1rem !important;
            margin-top: 0.25rem !important;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.18) !important;
        }

        aside#sidebar.pw-sidebar .sidebar-user-avatar,
        aside#sidebar.pw-sidebar .sidebar-user-avatar--initials {
            width: 2.75rem !important;
            height: 2.75rem !important;
            border-radius: 9999px !important;
            flex-shrink: 0;
            object-fit: cover;
            border: 2px solid #e2e8f0 !important;
        }

        aside#sidebar.pw-sidebar .sidebar-user-avatar--initials {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, #2563eb, #1a3a6b) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
        }

        aside#sidebar.pw-sidebar .sidebar-user-name {
            color: #0f172a !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            margin: 0 !important;
        }

        aside#sidebar.pw-sidebar .sidebar-user-role {
            color: #64748b !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            margin: 0.2rem 0 0 !important;
            text-transform: capitalize;
        }

        header.glass .header-logo-wrap {
            background: rgba(255, 255, 255, 0.96) !important;
            border-radius: 0.75rem !important;
            padding: 0.35rem 0.75rem !important;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.12) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        header.glass .header-logo-img {
            height: 2.5rem !important;
            width: auto !important;
            max-width: 9rem !important;
            object-fit: contain !important;
        }

        aside#sidebar.pw-sidebar .sidebar-item {
            border: 1px solid transparent !important;
            border-radius: 0.75rem !important;
        }

        aside#sidebar.pw-sidebar .sidebar-item.sidebar-item--active {
            background: rgba(255, 255, 255, 0.16) !important;
            border-color: rgba(147, 197, 253, 0.65) !important;
            box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.2) !important;
        }

        aside#sidebar.pw-sidebar .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }

        aside#sidebar.pw-sidebar .sidebar-item:hover,
        aside#sidebar.pw-sidebar .sidebar-item:hover span,
        aside#sidebar.pw-sidebar .sidebar-item:hover p,
        aside#sidebar.pw-sidebar .sidebar-item:hover i,
        aside#sidebar.pw-sidebar .sidebar-item.sidebar-item--active,
        aside#sidebar.pw-sidebar .sidebar-item.sidebar-item--active span,
        aside#sidebar.pw-sidebar .sidebar-item.sidebar-item--active p {
            color: #ffffff !important;
        }

        aside#sidebar.pw-sidebar .sidebar-item p {
            color: rgba(255, 255, 255, 0.65) !important;
        }

        aside#sidebar.pw-sidebar .menu-icon-box {
            background: rgba(255, 255, 255, 0.14) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
        }

        header.glass #sidebarToggle {
            position: relative;
            z-index: 20;
            cursor: pointer;
            flex-shrink: 0;
        }

        @media (min-width: 768px) {
            aside#sidebar.pw-sidebar {
                overflow: hidden;
            }

            aside#sidebar.pw-sidebar.is-collapsed {
                width: 0 !important;
                min-width: 0 !important;
                max-width: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                border: 0 !important;
                opacity: 0;
                pointer-events: none;
            }
        }
    </style>

    @php
        $fontFamily = $typographyCfg['font_family'] ?? 'Inter';
        $fontUrl = match($fontFamily) {
            'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap',
            'Open Sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap',
            'Lato' => 'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap',
            'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
            'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap',
            'Source Sans 3' => 'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap',
            'Nunito' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap',
            default => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        };
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $fontUrl }}" rel="stylesheet">
    <style>
        :root {
            --sams-typography-size: {{ $typographyCfg['size'] ?? 'md' }};
            --sams-typography-color: {{ $typographyCfg['color'] ?? '#1f2937' }};
            --sams-typography-bold: {{ ($typographyCfg['bold'] ?? false) ? '600' : '400' }};
            --sams-typography-font: '{{ addslashes($fontFamily) }}', sans-serif;
            --sams-typography-menu-text: {{ $typographyCfg['menu_text_color'] ?? $menuCfg['text'] ?? '#e5f0ff' }};
            --sams-modal-size: {{ $modalsCfg['size'] ?? 'md' }};
            --sams-modal-bg-type: {{ $modalsCfg['bg_type'] ?? 'solid' }};
            --sams-modal-bg: {{ $modalsCfg['bg_color'] ?? '#ffffff' }};
            --sams-modal-opacity: {{ $modalsCfg['bg_opacity'] ?? 0.95 }};
            --sams-modal-gradient-from: {{ $modalsCfg['bg_gradient_from'] ?? '#ffffff' }};
            --sams-modal-gradient-to: {{ $modalsCfg['bg_gradient_to'] ?? '#f3f4f6' }};
        }
    </style>
    @yield('styles')
</head>
<body class="min-h-screen relative overflow-hidden bg-[#f8fbff] pw-typography {{ $pwBrandedTheme ? 'pw-theme-branded' : '' }}" data-pw-branded="{{ $pwBrandedTheme ? '1' : '0' }}" data-cards-radius="{{ $cardsCfg['radius'] ?? 'xl' }}" data-cards-shadow="{{ $cardsCfg['shadow'] ?? 'soft' }}" data-cards-style="{{ $cardsCfg['style'] ?? 'solid' }}" data-cards-bg-type="{{ $cardsBgType ?? 'solid' }}" data-menu-layout="{{ $menuLayout }}" data-menu-size="{{ $menuCfg['item_size'] ?? 'md' }}" data-table-header-size="{{ $tablesCfg['header_size'] ?? 'md' }}" data-table-border="{{ $tablesCfg['border_style'] ?? 'solid' }}" data-table-row-lines="{{ $tablesCfg['row_lines'] ?? 'subtle' }}" data-typography-size="{{ $typographyCfg['size'] ?? 'md' }}" data-modal-size="{{ $modalsCfg['size'] ?? 'md' }}" data-modal-bg-type="{{ $modalsCfg['bg_type'] ?? 'solid' }}">
    {{-- Gradiente de marca siempre presente (evita fondo negro plano si el modo ligero oculta partículas) --}}
    <div id="sams-panel-bg" class="fixed inset-0 -z-10 pointer-events-none min-h-[100dvh] sams-panel-bg" aria-hidden="true"></div>
    @unless($samsLightweightUi)
    <div id="particles-js" class="fixed inset-0 -z-10"></div>
    <div id="bubbles" class="fixed inset-0 pointer-events-none z-0"></div>
    @endunless

    <!-- Notifications Container -->
    <div id="notificationsContainer" class="fixed top-4 right-4 z-[9999] max-w-sm w-full space-y-2 pointer-events-none"></div>

    <!-- Modal de confirmación (¿Seguro que deseas...? Aceptar / Cancelar) -->
    @include('partials.confirm-modal')

    @php
        $headerStyle = '';
        if ($headerType === 'image' && $headerImageUrl) {
            $headerStyle = 'background-image: url(\'' . $headerImageUrl . '\'); background-size: cover; background-position: center; backdrop-filter: blur(10px);';
        } elseif ($headerType === 'solid') {
            $headerStyle = 'background-color: ' . ($headerCfg['color'] ?? '#ffffff') . ';';
        } else {
            $from = $headerCfg['gradient_from'] ?? '#42A0D7';
            $to = $headerCfg['gradient_to'] ?? '#075479';
            $headerStyle = 'background-image: linear-gradient(135deg, ' . $from . ', ' . $to . ');';
        }


        
    @endphp

    <div class="relative z-10 flex flex-col h-screen">
        <!-- Header siempre arriba (z alto: dropdown Solicitudes por encima de mapas/iframes del contenido) -->
        <header class="relative z-[8000] overflow-visible shadow-sm border-b {{ $pwBrandedTheme ? 'border-white/15' : 'border-cyan-300/20' }} flex-shrink-0 glass" style="{{ $headerStyle }}">
            <div class="px-4 md:px-6 py-4 overflow-visible">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($menuLayout === 'sidebar')
                        <button type="button" id="sidebarToggle" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white hover:bg-gray-50" aria-controls="sidebar" aria-expanded="false">
                            <i data-lucide="menu" class="w-5 h-5 text-gray-700"></i>
                        </button>
                        @endif

                        <button type="button" id="mobileBackBtn" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white hover:bg-gray-50" aria-label="Volver">
                            <i data-lucide="arrow-left" class="w-5 h-5 text-gray-700"></i>
                        </button>

                        <div class="header-logo-wrap">
                            <img src="{{ asset($logosCfg['primario'] ?? 'images/logo principal .png') }}" alt="SAMS" class="header-logo-img">
                        </div>

                        <div class="min-w-0">
                            <h2 class="text-xl md:text-2xl font-bold truncate sams-header-title">@yield('header-title', 'Panel de Administración')</h2>
                            <p class="text-sm md:text-base truncate sams-header-subtitle">@yield('header-subtitle', 'Sistema de Gestión SAMS')</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 md:justify-end">
                        @if($pendingAccessCount > 0)
                        {{-- pt-2 en el envoltorio evita el "hueco" entre el enlace y el panel: sin eso el hover se pierde y el clic cae en el <a> y recarga la página. --}}
                        <div class="relative z-[9000] group">
                            <a href="{{ route('users.complete', ['status' => 'pending']) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                Solicitudes
                                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-xs bg-amber-600 text-white">{{ $pendingAccessCount }}</span>
                            </a>
                            <div class="absolute right-0 top-full z-[9100] pt-2">
                            <div class="hidden w-96 max-w-[calc(100vw-2rem)] rounded-xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5 group-hover:block group-focus-within:block">
                                <div class="px-3 py-2 border-b text-xs font-semibold text-gray-600">Solicitudes pendientes</div>
                                <div class="max-h-72 overflow-y-auto">
                                    @foreach($pendingAccessRequests as $pendingUser)
                                        <div class="px-3 py-2 border-b border-gray-100">
                                            <div class="text-sm font-medium text-gray-900">{{ $pendingUser->name }} {{ $pendingUser->last_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $pendingUser->email }}</div>
                                            <div class="text-xs text-amber-700">Empresa: {{ $pendingUser->empresa->nombre ?? 'N/A' }}</div>
                                            <div class="mt-2 flex gap-2">
                                                <button type="button" onclick="openPendingApproveModal({{ $pendingUser->id }})" class="px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700">Aceptar</button>
                                                <button type="button" onclick="openPendingRejectModal({{ $pendingUser->id }})" class="px-2 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700">Rechazar</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="px-3 py-2 border-t bg-gray-50">
                                    <a href="{{ route('users.complete', ['status' => 'pending']) }}" class="text-xs text-blue-700 hover:underline">Ver todas las solicitudes</a>
                                </div>
                            </div>
                            </div>
                        </div>
                        @endif
                        @if($empresaActiva && !auth()->user()->empresa_id)
                        <form action="{{ route('empresa.salir') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg {{ $pwBrandedTheme ? 'border border-stone-500/50 bg-stone-800/80 text-stone-100 hover:bg-stone-700/90' : 'border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' }}" title="Volver a ver todos los equipos">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Salir de {{ $empresaActiva->nombre }}
                            </button>
                        </form>
                        @endif
                        @yield('header-actions')
                    </div>
                </div>
            </div>
        </header>

        @if($menuLayout === 'top')
            {{-- MenÃº horizontal debajo del encabezado --}}
            @include('partials.admin-menu-top')
        @endif

        <div class="relative z-0 flex flex-1 min-h-0">
            @if($menuLayout === 'sidebar')
            <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 hidden z-40"></div>
            @include('partials.admin-menu')
            @endif

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <main class="flex-1 overflow-y-auto p-4 md:p-6">
                    @yield('content')
                </main>
            </div>
        </div>
        <footer class="flex-shrink-0 px-4 md:px-6 py-2 border-t {{ $pwBrandedTheme ? 'border-white/10 text-stone-300/90 bg-stone-950/55' : 'border-cyan-300/20 text-cyan-100/80 bg-[#0a2d4a]/60' }} text-xs">
            <div class="flex items-center justify-between">
                <span>SAMS NEXUS Â· Prevention World</span>
                <span>{{ date('Y') }}</span>
            </div>
        </footer>
    </div>

    @unless($samsLightweightUi)
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    @endunless
    <script>window.pwFlash = @json(['success' => session('success'), 'error' => session('error'), 'warning' => session('warning')]);</script>
    @unless($samsLightweightUi)
    <script>
        (function () {
            function isLowPowerMode() {
                const isMobile = window.matchMedia('(max-width: 767px)').matches;
                const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const lowCoreDevice = (navigator.hardwareConcurrency || 8) <= 4;
                return isMobile || prefersReduced || lowCoreDevice;
            }

            function disableBgFxForMobile() {
                const particles = document.getElementById('particles-js');
                const bubbles = document.getElementById('bubbles');
                if (particles) particles.style.display = 'none';
                if (bubbles) bubbles.style.display = 'none';
                if (window.__pwBubbleTimer) {
                    clearInterval(window.__pwBubbleTimer);
                    window.__pwBubbleTimer = null;
                }
            }

            function pwInitBgFx() {
                if (window.__pwBgFxInit || typeof particlesJS === 'undefined') return;
                if (isLowPowerMode()) {
                    disableBgFxForMobile();
                    return;
                }
                window.__pwBgFxInit = true;
                particlesJS('particles-js', {
                    particles: {
                        number: { value: 42, density: { enable: true, value_area: 1100 } },
                        color: { value: @json($pwParticlesColors) },
                        shape: { type: 'circle' },
                        opacity: { value: 0.42, random: true },
                        size: { value: 3, random: true },
                        line_linked: { enable: true, distance: 120, color: @json($pwParticlesLine), opacity: 0.14, width: 1 },
                        move: { enable: true, speed: 1.1, direction: 'none', random: false, straight: false, out_mode: 'out' }
                    },
                    interactivity: { detect_on: 'canvas', events: { onhover: { enable: false, mode: 'repulse' }, onclick: { enable: false, mode: 'push' }, resize: true } },
                    retina_detect: true
                });
                window.__pwBubbleTimer = setInterval(function () {
                    const bubbles = document.getElementById('bubbles');
                    if (!bubbles) return;
                    if (bubbles.childElementCount > 28) return;
                    const bubble = document.createElement('div');
                    bubble.className = 'bubble';
                    const size = Math.random() * 48 + 14;
                    bubble.style.width = size + 'px';
                    bubble.style.height = size + 'px';
                    bubble.style.left = (Math.random() * 100) + 'vw';
                    bubble.style.animationDuration = (Math.random() * 18 + 14) + 's';
                    bubble.style.opacity = (Math.random() * 0.35 + 0.12).toString();
                    bubbles.appendChild(bubble);
                    setTimeout(function () { bubble.remove(); }, 40000);
                }, 900);
            }
            if ('requestIdleCallback' in window) {
                requestIdleCallback(function () { pwInitBgFx(); }, { timeout: 2500 });
            } else {
                setTimeout(pwInitBgFx, 120);
            }

            window.addEventListener('resize', function () {
                if (!isLowPowerMode()) return;
                disableBgFxForMobile();
            });

            window.addEventListener('pagehide', function () {
                if (window.__pwBubbleTimer) {
                    clearInterval(window.__pwBubbleTimer);
                    window.__pwBubbleTimer = null;
                }
            });
        })();
    </script>
    @endunless
    <!-- Include Admin Scripts -->
    @include('partials.admin-scripts')
    <div id="pendingApproveModal" class="fixed inset-0 hidden z-[12000] items-center justify-center p-4">
        <div class="pw-modal-content pw-modal-sm">
            <div class="pw-modal-header">
                <div class="pw-modal-header-main">
                    <div class="pw-modal-header-icon" aria-hidden="true">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="pw-modal-title">Aceptar solicitud</h3>
                        <p id="pendingApproveUserText" class="pw-modal-subtitle"></p>
                    </div>
                </div>
                <button type="button" class="pw-modal-close" onclick="closePendingApproveModal()" aria-label="Cerrar">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="pw-modal-body">
                <label>Rol</label>
                <select id="pendingApproveRole" class="w-full"></select>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="closePendingApproveModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">Cancelar</button>
                <button type="button" onclick="submitPendingApprove()" class="pw-btn-success px-4 py-2.5 rounded-lg text-sm">Aceptar</button>
            </div>
        </div>
    </div>

    <div id="pendingRejectModal" class="fixed inset-0 hidden z-[12000] items-center justify-center p-4">
        <div class="pw-modal-content pw-modal-sm">
            <div class="pw-modal-header">
                <div class="pw-modal-header-main">
                    <div class="pw-modal-header-icon" aria-hidden="true">
                        <i data-lucide="user-x" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="pw-modal-title">Rechazar solicitud</h3>
                        <p class="pw-modal-subtitle">Escribe el motivo del rechazo (obligatorio).</p>
                    </div>
                </div>
                <button type="button" class="pw-modal-close" onclick="closePendingRejectModal()" aria-label="Cerrar">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="pw-modal-body">
                <label>Motivo</label>
                <textarea id="pendingRejectReason" rows="4" class="w-full" placeholder="Motivo del rechazo"></textarea>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="closePendingRejectModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">Cancelar</button>
                <button type="button" onclick="submitPendingReject()" class="pw-btn-danger px-4 py-2.5 rounded-lg text-sm">Rechazar</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            if (window.__pwImagePickerInit) return;
            window.__pwImagePickerInit = true;

            function isImageCandidate(input) {
                if (!input || input.type !== 'file') return false;
                const accept = (input.getAttribute('accept') || '').toLowerCase();
                if (accept.includes('image')) return true;
                const hint = ((input.name || '') + ' ' + (input.id || '') + ' ' + (input.getAttribute('data-file-kind') || '')).toLowerCase();
                return /(image|imagen|foto|photo|logo|firma|signature|avatar)/.test(hint);
            }

            function openPicker(input, useCamera) {
                const prevCapture = input.getAttribute('capture');
                const prevAccept = input.getAttribute('accept');
                input.setAttribute('accept', 'image/*');

                if (useCamera) {
                    input.setAttribute('capture', 'environment');
                } else {
                    input.removeAttribute('capture');
                }

                input.click();

                setTimeout(function () {
                    if (prevAccept === null) input.removeAttribute('accept');
                    else input.setAttribute('accept', prevAccept);

                    if (prevCapture === null) input.removeAttribute('capture');
                    else input.setAttribute('capture', prevCapture);
                }, 500);
            }

            function attachImageButtons(input) {
                if (!input) return;
                if (input.dataset.cameraUiReady === '1') return;
                if (!input.dataset.cameraUiKey) input.dataset.cameraUiKey = (input.id || input.name || 'img');
                const key = input.dataset.cameraUiKey;

                const parent = input.parentElement;
                if (parent) {
                    parent.querySelectorAll('.pw-image-picker-actions').forEach(function (node) {
                        node.remove();
                    });
                }

                const wrap = document.createElement('div');
                wrap.className = 'pw-upload-box-actions pw-image-picker-actions';
                wrap.setAttribute('data-for', key);

                const btnCam = document.createElement('button');
                btnCam.type = 'button';
                btnCam.className = 'pw-upload-btn pw-upload-btn--camera';
                btnCam.textContent = 'Tomar foto';
                btnCam.addEventListener('click', function () { openPicker(input, true); });

                const btnFile = document.createElement('button');
                btnFile.type = 'button';
                btnFile.className = 'pw-upload-btn';
                btnFile.textContent = 'Subir archivo';
                btnFile.addEventListener('click', function () { openPicker(input, false); });

                const nameEl = document.createElement('span');
                nameEl.className = 'pw-upload-filename';
                nameEl.textContent = 'Ningún archivo seleccionado';

                wrap.appendChild(btnCam);
                wrap.appendChild(btnFile);

                input.classList.add('pw-file-enhanced');
                input.insertAdjacentElement('afterend', wrap);
                wrap.insertAdjacentElement('afterend', nameEl);

                input.addEventListener('change', function () {
                    nameEl.textContent = (input.files && input.files[0])
                        ? input.files[0].name
                        : 'Ningún archivo seleccionado';
                });

                input.dataset.cameraUiReady = '1';
            }

            function enableCameraOption(root) {
                const scope = root || document;
                scope.querySelectorAll('input[type="file"]').forEach(function (input) {
                    if (input.dataset.cameraUi === 'off' || input.closest('[data-camera-ui="off"]')) return;
                    if (!isImageCandidate(input)) return;

                    if (!input.getAttribute('accept')) {
                        input.setAttribute('accept', 'image/*');
                    }

                    if (input.hasAttribute('capture')) input.removeAttribute('capture');
                    attachImageButtons(input);
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                enableCameraOption(document);
                let cameraScanQueued = false;
                const observer = new MutationObserver(function (mutations) {
                    if (cameraScanQueued) return;
                    for (let i = 0; i < mutations.length; i++) {
                        const nodes = mutations[i].addedNodes;
                        for (let j = 0; j < nodes.length; j++) {
                            if (nodes[j] && nodes[j].nodeType === 1) {
                                cameraScanQueued = true;
                                requestAnimationFrame(function () {
                                    cameraScanQueued = false;
                                    enableCameraOption(document);
                                });
                                return;
                            }
                        }
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            });
        })();
    </script>
    <script>
        let __pendingUserId = null;
        function __csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }
        async function openPendingApproveModal(userId) {
            __pendingUserId = userId;
            const modal = document.getElementById('pendingApproveModal');
            const select = document.getElementById('pendingApproveRole');
            const text = document.getElementById('pendingApproveUserText');
            if (!modal || !select || !text) return;
            select.innerHTML = '<option value="">Cargando roles...</option>';
            text.textContent = 'Cargando usuario...';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const res = await fetch(`/users/${userId}/pending-meta`);
            const json = await res.json();
            if (!json?.success) {
                text.textContent = json?.message || 'No fue posible cargar datos.';
                select.innerHTML = '<option value="">Sin roles disponibles</option>';
                return;
            }
            text.textContent = `${json.user.name} (${json.user.email})`;
            select.innerHTML = '<option value="">Seleccionar rol</option>';
            (json.roles || []).forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.description ? `${r.name} - ${r.description}` : r.name;
                select.appendChild(opt);
            });
        }
        function closePendingApproveModal() {
            const modal = document.getElementById('pendingApproveModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        async function submitPendingApprove() {
            const roleId = document.getElementById('pendingApproveRole')?.value || '';
            if (!__pendingUserId || !roleId) {
                alert('Debes seleccionar un rol.');
                return;
            }
            const res = await fetch(`/users/${__pendingUserId}/approve-pending`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ role_id: roleId }),
            });
            const json = await res.json();
            if (!res.ok || !json?.success) {
                alert(json?.message || 'No se pudo aprobar la solicitud.');
                return;
            }
            location.reload();
        }
        function openPendingRejectModal(userId) {
            __pendingUserId = userId;
            const modal = document.getElementById('pendingRejectModal');
            const input = document.getElementById('pendingRejectReason');
            if (!modal || !input) return;
            input.value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closePendingRejectModal() {
            const modal = document.getElementById('pendingRejectModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        async function submitPendingReject() {
            const reason = (document.getElementById('pendingRejectReason')?.value || '').trim();
            if (!__pendingUserId) return;
            if (reason.length < 5) {
                alert('Debes escribir un motivo de rechazo.');
                return;
            }
            const res = await fetch(`/users/${__pendingUserId}/reject-pending`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ reason }),
            });
            const json = await res.json();
            if (!res.ok || !json?.success) {
                alert(json?.message || 'No se pudo rechazar la solicitud.');
                return;
            }
            location.reload();
        }
    </script>

    <!-- Additional Scripts -->
    @yield('scripts')
</body>
</html>
