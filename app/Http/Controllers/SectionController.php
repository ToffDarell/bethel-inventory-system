<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage sections.');
        }

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'code' => strtoupper(trim((string) $request->input('code', ''))),
        ]);

        $deptId = (int) $request->input('department_id');

        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')->where(fn ($q) => $q->where('department_id', $deptId)),
            ],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:6',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('sections', 'code')->where(fn ($q) => $q->where('department_id', $deptId)),
            ],
            'active' => ['nullable', 'boolean'],
        ], [
            'department_id.required' => 'Please select a parent department.',
            'department_id.exists' => 'The selected department does not exist.',
            'name.required' => 'Please enter a section name.',
            'name.unique' => 'A section with this name already exists in this department.',
            'code.required' => 'Please enter a section code.',
            'code.min' => 'Section code must be at least 2 characters.',
            'code.max' => 'Section code cannot exceed 6 characters.',
            'code.regex' => 'Section code must contain only letters and digits.',
            'code.unique' => 'A section with this code already exists in this department.',
        ]);

        $section = Section::create([
            'department_id' => (int) $validated['department_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'active' => $request->boolean('active', true),
            'code_confirmed' => true,
        ]);

        $section->load('department');

        activity()
            ->causedBy($request->user())
            ->performedOn($section)
            ->withProperties([
                'department_id' => $section->department_id,
                'name' => $section->name,
                'code' => $section->code,
            ])
            ->log("Section '{$section->name}' ({$section->code}) under {$section->department?->name} was created");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Section \"{$section->name}\" ({$section->code}) has been created.");
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage sections.');
        }

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'code' => strtoupper(trim((string) $request->input('code', ''))),
        ]);

        $deptId = (int) $request->input('department_id', $section->department_id);

        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')
                    ->where(fn ($q) => $q->where('department_id', $deptId))
                    ->ignore($section->id),
            ],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:6',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('sections', 'code')
                    ->where(fn ($q) => $q->where('department_id', $deptId))
                    ->ignore($section->id),
            ],
            'active' => ['nullable', 'boolean'],
            'code_confirmed' => ['nullable', 'boolean'],
        ], [
            'department_id.required' => 'Please select a parent department.',
            'department_id.exists' => 'The selected department does not exist.',
            'name.required' => 'Please enter a section name.',
            'name.unique' => 'A section with this name already exists in this department.',
            'code.required' => 'Please enter a section code.',
            'code.min' => 'Section code must be at least 2 characters.',
            'code.max' => 'Section code cannot exceed 6 characters.',
            'code.regex' => 'Section code must contain only letters and digits.',
            'code.unique' => 'A section with this code already exists in this department.',
        ]);

        $oldName = $section->name;
        $newName = $validated['name'];
        $newCode = strtoupper($validated['code']);

        $section->update([
            'department_id' => (int) $validated['department_id'],
            'name' => $newName,
            'code' => $newCode,
            'active' => $request->boolean('active', true),
            'code_confirmed' => $request->boolean('code_confirmed', true),
        ]);

        if ($oldName !== $newName) {
            $section->assets()->update(['section' => $newName]);
        }

        $section->load('department');

        activity()
            ->causedBy($request->user())
            ->performedOn($section)
            ->withProperties([
                'department_id' => $section->department_id,
                'name' => $section->name,
                'code' => $section->code,
                'active' => $section->active,
                'code_confirmed' => $section->code_confirmed,
            ])
            ->log("Section '{$section->name}' ({$section->code}) was updated");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Section \"{$section->name}\" ({$section->code}) has been updated.");
    }

    public function confirmCode(Request $request, Section $section): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can confirm section codes.');
        }

        $section->update(['code_confirmed' => true]);
        $section->load('department');

        activity()
            ->causedBy($request->user())
            ->performedOn($section)
            ->log("Section code for '{$section->name}' ({$section->code}) was confirmed");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Section code \"{$section->code}\" for \"{$section->name}\" confirmed.");
    }

    public function destroy(Request $request, Section $section): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage sections.');
        }

        $hasAssets = Asset::withTrashed()->where('section_id', $section->id)->exists();

        if ($hasAssets) {
            return redirect()
                ->route('settings.index', ['tab' => 'departments'])
                ->withErrors(['section' => "Cannot remove section \"{$section->name}\" because it is assigned to existing assets. You may mark it Inactive or rename it instead."]);
        }

        $section->load('department');
        $name = $section->name;
        $code = $section->code;
        $deptName = $section->department?->name ?? 'Unknown';
        $section->delete();

        activity()
            ->causedBy($request->user())
            ->log("Section '{$name}' ({$code}) under {$deptName} was removed");

        return redirect()
            ->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Section \"{$name}\" ({$code}) has been removed.");
    }
}
