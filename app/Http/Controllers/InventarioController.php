<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InventarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener el total de stocks de los productos
        $totalStocks = Inventario::select('producto_id', DB::raw('SUM(cantidad) as total_stock'))
            ->groupBy('producto_id')
            ->get()
            ->keyBy('producto_id'); // Convertir a colección indexada por 'producto_id'

        // Calcular el valor total del inventario
        $totalInventario = 0;

        // Obtener todos los productos y agregar el stock correspondiente
        $productos = Producto::with('inventarios') // Cargar inventarios si tienes esta relación definida
            ->get()
            ->map(function ($producto) use ($totalStocks, &$totalInventario) {
                // Calcular la cantidad total para este producto
                $cantidad = $totalStocks->has($producto->id) ? $totalStocks->get($producto->id)->total_stock : 0;
                $producto->cantidad = $cantidad;

                // Sumar al total del inventario
                $totalInventario += $producto->precio_contado * $cantidad;

                return $producto;
            });

            // dd($productos);

        // Obtener todas las categorías
        $categorias = Categoria::all();

        // Pasar el total del inventario a la vista
        return view('inventario.index', compact('productos', 'categorias', 'totalInventario'));
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
    public function store(StoreProductoRequest $request)
    {


        // return redirect()->route('producto.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductoRequest $request, $id)
    {


        // return redirect()->route('producto.index');
    }

    public function uploadFile(Request $request)
    {

        // return redirect()->route('producto.formUploadFile');
    }


    public function formUploadFile()
    {
        // return view('producto.producto-upload',compact('productos'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Producto::destroy($id);
        // return redirect()->route('producto.index');
    }
}
