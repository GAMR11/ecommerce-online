<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 50%;
            max-width: 1000px;
            margin: 0 auto;
            /* margin-top:0px; */
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 0px;
            padding-bottom: 0px;
        }

        .header .logo {
            width: 150px;
            margin-bottom: 0px;
            padding-bottom: 0px;
        }

        .header .recibo-numero {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
        }

        /* Diseño de la sección de información */
        .info {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .info .item {
            width: 100%; /* Ajustamos para que las etiquetas y valores estén juntos */
            margin-bottom: 10px;
        }

        .info .item p {
            margin: 0;
        }

        .item-label {
            /* font-weight: bold; */
        }

        .table {
            width: 60%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #f4f4f4;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
        }

        .signature-table td {
            text-align: center;
            padding: 20px;
        }

        /* Ajuste para la firma: hacer la línea más corta */
        .firma {
            width: 120px; /* Reduce el ancho de la línea */
            height: 10px;
            border-top: 1px solid #000;
            margin: 0 auto; /* Centra la línea en la celda */
        }

        /* Reduce el ancho de las celdas */
        .signature-table td {
            width: 150px; /* Ajusta el ancho de las celdas que contienen las firmas */
        }

        .terms {
            margin-top: 30px;
            font-size: 12px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
        }

        .raya
        {
            color:black;

        }

        .content
        {
            margin-bottom: 0px;
            padding-bottom: 0px;
            margin-top: 0px;
            padding-top: 0px;
        }

    </style>
</head>
<body>

    <div class="container">

        <!-- Header -->
        <div class="header">
            <div class="logo">
                <table class="signature-table">
                    <tr>
                        <td>
                            <img style="max-width:100px;" src="{{ public_path().'/assets/img/comercial_gusmor_logo.png' }}" alt="Comercial Gusmor">
                        </td>
                        <td>
                            <div style="width: 4px; height:1px; background-color: black;"></div>
                        </td>
                        <td>
                            <h4>Comercial Gusmor</h4>
                        </td>
                        <td>
                            <div style="width: 4px; height: 1px; background-color: black;"></div>
                        </td>
                        <td style="width:100%;">
                            <strong style="text-align:left;">RECIBO {{ $numero_recibo }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="text-align: center;">
                            Dir. Av 29 de Junio s/n (Diagonal a la iglesia Católica).
                            <br>
                            Cell: 0994 962 584 - Pedro Vicente Maldonado - Ecuador
                            <br>
                            Morales Peñafiel Jaime Gustavo
                            <br>
                            RUC. 0101516904001
                        </td>
                    </tr>
                </table>

            </div>
            {{-- <div>

            </div> --}}
            {{-- <div class="logo">
                <img style="max-width:200px;" src="{{ public_path().'/assets/img/comercial_gusmor_logo.png' }}" alt="Comercial Gusmor">
               <div style="width: 2px; height: 100px; background-color: black;"></div>
            </div> --}}
            {{-- <div class="recibo-numero">
                <strong>N° {{ $numero_recibo }}</strong>
            </div> --}}
        </div>

        <!-- Recibo de pago -->
        <div class="content">
            {{-- <div class="section-title">Información del recibo</div> --}}

            <!-- Información organizada en flexbox -->
            <div class="info">
                <div class="item">
                    <p class="item-label">Recibe de <strong>{{ $cliente }}</strong> por concepto de <strong>{{ $articulos }}</strong> un total de <strong>{{ $abono }}</strong> Dólares.</p>
                </div>
                <div class="item">
                    <strong class="item-label">Fecha del pago: {{ $fecha_pago }}</strong>
                    {{-- <p>{{ $fecha_pago }}</p> --}}
                </div>
            </div>

            <!-- Tabla para los saldos -->
            {{-- <div class="section-title">Saldo Actual</div> --}}
            <table class="table">
                <tr>
                    <th>TOTAL</th>
                    <td>${{ $saldo_anterior }}</td>
                </tr>
                <tr>
                    <th>ABONO</th>
                    <td>${{ $abono }}</td>
                </tr>
                <tr>
                    <th>SALDO</th>
                    <td>${{ $saldo_nuevo }}</td>
                </tr>
            </table>
        </div>

        <!-- Firmas (usando tabla para alinearlas horizontalmente) -->
        <table class="signature-table">
            <tr>
                <td>
                    <div class="firma"></div>
                    <strong>Firma Cliente</strong>
                </td>
                <td>
                    <div class="firma"></div>
                    <strong>Firma Autorizada</strong>
                </td>
            </tr>
        </table>

        <!-- Términos y condiciones -->
        <div class="terms">
            <p><strong>Términos y Condiciones:</strong></p>
            <ul>
                <li>Salida la mercadería no se acepta cambio ni devoluciones.</li>
                {{-- <li>Los pagos realizados después de la fecha indicada serán sujetos a recargos.</li>
                <li>El saldo pendiente debe ser abonado dentro de los próximos 30 días.</li> --}}
            </ul>
        </div>

        <!-- Footer -->
        {{-- <div class="footer">
            <p>Gracias por su preferencia. ¡Nos complace haberle servido!</p>
        </div> --}}

    </div>

</body>
</html>
