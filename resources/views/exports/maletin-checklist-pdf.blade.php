<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            background: #fff;
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', 'Arial', Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
        }
        .page {
            width: 100%;
            background: #fff;
            padding: 0 6px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        td, th {
            border: 1px solid #333;
            padding: 2px 4px;
            vertical-align: middle;
        }

        /* Encabezado */
        .header td {
            height: 40px;
        }
        .logo-box {
            width: 22%;
            text-align: center;
            font-weight: bold;
            font-size: 20px;
            color: #f08a00;
        }
        .logo-box img {
            max-width: 95px;
            max-height: 45px;
        }
        .logo-box small {
            display: block;
            font-size: 8px;
            color: #555;
        }
        .title-box {
            width: 50%;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            line-height: 1.3;
        }
        .info-box {
            width: 28%;
            padding: 0;
        }
        .info-box table td {
            height: 20px;
            font-size: 8px;
            font-weight: bold;
            border: 0.5px solid #333;
        }
        .info-box table tr:first-child td {
            border-top: 0;
        }
        .info-box table tr:last-child td {
            border-bottom: 0;
        }
        .info-box table td:first-child {
            border-left: 0;
        }
        .info-box table td:last-child {
            border-right: 0;
        }

        /* Datos */
        .datos {
            margin: 10px 0 4px;
        }
        .datos .linea {
            margin-top: 8px;
            font-weight: bold;
            font-size: 10px;
        }
        .datos .linea .l {
            white-space: nowrap;
        }
        .datos .linea .fill {
            display: inline-block;
            width: 380px;
            border-bottom: 1px dotted #333;
            height: 12px;
        }
        .datos .linea .gap {
            display: inline-block;
            width: 18px;
        }
        .datos .linea .valor {
            font-weight: normal;
            color: #1B5E20;
        }

        /* Tabla principal */
        .main-table th {
            background: #fff;
            font-size: 11px;
            text-align: center;
            height: 20px;
        }
        .main-table td {
            height: 21px;
            font-size: 8px;
        }
        .c-item {
            width: 6%;
            text-align: center;
        }
        .c-cant {
            width: 6%;
            text-align: center;
        }
        .c-si {
            width: 6%;
            text-align: center;
        }
        .c-no {
            width: 6%;
            text-align: center;
        }
        .c-estado {
            width: 8%;
            text-align: center;
        }
        .main-table td.x {
            text-align: center;
            font-size: 11px;
        }
        .main-table td.estado {
            text-align: center;
            font-size: 9px;
        }
        .bold {
            font-weight: bold;
        }
        .sec {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-decoration: underline;
            background: #fff;
        }
        .fila-doble td {
            height: 26px;
        }
        .estado-good {
            color: #1B5E20;
            font-weight: bold;
        }
        .estado-bad {
            color: #c00000;
            font-weight: bold;
        }

        /* Observaciones */
        .obs-title {
            font-weight: bold;
            font-size: 11px;
            margin-top: 10px;
        }
        .obs-box {
            border: 1px solid #333;
            height: 55px;
            margin-top: 2px;
            padding: 3px 5px;
            font-size: 9px;
        }

        /* Firmas */
        .firmas {
            width: 100%;
            margin-top: 40px;
            border: none;
        }
        .firmas td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .firma .linea {
            border-top: 1px solid #333;
            padding-top: 3px;
            font-size: 9px;
        }
        .firma .nombre {
            margin-top: 2px;
            font-size: 9px;
        }
        .nya {
            margin-top: 4px;
            font-size: 9px;
            padding-left: 12%;
            width: 50%;
        }
    </style>
</head>
<body>

@php
    $logoImagen = \App\Models\Imagen::activas()->tipo('logo')->first();
    $logoBase64 = '';

    if ($logoImagen) {
        $rutaCompleta = storage_path('app/public/' . $logoImagen->archivo);
        if (file_exists($rutaCompleta)) {
            $mime = mime_content_type($rutaCompleta) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($rutaCompleta));
        }
    }

    if (empty($logoBase64)) {
        $posiblesLogos = [
            'images/logo.png',
            'images/logo.jpg',
            'images/logo.jpeg',
            'images/login-logo.png',
        ];
        foreach ($posiblesLogos as $ruta) {
            $rutaCompleta = public_path($ruta);
            if (file_exists($rutaCompleta)) {
                $mime = mime_content_type($rutaCompleta) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($rutaCompleta));
                break;
            }
        }
    }
@endphp

<div class="page">

    <!-- ENCABEZADO -->
    <table class="header">
        <tr>
            <td class="logo-box">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="O&T">
                @else
                    O&amp;T<small>Energy</small>
                @endif
            </td>
            <td class="title-box">CHECK LIST DE ENTREGA –<br>{{ $maletin }}</td>
            <td class="info-box">
                <table>
                    <tr><td style="width:50%">Revisado: RED</td><td>Versión: 00</td></tr>
                    <tr><td>Aprobado: CC</td><td>Fecha: 01/07/14</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- DATOS DEL PROYECTO -->
    <div class="datos">
        <div class="linea"><span class="l">NOMBRE DEL PROYECTO Y CLIENTE:</span><span class="fill"></span></div>
        <div class="linea"><span class="l">RESPONSABLE SOLICITANTE:</span><span class="fill"></span></div>
        <div class="linea">
            <span class="l">FECHA DE PEDIDO:</span><span class="fill"></span>
            <span class="gap"></span>
            <span class="l">FECHA DE DEVOLUCIÓN:</span><span class="valor">{{ $fecha_devolucion }}</span>
        </div>
    </div>

    <!-- TABLA PRINCIPAL -->
    <table class="main-table">
        <thead>
            <tr>
                <th class="c-item">ITEM</th>
                <th class="c-cant">CANT</th>
                <th>CONTENIDO DE LA MALETA</th>
                <th class="c-si">SI</th>
                <th class="c-no">NO</th>
                <th class="c-estado">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @if ($row['section'])
                    <tr>
                        <td class="c-item">{{ $row['item'] }}</td>
                        <td></td>
                        <td colspan="4" class="sec">{{ $row['desc'] }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="c-item">{{ $row['item'] }}</td>
                        <td class="c-cant {{ $row['bold'] ? 'bold' : '' }}">{{ $row['cant'] }}</td>
                        <td class="{{ $row['bold'] ? 'bold' : '' }}">{{ $row['desc'] }}</td>
                        <td class="x">{{ $row['si'] ? 'X' : '' }}</td>
                        <td class="x">{{ $row['no'] ? 'X' : '' }}</td>
                        <td class="estado">
                            @if($row['estado'])
                                <span class="{{ $row['estado'] === 'OK' ? 'estado-good' : 'estado-bad' }}">{{ $row['estado'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- OBSERVACIONES -->
    <div class="obs-title">OBSERVACIONES:</div>
    <div class="obs-box">{{ $observaciones }}</div>

    <!-- FIRMAS -->
    <table class="firmas">
        <tr>
            <td class="firma">
                <div class="linea">ENTREGADO POR</div>
                <div class="nombre">&nbsp;</div>
            </td>
            <td class="firma">
                <div class="linea">RECIBIDO POR</div>
                <div class="nombre">&nbsp;</div>
            </td>
        </tr>
    </table>
    <div class="nya">N. y A.&nbsp;&nbsp;&nbsp;DNI N°</div>

</div>

</body>
</html>