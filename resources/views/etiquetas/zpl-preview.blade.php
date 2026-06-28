<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiqueta ZPL - {{ $skuOriginal }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f4f4f4;
        }
        .contenedor {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .etiqueta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            border: 2px solid #333;
            padding: 10px;
            background: #fff;
        }
        .columna {
            width: 50%;
            border: 1px solid #ddd;
            padding: 10px;
            min-height: 280px;
        }
        .producto { font-size: 16px; font-weight: bold; margin-bottom: 8px; }
        .marca, .modelo, .serie, .fecha { font-size: 14px; margin-bottom: 6px; }
        .barcode-container {
            margin: 12px 0;
            text-align: center;
        }
        .barcode-container img {
            max-width: 100%;
            height: auto;
        }
        .label { font-weight: bold; }
        .info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .botones a, .botones button {
            padding: 10px 18px;
            margin-left: 10px;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-descarga { background: #0066cc; color: white; }
        .btn-imprimir { background: #28a745; color: white; }
        .tamano-real {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <div class="etiqueta">
            <!-- Columna Izquierda -->
            <div class="columna columna-izquierda">
                <div class="producto"><span class="label">Producto:</span> {{ $producto->nombre }}</div>
                <div class="marca"><span class="label">Marca:</span> {{ $producto->marca->nombre ?? 'N/A' }}</div>
                <div class="modelo"><span class="label">Modelo:</span> {{ $producto->modelo }}</div>
                
                <div class="barcode-container">
                    <img src="data:image/png;base64,{{ base64_encode($barcode) }}" alt="Código de barras">
                </div>
                
                <div class="producto"><span class="label">SKU:</span> {{ $skuOriginal }}</div>
                
                <div class="serie"><span class="label">Serie:</span> {{ $producto->serie ?? 'N/A' }}</div>
                <div class="fecha">{{ now()->format('d/m/Y') }}</div>
            </div>
            
            <!-- Columna Derecha -->
            <div class="columna columna-derecha">
                <div class="producto"><span class="label">Producto:</span> {{ $producto->nombre }}</div>
                <div class="marca"><span class="label">Marca:</span> {{ $producto->marca->nombre ?? 'N/A' }}</div>
                <div class="modelo"><span class="label">Modelo:</span> {{ $producto->modelo }}</div>
                
                <div class="barcode-container">
                    <img src="data:image/png;base64,{{ base64_encode($barcode) }}" alt="Código de barras">
                </div>
                
                <div class="producto"><span class="label">SKU:</span> {{ $skuOriginal }}</div>
                
                <div class="serie"><span class="label">Serie:</span> {{ $producto->serie ?? 'N/A' }}</div>
                <div class="fecha">{{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
        
        <div class="info">
            <div class="datos-producto">
                <strong>Producto:</strong> {{ $producto->nombre }}<br>
                <strong>SKU:</strong> {{ $skuOriginal }}<br>
                <strong>Marca:</strong> {{ $producto->marca->nombre ?? 'N/A' }}<br>
                <strong>Modelo:</strong> {{ $producto->modelo }}<br>
                <strong>Serie:</strong> {{ $producto->serie ?? 'N/A' }}
            </div>
            
            <div class="botones">
                <a href="{{ route('etiqueta-zpl.producto', $producto) }}" class="btn-descarga">
                    ⬇️ Descargar ZPL
                </a>
                <button onclick="window.print()" class="btn-imprimir">
                    🖨️ Imprimir Vista
                </button>
            </div>
        </div>
        
        <div class="tamano-real">
            📏 Tamaño real: 4" × 1" (101.6mm × 25.4mm) - 2 columnas
        </div>
    </div>
</body>
</html>