<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Setting;

class AssetNumberGenerator
{
    public function generate(?string $prefix = null): string
    {
        $resolvedPrefix = trim((string) ($prefix ?? Setting::get('asset_no_prefix', 'BBH')));
        if ($resolvedPrefix === '') {
            $resolvedPrefix = 'BBH';
        }

        $number = Asset::withTrashed()->count() + 1;

        do {
            $candidate = sprintf('%s-%06d', $resolvedPrefix, $number);
            $exists = Asset::withTrashed()
                ->whereRaw('LOWER(asset_no) = LOWER(?)', [$candidate])
                ->exists();
            $number++;
        } while ($exists);

        return $candidate;
    }
}
