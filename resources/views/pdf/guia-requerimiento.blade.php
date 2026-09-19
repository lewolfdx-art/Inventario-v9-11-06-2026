<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Requerimiento O&T</title>

<style>

* { box-sizing: border-box; }

body {
    margin: 0;
    padding: 0;
    font-family: dejavusans, sans-serif;
    font-size: 9px;
    background: #fff;
}

.page {
    width: 100%;
    margin: 0 auto;
    background: white;
    padding: 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border: none;
}

td, th {
    padding: 3px 4px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    border: none;
}

.center { text-align: center; }
.bold-soft { font-weight: 600; }

/* =========================================
   ENCABEZADO — SÍ LLEVA BORDES
========================================= */

.header { width: 100%; }

.header td {
    border: 1px solid #333 !important;
    height: 1px;
    padding: 1px 4px;
    vertical-align: middle;
    overflow: hidden;
}

.logo-box {
    width: 22%;
    text-align: center;
    vertical-align: middle;
    padding: 1mm 1mm;
}

.logo-box img {
    max-width: 23mm;
    max-height: 10mm;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}

.title-box {
    width: 50%;
    height: 25px;
    text-align: center;
    vertical-align: middle;
    font-size: 16px;
    font-weight: 600;
}

.info-box {
    width: 20%;
    padding: 0 !important;
    vertical-align: top;
}

.info-box table {
    height: 45px;
    border: none;
}

.info-box table td {
    border: 0.5px solid #999 !important;
    padding: 2px 3px;
    text-align: center;
    vertical-align: middle;
    font-size: 7px;
}

.info-box table tr:first-child td { border-top: none !important; }
.info-box table tr:last-child td  { border-bottom: none !important; }
.info-box table td:first-child    { border-left: none !important; }
.info-box table td:last-child     { border-right: none !important; }

/* =========================================
   DATOS DEL PROYECTO
========================================= */

.project-info-table {
    margin-top: 8px;
    border: none !important;
}

.project-info-table > tbody > tr > td {
    border: none !important;
    vertical-align: top;
    padding: 0;
}

.project-left-cell  { width: 68%; padding-right: 8px !important; }
.project-right-cell { width: 32%; }

.field-row {
    width: 100%;
    margin-bottom: 4px;
    border: none !important;
}

.field-row td {
    border: none !important;
    padding: 2px 3px;
    vertical-align: bottom;
}

.field-label {
    font-weight: 600;
    font-size: 8px;
    border: none !important;
}

.field-value {
    border: none !important;
    border-bottom: 1px solid #777 !important;
    font-weight: 600;
    height: 5px;
    font-size: 8px;
}

.field-row.right .field-label {
    width: 48%;
    font-size: 8px;
    border: none !important;
}

.field-row.right .field-value {
    border: none !important;
    border-bottom: 1px solid #777 !important;
    text-align: center;
    font-weight: normal;
}

/* =========================================
   TABLA PRINCIPAL
========================================= */

.main-table {
    margin-top: 8px;
    font-size: 7px;
}

.main-table th,
.main-table td {
    border: 1px solid #333 !important;
}

.main-table th {
    font-size: 7px;
    padding: 4px 1px;
    color: #000;
    font-weight: 600;
    line-height: 1.15;
}

.main-table td {
    height: 17px;
    padding: 2px 2px;
}

