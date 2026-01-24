<?php

namespace App\Http\Controllers;

use App\Models\Garante;
use App\Http\Requests\StoreGaranteRequest;
use App\Http\Requests\UpdateGaranteRequest;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GaranteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $garantes = Garante::all();
        return view('garante.index',compact('garantes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function buscarByIdentificacion(Request $request)
    {
        $identificacion = $request->get('identificacion');
        $garante = Garante::where('identificacion',$identificacion)->first();
        return response()->json([
            'garante' => $garante
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGaranteRequest $request)
    {
        // dd($request);
        $nombre = $request->nombre;
        $apellido = $request->apellido;
        $identificacion = $request->identificacion;
        $telefono = $request->telefono;
        $direccion = $request->direccion;

        $garante = Garante::where('identificacion',$identificacion)->first();
        if($garante){
            Alert::toast("Ya se encuentra un garante registrado con la identificación ".$identificacion, 'warning');
            return redirect()->back();
        }

        // dd($nombre, $apellido, $identificacion, $telefono, $direccion);

        Garante::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'identificacion' => $identificacion,
            'telefono' => $telefono,
            'direccion' => $direccion,
        ]);

        Alert::toast("Garante registrado con exito", 'success');
        return redirect()->back();
    }

    public function formUploadFile()
    {
        return view('garante.garante-upload');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGaranteRequest $request, $id)
    {
        $garante = Garante::find($id);
        $garante->update([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'direccion' => $request->direccion,
            'identificacion' => $request->identificacion,
            'telefono' => $request->telefono
        ]);

        Alert::toast("Garante actualizado con exito", 'success');
        return redirect()->route('garante.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $garante = Garante::find($id);
        // dd($garante);
        $garante->delete();
        Alert::toast("Garante eliminado con exito", 'success');
        return redirect()->back();
    }

    public function uploadFile(Request $request)
    {
        if (!$request->hasFile('archivo'))
        {
            Alert::toast('Por favor, sube un archivo.', 'error');
            return redirect()->back();
        }

        $file = $request->file('archivo');

        try {
            // Cargar el archivo Excel
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Exception $e) {
            Alert::toast('Error al cargar el archivo Excel: ' . $e->getMessage(), 'error');
            return redirect()->back();
        }

        // Obtener la hoja activa
        $sheet = $spreadsheet->getActiveSheet();
        DB::beginTransaction(); // Iniciar transacción

        try {
            // Procesar filas desde la fila 2
            foreach ($sheet->getRowIterator(2) as $row) {
                $identificacion = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                $nombres = $sheet->getCell('B' . $row->getRowIndex())->getValue();
                $apellidos = $sheet->getCell('C' . $row->getRowIndex())->getValue();
                $telefono = $sheet->getCell('D' . $row->getRowIndex())->getValue();
                $direccion = $sheet->getCell('E' . $row->getRowIndex())->getValue();

                $cliente = Garante::where('identificacion', $identificacion)->first();

                if (!$cliente) {
                    $cliente = Garante::create([
                        'identificacion' => $identificacion,
                        'nombre' => $nombres,
                        'apellido' => $apellidos,
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                    ]);
                }
            }

            DB::commit();
            Alert::toast('Garantes subidos correctamente.', 'success');
            return redirect()->route('inventario.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::toast('Error al procesar la carga: ' . $e->getMessage(), 'error');
            return redirect()->back();
        }
    }
}
