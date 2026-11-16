<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssetTemplate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'brand',
        'model',
        'category_id',
        'default_remarks',
        'unique_key',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            $template->name = self::normalizeRequired($template->name ?? '');
            $template->brand = self::normalizeNullable($template->brand);
            $template->model = self::normalizeNullable($template->model);
            $template->default_remarks = self::normalizeNullable($template->default_remarks);
            $template->unique_key = self::buildUniqueKey($template->name, $template->brand, $template->model);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'brand', 'model', 'category_id', 'default_remarks', 'active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public static function normalizeRequired(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    public static function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = trim((string) preg_replace('/\s+/', ' ', $value));

        return $clean === '' ? null : $clean;
    }

    public static function buildUniqueKey(string $name, ?string $brand = null, ?string $model = null): string
    {
        $n = mb_strtolower(self::normalizeRequired($name), 'UTF-8');
        $b = mb_strtolower(self::normalizeNullable($brand) ?? '', 'UTF-8');
        $m = mb_strtolower(self::normalizeNullable($model) ?? '', 'UTF-8');

        return "{$n}|{$b}|{$m}";
    }

    public static function findDuplicate(string $name, ?string $brand = null, ?string $model = null, ?int $ignoreId = null): ?self
    {
        $key = self::buildUniqueKey($name, $brand, $model);

        return self::query()
            ->when($ignoreId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->where('unique_key', $key)
            ->first();
    }

    /**
     * Find an active template matching an import cell value (by unique_key or normalized name).
     *
     * @param  Collection<int, self>|null  $candidates
     */
    public static function findForImport(string $raw, ?Collection $candidates = null): ?self
    {
        $clean = self::normalizeRequired($raw);
        if ($clean === '') {
            return null;
        }

        $pool = $candidates ?? self::with('category')->where('active', true)->orderBy('name')->get();
        $lowerClean = mb_strtolower($clean, 'UTF-8');

        // Support "Name | Brand | Model" format if provided
        if (str_contains($clean, '|')) {
            $parts = array_map('trim', explode('|', $clean));
            $key = self::buildUniqueKey($parts[0] ?? '', $parts[1] ?? null, $parts[2] ?? null);
            $byKey = $pool->first(fn (self $tpl) => $tpl->unique_key === $key);
            if ($byKey) {
                return $byKey;
            }
        }

        // Exact normalized name match (preferring first exact name match)
        $byName = $pool->first(fn (self $tpl) => mb_strtolower($tpl->name, 'UTF-8') === $lowerClean);
        if ($byName) {
            return $byName;
        }

        // Match combined "Name Brand Model" or "Name - Brand Model"
        return $pool->first(function (self $tpl) use ($lowerClean) {
            $combined = trim(implode(' ', array_filter([$tpl->name, $tpl->brand, $tpl->model])));

            return mb_strtolower(self::normalizeRequired($combined), 'UTF-8') === $lowerClean;
        });
    }

    /**
     * Return strictly IDs, names, and category name (never depreciation or financial fields).
     *
     * @return array<string, mixed>
     */
    public function toPickerArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'category_id' => $this->category_id ? (int) $this->category_id : null,
            'category_name' => $this->category?->name,
            'default_remarks' => $this->default_remarks,
        ];
    }
}
