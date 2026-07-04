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
        $skuOriginal = $producto->sku ?? 'Sin SKU';
        $skuBarcode = '0' . $skuOriginal;
        
        $nombre = $this->limpiarTexto($producto->nombre ?? 'Sin nombre');
        $marca  = $this->limpiarTexto($producto->marca->nombre ?? '');
        $modelo = $this->limpiarTexto($producto->modelo ?? '');
        $serie  = $this->limpiarTexto($producto->serie ?? '');
        $fecha  = now()->format('d/m/Y');

        $nombre = substr($nombre, 0, 28);
        $marca  = substr($marca, 0, 15);
        $modelo = substr($modelo, 0, 15);
        $serie  = substr($serie, 0, 12);

        return <<<ZPL
^XA
^PW812
^LL406
^LS0
^LH0,0
^BY2,3,45

^FX COLUMNA IZQUIERDA
^FO5,26^ADN,14,9^FDProducto: {$nombre}^FS
^FO5,48^ADN,12,8^FDMarca: {$marca}^FS
^FO5,66^ADN,12,8^FDModelo: {$modelo}^FS
^FO5,88^BCN,45,Y,N,N^FD>{$skuBarcode}^FS
^FO5,175^ADN,11,7^FDSerie: {$serie}^FS
^FO5,195^ADN,9,6^FD{$fecha}^FS

