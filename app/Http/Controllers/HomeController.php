<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\HistorialPago;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\FacturaElectronicaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Cliente;
use Illuminate\Support\Facades\Mail;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */



    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard(Request $request)
    {
        $productos = Producto::all();
        $categorias = Categoria::all();

        // Obtener el total de productos por categoría y el total de precio_original por categoría
        $categorias_con_totales = $categorias->map(function ($categoria) use ($productos)
        {
            // Filtrar los productos que pertenecen a la categoría actual
            $productos_categoria = $productos->where('categoria_id', $categoria->id);

            // Obtener el total de productos en esa categoría y el total de precio_original
            $total_productos = $productos_categoria->count();
            $total_precio = $productos_categoria->sum('precio_original');

            // Devolver la categoría con los totales
            return [
                'categoria' => $categoria->nombre,
                'total_productos' => $total_productos,
                'total_precio' => $total_precio,
            ];
        });

        // Ordenar las categorías por el total de precio_original en orden descendente
        $categorias_con_totales = $categorias_con_totales->sortByDesc('total_precio');
        $categorias_con_totales = $categorias_con_totales->take(4);

        $metodosPago = HistorialPago::select('metodo_pago',
        DB::raw('COUNT(*) as total_usos'),
        DB::raw('SUM(monto_pagado) as total_pagado'))
        ->groupBy('metodo_pago')
        ->orderByDesc('total_usos')
        ->get();

        $totalVentas = DB::table('venta_detalles')
        ->join('inventarios', 'venta_detalles.inventario_id', '=', 'inventarios.id')
        ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
        ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
        ->sum(DB::raw('venta_detalles.precio_unitario * venta_detalles.cantidad'));

        $ventasPorCategoria = DB::table('venta_detalles')
        ->join('inventarios', 'venta_detalles.inventario_id', '=', 'inventarios.id')
        ->join('productos', 'inventarios.producto_id', '=', 'productos.id')
        ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
        ->select(
            'categorias.nombre as categoria',
            DB::raw('SUM(venta_detalles.precio_unitario * venta_detalles.cantidad) as total_ventas')
        )
        ->groupBy('categorias.nombre')
        ->get();

        // Calcular porcentaje de ventas por categoría
        $ventasConPorcentaje = $ventasPorCategoria->map(function ($venta) use ($totalVentas) {
        $venta->porcentaje = ($totalVentas > 0) ? ($venta->total_ventas / $totalVentas) * 100 : 0;
        return $venta;
        });

        $totalNumeroVentas = DB::table('ventas')->count();
        // Obtener solo los nombres de las 4 categorías con más ventas
        $topCategorias = $ventasPorCategoria->take(4)->pluck('categoria');

        $ventasPorCategoriaOrdenadas = $ventasPorCategoria->sortByDesc(function ($item) {
            return floatval($item->total_ventas);
        });
        $ventasPorCategoriaOrdenadas = $ventasPorCategoriaOrdenadas->values();

        // $pagosPorMes = HistorialPago::selectRaw('YEAR(fecha_pago) as year, MONTH(fecha_pago) as month, SUM(monto_pagado) as total_pagado')
        //     ->groupBy('year', 'month')
        //     ->orderBy('year', 'desc')
        //     ->orderBy('month', 'desc')
        //     ->get();

        // $totalPagosGeneral = HistorialPago::sum('monto_pagado');

        $pagosPorMes = DB::table('historial_pagos')
            ->selectRaw('YEAR(fecha_pago) as year, MONTH(fecha_pago) as month, SUM(monto_pagado) as total_pagado')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($pago) {
                $meses = [
                    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
                ];
                return [
                    'mes' => $meses[$pago->month] . ' ' . $pago->year, // Agregamos el año para diferenciar
                    'total_pagado' => (float) $pago->total_pagado
                ];
            });

        $totalPagosGeneral = $pagosPorMes->sum('total_pagado');
        // dd($pagosPorMes);
        // Pasar los datos a la vista
        return view('welcome', compact('categorias_con_totales','metodosPago', 'totalVentas','ventasPorCategoria','totalNumeroVentas','topCategorias','ventasPorCategoriaOrdenadas','pagosPorMes','totalPagosGeneral'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function generarFacturaXML()
    {



        $fes = new FacturaElectronicaService();
        $data = [
            'razonSocial' => 'Comercial Gusmor S.A.',
            'ruc' => '0101516904001',
            'estab' => '003',
            'ptoEmi' => '003',
            'secuencial' => '000000789',
            'dirMatriz' => 'Av. Principal y Calle Secundaria',
            'fechaEmision' => '2024-12-21',
            'dirEstablecimiento' => 'Av. Secundaria y Tercera Calle',
            'obligadoContabilidad' => 'SI',
            'tipoIdentificacionComprador' => '05', // 05: Cédula, 04: RUC, etc.
            'razonSocialComprador' => 'Juan Pérez',
            'identificacionComprador' => '0501234567',
            'totalSinImpuestos' => '4.46', // Base imponible sin impuestos
            'totalDescuento' => '0.00',
            'totalConImpuestos' => [
                [
                    'codigo' => '2', // IVA
                    'codigoPorcentaje' => '2', // 12%
                    'baseImponible' => '4.46',
                    'valor' => '0.54', // 12% de IVA sobre 4.46
                ]
            ],
            'propina' => '0.00',
            'importeTotal' => '5.00', // Total con impuestos
            'moneda' => 'DOLAR',
            'detalles' => [
                [
                    'codigoPrincipal' => '001',
                    'descripcion' => 'Producto A',
                    'cantidad' => '1',
                    'precioUnitario' => '4.46',
                    'descuento' => '0.00',
                    'precioTotalSinImpuesto' => '4.46',
                    'impuestos' => [
                        [
                            'codigo' => '2', // IVA
                            'codigoPorcentaje' => '2', // 12%
                            'tarifa' => '12',
                            'baseImponible' => '4.46',
                            'valor' => '0.54', // IVA calculado
                        ]
                    ]
                ]
            ]
        ];


        $xmlFileName = $fes->generarFacturaXML($data);
        // dd($xmlFileName);
        $xmlPath = resource_path('facturas/'.$xmlFileName);

        // $xmlPath = storage_path('factura.xml'); // Ruta del XML generado
        $certPath = env('FACTURACION_CERTIFICADO_PATH'); // Ruta al certificado
        $certPassword = env('FACTURACION_CERTIFICADO_PASSWORD'); // Contraseña del certificado

        $signedXmlPath = $fes->firmarXML($xmlPath, $certPath, $certPassword);
        // $res = $fes->enviarAlSRIConGuzzle($signedXmlPath);
        // 2024122101010151690400110010010000001231234567812
        // 2112202401010151690400110010010000001231234567813
        // 2112202401010151690400110010010000001231234567813
        // 2112202401010151690400110010010000001231234567812
        // 2112202401010151690400110030020000001231234567812
        // 2112202401010151690400110030020000004561234567813
        $res = $fes->consultarEstadoComprobante("2112202401010151690400110030030000007891234567812");
        dd($res);
        // if ($resultado['estado'] === 'RECIBIDA') {
        //     echo "La factura fue recibida correctamente.";
        // } else {
        //     echo "Error al enviar la factura: " . print_r($resultado['detalles'], true);
        // }
        // $fes->enviarAlSRI($signedXmlPath);
        // echo "El XML firmado se encuentra en: $signedXmlPath";
        // dd($resultado);
    }



}
