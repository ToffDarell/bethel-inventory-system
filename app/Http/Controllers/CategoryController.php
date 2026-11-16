<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (strtolower(trim((string) $request->input('name', ''))) === 'uncategorized') {
            return redirect()
                ->to(route('settings.index') . '#categories')
                ->withErrors(['name' => 'Uncategorized is reserved as a system placeholder.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
            'default_useful_life_years' => ['required', 'integer', 'min:1', 'max:100'],
            'default_salvage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'name.required' => 'Please enter a category name.',
            'name.unique' => 'A category with this name already exists.',
            'default_useful_life_years.required' => 'Please enter the default useful life in years.',
            'default_useful_life_years.integer' => 'Default useful life must be a whole number of years.',
            'default_useful_life_years.min' => 'Default useful life must be at least 1 year.',
            'default_useful_life_years.max' => 'Default useful life cannot exceed 100 years.',
            'default_salvage_percent.numeric' => 'Default disposal percent must be a valid number.',
            'default_salvage_percent.min' => 'Default disposal percent cannot be negative.',
            'default_salvage_percent.max' => 'Default disposal percent cannot exceed 100%.',
        ]);

        $category = Category::create([
            'name' => trim($validated['name']),
            'default_useful_life_years' => ! empty($validated['default_useful_life_years']) ? (int) $validated['default_useful_life_years'] : null,
            'default_salvage_percent' => ($validated['default_salvage_percent'] !== null && $validated['default_salvage_percent'] !== '') ? (float) $validated['default_salvage_percent'] : null,
        ]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->performedOn($category)
                ->withProperties([
                    'name' => $category->name,
                    'useful_life_years' => $category->default_useful_life_years,
                    'disposal_percent' => $category->default_salvage_percent,
                ])
                ->log("Category '{$category->name}' was created (useful life: {$category->default_useful_life_years} yrs, disposal: {$category->default_salvage_percent}%)");
        }

        return redirect()
            ->to(route('settings.index') . '#categories')
            ->with('success', "Category \"{$category->name}\" has been created.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        if (strtolower($category->name) === 'uncategorized' || strtolower(trim((string) $request->input('name', ''))) === 'uncategorized') {
            return redirect()
                ->to(route('settings.index') . '#categories')
                ->withErrors(['name' => 'The Uncategorized placeholder cannot be edited or renamed.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'default_useful_life_years' => ['required', 'integer', 'min:1', 'max:100'],
            'default_salvage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'name.required' => 'Please enter a category name.',
            'name.unique' => 'A category with this name already exists.',
            'default_useful_life_years.required' => 'Please enter the useful life in years.',
            'default_useful_life_years.integer' => 'Useful life must be a whole number of years.',
            'default_useful_life_years.min' => 'Useful life must be at least 1 year.',
            'default_useful_life_years.max' => 'Useful life cannot exceed 100 years.',
            'default_salvage_percent.numeric' => 'Disposal percent must be a valid number.',
            'default_salvage_percent.min' => 'Disposal percent cannot be negative.',
            'default_salvage_percent.max' => 'Disposal percent cannot exceed 100%.',
        ]);

        $oldName = $category->name;
        $oldLife = $category->default_useful_life_years;
        $oldDisposal = $category->default_salvage_percent;

        $newLife = ! empty($validated['default_useful_life_years']) ? (int) $validated['default_useful_life_years'] : null;
        $newDisposal = ($validated['default_salvage_percent'] !== null && $validated['default_salvage_percent'] !== '') ? (float) $validated['default_salvage_percent'] : null;

        $affectedCount = Asset::where('category_id', $category->id)->count();

        $category->update([
            'name' => trim($validated['name']),
            'default_useful_life_years' => $newLife,
            'default_salvage_percent' => $newDisposal,
            'disposal_reviewed' => true,
        ]);

        if (function_exists('activity')) {
            $changes = [];
            if ($oldName !== $category->name) {
                $changes[] = "name from '{$oldName}' to '{$category->name}'";
            }
            if ((int) $oldLife !== $newLife) {
                $changes[] = "useful life from {$oldLife} to {$newLife} years";
            }
            if ((float) $oldDisposal !== $newDisposal) {
                $changes[] = "disposal percent from {$oldDisposal}% to {$newDisposal}%";
            }
            $changeDesc = ! empty($changes) ? implode(', ', $changes) : 'details updated';

            activity()
                ->causedBy($request->user())
                ->performedOn($category)
                ->withProperties([
                    'old' => [
                        'name' => $oldName,
                        'useful_life_years' => $oldLife,
                        'disposal_percent' => $oldDisposal,
                    ],
                    'new' => [
                        'name' => $category->name,
                        'useful_life_years' => $newLife,
                        'disposal_percent' => $newDisposal,
                    ],
                    'affected_assets_count' => $affectedCount,
                ])
                ->log("Category '{$category->name}' depreciation rules updated ({$changeDesc}, affecting {$affectedCount} assets)");
        }

        return redirect()
            ->to(route('settings.index') . '#categories')
            ->with('success', "Category \"{$category->name}\" has been updated.");
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if (strtolower($category->name) === 'uncategorized') {
            return redirect()
                ->to(route('settings.index') . '#categories')
                ->withErrors(['category' => 'The Uncategorized placeholder cannot be deleted.']);
        }

        $isUsed = Asset::withTrashed()->where('category_id', $category->id)->exists();

        if ($isUsed) {
            return redirect()
                ->to(route('settings.index') . '#categories')
                ->withErrors(['category' => "Cannot delete category \"{$category->name}\" because it is assigned to existing assets. You may rename it instead."]);
        }

        $name = $category->name;
        $category->delete();

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->log("Category '{$name}' was deleted");
        }

        return redirect()
            ->to(route('settings.index') . '#categories')
            ->with('success', "Category \"{$name}\" has been deleted.");
    }

    public function markReviewed(Request $request, Category $category): RedirectResponse
    {
        $category->update(['disposal_reviewed' => true]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->performedOn($category)
                ->withProperties([
                    'name' => $category->name,
                    'disposal_percent' => $category->default_salvage_percent,
                ])
                ->log("Category '{$category->name}' 0% disposal confirmed as reviewed");
        }

        return redirect()
            ->to(route('settings.index') . '#categories')
            ->with('success', "Category \"{$category->name}\" 0% disposal confirmed.");
    }
}