^FX COLUMNA DERECHA
^LH425,0
^FO5,26^ADN,14,9^FDProducto: {$nombre}^FS
^FO5,48^ADN,12,8^FDMarca: {$marca}^FS
^FO5,66^ADN,12,8^FDModelo: {$modelo}^FS
^FO5,88^BCN,45,Y,N,N^FD>{$skuBarcode}^FS
^FO5,175^ADN,11,7^FDSerie: {$serie}^FS
^FO5,195^ADN,9,6^FD{$fecha}^FS

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

    public function preview(Producto $producto)
    {
        if (empty($producto->sku)) {
            abort(404, 'Este producto no tiene SKU.');
        }

        $skuOriginal = $producto->sku;
        $skuBarcode = '0' . $skuOriginal;

        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($skuBarcode, $generator::TYPE_CODE_128);

        $width = 812;
        $height = 203;
        $image = imagecreatetruecolor($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        imageline($image, 406, 0, 406, $height, $gray);
        imageline($image, 407, 0, 407, $height, $gray);
        
        $nombre = $this->limpiarTexto($producto->nombre ?? '');
        $marca  = $this->limpiarTexto($producto->marca->nombre ?? '');
        $modelo = $this->limpiarTexto($producto->modelo ?? '');
        $serie  = $this->limpiarTexto($producto->serie ?? '');
        $fecha  = now()->format('d/m/Y');
        
        $nombre = substr($nombre, 0, 28);
        $marca  = substr($marca, 0, 15);
        $modelo = substr($modelo, 0, 15);
        $serie  = substr($serie, 0, 12);
        
        $barcodeImg = imagecreatefromstring($barcode);
        $barcodeWidth = imagesx($barcodeImg);
        $barcodeHeight = imagesy($barcodeImg);
        
        $newWidth = 340;
        $newHeight = (int)(($barcodeHeight * $newWidth) / $barcodeWidth);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $barcodeImg, 0, 0, 0, 0, $newWidth, $newHeight, $barcodeWidth, $barcodeHeight);
        
        $fontGrande = 5;
        $fontMedio = 4;
        $fontPequeno = 3;
        $fontExtra = 2;
        
        // COLUMNA 1
        $y = 26;
        imagestring($image, $fontGrande, 5, $y, "Producto: {$nombre}", $black);
        $y += 22;
        imagestring($image, $fontMedio, 5, $y, "Marca: {$marca}", $black);
        $y += 18;
        imagestring($image, $fontMedio, 5, $y, "Modelo: {$modelo}", $black);
        $y += 22;
        imagecopy($image, $resized, 5, $y, 0, 0, $newWidth, $newHeight);
        $y += 50;
        imagestring($image, $fontPequeno, 5, $y, "SKU: {$skuOriginal}", $black);
        $y += 18;
        imagestring($image, $fontPequeno, 5, $y, "Serie: {$serie}", $black);
        $y += 20;
        imagestring($image, $fontExtra, 5, $y, $fecha, $black);
        
        // COLUMNA 2
        $y = 26;
        imagestring($image, $fontGrande, 430, $y, "Producto: {$nombre}", $black);
        $y += 22;
        imagestring($image, $fontMedio, 430, $y, "Marca: {$marca}", $black);
        $y += 18;
        imagestring($image, $fontMedio, 430, $y, "Modelo: {$modelo}", $black);
        $y += 22;
        imagecopy($image, $resized, 430, $y, 0, 0, $newWidth, $newHeight);
        $y += 50;
        imagestring($image, $fontPequeno, 430, $y, "SKU: {$skuOriginal}", $black);
        $y += 18;
        imagestring($image, $fontPequeno, 430, $y, "Serie: {$serie}", $black);
        $y += 20;
        imagestring($image, $fontExtra, 430, $y, $fecha, $black);
        
        imagedestroy($barcodeImg);
        imagedestroy($resized);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="etiqueta_' . $skuOriginal . '.png"');
    }

    public function show(Producto $producto)
    {
        if (empty($producto->sku)) {
            abort(404, 'Este producto no tiene SKU.');
        }

        $skuOriginal = $producto->sku;
        $skuBarcode = '0' . $skuOriginal;
        
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($skuBarcode, $generator::TYPE_CODE_128);

        return view('etiquetas.zpl', compact('producto', 'barcode', 'skuOriginal'));
    }

    /**
     * ✅ Opción 1: Solo abre Direct Communication con el archivo cargado
     * No descarga el archivo, solo lo abre en la utilidad
     */
    public function abrirDirectComm(Producto $producto)
    {
        if (empty($producto->sku)) {
            abort(404, 'Este producto no tiene SKU.');
        }

        $zpl = $this->generarZPL($producto);
        $filename = "etiqueta_{$producto->sku}.zpl";
        $tempFile = storage_path("app/temp/" . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        file_put_contents($tempFile, $zpl);
        
        try {
            // Abrir Direct Communication con el archivo cargado
            $prnUtils = '"C:\Program Files (x86)\Zebra Technologies\Zebra Setup Utilities\App\PrnUtils.exe"';
            $command = "start \"\" $prnUtils /direct /p USB001 /f \"$tempFile\"";
            pclose(popen("start /B $command", 'r'));
            
            return redirect()->back()->with('success', '✅ Direct Communication abierto. Presiona Ctrl+Enter para imprimir.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '❌ Error al abrir Direct Communication: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Opción 2: Descarga el ZPL y abre Direct Communication
     * Descarga el archivo y lo abre en la utilidad
     */
    public function descargarYAbir(Producto $producto)
    {
        if (empty($producto->sku)) {
            abort(404, 'Este producto no tiene SKU.');
        }

        $zpl = $this->generarZPL($producto);
        $filename = "etiqueta_{$producto->sku}.zpl";
        $tempFile = storage_path("app/temp/" . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        file_put_contents($tempFile, $zpl);
        
        try {
            // Abrir Direct Communication con el archivo cargado
            $prnUtils = '"C:\Program Files (x86)\Zebra Technologies\Zebra Setup Utilities\App\PrnUtils.exe"';
            $command = "start \"\" $prnUtils /direct /p USB001 /f \"$tempFile\"";
            pclose(popen("start /B $command", 'r'));
            
            // Descargar el archivo
            return response($zpl, 200)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
        } catch (\Exception $e) {
            // Si falla, al menos descarga el archivo
            return response($zpl, 200)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }
    }
}