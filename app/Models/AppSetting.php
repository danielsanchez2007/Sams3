<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getJson(string $key, array $default = []): array
    {
        $raw = static::getValue($key);
        if ($raw === null || $raw === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default;
        }

        return array_replace_recursive($default, $decoded);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (!static::canUseSettingsTable()) {
            return $default;
        }

        try {
            $row = static::query()->where('key', $key)->first();
        } catch (Throwable) {
            return $default;
        }

        if (!$row) {
            return $default;
        }

        return $row->value;
    }

    public static function setValue(string $key, mixed $value): void
    {
        if (!static::canUseSettingsTable()) {
            return;
        }

        try {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]
            );
        } catch (Throwable) {
            // Silently ignore persistence errors to avoid breaking UI rendering paths.
        }
    }

    public static function setJson(string $key, array $value): void
    {
        static::setValue($key, $value);
    }

    protected static function canUseSettingsTable(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
