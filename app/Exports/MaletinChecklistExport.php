<?php
// app/Exports/MaletinChecklistExport.php
//
// Replica la plantilla "Plantilla_FO_T_07_v0__ISA_CBA_3000.xlsx":
//   - Cuadrícula de columnas angostas A:T (A-G = 4.33, H = 7.0, I-T = 4.33)
//   - ITEM = A | CANT = B | CONTENIDO = C:R (combinadas) | SI = S | NO = T
//   - Columna agregada U = ESTADO (FALTA / DAÑADO / OK), junto a NO
//   - Fila 10 agregada: FECHA DE ATENCIÓN + ESTADO del maletín
//   - Alturas de fila, combinaciones, fuentes y bordes de la plantilla

namespace App\Exports;

use App\Models\Maletin;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MaletinChecklistExport
{
    protected $maletin;

    public function __construct(Maletin $maletin)
    {
        $this->maletin = $maletin;
    }

    protected function formatItemNumber($num)
    {
        $num = (int) $num;

        if ($num >= 100) {
            return intdiv($num, 100) . '.' . str_pad((string) ($num % 100), 2, '0', STR_PAD_LEFT);
        }

        return sprintf('%d.00', $num);
    }

    protected function estadoLabel($estado)
    {
        return match ($estado) {
            'falta' => 'FALTA',
            'danado' => 'DAÑADO',
            'ok' => 'OK',
            default => '',
        };
    }

    protected function estadoLabelColor($estado)
    {
        return match ($estado) {
            'falta', 'danado' => 'FFC00000',
            'ok' => 'FF1B5E20',
            default => null,
        };
    }

    protected function maletinEstadoLabel($estado)
    {
        return match ($estado) {
            'activo' => 'ACTIVO',
            'prestado' => 'PRESTADO',
            'devuelto' => 'DEVUELTO',
            'mantenimiento' => 'EN MANTENIMIENTO',
            default => mb_strtoupper((string) ($estado ?: '')),
        };
    }

    protected function maletinEstadoColor($estado)
    {
        return match ($estado) {
            'activo' => 'FF1565C0',
            'prestado' => 'FFF08A00',
            'devuelto' => 'FF1B5E20',
            'mantenimiento' => 'FFC00000',
            default => null,
        };
    }

    protected function logoPath(): ?string
    {
        $logoImagen = \App\Models\Imagen::activas()->tipo('logo')->first();

        if ($logoImagen && file_exists(storage_path('app/public/' . $logoImagen->archivo))) {
            return storage_path('app/public/' . $logoImagen->archivo);
        }

        foreach (['images/logo.png', 'images/logo.jpg', 'images/logo.jpeg', 'images/login-logo.png'] as $ruta) {
            $completa = public_path($ruta);
            if (file_exists($completa)) {
                return $completa;
            }
        }

        return null;
    }

    /**
     * Combina el rango (si es de varias celdas), escribe el texto y aplica fuente y alineación.
     * Opciones: font, size, bold, h, v, wrap, shrink, color
     */
    protected function box($ws, string $range, $text, array $o = []): void
    {
        $first = explode(':', $range)[0];

        if (str_contains($range, ':')) {
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
        if (!empty($o['color'])) {
            $st->getFont()->getColor()->setARGB($o['color']);
        }
        $st->getAlignment()
            ->setHorizontal($o['h'] ?? Alignment::HORIZONTAL_LEFT)
            ->setVertical($o['v'] ?? Alignment::VERTICAL_CENTER)
            ->setWrapText($o['wrap'] ?? false)
            ->setShrinkToFit($o['shrink'] ?? false);
    }

    /** Parte el texto de observaciones en las 3 líneas de la plantilla (A:T, A:T, A:T). */
    protected function splitObs(?string $texto): array
    {
        $texto = trim((string) preg_replace('/\s+/', ' ', (string) $texto));
        $limites = [70, 100, 100];
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

        $ss = new Spreadsheet();
        $ws = $ss->setActiveSheetIndex(0);
        $ws->setTitle('Checklist');

        // ===== ANCHOS DE COLUMNA (plantilla + U para ESTADO) =====
        foreach (range('A', 'T') as $col) {
            $ws->getColumnDimension($col)->setWidth($col === 'H' ? 7.0 : 4.33);
        }
        $ws->getColumnDimension('U')->setWidth(9);

        // ===== ALTURAS DE LAS FILAS FIJAS DE LA PLANTILLA =====
        foreach ([1, 2, 3, 4] as $r) {
            $ws->getRowDimension($r)->setRowHeight(16.5);
        }
        $ws->getRowDimension(5)->setRowHeight(6.75);
        foreach ([6, 7, 8, 9, 10] as $r) {
            $ws->getRowDimension($r)->setRowHeight(13.5);
        }
        $ws->getRowDimension(11)->setRowHeight(5.25);

        // ===== ENCABEZADO (filas 1-4) =====
        // Logo: A1:E4
        $ws->mergeCells('A1:E4');
        if ($logoPath = $this->logoPath()) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setResizeProportional(true);
            $drawing->setHeight(70);
            if ($drawing->getWidth() > 168) {
                $drawing->setWidth(168);
            }
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(6);
            $drawing->setOffsetY(8);
            $drawing->setWorksheet($ws);
        } else {
            $this->box($ws, 'A1:E4', "O&T\nEnergy", [
                'font' => 'Arial', 'size' => 16, 'bold' => true, 'h' => $center, 'wrap' => true, 'color' => 'FFF08A00',
            ]);
        }

        // Título: F1:N4
        $this->box($ws, 'F1:N4', 'CHECK LIST DE ENTREGA - ' . Str::upper($maletin->nombre), [
            'font' => 'Arial', 'size' => 11, 'bold' => true, 'h' => $center, 'wrap' => true,
        ]);

        // Cuadro de revisión: 4 cajas de 3 columnas x 2 filas
        $rev = ['font' => 'Arial', 'size' => 8, 'bold' => true, 'h' => $center, 'wrap' => true];
        $this->box($ws, 'O1:Q2', "Revisado:\nRED", $rev);
        $this->box($ws, 'R1:T2', "Versión:\n00", $rev);
        $this->box($ws, 'O3:Q4', "Aprobado:\nGG", $rev);
        $this->box($ws, 'R3:T4', "Fecha:\n01/07/14", $rev);

        foreach (['A1:E4', 'F1:N4', 'O1:Q2', 'R1:T2', 'O3:Q4', 'R3:T4'] as $r) {
            $ws->getStyle($r)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // ===== DATOS DEL PROYECTO (filas 6-10) =====
        $this->box($ws, 'A6:G6', 'NOMBRE DEL PROYECTO Y CLIENTE:', ['size' => 10, 'bold' => true, 'shrink' => true]);
        $ws->getStyle('H6:T6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle('A7:T7')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        $this->box($ws, 'A8:F8', 'RESPONSABLE SOLICITANTE:', ['size' => 9, 'bold' => true, 'shrink' => true]);
        $ws->getStyle('G8:T8')->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle('G8:T8')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        $this->box($ws, 'A9:C9', 'FECHA DE PEDIDO:', ['size' => 8, 'bold' => true, 'shrink' => true]);
        $this->box($ws, 'D9:I9', '', ['size' => 9]);
        $ws->getStyle('D9:I9')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        $this->box($ws, 'J9:M9', 'FECHA DE DEVOLUCIÓN:', ['size' => 8, 'bold' => true, 'shrink' => true]);
        $fechaTxt = $maletin->fecha_devolucion ? $maletin->fecha_devolucion->format('d/m/Y') : 'Pendiente';
        $this->box($ws, 'N9:T9', $fechaTxt, [
            'size' => 9,
            'bold' => true,
            'h' => $center,
            'color' => $maletin->fecha_devolucion ? 'FF1B5E20' : 'FFC00000',
        ]);
        $ws->getStyle('N9:T9')->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle('N9:T9')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        // Fila 10 (agregada): FECHA DE ATENCIÓN + ESTADO del maletín
        $this->box($ws, 'A10:C10', 'FECHA DE ATENCIÓN:', ['size' => 8, 'bold' => true, 'shrink' => true]);
        $fechaAtencionTxt = $maletin->fecha_atencion ? $maletin->fecha_atencion->format('d/m/Y') : 'Pendiente';
        $this->box($ws, 'D10:I10', $fechaAtencionTxt, [
            'size' => 9,
            'bold' => true,
            'h' => $center,
            'color' => $maletin->fecha_atencion ? 'FF1B5E20' : 'FFC00000',
        ]);
        $ws->getStyle('D10:I10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        $this->box($ws, 'J10:M10', 'ESTADO:', ['size' => 8, 'bold' => true, 'shrink' => true]);
        $this->box($ws, 'N10:T10', $this->maletinEstadoLabel($maletin->estado), [
            'size' => 9,
            'bold' => true,
            'h' => $center,
            'color' => $this->maletinEstadoColor($maletin->estado) ?? 'FF000000',
        ]);
        $ws->getStyle('N10:T10')->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle('N10:T10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);

        // ===== CABECERA DE LA TABLA (fila 12) =====
        $head = 12;
        $ws->getRowDimension($head)->setRowHeight(15.75);
        $this->box($ws, "A{$head}", 'ITEM', ['bold' => true, 'h' => $center]);
        $this->box($ws, "B{$head}", 'CANT.', ['bold' => true, 'h' => $center]);
        $this->box($ws, "C{$head}:R{$head}", 'CONTENIDO DE LA MALETA', ['size' => 10, 'bold' => true, 'h' => $center]);
        $this->box($ws, "S{$head}", 'SI', ['size' => 10, 'bold' => true, 'h' => $center]);
        $this->box($ws, "T{$head}", 'NO', ['size' => 10, 'bold' => true, 'h' => $center]);
        $this->box($ws, "U{$head}", 'ESTADO', ['size' => 8, 'bold' => true, 'h' => $center]);

        $fila = $head + 1;

        // Fila de item: A = número, B = cantidad, C:R = descripción, S = SI, T = NO, U = ESTADO
        $push = function ($numero, $cant, $desc, $estado, array $o = []) use ($ws, &$fila, $center) {
            $size = $o['size'] ?? 9;
            $bold = $o['bold'] ?? false;

            // ITEM como número real con formato 0.00 (no genera triángulo verde)
            $ws->setCellValueExplicit('A' . $fila, (float) $numero, DataType::TYPE_NUMERIC);
            $ws->getStyle('A' . $fila)->getNumberFormat()->setFormatCode('0.00');
            $this->box($ws, 'A' . $fila, null, ['size' => 9, 'h' => $center]);

            // CANT como número con formato 00 (se ve "01", "03", "20")
            if ($cant !== '' && $cant !== null) {
                $ws->setCellValueExplicit('B' . $fila, (int) $cant, DataType::TYPE_NUMERIC);
                $ws->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('00');
            }
            $this->box($ws, 'B' . $fila, null, ['size' => $size, 'bold' => $bold, 'h' => $center]);

            // Descripción en C:R combinadas
            $desc = (string) $desc;
            $this->box($ws, "C{$fila}:R{$fila}", $desc, ['size' => $size, 'bold' => $bold, 'wrap' => true]);

            // SI / NO
            $marcaSi = $estado === 'ok' ? 'X' : '';
            $marcaNo = ($estado === 'falta' || $estado === 'danado') ? 'X' : '';
            $this->box($ws, 'S' . $fila, $marcaSi, ['size' => 9, 'bold' => true, 'h' => $center]);
            $this->box($ws, 'T' . $fila, $marcaNo, ['size' => 9, 'bold' => true, 'h' => $center, 'color' => 'FFC00000']);

            // ESTADO junto a NO
            $this->box($ws, 'U' . $fila, $this->estadoLabel($estado), [
                'size' => 8,
                'bold' => ($estado === 'falta' || $estado === 'danado' || $estado === 'ok'),
                'h' => $center,
                'shrink' => true,
                'color' => $this->estadoLabelColor($estado) ?? 'FF000000',
            ]);
            $ws->getStyle('U' . $fila)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);

            // Altura: la fila no crece sola dentro de celdas combinadas, así que se calcula
            $alto = $o['height'] ?? 14.25;
            if (mb_strlen($desc) > 95) {
                $alto = max($alto, 26);
            }
            $ws->getRowDimension($fila)->setRowHeight($alto);

            $fila++;
        };

        // Fila de sección (3.00 / 4.00): A = número, B:U = título combinado
        $section = function ($numero, $titulo, $alto) use ($ws, &$fila, $center) {
            $ws->setCellValueExplicit('A' . $fila, (float) $numero, DataType::TYPE_NUMERIC);
            $ws->getStyle('A' . $fila)->getNumberFormat()->setFormatCode('0.00');
            $this->box($ws, 'A' . $fila, null, ['size' => 9, 'h' => $center]);
            $this->box($ws, "B{$fila}:U{$fila}", $titulo, ['size' => 9, 'bold' => true, 'h' => $center, 'wrap' => true]);
            $ws->getRowDimension($fila)->setRowHeight($alto);
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
            $section(3, 'SET DE ACCESORIOS DE CONEXIÓN DE LA LÍNEA:', 17.25);
            foreach ($maletin->accesoriosSet as $a) {
                $push($this->formatItemNumber($a->item_numero), $a->cantidad, $a->descripcion, $a->estado_devolucion);
            }
        }

        // 4.xx: paquete adicional (la plantilla siempre deja 3 filas 4.01-4.03)
        $section(4, 'PAQUETE ADICIONAL DE ACCESORIOS:', 15);
        $n = 0;
        foreach ($maletin->accesoriosAdicionales as $a) {
            $push($this->formatItemNumber($a->item_numero), $a->cantidad, $a->descripcion, $a->estado_devolucion, ['height' => 15]);
            $n++;
        }
        for ($i = $n + 1; $i <= 3; $i++) {
            $push(4 + $i / 100, '', '', null, ['height' => 15]);
        }

        // ===== BORDES DE LA TABLA: interior hair, contorno thin =====
        $ultima = $fila - 1;
        $ws->getStyle("A{$head}:U{$ultima}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle("A{$head}:U{$ultima}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("A{$head}:U{$head}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("S{$head}:S{$ultima}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("A{$head}:A{$ultima}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("U{$head}:U{$ultima}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);

        // ===== OBSERVACIONES (3 líneas: A:T, A:T, A:T) =====
        $ws->getRowDimension($fila)->setRowHeight(6);
        $fila++;

        $obs = $fila;
        $lineas = $this->splitObs($maletin->observaciones);
        $this->box($ws, "A{$obs}:E{$obs}", 'OBSERVACIONES:', ['size' => 11, 'bold' => true]);
        $this->box($ws, "F{$obs}:T{$obs}", $lineas[0], ['size' => 9, 'wrap' => true]);
        $this->box($ws, 'A' . ($obs + 1) . ':T' . ($obs + 1), $lineas[1], ['size' => 9, 'wrap' => true]);
        $this->box($ws, 'A' . ($obs + 2) . ':T' . ($obs + 2), $lineas[2], ['size' => 9, 'wrap' => true]);

        $ws->getRowDimension($obs)->setRowHeight(18);
        $ws->getRowDimension($obs + 1)->setRowHeight(18);
        $ws->getRowDimension($obs + 2)->setRowHeight(max(18, ceil(mb_strlen($lineas[2]) / 100) * 12 + 6));

        $ws->getStyle("A{$obs}:T" . ($obs + 2))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
        $ws->getStyle("A{$obs}:T" . ($obs + 2))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

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

        // ===== IMPRESIÓN (como la plantilla: A4 vertical, área A1:U<última>) =====
        $ws->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea("A1:U{$f}");
        $ws->getPageMargins()
            ->setLeft(0.7087)->setRight(0.7087)
            ->setTop(0.748)->setBottom(0.748)
            ->setHeader(0.315)->setFooter(0.315);

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