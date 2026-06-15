<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GuiaRemisionExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $records;
    protected array $selectedColumns;

    public function __construct(Collection $records, array $selectedColumns = [])
    {
        $this->records = $records;
        $this->selectedColumns = $selectedColumns;
    }

    public function collection()
    {
        return $this->records;
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
            'created_at' => 'Fecha Registro',
        ];
    }

    public function map($guia): array
    {
        $row = [];

        $keysToExport = empty($this->selectedColumns) ? array_keys($this->getAllColumns()) : $this->selectedColumns;

        foreach ($keysToExport as $key) {
            $row[] = match($key) {
                'numero_guia' => $guia->numero_guia ?? '',
                'producto_nombre' => $guia->producto->nombre ?? '',
                'marca' => $guia->marca ?? '',
                'modelo' => $guia->modelo ?? '',
                'serie' => $guia->serie ?? '',
                'descripcion_completa' => $guia->descripcion_completa ?? '',
                'fecha_emision' => $guia->fecha_emision?->format('d/m/Y') ?? '',
                'created_at' => $guia->created_at?->format('d/m/Y H:i:s') ?? '',
                default => '',
            };
        }

        return $row;
    }

    public function headings(): array
    {
        $columns = $this->getAllColumns();
        $keysToExport = empty($this->selectedColumns) ? array_keys($columns) : $this->selectedColumns;
        $headers = [];

        foreach ($keysToExport as $key) {
            $headers[] = $columns[$key];
        }

        return $headers;
    }

    public function title(): string
    {
        return 'Guías de Remisión';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}