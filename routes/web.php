<?php

use Illuminate\Support\Facades\Route;
use App\Models\GuiaRemision;
use App\Models\Producto;
use App\Models\GuiaRequerimiento;
use App\Http\Controllers\ProductoBarcodeController;
use App\Http\Controllers\EtiquetaZPLController;
use App\Models\Unidad;
use App\Http\Controllers\GuiaRequerimientoPdfController;   // ✅ NUEVO (mPDF)
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Livewire\Escaner;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\PdfController;

// ✅ Redirigir a la vista del escáner
Route::get('/', function () {
    return redirect('/escanear');
});

// ============================================================
// CÓDIGO DE BARRAS (IMAGEN PNG)
// ============================================================
Route::get('/barcode/producto/{producto}', [ProductoBarcodeController::class, 'generate'])
    ->name('barcode.producto');

// ✅ CÓDIGO DE BARRAS POR UNIDAD FÍSICA
Route::get('/barcode/unidad/{unidad}', [ProductoBarcodeController::class, 'generateUnidad'])
    ->name('barcode.unidad');

// ============================================================
// ETIQUETA EN PDF - CENTRADA
// ============================================================
Route::get('/etiqueta/producto/{producto}', function (Producto $producto) {
    if (empty($producto->sku)) {
        abort(404, 'Este producto no tiene SKU.');
    }

    $generator = new BarcodeGeneratorPNG();
    $unidades = $producto->unidades()->orderBy('codigo')->get();

    if ($unidades->isEmpty()) {
        $barcodeImage = base64_encode($generator->getBarcode($producto->sku, $generator::TYPE_CODE_128, 3, 80));
        $pdf = Pdf::loadView('etiquetas.producto-etiqueta', compact('producto', 'barcodeImage'));
    } else {
        // ✅ "Imprimir todas": una etiqueta por cada unidad física
        $etiquetas = $unidades->map(function ($unidad) use ($producto, $generator) {
            return [
                'producto'      => $producto,
                'codigo'        => $unidad->codigo,
                'serie'         => $unidad->serie,
                'barcodeImage'  => base64_encode($generator->getBarcode($unidad->codigo, $generator::TYPE_CODE_128, 3, 80)),
            ];
        });

        $pdf = Pdf::loadView('etiquetas.producto-etiquetas', compact('etiquetas'));
    }

    $pdf->setPaper([0, 0, 270, 350], 'portrait');

    return $pdf->download('etiqueta_' . $producto->sku . '.pdf');
})->name('etiqueta.producto');

// ✅ ETIQUETA EN PDF POR UNIDAD FÍSICA
Route::get('/etiqueta/unidad/{unidad}', function (Unidad $unidad) {
    if (empty($unidad->codigo)) {
        abort(404, 'Esta unidad no tiene código.');
    }

    $generator = new BarcodeGeneratorPNG();
    $barcodeImage = base64_encode($generator->getBarcode($unidad->codigo, $generator::TYPE_CODE_128, 3, 80));

    $pdf = Pdf::loadView('etiquetas.unidad-etiqueta', compact('unidad', 'barcodeImage'));

    $pdf->setPaper([0, 0, 270, 350], 'portrait');

    return $pdf->download('etiqueta_' . $unidad->codigo . '.pdf');
})->name('etiqueta.unidad');

// ============================================================
// GUÍA DE REMISIÓN
// ============================================================
Route::get('/guia-remision/imprimir/{guia}', function (GuiaRemision $guia) {
    return view('pdf.guia-remision', compact('guia'));
})->name('guia-remision.imprimir');

// ============================================================
// ✅ GUÍA DE REQUERIMIENTO - PDF (mPDF)
// ============================================================
Route::get('/guia-requerimiento/{guia}/pdf', GuiaRequerimientoPdfController::class)
    ->name('guia-requerimiento.pdf');

// ✅ GUÍA DE REQUERIMIENTO - PDF (DESCARGA con mPDF)
Route::get('/guia-requerimiento/{guia}/pdf/download', [GuiaRequerimientoPdfController::class, 'download'])
    ->name('guia-requerimiento.pdf.download');

// ✅ GUÍA DE REQUERIMIENTO - VISTA HTML (para imprimir desde navegador)
Route::get('/guia-requerimiento/imprimir/{guia}', function (GuiaRequerimiento $guia) {
    $guia->load(['items', 'firmas']);
    return view('pdf.guia-requerimiento', compact('guia'));
})->name('guia-requerimiento.imprimir');

// ============================================================
// ZPL - IMPRESORA ZEBRA (Descargar archivo .zpl)
// ============================================================
Route::get('/etiqueta-zpl/producto/{producto}', [EtiquetaZPLController::class, 'generate'])
    ->name('etiqueta-zpl.producto');

// ✅ ZPL POR UNIDAD FÍSICA
Route::get('/etiqueta-zpl/unidad/{unidad}', [EtiquetaZPLController::class, 'generateUnidad'])
    ->name('etiqueta-zpl.unidad');

// ✅ Opción 1: Abrir Direct Communication (sin descargar)
Route::get('/etiqueta/abrir/{producto}', [EtiquetaZPLController::class, 'abrirDirectComm'])
    ->name('etiqueta.abrir');

// ✅ Opción 1 (unidad): Abrir Direct Communication (sin descargar)
Route::get('/etiqueta/abrir/unidad/{unidad}', [EtiquetaZPLController::class, 'abrirDirectCommUnidad'])
    ->name('etiqueta.unidad.abrir');

// ✅ Opción 2: Descargar ZPL y abrir Direct Communication
Route::get('/etiqueta/descargar/{producto}', [EtiquetaZPLController::class, 'descargarYAbir'])
    ->name('etiqueta.descargar');

// ✅ Opción 2 (unidad): Descargar ZPL y abrir Direct Communication
Route::get('/etiqueta/descargar/unidad/{unidad}', [EtiquetaZPLController::class, 'descargarYAbirUnidad'])
    ->name('etiqueta.unidad.descargar');

// ============================================================
// ESCÁNER CON LIVEWIRE
// ============================================================
Route::get('/escanear', Escaner::class)->name('escaneo.index');

// ============================================================
// LOGOUT PERSONALIZADO (Redirige a /escanear)
// ============================================================
Route::post('/admin/logout', [LogoutController::class, 'logout'])->name('filament.admin.auth.logout');

Route::get('/pdf/demo', [PdfController::class, 'demo']);