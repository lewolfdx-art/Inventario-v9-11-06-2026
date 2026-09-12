<?php

namespace App\Http\Controllers;

use Mpdf\Mpdf;

class PdfController extends Controller
{
    public function demo()
    {
        $mpdf = new Mpdf([
            'tempDir'          => storage_path('app/mpdf-tmp'),
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'format'           => 'A4',
        ]);

        $html = view('pdf.demo', [
            'titulo' => 'Reporte de Inventario',
        ])->render();

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200)
            ->header('Content-Type', 'application/pdf');
    }
}