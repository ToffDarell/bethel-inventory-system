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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
            'default_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'default_salvage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'name.required' => 'Please enter a category name.',
            'name.unique' => 'A category with this name already exists.',
            'default_useful_life_years.integer' => 'Default useful life must be a whole number of years.',
            'default_salvage_percent.numeric' => 'Default salvage percent must be a valid number.',
        ]);

        $category = Category::create([
            'name' => trim($validated['name']),
            'default_useful_life_years' => ! empty($validated['default_useful_life_years']) ? (int) $validated['default_useful_life_years'] : null,
            'default_salvage_percent' => isset($validated['default_salvage_percent']) && $validated['default_salvage_percent'] !== '' ? (float) $validated['default_salvage_percent'] : 0.00,
        ]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->performedOn($category)
                ->log("Category '{$category->name}' was created");
        }

        return redirect()
            ->to(route('settings.index') . '#categories')
            ->with('success', "Category \"{$category->name}\" has been created.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'default_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'default_salvage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'name.required' => 'Please enter a category name.',
            'name.unique' => 'A category with this name already exists.',
            'default_useful_life_years.integer' => 'Default useful life must be a whole number of years.',
            'default_salvage_percent.numeric' => 'Default salvage percent must be a valid number.',
        ]);

        $oldName = $category->name;
        $category->update([
            'name' => trim($validated['name']),
            'default_useful_life_years' => ! empty($validated['default_useful_life_years']) ? (int) $validated['default_useful_life_years'] : null,
            'default_salvage_percent' => isset($validated['default_salvage_percent']) && $validated['default_salvage_percent'] !== '' ? (float) $validated['default_salvage_percent'] : 0.00,
        ]);

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->performedOn($category)
                ->log("Category '{$oldName}' was updated to '{$category->name}'");
        }

        return redirect()
            ->to(route('settings.index') . '#categories')
            ->with('success', "Category \"{$category->name}\" has been updated.");
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
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
}
