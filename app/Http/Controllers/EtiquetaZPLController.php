<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Http\Response;

class EtiquetaZPLController extends Controller
{
    public function generate(Producto $producto)
    {
        if (empty($producto->sku)) {
            abort(404, 'Este producto no tiene SKU.');
        }

        $zpl = $this->generarZPL($producto);

        return response($zpl, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="etiqueta_' . $producto->sku . '.zpl"');
    }

    public function generarZPL(Producto $producto)
    {
        $nombre = $this->limpiarTexto($producto->nombre ?? 'Sin nombre');
        $sku = $this->limpiarTexto($producto->sku ?? 'Sin SKU');
        $marca = $this->limpiarTexto($producto->marca->nombre ?? '');
        $modelo = $this->limpiarTexto($producto->modelo ?? '');
        $serie = $this->limpiarTexto($producto->serie ?? '');
        $fecha = now()->format('d/m/Y H:i');

        return <<<ZPL
^XA

^FX SECCIÓN 1: TÍTULO Y ENCABEZADO
^CF0,50
^FO50,30^FDSISTEMA DE INVENTARIO^FS
^FO50,100^GB700,3,3^FS

^FX SECCIÓN 2: DATOS DEL PRODUCTO
^CF0,30
^FO50,140^FDProducto:^FS
^FO250,140^FD$nombre^FS
^FO50,190^FDSKU:^FS
^FO250,190^FD$sku^FS
^FO50,240^FDMarca:^FS
^FO250,240^FD$marca^FS
^FO50,290^FDModelo:^FS
^FO250,290^FD$modelo^FS
^FO50,340^FDSerie:^FS
^FO250,340^FD$serie^FS
^FO50,390^FDFecha:^FS
^FO250,390^FD$fecha^FS
^FO50,440^GB700,3,3^FS

^FX SECCIÓN 3: CÓDIGO DE BARRAS
^BY5,2,200
^FO50,480^BC^FD$sku^FS

^FX SECCIÓN 4: PIE DE PÁGINA
^FO50,750^GB700,3,3^FS
^CF0,20
^FO50,780^FDSISTEMA DE INVENTARIO - www.sistema.com^FS
^FO50,810^FDGenerado: $fecha^FS

^XZ
ZPL;
    }

    private function limpiarTexto($texto)
    {
        if (empty($texto)) return '';
        $texto = strip_tags($texto);
        $texto = preg_replace('/[^a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s\-\.\/\&]/', '', $texto);
        $texto = substr($texto, 0, 35);
        return strtoupper($texto);
    }
}