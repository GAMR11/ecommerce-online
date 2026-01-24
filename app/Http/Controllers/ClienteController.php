<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\HistorialPago;
use App\Models\KardexCliente;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Cliente::all();
        return view('cliente.index',compact('clientes'));
    }


    public function detalleCredito($id)
    {
        $credito = KardexCliente::find($id);
        // dd($credito);
        $cliente = $credito->cliente;
        $venta = $credito->venta;

        $historialPagos = $credito->historialPagos()->with('imagen')->get();

        // dd(HistorialPago::all());
         // Formatear la fecha antes de enviarla a la vista
        $fechaPago = Carbon::parse($credito->fecha_compra)->format('d') . ' de cada mes';
        // dd($historialPagos);
        // foreach($historialPagos as $key => $hp)
        // {
        //     if(isset($hp->imagen[0]))
        //         dump($hp->imagen[0]->url);
        // }
        // die;
        // dd($historialPagos);
        return view('creditos.detalle',compact('credito','cliente','venta','historialPagos','fechaPago'));
    }

    public function creditos($id)
    {
        $cliente = Cliente::find($id);
        $creditos = $cliente->kardexClientes()->get();
        $creditosVigentes = new Collection();
        $creditosFinalizados = new Collection();

        foreach ($creditos as $credito)
        {
            if ($credito->saldo_pendiente > 0)
            {
                $numeroMeses = $credito->num_cuotas;
                $fechaCompra = Carbon::parse($credito->fecha_compra);
                $fechaCompraCopy = $fechaCompra->copy();
                $fechaUltimoPago = $fechaCompraCopy->addMonths($numeroMeses);

                $mesesProximosPago = [];
                $hoy = Carbon::now();

                // Agregar la fecha de compra
                $mesesProximosPago[] = $fechaCompra->copy();
                $mesesProximosPago2[] = $fechaCompra->copy();


                // Calcular las fechas de pago esperadas
                for ($i = 1; $i <= $numeroMeses; $i++)
                {
                    $mesProximoPago = $fechaCompra->copy()->addMonths($i);
                    if ($mesProximoPago->lessThanOrEqualTo($fechaUltimoPago))
                    {
                        $mesesProximosPago[] = $mesProximoPago;
                    }
                }



                // Validar las fechas de pago y obtener detalles
                $detalleCredito = $this->validarCredito($credito, $mesesProximosPago);
                $creditosVigentes->push($detalleCredito);
            }else if($credito->saldo_pendiente <= 0)
            {
                $numeroMeses = $credito->num_cuotas;
                $fechaCompra = Carbon::parse($credito->fecha_compra);
                $fechaCompraCopy = $fechaCompra->copy();
                $fechaUltimoPago = $fechaCompraCopy->addMonths($numeroMeses);


                $mesesProximosPago = [];
                $hoy = Carbon::now();

                // Agregar la fecha de compra
                $mesesProximosPago[] = $fechaCompra->copy();
                $mesesProximosPago2[] = $fechaCompra->copy();


                // Calcular las fechas de pago esperadas
                for ($i = 1; $i <= $numeroMeses; $i++)
                {
                    $mesProximoPago = $fechaCompra->copy()->addMonths($i);
                    if ($mesProximoPago->lessThanOrEqualTo($fechaUltimoPago))
                    {
                        $mesesProximosPago[] = $mesProximoPago;
                    }
                }

                // Validar las fechas de pago y obtener detalles
                $detalleCredito = $this->validarCredito($credito, $mesesProximosPago);
                $creditosFinalizados->push($detalleCredito);
            }
        }

        // dd($infoCreditos);

        return view('creditos.table',compact('creditosVigentes','creditosFinalizados','cliente'));

        // return response()->json($infoCreditos); // Retornar en formato JSON para verificar
    }

    // public function validarCredito($credito, $mesesPago): array
    // {
    //     $cantidadPagos = count($mesesPago);
    //     $fechaHoy = Carbon::now();
    //     $mesActual = $fechaHoy->month;

    //     $detallesCredito = [
    //         'credito' => $credito,
    //         'fechas_pagas' => [],
    //         'fechas_impagas' => [],
    //         'pagos' => '',
    //         'proximoPago' => '',
    //         'estado_credito' => 'Atrasado'
    //     ];

    //     $pagoMesActual = false;
    //     $hayPagosFuturos = false;

    //     foreach ($mesesPago as $mesProximo) {
    //         $fechaPagoMes = $mesProximo->copy();
    //         $fechaInicioMes = $mesProximo->copy()->startOfMonth();

    //         $existePago = DB::table('historial_pagos')
    //             ->where('cliente_id', $credito->cliente_id)
    //             ->whereBetween('fecha_pago', [$fechaInicioMes, $fechaPagoMes])
    //             ->where('kardex_cliente_id', $credito->id)
    //             ->exists();

    //         if ($existePago) {
    //             $detallesCredito['fechas_pagas'][] = $fechaPagoMes->toDateString();

    //             // Verificar si el pago es del mes actual
    //             if ($fechaPagoMes->month == $mesActual) {
    //                 $pagoMesActual = true;
    //             }
    //         } else {
    //             $detallesCredito['fechas_impagas'][] = $fechaPagoMes->toDateString();

    //             // Verificar si hay pagos pendientes en el futuro
    //             if ($fechaPagoMes->month > $mesActual) {
    //                 $hayPagosFuturos = true;
    //             }
    //         }
    //     }

    //     // 🔹 Si ha pagado el mes actual y tiene pagos futuros, está "Al día"
    //     if ($pagoMesActual && $hayPagosFuturos) {
    //         $detallesCredito['estado_credito'] = 'Al día';
    //     }
    //     // 🔹 Si ha pagado todas sus cuotas
    //     elseif (count($detallesCredito['fechas_pagas']) == $cantidadPagos) {
    //         $detallesCredito['estado_credito'] = 'Al día';
    //     }

    //     // Formato de pagos realizados
    //     $detallesCredito['pagos'] = count($detallesCredito['fechas_pagas']) . '/' . $cantidadPagos;

    //     return $detallesCredito;
    // }



    /**
     * Show the form for creating a new resource.
     */
    // public function validarCredito($credito, $mesesPago): array
    // {
    //     $cantidadPagos = count($mesesPago);
    //     $fechaHoy = Carbon::now();
    //     $mesActual = $fechaHoy->month;

    //     $detallesCredito = [
    //         'credito' => $credito,
    //         'fechas_pagas' => [],
    //         'fechas_impagas' => [],
    //         'pagos' => '',
    //         'proximo_pago' => '',
    //         'estado_credito' => 'Atrasado'
    //     ];

    //     $pagoMesActual = false;
    //     $hayPagosFuturos = false;

    //     foreach ($mesesPago as $keys => $mesProximo) {
    //         $fechaPagoMes = $mesProximo->copy();
    //         $fechaInicioMes = $mesProximo->copy()->startOfMonth();

    //         $existePago = DB::table('historial_pagos')
    //             ->where('cliente_id', $credito->cliente_id)
    //             ->whereBetween('fecha_pago', [$fechaInicioMes, $fechaPagoMes])
    //             ->where('kardex_cliente_id', $credito->id)
    //             ->get();

    //             dd($existePago);

    //             if($existePago)
    //                 dump("encontro:",$keys);

    //         if ($existePago) {
    //             $detallesCredito['fechas_pagas'][] = $fechaPagoMes->toDateString();

    //             if ($fechaPagoMes->month == $mesActual) {
    //                 $pagoMesActual = true;
    //             }
    //         } else {
    //             $detallesCredito['fechas_impagas'][] = $fechaPagoMes->toDateString();

    //             if ($fechaPagoMes->month > $mesActual) {
    //                 $hayPagosFuturos = true;
    //             }
    //         }
    //     }
    //     die;

    //     dd($detallesCredito);

    //     // 🔹 Si ha pagado el mes actual y tiene pagos futuros, está "Al día"
    //     if ($pagoMesActual && $hayPagosFuturos) {
    //         $detallesCredito['estado_credito'] = 'Al día';
    //     }
    //     // 🔹 Si ha pagado todas sus cuotas
    //     elseif (count($detallesCredito['fechas_pagas']) == $cantidadPagos) {
    //         $detallesCredito['estado_credito'] = 'Al día';
    //     }

    //     // 🔹 Buscar el próximo pago (el primer mes impago después del actual)
    //     foreach ($detallesCredito['fechas_impagas'] as $fechaImpaga) {
    //         $fechaImpagaCarbon = Carbon::parse($fechaImpaga);
    //         if ($fechaImpagaCarbon->month >= $mesActual) {
    //             $detallesCredito['proximo_pago'] = $fechaImpagaCarbon->toDateString();
    //             break;
    //         }
    //     }

    //     // Formato de pagos realizados
    //     $detallesCredito['pagos'] = count($detallesCredito['fechas_pagas']) . '/' . $cantidadPagos;

    //     return $detallesCredito;
    // }

    public function validarCredito($credito, $mesesPago): array
    {
        $cantidadPagos = count($mesesPago);
        $fechaHoy = Carbon::now();
        $mesActual = $fechaHoy->month;

        $detallesCredito = [
            'credito' => $credito,
            'fechas_pagas' => [],
            'fechas_impagas' => [],
            'pagos' => '',
            'proximo_pago' => '',
            'estado_credito' => 'Atrasado'
        ];

        $pagoMesActual = false;
        $hayPagosFuturos = false;

        foreach ($mesesPago as $keys => $mesProximo) {
            $fechaPagoMes = $mesProximo->copy();
            $fechaInicioMes = $mesProximo->copy()->startOfMonth();
            $fechaFinMes = $mesProximo->copy()->endOfMonth();

            // Contar cuántos pagos se han hecho en el mes
            $cantidadPagosMes = DB::table('historial_pagos')
                ->where('cliente_id', $credito->cliente_id)
                ->whereBetween('fecha_pago', [$fechaInicioMes, $fechaFinMes])
                ->where('kardex_cliente_id', $credito->id)
                ->count(); // 🔹 En lugar de get(), usamos count() para saber cuántos pagos hay

            if ($cantidadPagosMes > 0) {
                $detallesCredito['fechas_pagas'][] = [
                    'mes' => $fechaPagoMes->format('Y-m'),
                    'cantidad' => $cantidadPagosMes
                ];

                if ($fechaPagoMes->month == $mesActual) {
                    $pagoMesActual = true;
                }
            } else {
                $detallesCredito['fechas_impagas'][] = $fechaPagoMes->toDateString();

                if ($fechaPagoMes->month > $mesActual) {
                    $hayPagosFuturos = true;
                }
            }
        }

        // 🔹 Determinar el estado del crédito
        if ($pagoMesActual && $hayPagosFuturos) {
            $detallesCredito['estado_credito'] = 'Al día';
        } elseif (count($detallesCredito['fechas_pagas']) == $cantidadPagos) {
            $detallesCredito['estado_credito'] = 'Al día';
        }

        // 🔹 Buscar el próximo pago (el primer mes impago después del actual)
        foreach ($detallesCredito['fechas_impagas'] as $fechaImpaga) {
            $fechaImpagaCarbon = Carbon::parse($fechaImpaga);
            if ($fechaImpagaCarbon->month >= $mesActual) {
                $detallesCredito['proximo_pago'] = $fechaImpagaCarbon->toDateString();
                break;
            }
        }

        // 🔹 Contar total de pagos realizados
        $totalPagosRealizados = array_sum(array_column($detallesCredito['fechas_pagas'], 'cantidad'));
        $detallesCredito['pagos'] = $totalPagosRealizados . '/' . $cantidadPagos;
        // dd($detallesCredito);
        return $detallesCredito;
    }


     public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClienteRequest $request)
    {
        $nombre = $request->nombre;
        $apellidos = $request->apellidos;
        $identificacion = $request->identificacion;
        $telefono = $request->telefono;
        $direccion = $request->direccion;

        Cliente::create([
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'identificacion' => $identificacion,
            'telefono' => $telefono,
            'direccion' => $direccion,
        ]);

        Alert::toast("Cliente registrado con exito", 'success');
        return redirect()->back();
    }

    public function buscarByIdentificacion(Request $request)
    {
        $identificacion = $request->get('identificacion');
        $cliente = Cliente::where('identificacion',$identificacion)->first();
        return response()->json([
            'cliente' => $cliente
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClienteRequest $request, $id)
    {
        $cliente = Cliente::find($id);
        $cliente->update([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'direccion' => $request->direccion,
            'identificacion' => $request->identificacion,
            'telefono' => $request->telefono
        ]);

        Alert::toast("Cliente actualizado con exito", 'success');
        return redirect()->route('cliente.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cliente = Cliente::find($id);
        // dd($cliente);
        $cliente->delete();
        Alert::toast("Cliente eliminado con exito", 'success');
        return redirect()->back();
    }

    public function formUploadFile()
    {
        return view('cliente.cliente-upload');
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

                $cliente = Cliente::where('identificacion', $identificacion)->first();

                if (!$cliente) {
                    $cliente = Cliente::create([
                        'identificacion' => $identificacion,
                        'nombre' => $nombres,
                        'apellidos' => $apellidos,
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                    ]);
                }
            }

            DB::commit();
            Alert::toast('Clientes subidos correctamente.', 'success');
            return redirect()->route('inventario.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::toast('Error al procesar la carga: ' . $e->getMessage(), 'error');
            return redirect()->back();
        }
    }
}
