<?php

namespace App\Exports;

use App\Models\GuiaRequerimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class GuiaRequerimientoPdfExport
{
    protected GuiaRequerimiento $guia;

    public function __construct(GuiaRequerimiento $guia)
    {
        $this->guia = $guia->load(['items', 'firmas']);
    }

    /**
     * Descarga el PDF.
     */
    public function download(): Response
    {
        $pdf = Pdf::loadView('pdf.guia-requerimiento', [
            'guia' => $this->guia,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        $nombre = 'guia-requerimiento-' . $this->guia->codigo . '-' . $this->guia->id . '.pdf';

        return $pdf->download($nombre);
    }

    /**
     * Muestra el PDF en el navegador.
     */
    public function stream(): Response
    {
        $pdf = Pdf::loadView('pdf.guia-requerimiento', [
            'guia' => $this->guia,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->stream('guia-requerimiento-' . $this->guia->id . '.pdf');
    }

    /**
     * Devuelve el PDF como string.
     */
    public function output(): string
    {
        return Pdf::loadView('pdf.guia-requerimiento', [
            'guia' => $this->guia,
        ])
            ->setPaper('a4', 'portrait')
            ->output();
    }
}