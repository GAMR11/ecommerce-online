<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación: Producto sin stock</title>
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
            Notificación: Producto sin stock
        </div>

        @if(!empty($producto))
        <div class="content">
            <p><strong>Nombre:</strong> {{ $producto->nombre }}</p>
            <p><strong>Marca:</strong> {{ $producto->marca }}</p>
            <p><strong>Modelo:</strong> {{ $producto->modelo }}</p>
            <p><strong>Color:</strong> {{ $producto->color }}</p>
            <p><strong>Descripción:</strong> {{ $producto->descripcion }}</p>
            <p><strong>Precio Original:</strong> ${{ number_format($producto->precio_original, 2) }}</p>
            <p><strong>Precio al Contado:</strong> ${{ number_format($producto->precio_contado, 2) }}</p>
            <p><strong>Precio a Crédito:</strong> ${{ number_format($producto->precio_credito, 2) }}</p>
            <p><strong>Categoría:</strong> {{ $producto->categoria->nombre ?? 'Sin categoría' }}</p>

            <div class="imagenes">
                <strong>Imágenes del producto:</strong>
                @if($producto->imagenes->isNotEmpty())
                    @foreach($producto->imagenes as $imagen)
                        <img src="{{ $imagen->url }}" alt="Imagen del producto">
                    @endforeach
                @else
                    <p>No hay imágenes disponibles.</p>
                @endif
            </div>
        </div>
        @else
        <div class="content">
            <p>No se encontraron productos sin stock.</p>
        </div>
    </div>
        @endif

        <div class="footer">
            <p>Este es un mensaje automático. No responda este correo.</p>
        </div>
    </div>

</body>
</html>
