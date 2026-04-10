<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\CreditCardExpense;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditCardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $cards = CreditCard::whereIn('user_id', $familyUserIds)
            ->with(['user', 'expenses'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($card) {
                $card->used_amount = $card->expenses->sum('installment_amount');
                $card->available = $card->limit_amount - $card->used_amount;
                return $card;
            });

        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);

        return Inertia::render('CreditCards/Index', [
            'cards' => $cards,
            'familyUsers' => $familyUsers,
            'usdRate' => Setting::getUsdRate(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|in:visa,mastercard,amex,naranja,cabal,otro',
            'last_four' => 'nullable|string|max:4',
            'bank' => 'nullable|string|max:255',
            'limit_amount' => 'required|numeric|min:0',
            'closing_day' => 'nullable|integer|min:1|max:31',
            'due_day' => 'nullable|integer|min:1|max:31',
            'color' => 'nullable|string|max:7',
        ], [
            'user_id.required' => 'Selecciona el titular de la tarjeta.',
            'user_id.exists' => 'El titular seleccionado no es valido.',
            'name.required' => 'El nombre de la tarjeta es obligatorio.',
            'brand.required' => 'Selecciona la marca de la tarjeta.',
            'brand.in' => 'La marca seleccionada no es valida.',
            'limit_amount.required' => 'Ingresa el limite de la tarjeta.',
            'limit_amount.numeric' => 'El limite debe ser un numero valido.',
            'limit_amount.min' => 'El limite no puede ser negativo.',
            'closing_day.min' => 'El dia de cierre debe ser entre 1 y 31.',
            'closing_day.max' => 'El dia de cierre debe ser entre 1 y 31.',
            'due_day.min' => 'El dia de vencimiento debe ser entre 1 y 31.',
            'due_day.max' => 'El dia de vencimiento debe ser entre 1 y 31.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['limit_amount_usd'] = round($validated['limit_amount'] / $usdRate, 2);

            CreditCard::create($validated);

            return redirect()->back()->with('success', 'La tarjeta "' . $validated['name'] . '" fue creada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear la tarjeta. Verifica los datos e intenta nuevamente.');
        }
    }

    public function update(Request $request, CreditCard $creditCard)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|in:visa,mastercard,amex,naranja,cabal,otro',
            'last_four' => 'nullable|string|max:4',
            'bank' => 'nullable|string|max:255',
            'limit_amount' => 'required|numeric|min:0',
            'closing_day' => 'nullable|integer|min:1|max:31',
            'due_day' => 'nullable|integer|min:1|max:31',
            'color' => 'nullable|string|max:7',
        ], [
            'user_id.required' => 'Selecciona el titular de la tarjeta.',
            'user_id.exists' => 'El titular seleccionado no es valido.',
            'name.required' => 'El nombre de la tarjeta es obligatorio.',
            'limit_amount.required' => 'Ingresa el limite de la tarjeta.',
            'limit_amount.numeric' => 'El limite debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['limit_amount_usd'] = round($validated['limit_amount'] / $usdRate, 2);

            $creditCard->update($validated);

            return redirect()->back()->with('success', 'La tarjeta "' . $validated['name'] . '" fue actualizada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar la tarjeta. Intenta nuevamente.');
        }
    }

    public function destroy(CreditCard $creditCard)
    {
        try {
            $name = $creditCard->name;
            $creditCard->delete();
            return redirect()->back()->with('success', 'La tarjeta "' . $name . '" fue eliminada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar la tarjeta. Puede tener gastos asociados.');
        }
    }

    public function storeExpense(Request $request, CreditCard $creditCard)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'total_installments' => 'required|integer|min:1',
            'current_installment' => 'required|integer|min:1',
            'purchase_date' => 'required|date',
            'category' => 'nullable|string|max:255',
        ], [
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required' => 'Ingresa el monto del gasto.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
            'amount.min' => 'El monto no puede ser negativo.',
            'total_installments.required' => 'Ingresa la cantidad de cuotas.',
            'total_installments.min' => 'Debe haber al menos 1 cuota.',
            'current_installment.required' => 'Ingresa la cuota actual.',
            'purchase_date.required' => 'Selecciona la fecha de compra.',
            'purchase_date.date' => 'La fecha de compra no es valida.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['credit_card_id'] = $creditCard->id;
            $validated['user_id'] = $request->user()->id;
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);
            $validated['installment_amount'] = round($validated['amount'] / $validated['total_installments'], 2);

            CreditCardExpense::create($validated);

            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue agregado a la tarjeta.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar el gasto en la tarjeta. Intenta nuevamente.');
        }
    }

    public function updateExpense(Request $request, CreditCardExpense $expense)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'total_installments' => 'required|integer|min:1',
            'current_installment' => 'required|integer|min:1',
            'purchase_date' => 'required|date',
            'category' => 'nullable|string|max:255',
        ], [
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required' => 'Ingresa el monto del gasto.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);
            $validated['installment_amount'] = round($validated['amount'] / $validated['total_installments'], 2);

            $expense->update($validated);

            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el gasto. Intenta nuevamente.');
        }
    }

    public function destroyExpense(CreditCardExpense $expense)
    {
        try {
            $desc = $expense->description;
            $expense->delete();
            return redirect()->back()->with('success', 'El gasto "' . $desc . '" fue eliminado de la tarjeta.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el gasto. Intenta nuevamente.');
        }
    }
}
