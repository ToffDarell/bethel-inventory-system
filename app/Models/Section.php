<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Section extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'active',
        'code_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'active' => 'boolean',
            'code_confirmed' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('code_confirmed', true);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('code_confirmed', false);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Section {$this->name} ({$this->code}) was {$eventName}");
    }
}
