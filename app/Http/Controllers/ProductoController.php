<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Imagen;
use App\Models\Inventario;
use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary as Cloudinary;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\Snappy\Facades\SnappyPdf;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Obtener todos los productos y categorías
            $productos = Producto::with('categoria')->paginate(10); // Carga anticipada de la relación 'categoria' y paginación
            $categorias = Categoria::all(); // Considera paginar también si hay muchas categorías
            // Retornar la vista con los productos y categorías
            return view('producto.producto-table', compact('productos', 'categorias'));

        } catch (\Exception $e) {
            // Manejo de errores: puedes registrar el error y/o redirigir a una vista con un mensaje de error
            return redirect()->route('producto.index')->with('error', 'No se pudieron cargar los productos.');
        }
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

        try {
            // Crear el producto
            $producto = Producto::create([
                'nombre' => $request->get('nombre'),
                'modelo' => $request->get('modelo'),
                'marca' => $request->get('marca'),
                'serie' => $request->get('serie'),
                'precio_original' => $request->get('preciooriginal'),
                'precio_contado' => $request->get('preciocontado'),
                'precio_credito' => $request->get('preciocredito'),
                'color' => $request->get('color'),
                'descripcion' => $request->get('descripcion'),
                'categoria_id' => $request->get('categoria'),
            ]);

            // Verificar si ya existe un producto con los mismos valores
            $productoExistente = Producto::where('marca', $request->get('marca'))
            ->where('modelo', $request->get('modelo'))
            ->where('precio_original', $request->get('preciooriginal'))
            ->where('precio_contado', $request->get('preciocontado'))
            ->where('precio_credito', $request->get('preciocredito'))
            ->where('categoria_id', $request->get('categoria'))
            ->first();

            // Si ya existe, no se crea un nuevo producto
            if ($productoExistente)
            {
                // Crear registro en inventario
                Inventario::create([
                    'producto_id' => $productoExistente->id,
                    'numero_serie' => $request->get('serie'),
                    'cantidad' => 1, // Por defecto
                ]);
            }else
            {
                // Crear registro en inventario
                Inventario::create([
                    'producto_id' => $producto->id,
                    'numero_serie' => $request->get('serie'),
                    'cantidad' => 1, // Por defecto
                ]);
            }

            // Manejar carga de imágenes
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $img) {
                    $elemento = Cloudinary::upload($img->getRealPath(), ['folder' => 'productos']);
                    $producto->imagenes()->create([
                        'url' => $elemento->getSecurePath(),
                        'public_id' => $elemento->getPublicId(),
                    ]);
                }
            }

            // Mensaje de éxito usando Toast
            Alert::toast('Producto creado exitosamente', 'success');

            return redirect()->route('inventario.index');

        } catch (\Exception $e) {
            // Mensaje de error usando Toast
            Alert::toast('Error al guardar el producto: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput(); // Retorna con los datos ingresados
        }
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
        // Buscar el producto o lanzar excepción si no existe
        $producto = Producto::findOrFail($id);

        // Actualizar los datos del producto usando validated()
        $producto->update($request->all());

        // Manejar imágenes nuevas
        $this->handleNewImages($request, $producto);

        // Manejar imágenes eliminadas
        $this->handleDeletedImages($request);

        Alert::toast('Producto actualizado correctamente', 'success');
        return redirect()->route('inventario.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function reports()
    {
        $categorias = Categoria::all();
        return view('producto.producto-reports',compact('categorias'));
    }

    public function generarReporte(Request $request)
    {
        $fechaInicio = $request->get('fechaInicio');
        $fechaFin = $request->get('fechaFin');
        $tipoFormato = $request->get('tipoFormato');
        $categorias = $request->get('categorias'); // Es un array con los IDs de las categorías seleccionadas
        $agregarEliminados = $request->get('agregarEliminados');
        $status = 'error';

        // Validamos que las fechas sean válidas
        if (!$fechaInicio || !$fechaFin) {
            $status = 'error';
            // return response()->json(['error' => 'Las fechas son obligatorias'], 400);
        }

        // Iniciamos la consulta base
        $productosQuery = Producto::with('categoria') // Carga la relación de categoría
        ->leftJoin('inventarios', 'productos.id', '=', 'inventarios.producto_id')
        ->selectRaw(
            'productos.id,
            productos.categoria_id, -- Agregar esta columna
            productos.nombre,
            productos.marca,
            productos.modelo,
            productos.precio_original,
            productos.precio_contado,
            productos.precio_credito,
            productos.descripcion,
            productos.color,
            COALESCE(SUM(inventarios.cantidad), 0) as total_cantidad,
            productos.deleted_at,
            productos.created_at'
        )
        ->groupBy('productos.id', 'productos.categoria_id', 'productos.nombre', 'productos.marca', 'productos.modelo', 'productos.deleted_at');

        // Filtrar por fecha
        $productosQuery->whereBetween('productos.created_at', [$fechaInicio, $fechaFin]);

        // Filtrar por categorías si $categorias no está vacío
        if (!empty($categorias)) {
            $productosQuery->whereIn('productos.categoria_id', $categorias);
        }

        // Filtrar por productos eliminados según el valor de $agregarEliminados
        if ($agregarEliminados === 'on') {
            // Incluir productos eliminados (con SoftDeletes)
            $productosQuery->withTrashed();
        } else {
            // Excluir productos eliminados (sin SoftDeletes)
            $productosQuery->withoutTrashed();
        }

        // Ejecutar la consulta
        $productos = $productosQuery->get();

        $url = '';
        if (!empty($productos)) {
            if ($tipoFormato == 'xlsx') {
                $url = route('producto.descargarReporteExcel');
            } else {
                $url = route('producto.descargarReportePDF');
            }
            // dd($productos);
            session()->put('productos_reporte', $productos);
            $status = 'success';
        }

        return response()->json([
            'url' => $url,
            'status' => $status
        ]);
    }




    public function descargarReporteExcel()
    {
        $productos = session('productos_reporte');
            // Creación del Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Código');
        $sheet->setCellValue('C1', 'Marca');
        $sheet->setCellValue('D1', 'Modelo');
        $sheet->setCellValue('E1', 'Color');
        $sheet->setCellValue('F1', 'Descripción');
        $sheet->setCellValue('G1', 'Cantidad');
        $sheet->setCellValue('H1', 'Precio Original');
        $sheet->setCellValue('I1', 'Precio Contado');
        $sheet->setCellValue('J1', 'Precio Credito');
        $sheet->setCellValue('K1', 'Categoría ID');
        $sheet->setCellValue('L1', 'Categoría');
        $sheet->setCellValue('M1', 'Nombre');
        $sheet->setCellValue('N1', 'Fecha Creación');

        // Aplicar negrita a los encabezados
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        $row = 2;
        foreach ($productos as $producto) {
            foreach($producto->inventarios as $keyInv => $detalleInventario)
            {
                $sheet->setCellValue("A{$row}", $producto->id);
                $sheet->setCellValue("B{$row}", $detalleInventario->numero_serie);
                $sheet->setCellValue("C{$row}", $producto->marca);
                $sheet->setCellValue("D{$row}", $producto->modelo);
                $sheet->setCellValue("E{$row}", $producto->color);
                $sheet->setCellValue("F{$row}", $producto->descripcion);
                $sheet->setCellValue("G{$row}", $detalleInventario->cantidad);
                $sheet->setCellValue("H{$row}", $producto->precio_original);
                $sheet->setCellValue("I{$row}", $producto->precio_contado);
                $sheet->setCellValue("J{$row}", $producto->precio_credito);
                $sheet->setCellValue("K{$row}", $producto->categoria->id);
                $sheet->setCellValue("L{$row}", $producto->categoria->nombre ?? 'Sin categoría');
                $sheet->setCellValue("M{$row}", $producto->nombre ?? 'Sin nombre');
                $sheet->setCellValue("N{$row}", $producto->created_at->format('Y-m-d'));
                $row++;
            }
        }

         // Ajustar el tamaño de las columnas según el contenido
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Agregar bordes a todas las celdas con información
        $lastRow = $row - 1; // Última fila con datos
        $sheet->getStyle("A1:N{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


          // Alineación centrada para todas las celdas con datos
        $sheet->getStyle("A1:N{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


        $writer = new Xlsx($spreadsheet);
        $fileName = "Reporte_Productos.xlsx";

        // Descargar el archivo directamente
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function descargarReportePDF()
    {
        $productos = session('productos_reporte');

         // Generar el PDF usando la plantilla Blade
        $pdf = SnappyPdf::loadView('producto.pdf.reporte_productos_pdf', compact('productos'))
        ->setOption('no-outline', true)
        ->setOption('encoding', 'UTF-8')
        ->setOption('margin-top', 10)
        ->setOption('margin-bottom', 10)
        ->setOption('margin-left', 10)
        ->setOption('margin-right', 10);

// Descargar el archivo PDF
return $pdf->download('Reporte_Productos.pdf');

    }


    private function handleNewImages(Request $request, Producto $producto)
    {
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $img_nueva) {
                try {
                    $elemento = Cloudinary::upload($img_nueva->getRealPath(), [
                        'folder' => 'productos',
                    ]);

                    $producto->imagenes()->create([
                        'url' => $elemento->getSecurePath(),
                        'public_id' => $elemento->getPublicId(),
                    ]);
                } catch (\Exception $e) {
                    // Mostrar alerta de error con SweetAlert
                    Alert::toast('Error al cargar la imagen: ' . $e->getMessage(), 'error');
                    return redirect()->back();
                }
            }
            // Mostrar alerta de éxito si todas las imágenes se cargan correctamente
            Alert::toast('Imágenes cargadas correctamente.', 'success');
        }
    }


        private function handleDeletedImages(Request $request)
    {
        $imagenesEliminadas = explode(',', $request->get('imagenesEliminadas', ''));
        foreach ($imagenesEliminadas as $img_eliminada) {
            $imagen = Imagen::find($img_eliminada);
            if ($imagen) {
                try {
                    Cloudinary::destroy($imagen->public_id);
                    $imagen->delete();
                } catch (\Exception $e) {
                    // Mostrar alerta de error con SweetAlert
                    Alert::toast('Error al eliminar la imagen: ' . $e->getMessage(), 'error');
                    return redirect()->back();
                }
            }
        }
        // Mostrar alerta de éxito si todas las imágenes se eliminan correctamente
        Alert::toast('Imágenes eliminadas correctamente.', 'success');
    }


    public function urlExists($url) {
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200')) {
            return true; // La URL existe y responde con un código 200 (OK)
        }
        return false; // La URL no es accesible o responde con un error
    }


    public function uploadFile(Request $request)
    {
        set_time_limit(300); // 300 segundos (5 minutos)
        // Validar la carga del archivo
        if (!$request->hasFile('archivo')) {
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
        $productosConSeries = [];

        DB::beginTransaction(); // Iniciar transacción

        try {
            // Procesar filas desde la fila 2
            foreach ($sheet->getRowIterator(2) as $row) {
                $codigo = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                $marca = $sheet->getCell('B' . $row->getRowIndex())->getValue();
                $modelo = $sheet->getCell('C' . $row->getRowIndex())->getValue();
                $color = $sheet->getCell('D' . $row->getRowIndex())->getValue();
                $descripcion = $sheet->getCell('E' . $row->getRowIndex())->getValue();
                $cantidad = $sheet->getCell('F' . $row->getRowIndex())->getValue();
                $precioOriginal = $sheet->getCell('G' . $row->getRowIndex())->getValue();
                $precioContado = $sheet->getCell('H' . $row->getRowIndex())->getValue();
                $precioCredito = $sheet->getCell('I' . $row->getRowIndex())->getValue();
                $categoria_id = $sheet->getCell('J' . $row->getRowIndex())->getValue();
                $nombreCategoria = $sheet->getCell('K' . $row->getRowIndex())->getValue();

                if (empty($marca) || empty($modelo) || empty($categoria_id)) {
                    continue; // Saltar si faltan datos importantes
                }

                $imagenes_urls = [];
                $columnasImagen = ['L', 'M', 'N'];

                foreach ($columnasImagen as $columna) {
                    $urlImagen = $sheet->getCell($columna . $row->getRowIndex())->getValue();
                    if ($urlImagen && $this->urlExists($urlImagen)) {
                        $imagenes_urls[] = $urlImagen;
                    }
                }

                $producto = Producto::where('marca', $marca)
                                    ->where('modelo', $modelo)
                                    ->where('categoria_id', $categoria_id)
                                    ->first();

                if (!$producto) {
                    $producto = Producto::create([
                        'nombre' => $nombreCategoria,
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'color' => $color,
                        'descripcion' => $descripcion,
                        'precio_original' => $precioOriginal,
                        'precio_contado' => $precioContado,
                        'precio_credito' => $precioCredito,
                        'categoria_id' => $categoria_id
                    ]);
                }

                if (!empty($imagenes_urls)) {
                    $this->upload_images_from_urls(['imagenes_urls' => $imagenes_urls], $producto);
                }

                $productosConSeries[$producto->id][] = [
                    'numero_serie' => $codigo,
                    'cantidad' => $cantidad
                ];
            }

            foreach ($productosConSeries as $producto_id => $series) {
                foreach ($series as $serie) {
                    Inventario::create([
                        'producto_id' => $producto_id,
                        'numero_serie' => $serie['numero_serie'],
                        'cantidad' => $serie['cantidad']
                    ]);
                }
            }

            DB::commit();
            Alert::toast('Productos y series subidos correctamente.', 'success');
            return redirect()->route('inventario.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::toast('Error al procesar la carga: ' . $e->getMessage(), 'error');
            return redirect()->back();
        }
    }


    public function upload_images_from_urls(array $data, Producto $producto)
    {
        $imagenes_urls = $data['imagenes_urls'];

        foreach ($imagenes_urls as $url) {
            try {
                $elemento = Cloudinary::upload($url, ['folder' => 'productos']);
                $public_id = $elemento->getPublicId();
                $urlImagen = $elemento->getSecurePath();

                $producto->imagenes()->create([
                    'url' => $urlImagen,
                    'public_id' => $public_id,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error al subir imagen: ' . $e->getMessage());
                continue; // Continuar con la siguiente imagen en caso de error
            }
        }
    }


    public function formUploadFile()
    {
        $productos = Producto::all();
        return view('producto.producto-upload',compact('productos'));
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            Alert::toast('El producto no existe.', 'error');
            return redirect()->route('inventario.index');
        }

        try {
            // Eliminar imágenes y el producto
            foreach ($producto->imagenes as $img) {
                Cloudinary::destroy($img->public_id);
                $img->delete();
            }

            $producto->delete();

            // Mensaje de éxito usando Toast
            Alert::toast('Producto eliminado correctamente', 'success');
            return redirect()->route('inventario.index');
        } catch (\Exception $e) {
            Alert::toast('Error al eliminar el producto: ' . $e->getMessage(), 'error');
            return redirect()->route('inventario.index');
        }
    }

}
