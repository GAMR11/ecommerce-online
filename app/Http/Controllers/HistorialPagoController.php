<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\HistorialPago;
use App\Http\Requests\StoreHistorialPagoRequest;
use App\Http\Requests\UpdateHistorialPagoRequest;
use App\Models\Inventario;
use App\Models\KardexCliente;
use App\Models\Mora;
use App\Models\Venta;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary as Cloudinary;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class HistorialPagoController extends Controller
{

    public function descargarComprobante($id)
    {
        // Buscar el historial de pago
        $pago = HistorialPago::find($id);
        $cliente = $pago->cliente->first();
        $kardex = $pago->kardexCliente()->first();
        $venta_id = $kardex->venta_id;
        $venta = Venta::find($venta_id);
        $detallesVenta = $venta->detalles()->get();
        $nombreArticulos = '';
        foreach ($detallesVenta as $key => $dv) {
            $inventario_id = $dv->inventario_id;
            $inventario = Inventario::find($inventario_id);
            $producto = $inventario->producto()->first();
            if (strlen($nombreArticulos) == $key)
                $nombreArticulos .= $producto->nombre . '. ';
            else
                $nombreArticulos .= $producto->nombre . ', ';
        }
        $pago_id = $pago->id;

        // Verificar si el pago existe
        if ($pago) {
            // Configurar las opciones para Snappy
            PDF::setOption('no-stop-slow-scripts', true);
            PDF::setOption('enable-local-file-access', true);
            PDF::setOption('debug-javascript', true);
            PDF::setOption('no-images', false);

            // Datos para el recibo
            $data = [
                'cliente' => $cliente->nombre . ' ' . $cliente->apellidos,
                'articulos' => $nombreArticulos,
                'saldo_anterior' => $pago->monto_pagado + $pago->saldo_restante,
                'abono' => $pago->monto_pagado,
                'saldo_nuevo' => $pago->saldo_restante,
                'fecha_pago' => $pago->fecha_pago,
                'numero_recibo' => str_pad($pago_id, 7, '0', STR_PAD_LEFT),
            ];

            // Generar el PDF
            $pdf = PDF::setOptions([
                'encoding' => 'utf-8',
                'enable-local-file-access' => true,
                'page-width' => '210mm',  // Ancho personalizado
                'page-height' => '190mm' // Alto personalizado
            ])->loadView('recibos.recibo', $data);

            // Mostrar el PDF en el navegador
            return $pdf->stream('recibo-pago.pdf');
        } else {
            dd("No existe el pago.");
        }
    }




    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pagos = HistorialPago::all();
        // dd($pagos);die;
        return view('pagos.historial', compact('pagos'));
    }

    public function formPago()
    {
        $clientes = Cliente::all();
        return view('pagos.form-pago',compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    public function store(StoreHistorialPagoRequest $request)
    {
        // Iniciar transacción para asegurar que todas las operaciones se realicen correctamente
        DB::beginTransaction();

        try {
            // Validar la existencia del KardexCliente
            $kardexCliente = KardexCliente::findOrFail($request->kardex_cliente_id);

            $saldo_pendiente = round($kardexCliente->saldo_pendiente - $request->monto, 2);

            // Si el saldo pendiente es menor a 0.10, lo dejamos en 0
            if ($saldo_pendiente < 0.10) {
                $saldo_pendiente = 0;
            }

            // Actualizar saldo pendiente del cliente
            $kardexCliente->update([
                'saldo_pendiente' => $saldo_pendiente
            ]);



            // Obtener el último pago del cliente
            $ultimoPago = $kardexCliente->historialPagos()->latest()->first();
            $saldoAnterior = $ultimoPago ? $ultimoPago->saldo_restante : $kardexCliente->monto_total;
            $nuevoSaldo = $saldoAnterior - $request->monto;
            $comentario = $request->get('comentario');
            $formaPago = $request->get('formapago');
            $comprobante = $request->get('codigo_comprobante');

            $pago = HistorialPago::create([
                'cliente_id' => $kardexCliente->cliente_id,
                'usuario_id' => auth()->user()->id,
                'kardex_cliente_id' => $kardexCliente->id,
                'monto_pagado' => $request->monto,
                'fecha_pago' => Carbon::now(),
                'metodo_pago' => $formaPago,
                'comentarios' => $comentario,
                'comprobante' => $comprobante,
                'saldo_restante' => $nuevoSaldo,
                'estado_pago' => 'Recibido'
            ]);

            // Registrar el comprobante de pago si se ha subido un archivo
            if ($request->hasFile('abonoimagen')) {
                $elemento = Cloudinary::upload($request->file('abonoimagen')->getRealPath(), ['folder' => 'pagos']);
                $pago->imagen()->create([
                    'url' => $elemento->getSecurePath(),
                    'public_id' => $elemento->getPublicId(),
                ]);

                // $this->guardarComprobante($request->file('abonoimagen'), $pago);
            }

            DB::commit();

            Alert::toast('Pago registrado correctamente', 'success');
            return redirect()->route('cliente.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Hubo un problema al registrar el pago: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Función para guardar el comprobante de pago en Cloudinary
     */
    protected function guardarComprobante($imagen, $pago)
    {
        $elemento = Cloudinary::upload($imagen->getRealPath(), ['folder' => 'pagos']);
        $pago->imagen()->create([
            'url' => $elemento->getSecurePath(),
            'public_id' => $elemento->getPublicId(),
        ]);
    }



    public function agregarMora(Request $request)
    {
        $mora = $request->input('mora');
        $credito_id = $request->input('credito_id');

        // Validación de entrada
        if (!is_numeric($mora) || $mora <= 0) {
            Alert::error('Error', 'El valor de la mora debe ser mayor a cero.');
            return back();
        }

        $credito = KardexCliente::find($credito_id);

        if (!$credito) {
            Alert::error('Error', 'El crédito del cliente no fue encontrado.');
            return back();
        }

        $saldo_pendiente = $credito->saldo_pendiente;

        try {
            DB::transaction(function () use ($credito, $saldo_pendiente, $mora) {
                // Actualizar el saldo pendiente con mora
                $credito->update([
                    'saldo_pendiente_mora' => $saldo_pendiente + $mora
                ]);

                // Buscar si ya existe una mora activa
                $moraExistente = Mora::where('kardex_cliente_id', $credito->id)
                                    ->where('estado', 'A')
                                    ->latest('fecha_generacion')
                                    ->first();

                $dias_mora = $moraExistente ? $moraExistente->dias_mora + 1 : 1;

                // Registrar nueva mora
                Mora::create([
                    "kardex_cliente_id" => $credito->id,
                    "fecha_generacion" => Carbon::now(),
                    "dias_mora" => $dias_mora,
                    "saldo_pendiente" => $saldo_pendiente,
                    "interes_generado" => $mora,
                    "estado" => 'A',
                ]);
            });

            Alert::toast('Crédito actualizado', 'success');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo registrar la mora. Inténtalo de nuevo.');
        }

        return back();
    }




    public function pagosByArticulo($client_id, $kardex_id)
    {
          // Obtener el cliente por su ID
            $cliente = Cliente::find($client_id);

            if (!$cliente) {
                Alert::toast('Cliente no encontrado','error');
                return redirect()->back();
                // return response()->json(['message' => 'Cliente no encontrado'], 404);
            }

            // Buscar el registro de kardex del cliente por el ID proporcionado
            $kardexCliente = $cliente->kardexClientes()
                                    ->where('id', $kardex_id)
                                    ->with('historialPagos')
                                    ->first();

            // dd($kardexCliente,$kardexCliente->historialPagos);
            $historialPagos = $kardexCliente->historialPagos;

            if (!$kardexCliente) {
                Alert::toast('Compra no encontrada','error');
                return redirect()->back();
            }

            return view('pagos.pagos-by-articulo',compact('historialPagos'));

            // Devolver el historial de pagos asociado a esta compra
            // return response()->json([
            //     'kardex_cliente' => $kardexCliente,
            //     'historial_pagos' => $kardexCliente->historialPagos
            // ]);

    }

    public function comprasByCliente(Request $request)
    {
        // dd($request);
        $identificacion = $request->get('identificacion');
        $status = true;

        // Encuentra el cliente por su identificación y carga todas las relaciones necesarias
        $cliente = Cliente::where('identificacion', $identificacion)
            ->with([
                'kardexClientes.venta.detalles.inventario.producto',
                'kardexClientes.historialPagos' // Incluir historial de pagos
            ])
            ->first();

        // Verificar si se encontró el cliente
        if (!$cliente) {
            return response()->json(['status'=>false,'message' => 'Cliente no encontrado'], 404);
        }

        // Obtener la información de kardexClientes, ventas y pagos
        $kardexVentas = $cliente->kardexClientes->map(function ($kardexCliente) {
            $venta = $kardexCliente->venta;

            // Obtener los pagos relacionados con este kardexCliente
            $historialPagos = $kardexCliente->historialPagos->map(function ($pago) {
                return [
                    'monto_pagado' => $pago->monto_pagado,
                    'fecha_pago' => $pago->fecha_pago,
                    'metodo_pago' => $pago->metodo_pago,
                    'comentarios' => $pago->comentarios,
                    'comprobante' => $pago->comprobante,
                    'saldo_restante' => $pago->saldo_restante,
                    'estado_pago' => $pago->estado_pago,
                ];
            });

            return [
                'kardex_cliente' => [
                    'id' => $kardexCliente->id,
                    'fecha_compra' => $kardexCliente->fecha_compra,
                    'monto_total' => $kardexCliente->monto_total,
                    'entrada' => $kardexCliente->entrada,
                    'num_cuotas' => $kardexCliente->num_cuotas,
                    'monto_cuota' => $kardexCliente->monto_cuota,
                    'saldo_pendiente' => $kardexCliente->saldo_pendiente,
                    'estado' => $kardexCliente->estado,
                    'interes' => $kardexCliente->interes,
                    'fecha_vencimiento' => $kardexCliente->fecha_vencimiento
                ],
                'venta' => $venta ? [
                    'id' => $venta->id,
                    'total' => $venta->total,
                    'estado' => $venta->estado,
                    'comentario' => $venta->comentario,
                    'created_at' => $venta->created_at,
                ] : null,

                'detalles' => $venta ? $venta->detalles->map(function ($detalle) {
                    return [
                        'producto' => $detalle->inventario->producto->nombre ?? 'Producto desconocido', // Nombre del producto
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $detalle->precio_unitario,
                        'total' => $detalle->cantidad * $detalle->precio_unitario
                    ];
                }) : [],

                'historial_pagos' => $historialPagos, // Incluir los pagos
            ];
        })->filter(); // Filtramos para eliminar posibles kardexClientes sin venta asociada


        // Retorna el cliente, sus kardexClientes y las ventas con detalles y productos en la respuesta
        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'apellidos' => $cliente->apellidos,
                'identificacion' => $cliente->identificacion,
                'direccion' => $cliente->direccion,
                'telefono' => $cliente->telefono,
            ],
            'kardexVentas' => $kardexVentas,
            'status'=>$status
        ]);
    }

    public function actualizarPago(Request $request)
    {
        // dd($request);
        $pago_id = $request->get('pago_id');
        $estado = $request->get('estado');
        $comentarios = $request->get('comentarios');

        // Validar que se haya enviado el archivo y que sea válido
        if ($request->hasFile('imagenabono') && $request->file('imagenabono')->isValid()) {
            $imagen = $request->file('imagenabono');

            // Buscar el pago por ID
            $pago = HistorialPago::find($pago_id);

            if ($pago) {
                try {
                    // Subir el archivo a Cloudinary
                    $elemento = Cloudinary::upload($imagen->getRealPath(), ['folder' => 'pagos']);

                    // Guardar la imagen en la base de datos
                    $pago->imagen()->create([
                        'url' => $elemento->getSecurePath(),
                        'public_id' => $elemento->getPublicId(),
                    ]);

                    // Actualizar estado y comentarios del pago
                    $pago->update([
                        'estado_pago' => $estado,
                        'comentarios' => $comentarios
                    ]);

                    Alert::toast('Imagen y estado del pago actualizados correctamente', 'success');
                    return redirect()->back();
                } catch (\Exception $e) {
                    Alert::toast('Error al actualizar el pago: ' . $e->getMessage(), 'warning');
                    return redirect()->back();
                }

            } else {
                Alert::toast('Pago no encontrado', 'warning');
                return redirect()->back();
            }
        } else {
            Alert::toast('La imagen o archivo cargado no es válido', 'warning');
            return redirect()->back();
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(HistorialPago $historialPago)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HistorialPago $historialPago)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHistorialPagoRequest $request, HistorialPago $historialPago)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HistorialPago $historialPago)
    {
        //
    }
}
