<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'period' => 'required|in:mensual,semanal,anual',
            'color' => 'nullable|string|max:7',
        ], [
            'name.required' => 'El nombre del presupuesto es obligatorio.',
            'amount.required' => 'Ingresa el monto del presupuesto.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
            'amount.min' => 'El monto no puede ser negativo.',
            'period.required' => 'Selecciona el periodo del presupuesto.',
            'period.in' => 'El periodo seleccionado no es valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['user_id'] = $request->user()->id;
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);

            Budget::create($validated);

            return redirect()->back()->with('success', 'El presupuesto "' . $validated['name'] . '" fue creado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear el presupuesto. Intenta nuevamente.');
        }
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'period' => 'required|in:mensual,semanal,anual',
            'color' => 'nullable|string|max:7',
        ], [
            'name.required' => 'El nombre del presupuesto es obligatorio.',
            'amount.required' => 'Ingresa el monto del presupuesto.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);

            $budget->update($validated);

            return redirect()->back()->with('success', 'El presupuesto "' . $validated['name'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el presupuesto. Intenta nuevamente.');
        }
    }

    public function destroy(Budget $budget)
    {
        try {
            $name = $budget->name;
            $budget->delete();
            return redirect()->back()->with('success', 'El presupuesto "' . $name . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el presupuesto. Puede tener gastos asociados.');
        }
    }
}
