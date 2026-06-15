<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class GuiaRemisionPdfExport
{
    protected $records;
    protected array $selectedColumns;

    public function __construct(Collection $records, array $selectedColumns = [])
    {
        $this->records = $records;
        $this->selectedColumns = $selectedColumns;
    }

    private function getAllColumns(): array
    {
        return [
            'numero_guia' => 'N° Guía',
            'producto_nombre' => 'Producto',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'Serie',
            'descripcion_completa' => 'Descripción',
            'fecha_emision' => 'Fecha Emisión',
        ];
    }

    public function getHeadings(): array
    {
        $columns = $this->getAllColumns();
        $keysToExport = empty($this->selectedColumns) ? array_keys($columns) : $this->selectedColumns;
        $headers = [];

        foreach ($keysToExport as $key) {
            $headers[] = $columns[$key];
        }

        return $headers;
    }

    public function getRows(): array
    {
        $rows = [];
        $columns = $this->getAllColumns();
        $keysToExport = empty($this->selectedColumns) ? array_keys($columns) : $this->selectedColumns;

        foreach ($this->records as $guia) {
            $row = [];
            foreach ($keysToExport as $key) {
                $row[] = match($key) {
                    'numero_guia' => $guia->numero_guia ?? '',
                    'producto_nombre' => $guia->producto?->nombre ?? '',
                    'marca' => $guia->marca ?? '',
                    'modelo' => $guia->modelo ?? '',
                    'serie' => $guia->serie ?? '',
                    'descripcion_completa' => $guia->descripcion_completa ?? '',
                    'fecha_emision' => $guia->fecha_emision?->format('d/m/Y') ?? '',
                    default => '',
                };
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function getContent()
    {
        $data = [
            'title' => 'GUÍAS DE REMISIÓN',
            'subtitle' => 'SISTEMA DE INVENTARIO',
            'date' => now()->format('d/m/Y H:i:s'),
            'headings' => $this->getHeadings(),
            'rows' => $this->getRows(),
            'total' => $this->records->count(),
        ];

        $pdf = Pdf::loadView('exports.guias-remision-pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->output();
    }

    public function download()
    {
        return response()->streamDownload(function () {
            echo $this->getContent();
        }, 'guias_remision_' . now()->format('Ymd_His') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}