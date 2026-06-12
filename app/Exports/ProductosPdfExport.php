<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductosPdfExport
{
    protected $records;
    protected array $selectedColumns;

    public function __construct(Collection $records, array $selectedColumns = [])
    {
        $this->records = $records;
        $this->selectedColumns = $selectedColumns;
    }

    private function cleanString($string)
    {
        if ($string === null) return '';
        $string = (string) $string;
        // Eliminar caracteres no válidos
        $string = preg_replace('/[^\x20-\x7E\xC0-\xFF]/u', '', $string);
        $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        return trim($string);
    }

    private function getAllColumns(): array
    {
        return [
            'sku' => 'SKU',
            'modelo' => 'Modelo',
            'nombre' => 'Nombre',
            'categoria' => 'Categoría',
            'subcategoria' => 'Subcategoría',
            'marca' => 'Marca',
            'unidad_compra' => 'Unidad de Compra',
            'naturaleza' => 'Naturaleza',
            'estado' => 'Estado',
            'req_inventario' => 'Requiere Inventario',
            'req_serie' => 'Requiere Serie',
            'req_lote' => 'Requiere Lote',
            'req_calibracion' => 'Requiere Calibración',
            'descripcion' => 'Descripción',
            'created_at' => 'Fecha Registro',
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

        foreach ($this->records as $producto) {
            $row = [];
            foreach ($keysToExport as $key) {
                $value = match($key) {
                    'categoria' => $producto->categoria->nombre ?? '',
                    'subcategoria' => $producto->subcategoria->nombre ?? '',  
                    'marca' => $producto->marca->nombre ?? '',
                    'unidad_compra' => $producto->unidadCompra->nombre ?? '',
                    'naturaleza' => $producto->naturaleza->nombre ?? '',
                    'estado' => $producto->estado->nombre ?? '',
                    'req_inventario' => $producto->reqInventario->nombre ?? '',
                    'req_serie' => $producto->reqSerie->nombre ?? '',
                    'req_lote' => $producto->reqLote->nombre ?? '',
                    'req_calibracion' => $producto->reqCalibracion->nombre ?? '',
                    default => $producto->$key ?? '',
                };
                $row[] = $this->cleanString($value);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function getContent()
    {
        $data = [
            'title' => 'CATÁLOGO DE PRODUCTOS',
            'subtitle' => 'SISTEMA DE INVENTARIO',
            'date' => now()->format('d/m/Y H:i:s'),
            'headings' => $this->getHeadings(),
            'rows' => $this->getRows(),
            'total' => $this->records->count(),
        ];

        $pdf = Pdf::loadView('exports.productos-pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->output();
    }

    public function download()
    {
        return response()->streamDownload(function () {
            echo $this->getContent();
        }, 'catalogo_productos_' . now()->format('Ymd_His') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}