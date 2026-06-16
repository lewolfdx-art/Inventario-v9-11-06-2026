<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiqueta ZPL - {{ $producto->sku }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .etiqueta {
            width: 4in; /* 4 pulgadas */
            height: 2in; /* 2 pulgadas */
            background: white;
            padding: 15px;
            border: 2px solid #333;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            font-size: 10px;
            font-family: Arial, sans-serif;
        }
        .titulo {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .campo {
            margin: 3px 0;
            font-size: 10px;
        }
        .campo .label {
            font-weight: bold;
        }
        .barcode {
            text-align: center;
            margin: 8px 0;
            padding: 8px;
            background: white;
            font-family: 'Courier New', monospace;
            font-size: 20px;
            letter-spacing: 2px;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #666;
            margin-top: 5px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="etiqueta">
        <div class="titulo">INVENTARIO</div>
        
        <div class="campo">
            <span class="label">Producto:</span> {{ $producto->nombre }}
        </div>
        <div class="campo">
            <span class="label">SKU:</span> {{ $producto->sku }}
        </div>
        <div class="campo">
            <span class="label">Marca:</span> {{ $producto->marca->nombre ?? 'N/A' }}
        </div>
        <div class="campo">
            <span class="label">Modelo:</span> {{ $producto->modelo }}
        </div>
        <div class="campo">
            <span class="label">Serie:</span> {{ $producto->serie ?? 'N/A' }}
        </div>
        
        <div class="barcode">
            {{ $producto->sku }}
        </div>
        
        <div class="footer">
            {{ now()->format('d/m/Y H:i') }} - SISTEMA DE INVENTARIO
        </div>
    </div>
</body>
</html>