<?php
// app/Exports/MaletinChecklistExportPlantilla.php
//
// Carga la plantilla .xlsx (encabezado, logo, cuadro de revisión, datos del proyecto
// y cabecera de la tabla ya diseñados en Excel) y rellena:
//   - Título (F1) y cuadro de revisión (O1 / R1:U2 / O3 / R3:U4) con los campos del formulario
//   - FECHA DE ATENCIÓN (N9), RESPONSABLE SOLICITANTE (G8)
//   - Fila 10: FECHA DE DEVOLUCIÓN (D10)
//   - Items desde la fila 13: A ITEM | B CANT | C:R CONTENIDO | S SI | T NO | U ESTADO
//   - Observaciones y firmas (dinámicas, debajo de la tabla)
//
// Uso:
//   $export = new MaletinChecklistExportPlantilla($maletin);                        // plantilla por defecto
//   $export = new MaletinChecklistExportPlantilla($maletin, storage_path('app/templates/otra.xlsx'));
//   return $export->download();

namespace App\Exports;

use App\Models\Maletin;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MaletinChecklistExportPlantilla
{
    protected $maletin;
    protected string $plantilla;

    // Estructura de la plantilla "Formato_Maletines.xlsx":
    protected const FILA_CABECERA = 12;      // ITEM/CANT/CONTENIDO/SI/NO/ESTADO
    protected const FILA_INICIO = 13;         // primera fila de items
    protected const ULT = 'U';                // última columna (U = ESTADO)

    protected string $tituloSet = 'SET DE ACCESORIOS DE CONEXIÓN DE LA LÍNEA:';
    protected string $tituloAdicional = 'PAQUETE ADICIONAL DE ACCESORIOS:';

    public function __construct(Maletin $maletin, ?string $plantilla = null)
    {
        $this->maletin = $maletin;

        if ($plantilla !== null) {
            $this->plantilla = $plantilla;
            return;
        }

        // Prioridad: plantilla personalizada en storage (permite editarla).
        // Si no existe, usa la plantilla que viene incluida con la aplicación.
        $enStorage = storage_path('app/templates/Formato_Maletines.xlsx');
        $this->plantilla = is_file($enStorage)
            ? $enStorage
            : resource_path('templates/Formato_Maletines.xlsx');
    }

    protected function estadoLabel($estado)
    {
        return match ($estado) {
            'falta' => 'FALTA',
            'danado' => 'DAÑADO',
            default => 'OK',
        };
    }

    protected function estadoLabelColor($estado)
    {
        return match ($estado) {
            'falta', 'danado' => 'FFC00000',
            'ok' => 'FF1B5E20',
            default => 'FF000000',
        };
    }

    protected function formatItemNumber($num)
    {
        $num = (int) $num;

        if ($num >= 100) {
            return intdiv($num, 100) . '.' . str_pad((string) ($num % 100), 2, '0', STR_PAD_LEFT);
        }

        return sprintf('%d.00', $num);
    }

    /**
     * Combina el rango (si no está ya combinado), escribe el texto y aplica fuente y alineación.
     * Opciones: size, bold, h, v, wrap, shrink, color, font
     */
    protected function box($ws, string $range, $text, array $o = []): void
    {
        $first = explode(':', $range)[0];

        if (str_contains($range, ':') && !isset($ws->getMergeCells()[$range])) {
            $ws->mergeCells($range);
        }
        if ($text !== null && $text !== '') {
            $ws->setCellValueExplicit($first, (string) $text, DataType::TYPE_STRING);
        }

        $st = $ws->getStyle($range);
        $st->getFont()
            ->setName($o['font'] ?? 'Calibri')
            ->setSize($o['size'] ?? 9)
            ->setBold($o['bold'] ?? false);
        $st->getFont()->getColor()->setARGB($o['color'] ?? 'FF000000');
        $st->getAlignment()
            ->setHorizontal($o['h'] ?? Alignment::HORIZONTAL_LEFT)
            ->setVertical($o['v'] ?? Alignment::VERTICAL_CENTER)
            ->setWrapText($o['wrap'] ?? false)
            ->setShrinkToFit($o['shrink'] ?? false);
    }

    /** Parte el texto de observaciones en 3 líneas (F:U, A:U, A:U). */
    protected function splitObs(?string $texto): array
    {
        $texto = trim((string) preg_replace('/\s+/', ' ', (string) $texto));
        $limites = [85, 115, 115];
        $out = ['', '', ''];
        $i = 0;

        foreach ($texto === '' ? [] : explode(' ', $texto) as $w) {
            if ($i < 2 && mb_strlen(trim($out[$i] . ' ' . $w)) > $limites[$i]) {
                $i++;
            }
            $out[$i] = trim($out[$i] . ' ' . $w);
        }

        return $out;
    }

    protected function buildSpreadsheet(): Spreadsheet
    {
        $maletin = $this->maletin;
        $center = Alignment::HORIZONTAL_CENTER;
        $ult = self::ULT;

        if (!is_file($this->plantilla)) {
            throw new \RuntimeException('No se encontró la plantilla: ' . $this->plantilla);
        }

        $ss = IOFactory::load($this->plantilla);

        // Se deja solo la primera hoja
        while ($ss->getSheetCount() > 1) {
            $ss->removeSheetByIndex($ss->getSheetCount() - 1);
        }
        $ws = $ss->getSheet(0);
        $ss->setActiveSheetIndex(0);
        $ws->setTitle('Checklist');

        // ===== TÍTULO (F1) =====
        $ws->setCellValueExplicit('F1', 'CHECK LIST DE ENTREGA - ' . Str::upper($maletin->nombre), DataType::TYPE_STRING);

        // ===== CUADRO DE REVISIÓN desde el formulario =====
        $marca = ['font' => 'Arial', 'size' => 8, 'bold' => true, 'h' => $center, 'wrap' => true];
        $this->box($ws, 'O1:Q2', "Revisado:\n" . ($maletin->revisado_por ?: 'RED'), $marca);
        $this->box($ws, 'R1:U2', "Versión:\n" . ($maletin->version ?: '00'), $marca);
        $this->box($ws, 'O3:Q4', "Aprobado:\n" . ($maletin->aprobado_por ?: 'GG'), $marca);
        $this->box($ws, 'R3:U4', "Fecha:\n" . ($maletin->fecha_documento ?: '01/07/14'), $marca);

        // ===== DATOS DEL PROYECTO =====
        if ($maletin->responsable_solicitante) {
            $this->box($ws, 'G8:T8', $maletin->responsable_solicitante, [
                'size' => 9, 'bold' => true, 'v' => Alignment::VERTICAL_BOTTOM,
            ]);
        }

        // FECHA DE ATENCIÓN (N9) - la plantilla ya trae la etiqueta en J9:M9
        $fechaAtencionTxt = $maletin->fecha_atencion ? $maletin->fecha_atencion->format('d/m/Y') : '';
        $this->box($ws, 'N9:T9', $fechaAtencionTxt, [
            'size' => 9, 'bold' => true, 'h' => $center,
            'color' => $maletin->fecha_atencion ? 'FF1B5E20' : 'FF000000',
        ]);

        // Fila 10 (la plantilla ya trae "FECHA DE DEVOLUCION:" en A10): valor en D10
        $fechaDevTxt = $maletin->fecha_devolucion ? $maletin->fecha_devolucion->format('d/m/Y') : 'Pendiente';
        $this->box($ws, 'D10:I10', $fechaDevTxt, [
            'size' => 9, 'bold' => true, 'h' => $center,
            'color' => $maletin->fecha_devolucion ? 'FF1B5E20' : 'FFC00000',
        ]);

        $ws->getStyle('D10:I10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        // ===== LIMPIAR LA ZONA DE ITEMS DE LA PLANTILLA (desde la fila 13) =====
        $ini = self::FILA_INICIO;
        $fin = max($ini, $ws->getHighestRow());
        foreach ($ws->getMergeCells() as $rango) {
            if (preg_match('/^[A-Z](\d+):[A-Z](\d+)$/', $rango, $m)) {
                $r1 = (int) $m[1];
                if ($r1 >= $ini) {
                    $ws->unmergeCells($rango);
                }
            }
        }
        for ($r = $ini; $r <= $fin; $r++) {
            foreach (range('A', 'X') as $col) {
                $ws->setCellValue($col . $r, null);
            }
        }
        $ws->getStyle("A{$ini}:X{$fin}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        $ws->getStyle("A{$ini}:X{$fin}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_NONE);

        // ===== ZONA DE ITEMS =====
        $fila = $ini;

        // Fila de item: A ITEM | B CANT | C:R CONTENIDO | S SI | T NO | U ESTADO
        $push = function ($numero, $cant, $desc, $estado, array $o = []) use ($ws, &$fila, $center) {
            $size = $o['size'] ?? 9;
            $bold = $o['bold'] ?? false;

            $ws->setCellValueExplicit('A' . $fila, (float) $numero, DataType::TYPE_NUMERIC);
            $ws->getStyle('A' . $fila)->getNumberFormat()->setFormatCode('0.00');
            $this->box($ws, 'A' . $fila, null, ['size' => 9, 'h' => $center]);

            if ($cant !== '' && $cant !== null) {
                $ws->setCellValueExplicit('B' . $fila, (int) $cant, DataType::TYPE_NUMERIC);
                $ws->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('00');
            }
            $this->box($ws, 'B' . $fila, null, ['size' => $size, 'bold' => $bold, 'h' => $center]);

            $desc = (string) $desc;
            $this->box($ws, "C{$fila}:R{$fila}", $desc, ['size' => $size, 'bold' => $bold, 'wrap' => true]);

            $this->box($ws, 'S' . $fila, $estado === 'ok' ? 'X' : '', ['size' => 9, 'bold' => true, 'h' => $center]);
            $this->box($ws, 'T' . $fila, ($estado === 'falta' || $estado === 'danado') ? 'X' : '', [
                'size' => 9, 'bold' => true, 'h' => $center, 'color' => 'FFC00000',
            ]);

            $this->box($ws, 'U' . $fila, $estado === null ? '' : $this->estadoLabel($estado), [
                'size' => 8, 'bold' => true, 'h' => $center, 'color' => $this->estadoLabelColor($estado),
            ]);

            $ws->getRowDimension($fila)->setRowHeight(mb_strlen($desc) > 85 ? 24 : ($o['height'] ?? 14.25));

            $fila++;
        };

        // Fila de sección (3.00 / 4.00): A número | B:U título
        $section = function ($numero, $titulo) use ($ws, &$fila, $center, $ult) {
            $ws->setCellValueExplicit('A' . $fila, (float) $numero, DataType::TYPE_NUMERIC);
            $ws->getStyle('A' . $fila)->getNumberFormat()->setFormatCode('0.00');
            $this->box($ws, 'A' . $fila, null, ['size' => 9, 'h' => $center]);
            $this->box($ws, "B{$fila}:{$ult}{$fila}", $titulo, ['size' => 9, 'bold' => true, 'h' => $center, 'wrap' => true]);
            $ws->getRowDimension($fila)->setRowHeight(17.25);
            $fila++;
        };

        // 1.00 / 2.00: componentes del equipo
        foreach ($maletin->componentesEquipo as $c) {
            $push($this->formatItemNumber($c->item_numero), $c->cantidad, $c->descripcion, $c->estado_devolucion, [
                'size' => 8, 'bold' => true, 'height' => 15.75,
            ]);
        }

        // 3.xx: set de accesorios
        if ($maletin->accesoriosSet->isNotEmpty()) {
            $section(3, $this->tituloSet);
            foreach ($maletin->accesoriosSet as $a) {
                $push($this->formatItemNumber($a->item_numero), $a->cantidad, ' - ' . $a->descripcion, $a->estado_devolucion);
            }
        }

        // 4.xx: paquete adicional
        if ($maletin->accesoriosAdicionales->isNotEmpty()) {
            $section(4, $this->tituloAdicional);
            foreach ($maletin->accesoriosAdicionales as $a) {
                $push($this->formatItemNumber($a->item_numero), $a->cantidad, ' - ' . $a->descripcion, $a->estado_devolucion, ['height' => 15]);
            }
        }

        // ===== BORDES DE LA TABLA: interior hair, contorno thin =====
        $head = self::FILA_CABECERA;
        $ultima = max($fila - 1, $ini);
        $ws->getStyle("A{$ini}:{$ult}{$ultima}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle("A{$head}:{$ult}{$ultima}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("A{$ini}:{$ult}{$ini}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        foreach (['A' => 'getRight', 'R' => 'getRight', 'S' => 'getLeft', 'U' => 'getLeft'] as $col => $lado) {
            $ws->getStyle("{$col}{$ini}:{$col}{$ultima}")->getBorders()->$lado()->setBorderStyle(Border::BORDER_THIN);
        }

        // ===== OBSERVACIONES (3 líneas) =====
        $ws->getRowDimension($fila)->setRowHeight(6);
        $fila++;

        $obs = $fila;
        $lineas = $this->splitObs($maletin->observaciones);
        $this->box($ws, "A{$obs}:E{$obs}", 'OBSERVACIONES:', ['size' => 11, 'bold' => true]);
        $this->box($ws, "F{$obs}:{$ult}{$obs}", $lineas[0], ['wrap' => true]);
        $this->box($ws, 'A' . ($obs + 1) . ":{$ult}" . ($obs + 1), $lineas[1], ['wrap' => true]);
        $this->box($ws, 'A' . ($obs + 2) . ":{$ult}" . ($obs + 2), $lineas[2], ['wrap' => true]);

        $ws->getRowDimension($obs)->setRowHeight(18);
        $ws->getRowDimension($obs + 1)->setRowHeight(18);
        $ws->getRowDimension($obs + 2)->setRowHeight(max(18, ceil(mb_strlen($lineas[2]) / 115) * 12 + 6));

        $ws->getStyle("A{$obs}:{$ult}" . ($obs + 2))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle("A{$obs}:{$ult}" . ($obs + 2))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

        // ===== FIRMAS =====
        $f = $obs + 3;
        for ($i = 0; $i < 4; $i++) {      // espacio para firmar (4 filas de 12)
            $ws->getRowDimension($f + $i)->setRowHeight(12);
        }
        $f += 4;

        $this->box($ws, "C{$f}:G{$f}", 'ENTREGADO POR', ['size' => 10, 'h' => $center]);
        $this->box($ws, "O{$f}:S{$f}", 'RECIBIDO POR', ['size' => 10, 'h' => $center]);
        $ws->getRowDimension($f)->setRowHeight(15);

        $f++;
        $this->box($ws, "A{$f}:B{$f}", 'N. y A.', ['size' => 10, 'shrink' => true]);
        $this->box($ws, "I{$f}:N{$f}", 'N. y A.', ['size' => 10, 'h' => Alignment::HORIZONTAL_RIGHT]);
        $this->box($ws, "C{$f}:G{$f}", '', ['size' => 10, 'h' => $center]);
        $this->box($ws, "O{$f}:S{$f}", '', ['size' => 10, 'h' => $center]);
        $ws->getRowDimension($f)->setRowHeight(13.5);

        $f++;
        $this->box($ws, "A{$f}:B{$f}", 'DNI N°', ['size' => 10, 'shrink' => true]);
        $this->box($ws, "I{$f}:N{$f}", 'DNI N°', ['size' => 10, 'h' => Alignment::HORIZONTAL_RIGHT]);
        $this->box($ws, "C{$f}:G{$f}", '', ['size' => 10, 'h' => $center]);
        $this->box($ws, "O{$f}:S{$f}", '', ['size' => 10, 'h' => $center]);
        $ws->getRowDimension($f)->setRowHeight(13.5);

        // Recuadro de firmas: solo la línea superior (ENTREGADO POR / RECIBIDO POR)
        $signHeader = $f - 2;
        $ws->getStyle("C{$signHeader}:G{$signHeader}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("O{$signHeader}:S{$signHeader}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        // ===== IMPRESIÓN: A4 vertical, ajustar a 1 página de ancho =====
        $ws->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea("A1:{$ult}{$f}");

        return $ss;
    }

    public function getContent()
    {
        $ss = $this->buildSpreadsheet();
        $writer = new Xlsx($ss);

        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    public function download()
    {
        return response()->streamDownload(function () {
            echo $this->getContent();
        }, 'checklist_' . Str::slug($this->maletin->nombre) . '_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}