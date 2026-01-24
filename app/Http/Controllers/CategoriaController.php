<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RealRashid\SweetAlert\Facades\Alert;


class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = Categoria::all();
        return view('categoria.categoria-table',compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoriaRequest $request)
    {
        // Desestructuración de la solicitud
        $data = $request->only(['nombre', 'descripcion']);

        try {
            // Crear la categoría
            Categoria::create($data);

            // Mensaje de éxito informativo
            Alert::toast("Categoría '{$data['nombre']}' creada con éxito", 'success');
        } catch (\Exception $e) {
            // Manejo de errores
            Alert::toast('Error al crear la categoría: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput();
        }

        return redirect()->route('categoria.index');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoriaRequest $request, $id)
    {
        // Desestructuración de la solicitud
        $data = $request->only(['nombre', 'descripcion']);

        // Intentar encontrar la categoría
        $categoria = Categoria::find($id);

        if (!$categoria) {
            // Manejar el caso donde la categoría no existe
            Alert::toast('Categoría no encontrada', 'error');
            return redirect()->route('categoria.index');
        }

        try {
            // Actualizar la categoría
            $categoria->update($data);

            // Mensaje de éxito informativo
            Alert::toast("Categoría '{$data['nombre']}' actualizada con éxito", 'success');
        } catch (\Exception $e) {
            // Manejo de errores
            Alert::toast('Error al actualizar la categoría: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput();
        }

        return redirect()->route('categoria.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Intentar encontrar la categoría
        $categoria = Categoria::find($id);

        if (!$categoria) {
            // Manejar el caso donde la categoría no existe
            Alert::toast('Categoría no encontrada', 'error');
            return redirect()->route('categoria.index');
        }

        try {
            // Eliminar la categoría
            $categoria->delete();

            // Mensaje de éxito informativo
            Alert::toast("Categoría '{$categoria->nombre}' eliminada con éxito", 'success');
        } catch (\Exception $e) {
            // Manejo de errores
            Alert::toast('Error al eliminar la categoría: ' . $e->getMessage(), 'error');
            return redirect()->back();
        }

        return redirect()->route('categoria.index');
    }


    public function uploadFile(Request $request)
    {
        // Validar que el archivo fue subido y es un archivo Excel
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('archivo');

        try {
            // Cargar el archivo Excel
            $spreadsheet = IOFactory::load($file->getRealPath());

            // Obtener la hoja de cálculo activa
            $sheet = $spreadsheet->getActiveSheet();

            $categoriasCreadas = 0;

            // Recorrer cada fila desde la fila 2
            foreach ($sheet->getRowIterator(2) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                // Obtener el valor de las columnas
                $categoriaNombre = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                $categoriaDescripcion = $sheet->getCell('B' . $row->getRowIndex())->getValue();

                // Verificar si la categoría ya existe
                if ($categoriaNombre && !Categoria::where('nombre', $categoriaNombre)->exists()) {
                    // Crear la categoría
                    Categoria::create(['nombre' => $categoriaNombre, 'descripcion' => $categoriaDescripcion]);
                    $categoriasCreadas++;
                }
            }

            // Mensaje de éxito
            Alert::toast("Se crearon {$categoriasCreadas} categorías exitosamente.", 'success');

        } catch (\Exception $e) {
            // Mensaje de error en caso de excepción
            Alert::toast('Error al procesar el archivo: ' . $e->getMessage(), 'error');
        }

        return redirect()->route('categoria.formUploadFile');
    }


    public function formUploadFile()
    {
        try {
            // Obtener todas las categorías desde la base de datos
            $categorias = Categoria::all(); // Considera paginación si hay muchos registros

            // Retornar la vista con las categorías
            return view('categoria.categoria-upload', compact('categorias'));

        } catch (\Exception $e) {
            // Aquí podrías manejar el error (ej. loguear el error, retornar un mensaje)
            // Por simplicidad, se puede retornar un mensaje de error genérico
            return redirect()->route('categoria.index')->with('error', 'No se pudo cargar las categorías.');
        }
    }

}
