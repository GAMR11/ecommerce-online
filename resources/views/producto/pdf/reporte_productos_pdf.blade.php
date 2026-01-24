<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            font-weight: bold;
        }
        .center {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <h1>Reporte de Productos</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Color</th>
                <th>Precio Original</th>
                <th>Precio Contado</th>
                <th>Precio Credito</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Fecha Creación</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productos as $producto)
                <tr>
                    <td>{{ $producto->id }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->marca }}</td>
                    <td>{{ $producto->modelo }}</td>
                    <td>{{ $producto->color }}</td>
                    <td>{{ $producto->precio_original }}</td>
                    <td>{{ $producto->precio_contado }}</td>
                    <td>{{ $producto->precio_credito }}</td>
                    <td>{{ $producto->categoria_nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $producto->total_cantidad }}</td>
                    <td>{{ $producto->created_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
