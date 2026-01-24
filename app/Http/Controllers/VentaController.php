<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Garante;
use App\Models\HistorialPago;
use App\Models\Inventario;
use App\Models\KardexCliente;
use App\Models\Pago;
use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary as Cloudinary;
use RealRashid\SweetAlert\Facades\Alert;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function form()
    {
        // Obtiene los stocks totales junto con la información del producto en una sola consulta
        $totalStocks = Inventario::select('producto_id', DB::raw('SUM(cantidad) as total_stock'))
            ->join('productos', 'inventarios.producto_id', '=', 'productos.id') // Unir con productos
            ->groupBy('producto_id')
            ->get();

        // Mapear los resultados para agregar la cantidad al objeto producto
        $productos = $totalStocks->map(function($ts) {
            $producto = Producto::find($ts->producto_id);
            if ($producto) {
                $producto->cantidad = $ts->total_stock; // Asignar la cantidad directamente
                return $producto;
            }
        })->filter(); // Filtrar productos nulos si no se encontraron

        // Obtener todas las categorías
        $categorias = Categoria::all();

        // Retornar la vista con los productos y categorías
        return view('venta.form', compact('productos', 'categorias'));
    }





    // public function guardarVenta(Request $request)
    // {
    //     dd($request);
    //     $tiposPrecio = $request->get('tipo_precio');
    //     $preciosElegidos = $request->get('nuevoprecio');
    //     $cantidadesSeleccionadas = $request->get('cantidades');
    //     $estado = $request->get('estado');
    //     $comentario = $request->get('comentario');

    //     foreach ($cantidadesSeleccionadas as $cs) {
    //         if ($cs == 0) {
    //             Alert::toast("No hay suficientes artículos en stock para uno de los artículos seleccionados, intente de nuevo.", 'warning');
    //             return redirect()->back();
    //         }
    //     }

    //     $usuarioId = auth()->user()->id;
    //     $totalVenta = 0;

    //     // Iniciar una transacción
    //     DB::beginTransaction();

    //     try {
    //         // Crear el registro de la venta usando Eloquent
    //         $venta = Venta::create([
    //             'usuario_id' => $usuarioId,
    //             'total' => 0,
    //             'estado' => $estado,
    //             'comentario' => $comentario
    //         ]);

    //         $datosProductos = json_decode($request->productos, true);
    //         // Obtener todos los inventarios de los productos requeridos
    //         $productosIds = array_column($datosProductos, 'id');
    //         $inventarios = DB::table('productos')
    //             ->join('inventarios', 'productos.id', '=', 'inventarios.producto_id')
    //             ->whereIn('productos.id', $productosIds)
    //             ->orderBy('inventarios.cantidad', 'desc')
    //             ->select('productos.id as productoid', 'inventarios.*')
    //             ->get();

    //         $iteracion = 0;
    //         foreach ($datosProductos as $key => $producto) {
    //             $productoid = $producto['id'];
    //             $cantidadSolicitada = $cantidadesSeleccionadas[$iteracion];
    //             $tipoPrecio = $tiposPrecio[$iteracion];
    //             $precioElegido = $tipoPrecio == 3 ? $preciosElegidos[$iteracion] : ($tipoPrecio == 1 ? $producto['precio_contado'] : $producto['precio_credito']);
    //             $cantidadRestante = $cantidadSolicitada;

    //             // Filtrar los inventarios correspondientes al producto actual
    //             $inventariosProducto = $inventarios->where('producto_id', $productoid);

    //             foreach ($inventariosProducto as $inventario) {
    //                 if ($cantidadRestante <= 0) {
    //                     break;
    //                 }

    //                 if ($inventario->cantidad >= $cantidadRestante) {
    //                     // Reducir la cantidad en este inventario
    //                     $inventario->cantidad -= $cantidadRestante;
    //                     DB::table('inventarios')->where('id', $inventario->id)->update(['cantidad' => $inventario->cantidad]);

    //                     // Registrar en los detalles de la venta
    //                     DB::table('venta_detalles')->insert([
    //                         'venta_id' => $venta->id,
    //                         'inventario_id' => $inventario->id,
    //                         'cantidad' => $cantidadSolicitada,
    //                         'precio_unitario' => $precioElegido,
    //                     ]);

    //                     $totalVenta += $cantidadRestante * $precioElegido;
    //                     $cantidadRestante = 0; // Todo vendido
    //                 } else {
    //                     // Registrar la cantidad parcial y ajustar el inventario
    //                     $totalVenta += $inventario->cantidad * $precioElegido;
    //                     $cantidadRestante -= $inventario->cantidad;

    //                     DB::table('inventarios')->where('id', $inventario->id)->update(['cantidad' => 0]);

    //                     DB::table('venta_detalles')->insert([
    //                         'venta_id' => $venta->id,
    //                         'inventario_id' => $inventario->id,
    //                         'cantidad' => $inventario->cantidad,
    //                         'precio_unitario' => $precioElegido,
    //                     ]);
    //                 }
    //             }

    //             if ($cantidadRestante > 0) {
    //                 // Mostrar alerta de advertencia
    //                 Alert::toast("No hay suficientes artículos en stock", 'warning');
    //             }
    //             $iteracion++; // Incrementar iteración
    //         }

    //         // Actualizar el total de la venta
    //         $venta->total = $totalVenta;
    //         $venta->save();

    //         // Confirmar la transacción
    //         DB::commit();

    //         // Mostrar alerta de éxito
    //         Alert::toast('Producto despachado correctamente', 'success');

    //     } catch (\Exception $e) {
    //         // Revertir la transacción en caso de error
    //         DB::rollBack();
    //         // Mostrar alerta de error
    //         Alert::toast('Ocurrió un error al procesar la venta: ' . $e->getMessage(), 'error');
    //     }

    //     return redirect()->route('inventario.index');
    // }



    public function guardarVenta(Request $request)
    {
        // Debug inicial
        // dd($request);

        $tiposPrecio = $request->get('tipo_precio');
        $preciosElegidos = $request->get('precios');
        $cantidadesSeleccionadas = $request->get('cantidades');
        $estado = $request->get('estado');
        $comentario = $request->get('comentario');
        $abonoImagen = $request->file('abono');
        $saldo = $request->get('saldo');
        $interes = $request->get('interes');
        $meses = $request->get('meses');
        $entrada = $request->get('entrada');
        $cuotas = $request->get('cuotas');



        foreach ($cantidadesSeleccionadas as $cs) {
            if ($cs == 0) {
                Alert::toast("No hay suficientes artículos en stock para uno de los artículos seleccionados, intente de nuevo.", 'warning');
                return redirect()->back();
            }
        }

        $usuarioId = auth()->user()->id;
        $totalVenta = 0;

        // Iniciar una transacción
        DB::beginTransaction();

        // try {
            // Crear o encontrar cliente
            $cliente = Cliente::updateOrCreate(
                ['identificacion' => $request->get('identificacion')],
                [
                    'nombre' => $request->get('nombre'),
                    'apellidos' => $request->get('apellidos'),
                    'direccion' => $request->get('direccion'),
                    'telefono' => $request->get('telefono')
                ]
            );

            // Crear o encontrar garante si se proporciona
            $garante = null;

            if ($request->get('showGarante') == 2)
            {
                $garante = Garante::updateOrCreate(
                    ['identificacion' => $request->get('identificacion_garante')],
                    [
                        'cliente_id' => $cliente->id, // Agregar cliente_id aquí
                        'nombre' => $request->get('nombre_garante'),
                        'apellido' => $request->get('apellido_garante'),
                        'direccion' => $request->get('direccion_garante'),
                        'telefono' => $request->get('telefono_garante')
                    ]
                );
            }


            // Crear el registro de la venta
            $venta = Venta::create([
                'usuario_id' => $usuarioId,
                'total' => 0,
                'estado' => $request->get('estadoentrega'),//pendiente, por agregar en el formulario como un select de estado de entrega
                'comentario' => $comentario
            ]);




            // Detalles de la venta y cálculo de total
            $datosProductos = json_decode($request->productos, true);
            $productosIds = array_column($datosProductos, 'id');
            $inventarios = DB::table('productos')
                ->join('inventarios', 'productos.id', '=', 'inventarios.producto_id')
                ->whereIn('productos.id', $productosIds)
                ->orderBy('inventarios.cantidad', 'desc')
                ->select('productos.id as productoid', 'inventarios.*')
                ->get();


            $iteracion = 0;
            // if($meses>5)
            //     {
                    // $interesTotal = $saldo * ($interes/100) * $meses;//82.5 bien.
                    // dd($interesTotal);
                    // $totalPagarConInteres = $interesTotal+$saldo;
                    // $cuotasPagarPorMes = $totalPagarConInteres/$meses;
                    // dd($interesTotal, $totalPagarConInteres, $cuotasPagarPorMes);
                    // $saldoConInteres = $saldo * $interesTotal;
                    // $cuotasTotal = $saldoConInteres / $meses;
                    // dd($interesTotal, $saldoConInteres, $cuotasTotal, $meses, $saldo, $interes);
                    // let interesTotal = saldo * (interes / 100) * meses; // Interés total acumulado
                    // let saldoConInteres = saldo + interesTotal; // Saldo con el interés total incluido
                    // cuotasTotal = saldoConInteres / meses; // Cuota mensual
                    // dd($interesTotal, $saldoConInteres, $cuotasTotal);
                // }

            $subtotal = 0;
            $iteracion = 0;
            foreach($datosProductos as $key => $producto)
            {
                $subtotal +=  $preciosElegidos[$iteracion] * $cantidadesSeleccionadas[$iteracion];
                $iteracion++;
            }


            if($entrada > 0)
            {
                $subtotal -= $entrada;
            }
            // dump($cuotas, $interes, $subtotal, $meses);
            if($meses > 3)
            {
                $interesTotal = $subtotal * ($interes/100) * $meses;
                $subtotalMasInteres = $interesTotal+$subtotal;
                $totalPagarPorMes = $subtotalMasInteres/$meses;
                $totalFinalPagar = $totalPagarPorMes*$meses + $entrada;
                $saldo = $subtotalMasInteres;
                // dd($interesTotal, $subtotalMasInteres, $totalPagarPorMes, $totalFinalPagar);
            }else
            {
                $totalFinalPagar = $subtotal + $entrada;
            }




            /** Calculamos el verdadero valor a pagar */



            // dd($subtotal);



            $iteracion = 0;

            foreach ($datosProductos as $key => $producto)
            {
                $productoid = $producto['id'];
                $cantidadSolicitada = $cantidadesSeleccionadas[$iteracion];
                $tipoPrecio = $tiposPrecio[$iteracion];
                $precioElegido = $preciosElegidos[$iteracion];
                // $precioElegido = $tipoPrecio == 3 ? $preciosElegidos[$iteracion] : ($tipoPrecio == 1 ? $producto['precio_contado'] : $producto['precio_credito']);
                $cantidadRestante = $cantidadSolicitada;

                /** Validacion para calcular valor real cuando la venta es a credito mayor a 3 meses (ejemplo 4 meses o más) */
                // if($meses>3 && ($tipoPrecio == 2 || $tipoPrecio == 3))
                // {
                    // let interesTotal = saldo * (interes / 100) * meses; // Interés total acumulado
                    // let saldoConInteres = saldo + interesTotal; // Saldo con el interés total incluido
                    // cuotasTotal = saldoConInteres / meses; // Cuota mensual
                // }

                $inventariosProducto = $inventarios->where('producto_id', $productoid);

                foreach ($inventariosProducto as $inventario) {
                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    if ($inventario->cantidad >= $cantidadRestante)
                    {
                        $inventario->cantidad -= $cantidadRestante;
                        DB::table('inventarios')->where('id', $inventario->id)->update(['cantidad' => $inventario->cantidad]);

                        DB::table('venta_detalles')->insert([
                            'venta_id' => $venta->id,
                            'inventario_id' => $inventario->id,
                            'cantidad' => $cantidadSolicitada,
                            'precio_unitario' => $precioElegido,
                        ]);

                        // $totalVenta += $cantidadRestante * $precioElegido;
                        $cantidadRestante = 0;
                    } else
                    {
                        // $totalVenta += $inventario->cantidad * $precioElegido;
                        $cantidadRestante -= $inventario->cantidad;

                        DB::table('inventarios')->where('id', $inventario->id)->update(['cantidad' => 0]);

                        DB::table('venta_detalles')->insert([
                            'venta_id' => $venta->id,
                            'inventario_id' => $inventario->id,
                            'cantidad' => $inventario->cantidad,
                            'precio_unitario' => $precioElegido,
                        ]);
                    }
                }

                if ($cantidadRestante > 0) {
                    Alert::toast("No hay suficientes artículos en stock", 'warning');
                }
                $iteracion++;
            }


            // Actualizar el total de la venta
            // $venta->total = $totalVenta;
            $venta->total = $totalFinalPagar;

            $venta->save();

            $meses = $request->get('meses');
            // Crear registro en kardex_cliente
            $kardexCliente = KardexCliente::create([
                'cliente_id' => $cliente->id,
                'venta_id' => $venta->id,
                'fecha_compra' => now(),
                // 'monto_total' => $totalVenta,
                'monto_total' => $totalFinalPagar,
                'entrada' => $entrada,
                'num_cuotas' => $meses,
                'monto_cuota' => $cuotas,
                'saldo_pendiente' => $saldo,
                'estado' => 'Activo', // define si el credito esta activo, cancelado, etc. Activo por defecto al momento de vender.
                'interes' => $interes,
                'fecha_vencimiento' => now()->addMonths($meses),
            ]);



            // Crear registro inicial en historial de pagos si hay entrada
            if ($entrada > 0)
            {
                // Crear el historial de pago
                $historialPago = HistorialPago::create([
                    'cliente_id' => $cliente->id,
                    'kardex_cliente_id' => $kardexCliente->id,
                    'monto_pagado' => $entrada,
                    'fecha_pago' => now(),
                    'metodo_pago' => $request->get('formapago'),
                    'saldo_restante' => $saldo,
                    'comentarios' => 'Primer Pago',
                    'comprobante' => 'ninguno',
                    'estado_pago' => $estado,
                    'usuario_id' => auth()->user()->id
                ]);

                // Validar si el estado es '1' y si el archivo se ha cargado
                if ($estado == 'Recibido' && $request->hasFile('abono') && $request->file('abono')->isValid()) {
                    $abonoImagen = $request->file('abono');
                    // Subir el archivo a Cloudinary
                    $elemento = Cloudinary::upload($abonoImagen->getRealPath(), ['folder' => 'pagos']);
                    // Guardar la URL y el public_id en la base de datos
                    $historialPago->imagen()->create([
                        'url' => $elemento->getSecurePath(),
                        'public_id' => $elemento->getPublicId(),
                    ]);
                }
            }





            // Confirmar la transacción
            DB::commit();
            Alert::toast('Producto despachado correctamente', 'success');

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Alert::toast('Ocurrió un error al procesar la venta: ' . $e->getMessage(), 'error');
        // }

        return redirect()->route('inventario.index');
    }

    // public function historialVenta(Request $request)
    // {
    //     // $ventas = Venta::all();
    //      // Obtener todas las ventas junto con el cliente
    //     //  $ventas = Venta::with('kardexClientes.cliente')->get();
    //        // Obtener todas las ventas junto con el cliente y el garante
    //     //    $ventas = Venta::with('kardexClientes.cliente.garante')->get();
    //      // Obtener todas las ventas junto con el cliente, garante y historial de pagos
    //      $ventas = Venta::with('kardexCliente.cliente.garante', 'kardexCliente.historialPagos')->get();


    //     return view('venta.historial',compact('ventas'));
    // }

    public function historialVenta()
{
    // Obtener todas las ventas junto con la información del cliente, kardex, y el historial de pagos
    $ventas = Venta::with([
        // 'kardexCliente.cliente.garante',
        'kardexCliente.historialPagos',
        'detalles.inventario.producto'
    ])->get();

    // Mapeamos la información para estructurarla de manera similar a la función comprasByCliente
    $historialVentas = $ventas->map(function ($venta) {
        $kardexCliente = $venta->kardexCliente;

        // Obtener el cliente asociado, si existe
        $cliente = $kardexCliente ? $kardexCliente->cliente : null;

        // Obtener los pagos relacionados con este kardexCliente
        $historialPagos = $kardexCliente ? $kardexCliente->historialPagos->map(function ($pago) {
            return [
                'monto_pagado' => $pago->monto_pagado,
                'fecha_pago' => $pago->fecha_pago,
                'metodo_pago' => $pago->metodo_pago,
                'comentarios' => $pago->comentarios,
                'comprobante' => $pago->comprobante,
                'saldo_restante' => $pago->saldo_restante,
                'estado_pago' => $pago->estado_pago,
            ];
        }) : [];

        // Obtener los detalles de la venta
        $detalles = $venta->detalles->map(function ($detalle) {
            return [
                'producto' => $detalle->inventario->producto,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'total' => $detalle->cantidad * $detalle->precio_unitario,
                'numero_serie' => $detalle->inventario->numero_serie
            ];
        });

        return [
            'venta' => [
                'id' => $venta->id,
                'total' => $venta->total,
                'estado' => $venta->estado,
                'vendedor' => $venta->usuario->name,
                'comentario' => $venta->comentario,
                'created_at' => $venta->created_at,
            ],
            'cliente' => $cliente ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'apellidos' => $cliente->apellidos,
                'identificacion' => $cliente->identificacion,
                'direccion' => $cliente->direccion,
                'telefono' => $cliente->telefono,
            ] : null,
            'kardex_cliente' => $kardexCliente ? [
                'id' => $kardexCliente->id,
                'cliente' => $kardexCliente->cliente,
                'fecha_compra' => $kardexCliente->fecha_compra,
                'monto_total' => $kardexCliente->monto_total,
                'entrada' => $kardexCliente->entrada,
                'num_cuotas' => $kardexCliente->num_cuotas,
                'monto_cuota' => $kardexCliente->monto_cuota,
                'saldo_pendiente' => $kardexCliente->saldo_pendiente,
                'estado' => $kardexCliente->estado,
                'interes' => $kardexCliente->interes,
                'fecha_vencimiento' => $kardexCliente->fecha_vencimiento,
            ] : null,
            'detalles' => $detalles,
            'historial_pagos' => $historialPagos,
        ];
    });

    // dd($historialVentas);
    // Retornar la información a la vista
    return view('venta.historial', compact('historialVentas'));
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
    public function update(Request $request, $id)
    {
        $venta = Venta::find($id);

        if(!$venta)
        {
            Alert::toast('No se encontró la venta', 'warning');
            return redirect()->back();
        }

        $venta->update([
            'comentario' => $request->get('comentario'),
            'estado' => $request->get('estado'),
        ]);

        Alert::toast('Datos actualizados correctamente', 'success');
        return redirect()->back();
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
         // Busca el registro de venta por su ID
        $venta = Venta::find($id);

        if (!$venta) {
            Alert::toast('No se encontró la venta', 'warning');
            return redirect()->back();
        }

        // Realiza el borrado lógico
        $venta->delete();

        Alert::toast('Venta eliminada correctamente', 'success');
        return redirect()->route('venta.historial');
        }
}
