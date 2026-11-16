<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'address',
        'contact_person',
        'phone',
        'is_donation',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'is_donation' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'address', 'contact_person', 'phone', 'is_donation', 'notes', 'active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public static function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $name));
    }

    public static function normalizedKey(string $name): string
    {
        return mb_strtolower(self::normalizeName($name), 'UTF-8');
    }

    public static function findByNormalizedName(string $name, bool $activeOnly = false): ?self
    {
        $key = self::normalizedKey($name);
        if ($key === '') {
            return null;
        }

        $query = self::query();
        if ($activeOnly) {
            $query->where('active', true);
        }

        return $query->get()->first(fn (self $vendor) => self::normalizedKey($vendor->name) === $key);
    }

    /**
     * Find near matches for an unknown vendor name (punctuation, spacing, substring, or close spelling).
     *
     * @param  Collection<int, self>|null  $candidates
     * @return array<int, array{id: int, name: string, active: bool}>
     */
    public static function findNearMatches(string $rawName, ?Collection $candidates = null, int $limit = 3): array
    {
        $key = self::normalizedKey($rawName);
        if ($key === '') {
            return [];
        }

        $compactInput = (string) preg_replace('/[^a-z0-9]/', '', $key);
        $pool = $candidates ?? self::orderBy('name')->get();
        $scored = [];

        foreach ($pool as $vendor) {
            $vendorKey = self::normalizedKey($vendor->name);
            if ($vendorKey === $key && $vendor->active) {
                continue;
            }

            $compactVendor = (string) preg_replace('/[^a-z0-9]/', '', $vendorKey);
            $score = 0;

            if ($vendorKey === $key && ! $vendor->active) {
                $score = 100;
            } elseif ($compactInput !== '' && $compactInput === $compactVendor) {
                $score = 100;
            } elseif (strlen($key) >= 4 && strlen($vendorKey) >= 4 && (str_contains($vendorKey, $key) || str_contains($key, $vendorKey))) {
                $score = 85;
            } elseif (strlen($compactInput) <= 255 && strlen($compactVendor) <= 255) {
                $distance = levenshtein($key, $vendorKey);
                $maxLen = max(strlen($key), strlen($vendorKey));
                if ($maxLen > 0) {
                    similar_text($key, $vendorKey, $pct);
                    if ($distance <= 3 || $pct >= 72.0) {
                        $score = (int) round($pct);
                    }
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'score' => $score,
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'active' => (bool) $vendor->active,
                ];
            }
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score'] ?: strcmp($a['name'], $b['name']));

        return array_map(
            fn (array $item) => ['id' => $item['id'], 'name' => $item['name'], 'active' => $item['active']],
            array_slice($scored, 0, $limit)
        );
    }
}
