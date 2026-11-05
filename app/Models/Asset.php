<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asset extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUSES = [
        'In Use',
        'Under Repair',
        'Stored',
        'Disposed',
    ];

    public const CONDITIONS = [
        'Brand-new',
        'Refurbish',
        'Damage',
        'In Good Condition',
    ];

    protected $fillable = [
        'asset_no',
        'asset_name',
        'brand',
        'model',
        'serial_no',
        'date_acquired',
        'department',
        'section',
        'assigned_personnel',
        'location',
        'status',
        'asset_condition',
        'notes',
        'category_id',
        'cost',
        'useful_life_years',
        'salvage_value',
        'acquired_year_only',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'useful_life_years' => 'integer',
            'acquired_year_only' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class)->latest('moved_at')->latest('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                if ($eventName === 'deleted') {
                    return $this->isForceDeleting()
                        ? "Asset {$this->asset_no} ({$this->asset_name}) was permanently deleted"
                        : "Asset {$this->asset_no} ({$this->asset_name}) was moved to archive";
                }
                if ($eventName === 'restored') {
                    return "Asset {$this->asset_no} ({$this->asset_name}) was restored from archive";
                }
                return "Asset {$this->asset_no} ({$this->asset_name}) was {$eventName}";
            });
    }
}
