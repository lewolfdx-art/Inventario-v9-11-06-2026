<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta - {{ $producto->sku }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .etiqueta {
            width: 240px;
            padding: 12px 15px;
            text-align: center;
            border: 1px dashed #aaa;
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin: auto;
        }
        .titulo {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a3c6e;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .divider {
            border-top: 1px dashed #ccc;
            margin: 5px 0;
        }
        .nombre {
            font-size: 12px;
            font-weight: bold;
            margin: 4px 0 2px 0;
            color: #222;
            line-height: 1.3;
        }
        .sku {
            font-size: 9px;
            color: #666;
            margin-bottom: 4px;
        }
        .barcode {
            margin: 4px 0;
            padding: 4px;
            background: white;
        }
        .barcode img {
            width: 100%;
            max-width: 180px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .marca {
            font-size: 9px;
            color: #555;
            margin-top: 3px;
        }
        .serie {
            font-size: 8px;
            color: #888;
            margin-top: 2px;
        }
        .footer {
            font-size: 7px;
            color: #aaa;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #eee;
        }
    </style>
</head>
<body>
    <div class="etiqueta">
        <div class="titulo">SISTEMA DE INVENTARIO</div>
        <div class="divider"></div>

        <div class="nombre">{{ $producto->nombre }}</div>
        <div class="sku"><strong>SKU:</strong> {{ $producto->sku }}</div>

        <div class="divider"></div>

        <div class="barcode">
            <img src="data:image/png;base64,{{ $barcodeImage }}" alt="Código de barras">
        </div>

        <div class="marca">
            {{ $producto->marca->nombre ?? '' }} {{ $producto->modelo ?? '' }}
        </div>
        @if($producto->serie)
        <div class="serie">Serie: {{ $producto->serie }}</div>
        @endif

        <div class="divider"></div>

        <div class="footer">
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>