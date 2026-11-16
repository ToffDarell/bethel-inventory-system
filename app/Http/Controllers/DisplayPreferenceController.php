<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisplayPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'text_size' => ['required', 'string', Rule::in(User::TEXT_SIZES)],
            'density' => ['required', 'string', Rule::in(User::DENSITIES)],
            'theme' => ['required', 'string', Rule::in(User::THEMES)],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->update([
            'text_size' => $validated['text_size'],
            'density' => $validated['density'],
            'theme' => $validated['theme'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'text_size' => $user->effectiveTextSize(),
                'density' => $user->effectiveDensity(),
                'theme' => $user->effectiveTheme(),
                'message' => 'Display settings saved.',
            ]);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'display'])
            ->with('success', 'Display settings saved.');
    }
}
