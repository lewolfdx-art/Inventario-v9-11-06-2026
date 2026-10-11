<?php
// app/Exports/MaletinChecklistPdfExport.php

namespace App\Exports;

use App\Models\Maletin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class MaletinChecklistPdfExport
{
    protected $maletin;

    public function __construct(Maletin $maletin)
    {
        $this->maletin = $maletin;
    }

    protected function estadoLabel($estado)
    {
        return match ($estado) {
            'falta' => 'FALTA',
            'danado' => 'DAÑADO',
            default => 'OK',
        };
    }

    protected function formatItemNumber($num)
    {
        $num = (int) $num;

        if ($num >= 100) {
            $g = intdiv($num, 100);
            $r = $num % 100;

            return $g . '.' . str_pad((string) $r, 2, '0', STR_PAD_LEFT);
        }

        return sprintf('%d.00', $num);
    }

    protected function buildRows(): array
    {
        $rows = [];

        $push = function ($numero, $cant, $desc, $estado, $bold = false, $section = false) use (&$rows) {
            $label = $estado === null ? '' : $this->estadoLabel($estado);

            $rows[] = [
                'item' => $numero,
                'cant' => $cant,
                'desc' => $desc,
                'bold' => $bold,
                'section' => $section,
                'si' => $estado === 'ok',
                'no' => $estado === 'falta' || $estado === 'danado',
                'estado' => $label,
            ];
        };

        foreach ($this->maletin->componentesEquipo as $c) {
            $push(
                $this->formatItemNumber($c->item_numero),
                $c->cantidad,
                $c->descripcion,
                $c->estado_devolucion,
                true
            );
        }

        if ($this->maletin->accesoriosSet->isNotEmpty()) {
            $push('3.00', '', 'SET DE ACCESORIOS DE CONEXIÓN DE LA LÍNEA:', null, true, true);

            foreach ($this->maletin->accesoriosSet as $a) {
                $push(
                    $this->formatItemNumber($a->item_numero),
                    $a->cantidad,
                    ' - ' . $a->descripcion,
                    $a->estado_devolucion
                );
            }
        }

        if ($this->maletin->accesoriosAdicionales->isNotEmpty()) {
            $push('4.00', '', 'PAQUETE ADICIONAL DE ACCESORIOS:', null, true, true);

            foreach ($this->maletin->accesoriosAdicionales as $a) {
                $push(
                    $this->formatItemNumber($a->item_numero),
                    $a->cantidad,
                    ' - ' . $a->descripcion,
                    $a->estado_devolucion
                );
            }
        }

        return $rows;
    }

    protected function getData(): array
    {
        $maletin = $this->maletin;

        return [
            'title' => 'CHECK LIST DE ENTREGA',
            'subtitle' => 'Sistema de Inventario',
            'date' => now()->format('d/m/Y H:i:s'),
            'maletin' => Str::upper($maletin->nombre),
            'fecha_devolucion' => $maletin->fecha_devolucion ? $maletin->fecha_devolucion->format('d/m/Y') : 'Pendiente',
            'observaciones' => $maletin->observaciones ?? '',
            'rows' => $this->buildRows(),
        ];
    }

    public function getContent()
    {
        $pdf = Pdf::loadView('exports.maletin-checklist-pdf', $this->getData());
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    public function download()
    {
        return response()->streamDownload(function () {
            echo $this->getContent();
        }, 'checklist_' . Str::slug($this->maletin->nombre) . '_' . now()->format('Ymd_His') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}