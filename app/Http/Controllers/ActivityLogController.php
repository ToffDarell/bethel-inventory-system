<?php

namespace App\Http\Controllers;

use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        $activities = Activity::with(['causer', 'subject'])
            ->latest('id')
            ->paginate(25);

        return view('settings.activity-log', [
            'activities' => $activities,
        ]);
    }
}
