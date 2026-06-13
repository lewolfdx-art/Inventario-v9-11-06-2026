<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guía de Remisión - {{ $guia->numero_guia }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .info { margin-bottom: 20px; width: 100%; border-collapse: collapse; }
        .info td { padding: 5px; vertical-align: top; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; }
        .producto { margin-bottom: 5px; padding: 10px; background: #f9f9f9; border-left: 3px solid #2E7D32; }
        .producto p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GUÍA DE REMISIÓN</h1>
        <p>N° {{ $guia->numero_guia }}</p>
        <p>Fecha: {{ $guia->fecha_emision->format('d/m/Y') }}</p>
    </div>

    <div class="producto">
        <p><strong>Producto:</strong> {{ $guia->descripcion_completa }}</p>
        <p><strong>Serie:</strong> {{ $guia->serie ?? 'No registrada' }}</p>
    </div>

    <div class="footer">
        <p>Documento generado por el Sistema de Inventario</p>
        <p>Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>