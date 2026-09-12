<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; }
        h1 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr><th>Producto</th><th>Cantidad</th><th>Precio</th></tr>
        </thead>
        <tbody>
            <tr><td>Artículo ñ</td><td>10</td><td>$ 25.50</td></tr>
            <tr><td>Artículo á</td><td>5</td><td>$ 100.00</td></tr>
        </tbody>
    </table>
</body>
</html>