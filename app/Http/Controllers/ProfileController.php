<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\UsdConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $family = $request->user()->primaryFamily();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => false,
            'status' => session('status'),
            'usdRate' => $family ? (float) $family->usd_rate : null,
            'familyName' => $family?->name,
        ]);
    }

    public function updateUsdRate(Request $request, UsdConversionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'usd_rate' => ['required', 'numeric', 'min:1'],
        ], [
            'usd_rate.required' => 'Ingresa el valor del dolar.',
            'usd_rate.numeric'  => 'El valor del dolar debe ser un numero valido.',
            'usd_rate.min'      => 'El valor del dolar debe ser mayor a 0.',
        ]);

        $family = $request->user()->primaryFamily();
        if (! $family) {
            return Redirect::back()->with('error', 'No perteneces a ninguna familia todavia.');
        }

        $family->usd_rate = $validated['usd_rate'];
        $family->save();

        // Reconvert every stored ARS/USD value across the family so the whole app
        // reflects the new rate.
        $service->recalculateForFamily($family);

        return Redirect::back()->with('success', 'Cotizacion del dolar actualizada. Todos los valores se reconvirtieron.');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
