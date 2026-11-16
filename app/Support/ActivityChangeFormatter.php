<?php

namespace App\Support;

use Spatie\Activitylog\Models\Activity;

class ActivityChangeFormatter
{
    /**
     * Map of model column names to clinical human-readable labels.
     *
     * @var array<string, string>
     */
    protected static array $fieldLabels = [
        'asset_condition' => 'Condition',
        'location' => 'Location',
        'department' => 'Department',
        'section' => 'Section',
        'assigned_personnel' => 'Assigned Personnel',
        'status' => 'Status',
        'brand' => 'Brand',
        'model' => 'Model',
        'serial_no' => 'Serial No.',
        'asset_name' => 'Asset Name',
        'asset_no' => 'Asset No.',
        'notes' => 'Notes',
        'useful_life_years' => 'Useful Life',
        'cost' => 'Cost',
        'salvage_value' => 'Disposal Value',
        'category_id' => 'Category',
        'acquired_year_only' => 'Year Only Convention',
    ];

    /**
     * Format before-and-after changes into readable chips.
     *
     * @return array<int, array{label: string, old: string, new: string}>
     */
    public static function format(Activity $activity, bool $isAdmin = true): array
    {
        $changes = [];
        $desc = (string) $activity->description;
        $properties = $activity->properties ? $activity->properties->toArray() : [];
        $old = $properties['old'] ?? [];
        $attributes = $properties['attributes'] ?? [];

        // 1. Standard Spatie Dirty Attributes (Old vs New)
        if (!empty($old) && !empty($attributes)) {
            foreach ($attributes as $key => $newVal) {
                if (!array_key_exists($key, $old)) {
                    continue;
                }

                $oldVal = $old[$key];
                if ($oldVal === $newVal) {
                    continue;
                }

                // Strict Financial Shield: never expose money to non-admins
                if (!$isAdmin && in_array($key, ['cost', 'salvage_value', 'current_value'], true)) {
                    continue;
                }

                $label = self::$fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));

                $changes[] = [
                    'label' => $label,
                    'old' => self::formatValue($key, $oldVal),
                    'new' => self::formatValue($key, $newVal),
                ];
            }
        }

        // 2. TransferController Location Moves
        if (!empty($properties['to_location'])) {
            $fromLoc = trim((string) ($properties['from_location'] ?? ''));
            $toLoc = trim((string) $properties['to_location']);

            $changes[] = [
                'label' => 'Location',
                'old' => $fromLoc !== '' ? $fromLoc : 'Unassigned',
                'new' => $toLoc,
            ];

            if (!empty($properties['department']) && empty($old['department'])) {
                $changes[] = [
                    'label' => 'Department',
                    'old' => '',
                    'new' => (string) $properties['department'],
                ];
            }
        }

        // 3. Setting changes logged as "X changed from 'Y' to 'Z' by ..."
        if (preg_match("/^(.*) changed from '(.*)' to '(.*)' by/i", $desc, $matches)) {
            $field = trim($matches[1]);
            $oldVal = trim($matches[2]);
            $newVal = trim($matches[3]);

            $isFinancial = str_contains(strtolower($field), 'cost') || str_contains(strtolower($field), 'salvage');
            if ($isAdmin || !$isFinancial) {
                $changes[] = [
                    'label' => $field,
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        // 4. Archive / Restore actions
        $descLower = strtolower($desc);
        if (str_contains($descLower, 'moved to archive')) {
            $changes[] = [
                'label' => 'Status',
                'old' => 'Active',
                'new' => 'Archived',
            ];
        } elseif (str_contains($descLower, 'restored from archive')) {
            $changes[] = [
                'label' => 'Status',
                'old' => 'Archived',
                'new' => 'Active',
            ];
        } elseif (str_contains($descLower, 'permanently deleted')) {
            $changes[] = [
                'label' => 'Status',
                'old' => 'Archived',
                'new' => 'Permanently Deleted',
            ];
        }

        // 5. Auth / Session actions
        if (str_contains($descLower, 'signed in')) {
            $changes[] = [
                'label' => 'Session',
                'old' => 'Offline',
                'new' => 'Signed In',
            ];
        } elseif (str_contains($descLower, 'signed out')) {
            $changes[] = [
                'label' => 'Session',
                'old' => 'Active',
                'new' => 'Signed Out',
            ];
        }

        // 6. Attributes only (initial creation or import without old values)
        if (empty($changes) && !empty($attributes)) {
            $allowedCreationKeys = ['asset_no', 'asset_name', 'brand', 'section', 'assigned_personnel', 'asset_condition', 'department', 'location', 'status'];
            if ($isAdmin) {
                $allowedCreationKeys[] = 'cost';
                $allowedCreationKeys[] = 'salvage_value';
                $allowedCreationKeys[] = 'useful_life_years';
            }

            foreach ($attributes as $key => $val) {
                if (!in_array($key, $allowedCreationKeys, true)) {
                    continue;
                }
                if (!$isAdmin && in_array($key, ['cost', 'salvage_value', 'current_value'], true)) {
                    continue;
                }
                if ($val === null || $val === '') {
                    continue;
                }
                $label = self::$fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                $changes[] = [
                    'label' => $label,
                    'old' => '',
                    'new' => self::formatValue($key, $val),
                ];
            }
        }

        return $changes;
    }

    /**
     * Format scalar values for clean clinical presentation.
     */
    protected static function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }

        if (in_array($key, ['cost', 'salvage_value', 'current_value'], true)) {
            return '₱' . number_format((float) $value, 2);
        }

        if ($key === 'useful_life_years') {
            return $value . ' ' . ($value == 1 ? 'year' : 'years');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