.gris      { background: #d9d9d9 !important; color: #000 !important; }
.entregado { background: #d6e6f7 !important; color: #000 !important; }
.devolucion{ background: #fbdede !important; color: #000 !important; }

.item-col          { width: 3%;    text-align: center; }
.desc-col          { width: 85%; }
.cant-col          { width: 4%;    text-align: center; }
.unit-col          { width: 5%;    text-align: center; }

.col-entregado     { width: 0.5%;  text-align: center; }
.col-cant-entregad { width: 0.5%;  text-align: center; }
.col-und-entregad  { width: 0.5%;  text-align: center; }

.col-devolucion    { width: 0.5%;  text-align: center; }
.col-cant-devoluc  { width: 0.5%;  text-align: center; }
.col-und-devoluc   { width: 0.5%;  text-align: center; }

.invisible-cell {
    border-top: none !important;
    border-left: none !important;
    border-right: 1px solid #333 !important;
    border-bottom: 1px solid #333 !important;
}

/* =========================================
   FIRMAS
========================================= */

.signatures-outer {
    margin-top: 10px;
    border: none !important;
    border-collapse: collapse;
}

.signatures-outer > tbody > tr > td {
    border: none !important;
    padding: 0;
    vertical-align: top;
}

.signatures-outer-left  { width: 61%; }
.signatures-outer-gap   { width: 6%;  }
.signatures-outer-right { width: 33%; }

.signatures {
    border: none !important;
    border-collapse: collapse;
}

.signatures td,
.signatures th {
    height: 22px;
    border: 1px solid #333 !important;
    padding: 3px 4px;
    font-size: 7px;
    vertical-align: middle;
}

.signature-title {
    text-align: center;
    font-weight: 600;
    color: #000;
    font-size: 7px;
    padding: 3px 2px;
}

.signature-title.entregado {
    background: #d6e6f7 !important;
    color: #000 !important;
}

.signatures-left td.signature-label {
    background: #d6e6f7 !important;
    color: #000 !important;
    font-weight: 600;
    font-size: 7px;
    padding: 3px 2px;
}

.signature-title.devolucion {
    background: #fbdede !important;
    color: #000 !important;
}

.signatures-right td.signature-label {
    background: #fbdede !important;
    color: #000 !important;
    font-weight: 600;
    font-size: 7px;
    padding: 3px 2px;
}

.signatures td:not(.signature-label) {
    background: #fff !important;
    color: #000 !important;
    vertical-align: bottom;
}

.signatures th.signature-invisible {
    border-top: none !important;
    border-left: none !important;
    border-right: 1px solid #333 !important;
    border-bottom: 1px solid #333 !important;
}

/* =========================================
   COMENTARIOS
========================================= */

.comments { margin-top: 10px; }

.comments-title {
    border: 1px solid #333;
    border-bottom: none;
    text-align: center;
    font-weight: 600;
    color: #000;
    padding: 4px;
    background: #d9d9d9 !important;
    font-size: 8px;
}

.comments-box {
    height: 90px;
    border: 1px solid #333;
    padding: 5px;
}

</style>
</head>

<body>

<div class="page">

    {{-- ✅ LOGO DESDE EL RESOURCE DE IMÁGENES --}}
    @php
        // 1. Intentar obtener el logo desde la base de datos
        $logoImagen = \App\Models\Imagen::activas()->tipo('logo')->first();
        $logoBase64 = '';
        
        if ($logoImagen) {
            $rutaCompleta = storage_path('app/public/' . $logoImagen->archivo);
            if (file_exists($rutaCompleta)) {
                $mime = mime_content_type($rutaCompleta) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($rutaCompleta));
            }
        }
        
        // 2. Fallback: buscar en public/images/ si no hay en BD
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

    <!-- ============ ENCABEZADO ============ -->
    <table class="header">
        <tr>
            <td class="logo-box">
                @if ($logoBase64)
                <img src="{{ $logoBase64 }}" alt="O&T" style="width: 25mm; height: auto; max-height: 12mm;">

                @else
                    <span style="font-size:14px;color:#48628c;font-weight:400;">O&T</span>
                @endif
            </td>

            <td class="title-box">REQUERIMIENTO</td>

            <td class="info-box">
                <table>
                    <tr>
                        <td class="center">REVISADO POR:<br>JEFE SIG</td>
                        <td>Versión:<br><b>{{ $guia->version }}</b></td>
                    </tr>
                    <tr>
                        <td class="center">APROBADO POR:<br>GG</td>
                        <td>Fecha:<br>{{ $guia->fecha_documento?->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="center">CÓDIGO:<br>{{ $guia->codigo }}</td>
                        <td>PÁGINA:<br>{{ $guia->pagina }} DE {{ $guia->total_paginas }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ============ DATOS DEL PROYECTO ============ -->
    <table class="project-info-table">
        <tr>
            <td class="project-left-cell">

                <table class="field-row">
                    <tr>
                        <td class="field-label" style="width:42%;">NOMBRE DEL PROYECTO / SERVICIO / ÁREA:</td>
                        <td class="field-value">{{ $guia->nombre_proyecto }}</td>
                    </tr>
                </table>

                <table class="field-row" style="margin-top:12px;">
                    <tr>
                        <td class="field-label" style="width:42%;"></td>
                        <td class="field-value">&nbsp;</td>
                    </tr>
                </table>

                <table class="field-row">
                    <tr>
                        <td class="field-label" style="width:42%;">RESPONSABLE SOLICITANTE:</td>
                        <td class="field-value">{{ $guia->responsable_solicitante }}</td>
                    </tr>
                </table>

            </td>

            <td class="project-right-cell">

                <table class="field-row right">
                    <tr>
                        <td class="field-label">FECHA DE PEDIDO:</td>
                        <td class="field-value">{{ $guia->fecha_pedido?->format('d/m/Y') }}</td>
                    </tr>
                </table>

                <table class="field-row right">
                    <tr>
                        <td class="field-label">CENTRO DE COSTOS:</td>
                        <td class="field-value">{{ $guia->centro_costos }}</td>
                    </tr>
                </table>

                <table class="field-row right">
                    <tr>
                        <td class="field-label">FECHA DE ATENCIÓN:</td>
                        <td class="field-value">{{ $guia->fecha_atencion?->format('d/m/Y') }}</td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

    <!-- ============ TABLA PRINCIPAL ============ -->
    <table class="main-table">

        <tr>
            <th colspan="4" class="gris bold-soft">SOLICITADO</th>
            <th colspan="3" class="entregado bold-soft">ENTREGADO</th>
            <th colspan="3" class="devolucion bold-soft">DEVOLUCIÓN</th>
        </tr>

        <tr>
            <th class="item-col gris bold-soft">ITEM</th>
            <th class="desc-col gris bold-soft">DESCRIPCIÓN</th>
            <th class="cant-col gris bold-soft">CANT.</th>
            <th class="unit-col gris bold-soft">UNID.<br>MED.</th>
            <th class="col-entregado entregado bold-soft">ENTREGADO</th>
            <th class="col-cant-entregad entregado bold-soft">CANT.<br>ENTREGAD.</th>
            <th class="col-und-entregad entregado bold-soft">UND.<br>MED.</th>
            <th class="col-devolucion devolucion bold-soft">DEVOLUCIÓN</th>
            <th class="col-cant-devoluc devolucion bold-soft">CANT.<br>DEVOLUC.</th>
            <th class="col-und-devoluc devolucion bold-soft">UND.<br>MED.</th>
        </tr>

        @forelse ($guia->items as $item)
            <tr>
                <td class="item-col">{{ $item->item }}</td>
                <td>{{ $item->descripcion }}</td>
                <td class="cant-col">{{ $item->cantidad_solicitada }}</td>
                <td class="unit-col">{{ $item->unidad_solicitada }}</td>
                <td class="col-entregado center">{{ $item->entregado ? 'X' : '' }}</td>
                <td class="col-cant-entregad center">{{ $item->cantidad_entregada }}</td>
                <td class="col-und-entregad center">{{ $item->unidad_entregada }}</td>
                <td class="col-devolucion center">{{ $item->devuelto ? 'X' : '' }}</td>
                <td class="col-cant-devoluc center">{{ $item->cantidad_devuelta }}</td>
                <td class="col-und-devoluc center">{{ $item->unidad_devuelta }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="center">Sin ítems registrados</td></tr>
        @endforelse

        @for ($i = $guia->items->count() + 1; $i <= 26; $i++)
            <tr>
                <td class="item-col">{{ $i }}</td>
                <td></td><td></td><td></td>
                <td class="col-entregado"></td>
                <td class="col-cant-entregad"></td>
                <td class="col-und-entregad"></td>
                <td class="col-devolucion"></td>
                <td class="col-cant-devoluc"></td>
                <td class="col-und-devoluc"></td>
            </tr>
        @endfor

    </table>

    <!-- ============ FIRMAS ============ -->
    <table class="signatures-outer">
        <tr>
            <td class="signatures-outer-left">
                <table class="signatures signatures-left">
                    <tr>
                        <th class="signature-invisible"></th>
                        <th class="signature-title entregado bold-soft">ATENDIDO POR</th>
                        <th class="signature-title entregado bold-soft">AUTORIZADO POR</th>
                        <th class="signature-title entregado bold-soft">RECIBÍ CONFORME</th>
                    </tr>
                    <tr>
                        <td class="signature-label bold-soft">FIRMA</td>
                        <td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <td class="signature-label bold-soft">FECHA</td>
                        <td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <td class="signature-label bold-soft">NOMBRE Y APELLIDOS</td>
                        @foreach (['atendido_por', 'autorizado_por', 'recibi_conforme'] as $tipo)
                            @php $firma = $guia->firmas->firstWhere('tipo', $tipo); @endphp
                            <td>{{ $firma->nombre_apellidos ?? '' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="signature-label bold-soft">DNI</td>
                        @foreach (['atendido_por', 'autorizado_por', 'recibi_conforme'] as $tipo)
                            @php $firma = $guia->firmas->firstWhere('tipo', $tipo); @endphp
                            <td>{{ $firma->dni ?? '' }}</td>
                        @endforeach
                    </tr>
                </table>
            </td>

            <td class="signatures-outer-gap"></td>

            <td class="signatures-outer-right">
                <table class="signatures signatures-right">
                    <tr>
                        <th class="signature-invisible"></th>
                        <th class="signature-title devolucion bold-soft">RECEPCIÓN - DEVOLUCIÓN</th>
                    </tr>
                    @php $firmaDev = $guia->firmas->firstWhere('tipo', 'recepcion_devolucion'); @endphp
                    <tr><td class="signature-label bold-soft">FIRMA</td><td></td></tr>
                    <tr><td class="signature-label bold-soft">FECHA</td><td>{{ $firmaDev?->fecha?->format('d/m/Y') ?? '' }}</td></tr>
                    <tr><td class="signature-label bold-soft">NOMBRE Y APELLIDOS</td><td>{{ $firmaDev?->nombre_apellidos ?? '' }}</td></tr>
                    <tr><td class="signature-label bold-soft">DNI</td><td>{{ $firmaDev?->dni ?? '' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ============ COMENTARIOS ============ -->
    <div class="comments">
        <div class="comments-title bold-soft">COMENTARIOS / OBSERVACIONES:</div>
        <div class="comments-box">{{ $guia->comentarios }}</div>
    </div>

</div>

</body>
</html>