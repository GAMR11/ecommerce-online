<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación: Clientes atrasados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 5px;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 150px;
            height: auto;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            color: #d9534f;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            font-size: 16px;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #888;
            text-align: center;
        }
        .imagenes img {
            max-width: 100%;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo">
            <img src="https://res.cloudinary.com/pasionreal/image/upload/v1735939012/comercial_gusmor_logo_mgrjxz.png" alt="Logo Comercial Gusmor">
        </div>

        <div class="header">
            Notificación: Clientes atrasados
        </div>

        {{-- @if(!empty($producto)) --}}

        <div class="content">
            @if($cantidad == 0)
                <p>No hay clientes atrasados.</p>
            @else
                @foreach($clientesAtrasados as $cliente)
                    <p><strong>Identificación:</strong> {{ $cliente->identificacion }}</p>
                    <p><strong>Nombre:</strong> {{ $cliente->nombre }}</p>
                    <p><strong>Apellido:</strong> {{ $cliente->apellidos }}</p>
                        @foreach($cliente->creditos as $keyCredito => $credito)
                            <p>Credito # {{ $keyCredito+1 }}</p>
                            <p>Cuota mensual de pago: ${{ $credito['credito']->monto_cuota }}</p>
                            <p>Fechas esperadas sin pago</p>
                            <li>
                                @foreach($credito['meses_sin_pago'] as $mesSinPago)
                                    <ul>{{$mesSinPago}}</ul>
                                @endforeach
                            </li>
                            <p>Articulos comprados</p>
                            <li>
                                @foreach($credito['credito_detalle'] as $detalle)
                                    <ul>{{$detalle->inventario->producto->nombre}} - {{$detalle->inventario->producto->marca}} - {{$detalle->inventario->producto->modelo}} - {{$detalle->inventario->producto->precio_credito}} - {{$detalle->inventario->numero_serie}}</ul>
                                @endforeach
                            </li>
                        @endforeach
                    <br>
                @endforeach
            @endif
        </div>
        {{-- @else
        <div class="content">
            <p>No se encontraron productos sin stock.</p>
        </div>
        @endif --}}
    </div>

        <div class="footer">
            <p>Este es un mensaje automático. No responda este correo.</p>
        </div>
    </div>

</body>
</html>
