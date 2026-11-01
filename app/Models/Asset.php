<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected $fillable = [
        'asset_no',
        'asset_name',
        'model',
        'serial_no',
        'date_acquired',
        'department',
        'location',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
        ];
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
            ->setDescriptionForEvent(fn (string $eventName) => "Asset {$this->asset_no} ({$this->asset_name}) was {$eventName}");
    }
}
