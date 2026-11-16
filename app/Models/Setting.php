<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /** @var array<string, mixed>|null */
    protected static ?array $runtimeCache = null;

    protected static ?bool $hasSettingsTable = null;

    public static function loadAll(): array
    {
        if (static::$runtimeCache !== null) {
            return static::$runtimeCache;
        }

        if (static::$hasSettingsTable === null) {
            static::$hasSettingsTable = Schema::hasTable('settings');
        }

        if (! static::$hasSettingsTable) {
            static::$runtimeCache = [];

            return static::$runtimeCache;
        }

        static::$runtimeCache = static::query()->pluck('value', 'key')->all();

        return static::$runtimeCache;
    }

    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = null;
        static::$hasSettingsTable = null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::loadAll();

        if (! array_key_exists($key, $all) || $all[$key] === null || $all[$key] === '') {
            return $default;
        }

        return $all[$key];
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value !== null ? (string) $value : null]
        );

        if (static::$runtimeCache !== null) {
            static::$runtimeCache[$key] = $value !== null ? (string) $value : null;
        }
    }

    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();

        if (static::$runtimeCache !== null) {
            unset(static::$runtimeCache[$key]);
        }
    }

    public static function defaultBackupFolder(): string
    {
        $docs = env('NATIVEPHP_DOCUMENTS_PATH')
            ?: (getenv('USERPROFILE') ? getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'Documents' : null)
            ?: (getenv('HOME') ? getenv('HOME') . DIRECTORY_SEPARATOR . 'Documents' : null)
            ?: storage_path('app');

        return rtrim($docs, '/\\') . DIRECTORY_SEPARATOR . 'BethelBackups';
    }
}
