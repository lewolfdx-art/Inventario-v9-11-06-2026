<?php

use Illuminate\Support\Facades\Route;
use App\Models\GuiaRemision;
use App\Models\Producto;
use App\Models\GuiaRequerimiento;
use App\Http\Controllers\ProductoBarcodeController;
use App\Http\Controllers\EtiquetaZPLController;
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

// ============================================================
// ETIQUETA EN PDF - CENTRADA
// ============================================================
Route::get('/etiqueta/producto/{producto}', function (Producto $producto) {
    if (empty($producto->sku)) {
        abort(404, 'Este producto no tiene SKU.');
    }

    $generator = new BarcodeGeneratorPNG();
    $barcodeImage = base64_encode($generator->getBarcode($producto->sku, $generator::TYPE_CODE_128, 3, 80));

    $pdf = Pdf::loadView('etiquetas.producto-etiqueta', compact('producto', 'barcodeImage'));

    $pdf->setPaper([0, 0, 270, 350], 'portrait');

    return $pdf->download('etiqueta_' . $producto->sku . '.pdf');
})->name('etiqueta.producto');

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

// ✅ Opción 1: Abrir Direct Communication (sin descargar)
Route::get('/etiqueta/abrir/{producto}', [EtiquetaZPLController::class, 'abrirDirectComm'])
    ->name('etiqueta.abrir');

// ✅ Opción 2: Descargar ZPL y abrir Direct Communication
Route::get('/etiqueta/descargar/{producto}', [EtiquetaZPLController::class, 'descargarYAbir'])
    ->name('etiqueta.descargar');

// ============================================================
// ESCÁNER CON LIVEWIRE
// ============================================================
Route::get('/escanear', Escaner::class)->name('escaneo.index');

// ============================================================
// LOGOUT PERSONALIZADO (Redirige a /escanear)
// ============================================================
Route::post('/admin/logout', [LogoutController::class, 'logout'])->name('filament.admin.auth.logout');

Route::get('/pdf/demo', [PdfController::class, 'demo']);