<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function index()
    {
        $items = MenuItem::orderBy('position')->get();
        return Inertia::render('Admin/Menu/Index', ['items' => $items]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'  => 'required|string|max:100',
            'url'    => 'required|string|max:500',
            'target' => 'required|in:_self,_blank',
        ], [
            'label.required'  => 'El nombre es obligatorio.',
            'url.required'    => 'La URL es obligatoria.',
            'target.required' => 'El destino es obligatorio.',
        ]);

        try {
            $position = MenuItem::max('position') + 1;
            MenuItem::create([...$validated, 'position' => $position, 'is_active' => true]);
            return back()->with('success', "Ítem \"{$validated['label']}\" agregado al menú.");
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo agregar el ítem.');
        }
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'label'     => 'required|string|max:100',
            'url'       => 'required|string|max:500',
            'target'    => 'required|in:_self,_blank',
            'is_active' => 'boolean',
        ]);

        try {
            $menuItem->update($validated);
            return back()->with('success', "Ítem \"{$menuItem->label}\" actualizado.");
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo actualizar el ítem.');
        }
    }

    public function destroy(MenuItem $menuItem)
    {
        try {
            $label = $menuItem->label;
            $menuItem->delete();
            return back()->with('success', "Ítem \"{$label}\" eliminado del menú.");
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo eliminar el ítem.');
        }
    }

    public function reorder(Request $request)
    {
        $request->validate(['items' => 'required|array']);
        foreach ($request->items as $index => $id) {
            MenuItem::where('id', $id)->update(['position' => $index + 1]);
        }
        return back()->with('success', 'Orden del menú actualizado.');
    }
}
