<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\KardexCliente;
use App\Models\Cliente;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index()
    {
        $pagos = Pago::with('cliente')->orderBy('fecha_pago', 'desc')->get();
        return view('pago.index', compact('pagos'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        return view('pagos.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'monto_pagado' => 'required|numeric|min:0',
            'tipo_pago' => 'required|string',
            'fecha_pago' => 'required|date',
        ]);

        // Buscar Kardex para actualizar el saldo
        $kardexCliente = KardexCliente::where('cliente_id', $request->cliente_id)->latest()->first();

        // Calcular el saldo restante
        $nuevoSaldo = $kardexCliente->saldo_pendiente - $request->monto_pagado;
        $kardexCliente->update(['saldo_pendiente' => $nuevoSaldo]);

        // Crear el pago
        Pago::create([
            'cliente_id' => $request->cliente_id,
            'kardex_cliente_id' => $kardexCliente->id ?? null,
            'tipo_pago' => $request->tipo_pago,
            'comprobante' => $request->comprobante,
            'monto_pagado' => $request->monto_pagado,
            'saldo_restante' => $nuevoSaldo,
            'fecha_pago' => $request->fecha_pago,
            'comentarios' => $request->comentarios,
        ]);

        return redirect()->route('pagos.index')->with('success', 'Pago registrado exitosamente.');
    }
}
