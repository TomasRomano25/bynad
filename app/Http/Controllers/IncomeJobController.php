<?php

namespace App\Http\Controllers;

use App\Models\IncomeJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncomeJobController extends Controller
{
    public function store(Request $request)
    {
        $family = $request->user()->families()->first();
        if (!$family) {
            return redirect()->back()->with('error', 'Debes pertenecer a una familia.');
        }

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('income_jobs', 'name')->where('family_id', $family->id),
            ],
            'color' => 'nullable|string|max:16',
        ], [
            'name.required' => 'Ingresa el nombre del trabajo.',
            'name.unique'   => 'Ya existe un trabajo con ese nombre.',
        ]);

        IncomeJob::create([
            'family_id' => $family->id,
            'name'      => $validated['name'],
            'color'     => $validated['color'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Trabajo "' . $validated['name'] . '" agregado.');
    }

    public function update(Request $request, IncomeJob $incomeJob)
    {
        $family = $request->user()->families()->first();
        abort_unless($family && $incomeJob->family_id === $family->id, 403);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('income_jobs', 'name')->where('family_id', $family->id)->ignore($incomeJob->id),
            ],
            'color' => 'nullable|string|max:16',
        ]);

        $oldName = $incomeJob->name;
        $incomeJob->update($validated);

        // Sync existing incomes that referenced the old name
        if ($oldName !== $validated['name']) {
            \App\Models\Income::where('job', $oldName)
                ->whereIn('user_id', $family->users()->pluck('users.id'))
                ->update(['job' => $validated['name']]);
        }

        return redirect()->back()->with('success', 'Trabajo actualizado.');
    }

    public function destroy(Request $request, IncomeJob $incomeJob)
    {
        $family = $request->user()->families()->first();
        abort_unless($family && $incomeJob->family_id === $family->id, 403);

        $name = $incomeJob->name;
        $incomeJob->delete();

        return redirect()->back()->with('success', 'Trabajo "' . $name . '" eliminado.');
    }
}
