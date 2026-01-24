<?php

namespace App\Console\Commands;

// use App\Models\Anuncio;
use Carbon\Carbon;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\KardexCliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Input\InputOption;
use App\Console\Commands\Alerts\notiReposicionStock;

class notificacionEstadoClientes extends Command
{
    protected $signature = 'command:notificacionestadoclientes';

    protected $description = 'Comando para el envío de notificación por correo al administrador para conocer que clientes estan al día y que clientes estan atrasados en sus pagos.';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('command:notificacionestadoclientes')
            ->setDescription('Ejecuta la notificación del estado de clientes.')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Asunto del reporte')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Correo destinatario')
            ->addOption('cc', null, InputOption::VALUE_OPTIONAL, 'Correo en copia')
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED, 'Fecha de inicio')
            ->addOption('endDate', null, InputOption::VALUE_REQUIRED, 'Fecha de fin');
    }

    public function handle()
    {

        $creditos = KardexCliente::all();
        $clientesAtrasados = new Collection();

        foreach ($creditos as $credito)
        {
            if ($credito->saldo_pendiente > 0) {
                $numeroMeses = $credito->num_cuotas;
                $fechaCompra = Carbon::parse($credito->fecha_compra);
                $mesesProximosPago = [];
                $hoy = Carbon::now(); // Fecha actual

                $mesesProximosPago[] = $fechaCompra;

                for ($i = 1; $i <= $numeroMeses; $i++) {
                    $mesProximoPago = $fechaCompra->copy()->addMonths($i);
                    if ($mesProximoPago->lessThanOrEqualTo($hoy)) {
                        $mesesProximosPago[] = $mesProximoPago;
                    }
                }

                foreach ($mesesProximosPago as $mesProximo) {
                    $fechaPagoMes = $mesProximo->copy();
                    $fechaInicioMes = $mesProximo->startOfMonth();

                    $existePago = DB::table('historial_pagos')
                        ->where('cliente_id', $credito->cliente_id)
                        ->whereBetween('fecha_pago', [$fechaInicioMes, $fechaPagoMes])
                        ->where('kardex_cliente_id', $credito->id)
                        ->exists();

                    if (!$existePago) {
                        $cliente = $credito->cliente;

                        // Verifica si el cliente ya ha sido agregado antes
                        if (!$clientesAtrasados->contains('id', $cliente->id)) {
                            $cliente->setAttribute('creditos', []);
                            $clientesAtrasados->push($cliente);
                        }

                        // Encuentra el cliente en la colección
                        $clientesAtrasados->each(function ($c) use ($cliente, $credito, $fechaPagoMes) {
                            if ($c->id === $cliente->id) {
                                // Obtener los créditos actuales del cliente
                                $creditos = $c->getAttribute('creditos');

                                // Buscar si el crédito ya existe en la lista
                                $index = array_search($credito->id, array_column($creditos, 'credito_id'));

                                if ($index === false) {
                                    // Si el crédito no está en la lista, agregarlo con índice consecutivo
                                    $index = count($creditos);
                                    $creditos[$index] = [
                                        'credito_id' => $credito->id,
                                        'meses_sin_pago' => [],
                                        'credito'=>$credito,
                                        'credito_detalle'=>$credito->venta->detalles
                                    ];
                                }

                                // Agregar la fecha al crédito correspondiente
                                $creditos[$index]['meses_sin_pago'][] = $fechaPagoMes->format('Y-m-d');

                                // Ordenar las fechas dentro del crédito
                                sort($creditos[$index]['meses_sin_pago']);

                                // Guardar los créditos ordenados en el cliente
                                $c->setAttribute('creditos', $creditos);
                            }
                        });
                    }
                }
            }
        }

         Mail::send(
                        'mail.notificacionEstadoClientes',  // Nombre de la vista Blade
                        ['clientesAtrasados' => $clientesAtrasados,'cantidad'=>$clientesAtrasados->count()],   // Datos que quieres pasar a la vista
                        function ($message) {
                            $message->to('gamr130898@gmail.com')
                                    ->subject('Clientes atrasados');
                        }
                    );

        dd('clientes atrasados:',$clientesAtrasados);
    }


}
