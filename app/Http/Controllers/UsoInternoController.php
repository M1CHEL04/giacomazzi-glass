<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsoInternoController extends Controller
{
    public function indexCategorias()
    {
        $categorias = Categoria::withCount('productos')
            ->orderBy('nombre')
            ->paginate(12);
        return view('UsoInterno.categorias.indexCategoria', compact('categorias'));
    }

    public function createCategoria()
    {
        return view('UsoInterno.categorias.createCategoria');
    }

    public function storeCategoria(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.string' => 'El nombre de la categoria debe ser un texto.',
            'nombre.max' => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoria con ese nombre.',
        ]);

        try {

            Categoria::create([
                'nombre' => $request->nombre,
                'activo' => true,
            ]);

            return redirect()->route('categorias.index')->with('success', 'Categoría creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear categoría: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear la categoría.');
        }
    }

    public function editCategoria($id)
    {
        $categoria = Categoria::findOrFail($id);
        return view('UsoInterno.categorias.createCategoria', [
            'categoria' => $categoria,
            'isEdit' => true,
            'formAction' => route('categorias.update', $categoria),
        ]);
    }

    public function updateCategoria(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre,' . $categoria->id,
            'activo' => 'nullable|in:0,1',
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.string' => 'El nombre de la categoria debe ser un texto.',
            'nombre.max' => 'El nombre de la categoria no puede superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una categoria con ese nombre.',
        ]);

        try {
            $categoria->update([
                'nombre' => $request->nombre,
                'activo' => (bool) $request->input('activo', 0),
            ]);

            return redirect()->route('categorias.index')->with('success', 'Categoría actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar categoría: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar la categoría.');
        }
    }
}
