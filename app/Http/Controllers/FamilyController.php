<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'Ingresa un nombre para la familia.',
        ]);

        try {
            $family = Family::create($validated);
            $family->users()->attach($request->user()->id, ['role' => 'admin']);

            return redirect()->back()->with('success', 'La familia "' . $validated['name'] . '" fue creada. Comparte el codigo ' . $family->id . ' para que otros se unan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear la familia. Intenta nuevamente.');
        }
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'family_id' => 'required|exists:families,id',
        ], [
            'family_id.required' => 'Ingresa el codigo de familia.',
            'family_id.exists' => 'No se encontro una familia con ese codigo. Verifica que sea correcto.',
        ]);

        try {
            $family = Family::findOrFail($validated['family_id']);
            $family->users()->syncWithoutDetaching([
                $request->user()->id => ['role' => 'member'],
            ]);

            return redirect()->back()->with('success', 'Te uniste a la familia "' . $family->name . '".');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo unir a la familia. Verifica el codigo e intenta nuevamente.');
        }
    }
}
