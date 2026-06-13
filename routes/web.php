<?php

use Illuminate\Support\Facades\Route;
use App\Models\GuiaRemision;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/guia-remision/imprimir/{guia}', function (GuiaRemision $guia) {
    return view('pdf.guia-remision', compact('guia'));
})->name('guia-remision.imprimir');