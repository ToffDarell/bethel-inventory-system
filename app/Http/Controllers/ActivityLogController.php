<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = Activity::with(['causer', 'subject'])->latest('id');

        match ($filter) {
            'archive', 'archived' => $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('description', 'like', '%archive%')
                        ->orWhere('description', 'like', '%permanently deleted%')
                        ->orWhere('event', 'deleted')
                        ->orWhere('event', 'restored');
                })->where('description', 'not like', '%backup%')
                  ->where('description', 'not like', '%database%');
            }),
            'location' => $query->where(function ($q) {
                $q->where('description', 'like', '%Moved asset%')
                  ->orWhere('description', 'like', '%location%')
                  ->orWhere('properties', 'like', '%from_location%')
                  ->orWhere('properties', 'like', '%to_location%')
                  ->orWhere('properties', 'like', '%"location"%');
            }),
            'depreciation' => $query->where(function ($q) {
                $q->where('description', 'like', '%depreciation%')
                  ->orWhere('description', 'like', '%salvage%')
                  ->orWhere('description', 'like', '%useful life%')
                  ->orWhere('properties', 'like', '%useful_life_years%')
                  ->orWhere('properties', 'like', '%salvage_value%')
                  ->orWhere('properties', 'like', '%depreciation_start_convention%');
            }),
            'backup' => $query->where(function ($q) {
                $q->where('description', 'like', '%backup%')
                  ->orWhere('description', 'like', '%database%')
                  ->orWhere('properties', 'like', '%backup_file%')
                  ->orWhere('properties', 'like', '%restored_from%');
            }),
            'login' => $query->where(function ($q) {
                $q->where('description', 'like', '%signed in%')
                  ->orWhere('description', 'like', '%signed out%')
                  ->orWhere('description', 'like', '%password%')
                  ->orWhere('description', 'like', '%login%');
            }),
            default => null,
        };

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                  ->orWhere('properties', 'like', $like);
            });
        }

        $activities = $query->paginate(25)->withQueryString();

        return view('settings.activity-log', [
            'activities' => $activities,
            'currentFilter' => $filter,
            'search' => $search,
        ]);
    }
}
