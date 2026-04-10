<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use App\Models\SupermarketProduct;
use App\Models\SupermarketPurchase;
use App\Models\SupermarketPurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupermarketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $purchases = SupermarketPurchase::whereIn('user_id', $familyUserIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['user', 'account', 'items.product'])
            ->orderBy('date', 'desc')
            ->get();

        $products = SupermarketProduct::orderBy('category')->orderBy('name')->get();
        $accounts = Account::whereIn('user_id', $familyUserIds)->get();

        $totalMonth = $purchases->sum('total');
        $usdRate = Setting::getUsdRate();

        return Inertia::render('Supermarket/Index', [
            'purchases' => $purchases,
            'products' => $products,
            'accounts' => $accounts,
            'filters' => ['month' => (int) $month, 'year' => (int) $year],
            'totalMonth' => round($totalMonth, 2),
            'totalMonthUsd' => round($totalMonth / $usdRate, 2),
            'usdRate' => $usdRate,
        ]);
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'date' => 'required|date',
            'store' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.supermarket_product_id' => 'nullable|exists:supermarket_products,id',
            'items.*.custom_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.is_necessary' => 'boolean',
        ], [
            'date.required' => 'Selecciona la fecha de la compra.',
            'date.date' => 'La fecha ingresada no es valida.',
            'items.required' => 'Agrega al menos un producto a la compra.',
            'items.min' => 'Agrega al menos un producto a la compra.',
            'items.*.quantity.required' => 'Ingresa la cantidad del producto.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.price.required' => 'Ingresa el precio del producto.',
            'items.*.price.numeric' => 'El precio debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();

            $purchase = DB::transaction(function () use ($request, $validated, $usdRate) {
                $purchase = SupermarketPurchase::create([
                    'user_id' => $request->user()->id,
                    'account_id' => $validated['account_id'],
                    'date' => $validated['date'],
                    'store' => $validated['store'],
                    'total' => 0,
                    'total_usd' => 0,
                ]);

                $total = 0;
                foreach ($validated['items'] as $item) {
                    $itemTotal = $item['price'] * $item['quantity'];
                    $total += $itemTotal;

                    SupermarketPurchaseItem::create([
                        'supermarket_purchase_id' => $purchase->id,
                        'supermarket_product_id' => $item['supermarket_product_id'] ?? null,
                        'custom_name' => $item['custom_name'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'price_usd' => round($item['price'] / $usdRate, 2),
                        'is_necessary' => $item['is_necessary'] ?? true,
                    ]);
                }

                $purchase->update([
                    'total' => $total,
                    'total_usd' => round($total / $usdRate, 2),
                ]);

                return $purchase;
            });

            $itemCount = count($validated['items']);
            return redirect()->back()->with('success', 'Compra registrada con ' . $itemCount . ' producto' . ($itemCount > 1 ? 's' : '') . '.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar la compra. Verifica los datos e intenta nuevamente.');
        }
    }

    public function destroyPurchase(SupermarketPurchase $purchase)
    {
        try {
            $purchase->delete();
            return redirect()->back()->with('success', 'La compra fue eliminada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar la compra. Intenta nuevamente.');
        }
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'default_price' => 'required|numeric|min:0',
            'is_necessary' => 'boolean',
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'category.required' => 'Selecciona una categoria para el producto.',
            'default_price.required' => 'Ingresa el precio del producto.',
            'default_price.numeric' => 'El precio debe ser un numero valido.',
            'default_price.min' => 'El precio no puede ser negativo.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['default_price_usd'] = round($validated['default_price'] / $usdRate, 2);

            SupermarketProduct::create($validated);

            return redirect()->back()->with('success', 'El producto "' . $validated['name'] . '" fue agregado al catalogo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo agregar el producto. Intenta nuevamente.');
        }
    }

    public function updateProduct(Request $request, SupermarketProduct $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'default_price' => 'required|numeric|min:0',
            'is_necessary' => 'boolean',
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'default_price.required' => 'Ingresa el precio del producto.',
            'default_price.numeric' => 'El precio debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['default_price_usd'] = round($validated['default_price'] / $usdRate, 2);

            $product->update($validated);

            return redirect()->back()->with('success', 'El producto "' . $validated['name'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el producto. Intenta nuevamente.');
        }
    }

    public function destroyProduct(SupermarketProduct $product)
    {
        try {
            $name = $product->name;
            $product->delete();
            return redirect()->back()->with('success', 'El producto "' . $name . '" fue eliminado del catalogo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el producto. Puede estar asociado a compras existentes.');
        }
    }
}
