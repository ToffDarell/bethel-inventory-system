<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage departments.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'code' => ['required', 'string', 'min:2', 'max:6', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('departments', 'code')],
            'next_number' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Please enter a department name.',
            'name.unique' => 'A department with this name already exists.',
            'code.required' => 'Please enter a department code.',
            'code.min' => 'Department code must be at least 2 characters.',
            'code.max' => 'Department code cannot exceed 6 characters.',
            'code.regex' => 'Department code must contain only letters and digits.',
            'code.unique' => 'A department with this code already exists.',
        ]);

        $code = strtoupper(trim($validated['code']));

        $department = Department::create([
            'name' => trim($validated['name']),
            'code' => $code,
            'next_number' => isset($validated['next_number']) ? (int) $validated['next_number'] : 1,
            'active' => $request->boolean('active', true),
            'code_confirmed' => true,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($department)
            ->withProperties([
                'name' => $department->name,
                'code' => $department->code,
                'next_number' => $department->next_number,
            ])
            ->log("Department '{$department->name}' ({$department->code}) was created");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department \"{$department->name}\" ({$department->code}) has been created.");
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage departments.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'code' => ['required', 'string', 'min:2', 'max:6', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('departments', 'code')->ignore($department->id)],
            'next_number' => ['required', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'code_confirmed' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Please enter a department name.',
            'name.unique' => 'A department with this name already exists.',
            'code.required' => 'Please enter a department code.',
            'code.min' => 'Department code must be at least 2 characters.',
            'code.max' => 'Department code cannot exceed 6 characters.',
            'code.regex' => 'Department code must contain only letters and digits.',
            'code.unique' => 'A department with this code already exists.',
            'next_number.required' => 'Sequence counter is required.',
            'next_number.min' => 'Sequence counter must be at least 1.',
        ]);

        $oldName = $department->name;
        $newName = trim($validated['name']);
        $newCode = strtoupper(trim($validated['code']));

        $department->update([
            'name' => $newName,
            'code' => $newCode,
            'next_number' => (int) $validated['next_number'],
            'active' => $request->boolean('active', true),
            'code_confirmed' => $request->boolean('code_confirmed', true),
        ]);

        if ($oldName !== $newName) {
            $department->assets()->update(['department' => $newName]);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($department)
            ->withProperties([
                'name' => $department->name,
                'code' => $department->code,
                'next_number' => $department->next_number,
                'active' => $department->active,
                'code_confirmed' => $department->code_confirmed,
            ])
            ->log("Department '{$department->name}' ({$department->code}) was updated");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department \"{$department->name}\" ({$department->code}) has been updated.");
    }

    public function confirmCode(Request $request, Department $department): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can confirm department codes.');
        }

        $department->update(['code_confirmed' => true]);

        activity()
            ->causedBy($request->user())
            ->performedOn($department)
            ->log("Department code for '{$department->name}' ({$department->code}) was confirmed");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department code \"{$department->code}\" for \"{$department->name}\" confirmed.");
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage departments.');
        }

        $hasAssets = Asset::withTrashed()->where('department_id', $department->id)->exists()
            || $department->sections()->whereHas('assets', fn ($q) => $q->withTrashed())->exists();

        if ($hasAssets) {
            return redirect()
                ->route('settings.index', ['tab' => 'departments'])
                ->withErrors(['department' => "Cannot remove department \"{$department->name}\" because it is assigned to existing assets. You may mark it Inactive or rename it instead."]);
        }

        $name = $department->name;
        $code = $department->code;
        $department->delete();

        activity()
            ->causedBy($request->user())
            ->log("Department '{$name}' ({$code}) was removed");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department \"{$name}\" ({$code}) has been removed.");
    }

    public function updateNumberingSettings(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can update numbering settings.');
        }

        $validated = $request->validate([
            'reassign_number_on_dept_change' => ['nullable', 'in:0,1'],
            'include_section_in_asset_no' => ['nullable', 'in:0,1'],
            'asset_number_digits' => ['required', 'integer', 'min:3', 'max:8'],
            'asset_number_scope' => ['required', 'string', 'in:department,global'],
            'asset_no_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
        ], [
            'asset_number_digits.required' => 'Please specify the number of digits.',
            'asset_number_digits.min' => 'Number digits must be at least 3.',
            'asset_number_digits.max' => 'Number digits cannot exceed 8.',
            'asset_number_scope.in' => 'Counter scope must be per department or global.',
            'asset_no_prefix.required' => 'Prefix is required.',
            'asset_no_prefix.regex' => 'Prefix must contain only letters and digits.',
        ]);

        Setting::set('reassign_number_on_dept_change', $request->has('reassign_number_on_dept_change') ? '1' : '0');
        Setting::set('include_section_in_asset_no', $request->has('include_section_in_asset_no') ? '1' : '0');
        Setting::set('asset_number_digits', (string) $validated['asset_number_digits']);
        Setting::set('asset_number_scope', $validated['asset_number_scope']);
        Setting::set('asset_no_prefix', strtoupper(trim($validated['asset_no_prefix'])));

        activity()
            ->causedBy($request->user())
            ->log("Updated asset numbering settings (prefix: {$validated['asset_no_prefix']}, digits: {$validated['asset_number_digits']}, scope: {$validated['asset_number_scope']})");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', 'Asset numbering settings updated successfully.');
    }

    public function previewNextNumber(Request $request, Department $department, \App\Services\AssetNumberService $numberService)
    {
        $sectionInput = $request->query('section');
        $sectionId = $request->query('section_id');
        $section = null;

        if ($sectionId !== null && $sectionId !== '') {
            $section = Section::where('id', (int) $sectionId)
                ->where('department_id', $department->id)
                ->first();
        }

        $includeSection = Setting::get('include_section_in_asset_no', '1') === '1';
        $sectionConfirmed = ! $includeSection || ! $section || (bool) $section->code_confirmed;
        $canPreview = $department->code_confirmed && $sectionConfirmed;

        return response()->json([
            'next_number' => $canPreview ? $numberService->previewNextNumber($department, $section ?? $sectionInput) : null,
            'code_confirmed' => $canPreview,
            'department_id' => $department->id,
            'department_name' => $department->name,
        ]);
    }
}
