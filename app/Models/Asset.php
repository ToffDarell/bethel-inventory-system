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
        'department_id',
        'section_id',
        'vendor_id',
        'asset_template_id',
        'cost',
        'useful_life_years',
        'salvage_value',
        'acquired_year_only',
        'needs_tag_reprint',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'useful_life_years' => 'integer',
            'acquired_year_only' => 'boolean',
            'needs_tag_reprint' => 'boolean',
            'department_id' => 'integer',
            'section_id' => 'integer',
            'category_id' => 'integer',
            'vendor_id' => 'integer',
            'asset_template_id' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assetTemplate(): BelongsTo
    {
        return $this->belongsTo(AssetTemplate::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function departmentRelation(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function sectionRelation(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function numberHistories(): HasMany
    {
        return $this->hasMany(AssetNumberHistory::class)->latest('changed_at')->latest('id');
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
