<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage vendors.');
        }
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'is_donation' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Please enter a vendor name.',
        ]);

        $normalizedName = Vendor::normalizeName($validated['name']);
        if ($normalizedName === '') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please enter a valid vendor name.'], 422);
            }

            return redirect()
                ->route('settings.index', ['tab' => 'vendors'])
                ->withInput()
                ->withErrors(['vendor_name' => 'Please enter a valid vendor name.']);
        }

        if ($existing = Vendor::findByNormalizedName($normalizedName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A vendor with this name already exists.',
                    'vendor' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'is_donation' => (bool) $existing->is_donation,
                        'active' => (bool) $existing->active,
                    ],
                ], 422);
            }

            return redirect()
                ->route('settings.index', ['tab' => 'vendors'])
                ->withInput()
                ->withErrors(['vendor_name' => 'A vendor with this name already exists.']);
        }

        $vendor = Vendor::create([
            'name' => $normalizedName,
            'address' => $this->nullableTrim($validated['address'] ?? null),
            'contact_person' => $this->nullableTrim($validated['contact_person'] ?? null),
            'phone' => $this->nullableTrim($validated['phone'] ?? null),
            'is_donation' => $request->boolean('is_donation'),
            'notes' => $this->nullableTrim($validated['notes'] ?? null),
            'active' => true,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($vendor)
            ->log("Vendor '{$vendor->name}' was created");

        if ($request->expectsJson()) {
            return response()->json([
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'is_donation' => (bool) $vendor->is_donation,
                    'active' => (bool) $vendor->active,
                ],
            ], 201);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'vendors'])
            ->with('success', "Vendor \"{$vendor->name}\" has been added.");
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'is_donation' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Please enter a vendor name.',
        ]);

        $normalizedName = Vendor::normalizeName($validated['name']);
        if ($normalizedName === '') {
            return redirect()
                ->route('settings.index', ['tab' => 'vendors'])
                ->withInput()
                ->withErrors(['vendor_name' => 'Please enter a valid vendor name.']);
        }

        $existing = Vendor::findByNormalizedName($normalizedName);
        if ($existing && $existing->id !== $vendor->id) {
            return redirect()
                ->route('settings.index', ['tab' => 'vendors'])
                ->withInput()
                ->withErrors(['vendor_name' => 'Another vendor with this name already exists.']);
        }

        $oldName = $vendor->name;
        $vendor->update([
            'name' => $normalizedName,
            'address' => $this->nullableTrim($validated['address'] ?? null),
            'contact_person' => $this->nullableTrim($validated['contact_person'] ?? null),
            'phone' => $this->nullableTrim($validated['phone'] ?? null),
            'is_donation' => $request->boolean('is_donation'),
            'notes' => $this->nullableTrim($validated['notes'] ?? null),
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($vendor)
            ->log("Vendor '{$oldName}' was updated to '{$vendor->name}'");

        return redirect()
            ->route('settings.index', ['tab' => 'vendors'])
            ->with('success', "Vendor \"{$vendor->name}\" has been updated.");
    }

    public function deactivate(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->ensureAdmin($request);

        $vendor->update(['active' => false]);

        activity()
            ->causedBy($request->user())
            ->performedOn($vendor)
            ->log("Vendor '{$vendor->name}' was deactivated");

        return redirect()
            ->route('settings.index', ['tab' => 'vendors'])
            ->with('success', "Vendor \"{$vendor->name}\" was deactivated.");
    }

    public function reactivate(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->ensureAdmin($request);

        $vendor->update(['active' => true]);

        activity()
            ->causedBy($request->user())
            ->performedOn($vendor)
            ->log("Vendor '{$vendor->name}' was reactivated");

        return redirect()
            ->route('settings.index', ['tab' => 'vendors'])
            ->with('success', "Vendor \"{$vendor->name}\" was reactivated.");
    }

    public function destroy(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->ensureAdmin($request);

        $assetCount = $vendor->assets()->withTrashed()->count();
        if ($assetCount > 0) {
            return redirect()
                ->route('settings.index', ['tab' => 'vendors'])
                ->with('error', "Cannot delete \"{$vendor->name}\" because {$assetCount} asset(s) reference it. Deactivate it instead.");
        }

        $name = $vendor->name;
        activity()
            ->causedBy($request->user())
            ->performedOn($vendor)
            ->log("Vendor '{$name}' was deleted");

        $vendor->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'vendors'])
            ->with('success', "Vendor \"{$name}\" has been deleted.");
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
