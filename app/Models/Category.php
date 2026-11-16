<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_useful_life_years',
        'default_salvage_percent',
        'disposal_reviewed',
    ];

    protected function casts(): array
    {
        return [
            'default_useful_life_years' => 'integer',
            'default_salvage_percent' => 'decimal:2',
            'disposal_reviewed' => 'boolean',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
