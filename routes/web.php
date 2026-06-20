<?php

use Illuminate\Support\Facades\Route;
use App\Models\GuiaRemision;
use App\Models\Producto;
use App\Http\Controllers\ProductoBarcodeController;
use App\Http\Controllers\EtiquetaZPLController;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Livewire\Escaner;
use App\Http\Controllers\LogoutController;

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
// ZPL - IMPRESORA ZEBRA
// ============================================================
Route::get('/etiqueta-zpl/producto/{producto}', [EtiquetaZPLController::class, 'generate'])
    ->name('etiqueta-zpl.producto');

// ============================================================
// ESCÁNER CON LIVEWIRE
// ============================================================
Route::get('/escanear', Escaner::class)->name('escaneo.index');

// ============================================================
// LOGOUT PERSONALIZADO (Redirige a /escanear)
// ============================================================
Route::post('/admin/logout', [LogoutController::class, 'logout'])->name('filament.admin.auth.logout');