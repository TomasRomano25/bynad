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
        $usdRate = Setting::getUsdRate();

        $cards = CreditCard::whereIn('user_id', $familyUserIds)
            ->with(['user', 'expenses'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($card) use ($usdRate) {
                // used_amount siempre en ARS para comparar con el límite
                $card->used_amount = $card->expenses->sum(function ($e) use ($usdRate) {
                    return ($e->currency ?? 'ARS') === 'USD'
                        ? $e->installment_amount * $usdRate
                        : $e->installment_amount;
                });
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

    public function show(Request $request, CreditCard $creditCard)
    {
        $usdRate = Setting::getUsdRate();

        $creditCard->load(['user', 'expenses' => function ($q) {
            $q->orderBy('purchase_date', 'desc');
        }]);

        $expenses = $creditCard->expenses->map(function ($e) use ($usdRate) {
            $paidInstallments     = max(0, $e->current_installment - 1);
            $remainingInstallments = $e->total_installments - $paidInstallments;
            $amountPaid           = round($paidInstallments * $e->installment_amount, 2);
            $amountRemaining      = round($remainingInstallments * $e->installment_amount, 2);
            $percentPaid          = $e->total_installments > 0
                ? round($paidInstallments / $e->total_installments * 100, 1)
                : 0;

            $amountArs = ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate : $e->amount;
            $installmentArs = ($e->currency ?? 'ARS') === 'USD' ? $e->installment_amount * $usdRate : $e->installment_amount;

            return array_merge($e->toArray(), [
                'paid_installments'      => $paidInstallments,
                'remaining_installments' => $remainingInstallments,
                'amount_paid'            => $amountPaid,
                'amount_remaining'       => $amountRemaining,
                'percent_paid'           => $percentPaid,
                'amount_ars'             => round($amountArs, 2),
                'installment_ars'        => round($installmentArs, 2),
            ]);
        });

        $usedAmount = $creditCard->expenses->sum(function ($e) use ($usdRate) {
            return ($e->currency ?? 'ARS') === 'USD'
                ? $e->installment_amount * $usdRate
                : $e->installment_amount;
        });

        return Inertia::render('CreditCards/Show', [
            'card'       => array_merge($creditCard->toArray(), [
                'used_amount' => round($usedAmount, 2),
                'available'   => round($creditCard->limit_amount - $usedAmount, 2),
            ]),
            'expenses'   => $expenses,
            'usdRate'    => $usdRate,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'name'         => 'required|string|max:255',
            'brand'        => 'required|in:visa,mastercard,amex,naranja,cabal,otro',
            'last_four'    => 'nullable|string|max:4',
            'bank'         => 'nullable|string|max:255',
            'limit_amount' => 'required|numeric|min:0',
            'closing_day'  => 'nullable|integer|min:1|max:31',
            'due_day'      => 'nullable|integer|min:1|max:31',
            'color'        => 'nullable|string|max:7',
        ], [
            'user_id.required'      => 'Selecciona el titular de la tarjeta.',
            'name.required'         => 'El nombre de la tarjeta es obligatorio.',
            'brand.required'        => 'Selecciona la marca de la tarjeta.',
            'limit_amount.required' => 'Ingresa el limite de la tarjeta.',
            'limit_amount.numeric'  => 'El limite debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['limit_amount_usd'] = round($validated['limit_amount'] / $usdRate, 2);
            CreditCard::create($validated);
            return redirect()->back()->with('success', 'La tarjeta "' . $validated['name'] . '" fue creada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear la tarjeta.');
        }
    }

    public function update(Request $request, CreditCard $creditCard)
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'name'         => 'required|string|max:255',
            'brand'        => 'required|in:visa,mastercard,amex,naranja,cabal,otro',
            'last_four'    => 'nullable|string|max:4',
            'bank'         => 'nullable|string|max:255',
            'limit_amount' => 'required|numeric|min:0',
            'closing_day'  => 'nullable|integer|min:1|max:31',
            'due_day'      => 'nullable|integer|min:1|max:31',
            'color'        => 'nullable|string|max:7',
        ], [
            'user_id.required'      => 'Selecciona el titular de la tarjeta.',
            'name.required'         => 'El nombre de la tarjeta es obligatorio.',
            'limit_amount.required' => 'Ingresa el limite de la tarjeta.',
            'limit_amount.numeric'  => 'El limite debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['limit_amount_usd'] = round($validated['limit_amount'] / $usdRate, 2);
            $creditCard->update($validated);
            return redirect()->back()->with('success', 'La tarjeta "' . $validated['name'] . '" fue actualizada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar la tarjeta.');
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
            'description'         => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0',
            'currency'            => 'required|in:ARS,USD',
            'total_installments'  => 'required|integer|min:1',
            'current_installment' => 'required|integer|min:1',
            'purchase_date'       => 'required|date',
            'category'            => 'nullable|string|max:255',
        ], [
            'description.required'        => 'Ingresa una descripcion del gasto.',
            'amount.required'             => 'Ingresa el monto del gasto.',
            'amount.numeric'              => 'El monto debe ser un numero valido.',
            'currency.required'           => 'Selecciona la moneda del gasto.',
            'total_installments.required' => 'Ingresa la cantidad de cuotas.',
            'current_installment.required'=> 'Ingresa la cuota actual.',
            'purchase_date.required'      => 'Selecciona la fecha de compra.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['credit_card_id'] = $creditCard->id;
            $validated['user_id'] = $request->user()->id;
            $validated['installment_amount'] = round($validated['amount'] / $validated['total_installments'], 2);

            if ($validated['currency'] === 'USD') {
                $validated['amount_usd'] = $validated['amount'];
            } else {
                $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);
            }

            CreditCardExpense::create($validated);
            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue agregado a la tarjeta.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar el gasto en la tarjeta.');
        }
    }

    public function updateExpense(Request $request, CreditCardExpense $expense)
    {
        $validated = $request->validate([
            'description'         => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0',
            'currency'            => 'required|in:ARS,USD',
            'total_installments'  => 'required|integer|min:1',
            'current_installment' => 'required|integer|min:1',
            'purchase_date'       => 'required|date',
            'category'            => 'nullable|string|max:255',
        ], [
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required'      => 'Ingresa el monto del gasto.',
            'currency.required'    => 'Selecciona la moneda del gasto.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['installment_amount'] = round($validated['amount'] / $validated['total_installments'], 2);
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            $expense->update($validated);
            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el gasto.');
        }
    }

    public function destroyExpense(CreditCardExpense $expense)
    {
        try {
            $desc = $expense->description;
            $expense->delete();
            return redirect()->back()->with('success', 'El gasto "' . $desc . '" fue eliminado de la tarjeta.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el gasto.');
        }
    }
}
