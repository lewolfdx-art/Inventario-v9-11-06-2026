<?php

namespace App\Http\Controllers;

use App\Models\GuiaRequerimiento;
use App\Models\Imagen;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class GuiaRequerimientoPdfController extends Controller
{
    /**
     * Muestra el PDF en el navegador (inline).
     */
    public function __invoke(GuiaRequerimiento $guia)
    {
        return $this->generar($guia, 'inline');
    }

    /**
     * Fuerza la descarga del PDF.
     */
    public function download(GuiaRequerimiento $guia)
    {
        return $this->generar($guia, 'download');
    }

    /**
     * Genera el PDF con mPDF.
     */
    protected function generar(GuiaRequerimiento $guia, string $modo = 'inline')
    {
        // ✅ Cargar menciones también
        $guia->load(['items', 'firmas', 'menciones']);

        // ✅ Obtener el logo activo
        $logo = Imagen::activas()->tipo('logo')->first();
        $logoBase64 = null;
        
        if ($logo && file_exists(storage_path('app/public/' . $logo->archivo))) {
            $rutaCompleta = storage_path('app/public/' . $logo->archivo);
            $contenido = file_get_contents($rutaCompleta);
            $mimeType = mime_content_type($rutaCompleta);
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($contenido);
        }

        $html = view('pdf.guia-requerimiento', [
            'guia' => $guia,
            'logoUrl' => $logoBase64,
        ])->render();

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => [215.9, 279.4], // Letter
            'orientation'      => 'P',
            'margin_top'       => 8,
            'margin_bottom'    => 8,
            'margin_left'      => 8,
            'margin_right'     => 8,
            'margin_header'    => 0,
            'margin_footer'    => 0,
            'tempDir'          => storage_path('app/mpdf-tmp'),
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'default_font'     => 'dejavusans',
            
            // ✅ NUEVO: Desactivar auto-ajuste de tablas
            'shrink_tables_to_fit' => 0,
        ]);

        $mpdf->WriteHTML($html);

        $nombre = 'requerimiento-' . $guia->codigo . '-' . $guia->id . '.pdf';

        $contenido = $mpdf->Output($nombre, Destination::STRING_RETURN);

        $disposicion = $modo === 'download'
            ? 'attachment'
            : 'inline';

        return response($contenido, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => $disposicion . '; filename="' . $nombre . '"',
        ]);
    }
}