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

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        $record = static::query()->find($key);

        if ($record === null || $record->value === null || $record->value === '') {
            return $default;
        }

        return $record->value;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value !== null ? (string) $value : null]
        );
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
