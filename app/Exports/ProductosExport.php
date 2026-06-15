<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductosExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents, WithStyles
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
            'sku'                       => ['header' => 'SKU',                       'value' => fn($p) => $p->sku ?? ''],
            'modelo'                    => ['header' => 'Modelo',                    'value' => fn($p) => $p->modelo ?? ''],
            'nombre'                    => ['header' => 'Nombre',                    'value' => fn($p) => $p->nombre ?? ''],
            'serie'                     => ['header' => 'Serie',                     'value' => fn($p) => $p->serie ?? ''],
            'categoria'                 => ['header' => 'Categoría',                 'value' => fn($p) => $p->categoria->nombre ?? ''],
            'subcategoria'              => ['header' => 'Subcategoría',              'value' => fn($p) => $p->subcategoria->nombre ?? ''],
            'marca'                     => ['header' => 'Marca',                     'value' => fn($p) => $p->marca->nombre ?? ''],
            'unidad_compra'             => ['header' => 'Unidad Compra',             'value' => fn($p) => $p->unidadCompra->nombre ?? ''],
            'naturaleza'                => ['header' => 'Naturaleza',                'value' => fn($p) => $p->naturaleza->nombre ?? ''],
            'estado'                    => ['header' => 'Estado',                    'value' => fn($p) => $p->estado->nombre ?? ''],
            'req_inventario'            => ['header' => 'Requiere Inventario',       'value' => fn($p) => $p->reqInventario->nombre ?? 'No'],
            'req_serie'                 => ['header' => 'Requiere Serie',            'value' => fn($p) => $p->reqSerie->nombre ?? 'No'],
            'req_lote'                  => ['header' => 'Requiere Lote',             'value' => fn($p) => $p->reqLote->nombre ?? 'No'],
            'req_calibracion'           => ['header' => 'Requiere Calibración',      'value' => fn($p) => $p->reqCalibracion->nombre ?? 'No'],
            'descripcion'               => ['header' => 'Descripción',               'value' => fn($p) => $p->descripcion ?? ''],
            'created_at'                => ['header' => 'Fecha Registro',            'value' => fn($p) => $p->created_at?->format('d/m/Y H:i:s') ?? ''],
            'updated_at'                => ['header' => 'Última Actualización',      'value' => fn($p) => $p->updated_at?->format('d/m/Y H:i:s') ?? ''],
        ];
    }

    public function map($producto): array
    {
        $columns = $this->getAllColumns();
        $row = [];

        $keysToExport = empty($this->selectedColumns) ? array_keys($columns) : $this->selectedColumns;

        foreach ($keysToExport as $key) {
            if (isset($columns[$key])) {
                $row[] = $columns[$key]['value']($producto);
            }
        }

        return $row;
    }

    public function headings(): array
    {
        $columns = $this->getAllColumns();
        $headers = [];

        $keysToExport = empty($this->selectedColumns) ? array_keys($columns) : $this->selectedColumns;

        foreach ($keysToExport as $key) {
            $headers[] = $columns[$key]['header'] ?? ucfirst($key);
        }

        return $headers;
    }

    public function title(): string
    {
        return 'Catálogo de Productos';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Solo el título tiene fondo verde
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Los encabezados NO tienen fondo verde
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insertar 2 filas al inicio
                $sheet->insertNewRowBefore(1, 2);

                $colCount = count($this->headings());
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

                // ==================== FILA 1: Título con fondo verde ====================
                $sheet->setCellValue('A1', 'CATÁLOGO DE PRODUCTOS - SISTEMA DE INVENTARIO');
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ==================== FILA 2: Fecha de generación (SIN fondo) ====================
                $sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i:s'));
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                // ==================== FILA 3: Encabezados (SIN fondo verde) ====================
                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ==================== BORDES a todas las celdas de datos ====================
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:{$lastColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                // ==================== CONGELAR PANEL ====================
                $sheet->freezePane('A4');
            },
        ];
    }
}