<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Http\Response;

class ProductoBarcodeController extends Controller
{
    public function generate(Producto $producto)
    {
        if (empty($producto->sku)) {
            abort(404, 'Este producto no tiene código de barras.');
        }

        $generator = new BarcodeGeneratorPNG();

        // ✅ Usar los mismos parámetros que en StockBarcodeController
        $widthFactor = 2;
        $height = 100;

        $rawImage = $generator->getBarcode(
            $producto->sku,
            $generator::TYPE_CODE_128,
            $widthFactor,
            $height
        );

        $im = imagecreatefromstring($rawImage);
        if ($im === false) {
            abort(500, 'Error al generar el código de barras.');
        }

        $bcWidth  = imagesx($im);
        $bcHeight = imagesy($im);

        // ✅ Márgenes blancos (igual que en Stock)
        $marginLeftRight = 15;
        $marginTopBottom = 12;

        $totalWidth  = $bcWidth + (2 * $marginLeftRight);
        $totalHeight = $bcHeight + (2 * $marginTopBottom);

        // ✅ Crear imagen con fondo BLANCO
        $newImage = imagecreatetruecolor($totalWidth, $totalHeight);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        // ✅ Copiar el código de barras centrado con márgenes blancos
        imagecopy($newImage, $im, $marginLeftRight, $marginTopBottom, 0, 0, $bcWidth, $bcHeight);

        imagedestroy($im);

        ob_start();
        imagepng($newImage, null, 9);
        $finalImage = ob_get_clean();

        imagedestroy($newImage);

        return response($finalImage)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('Content-Disposition', 'inline; filename="barcode_' . $producto->sku . '.png"');
    }
}