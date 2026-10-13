<?php

namespace App\Imports\Concerns;

use DateTime;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Parser de fechas y normalización de texto compartido por los importadores.
 *
 * Centraliza el manejo de fechas para que maletines y productos usen
 * exactamente la misma lógica: textos d/m/Y, d-m-Y, d.m.Y, ISO Y-m-d,
 * seriales de Excel (cuando el valor llega crudo) y meses escritos en
 * español ("13 de octubre de 2026").
 */
trait ParseaFechas
{
    /**
     * Normaliza una cadena: sin tildes, minúsculas y sin caracteres
     * problemáticos que algunos sistemas insertan (apóstrofos, tildes...).
     */
    protected function normalizarTexto($text)
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        $text = mb_strtolower($text, 'UTF-8');

        // Plegado explícito de tildes y "ñ" del español. No se usa solo
        // iconv //TRANSLIT porque en Windows convierte "Ó" en "'o" (apóstrofo)
        // y "Ñ" en "~n", lo que impedía reconocer etiquetas como
        // "FECHA DE ATENCIÓN" o "CONEXIÓN DE LA LÍNEA".
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);

        // Resto de caracteres no ASCII: transliterar y limpiar los signos que
        // algunos sistemas insertan (apóstrofos, tildes, comillas...).
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = str_replace(["'", '`', '~', '"', '^'], '', $text);

        return trim($text);
    }

    /**
     * Interpreta el primer valor numérico del arreglo como serial de fecha de
     * Excel. Solo acepta un rango razonable (años ~1954 a ~2118) para no
     * confundir cantidades u otros números con fechas. Devuelve "Y-m-d" o null.
     */
    protected function parseFechaSerialExcel(array $filaArray)
    {
        foreach ($filaArray as $valor) {
            if (!is_numeric($valor)) {
                continue;
            }

            $num = (float) $valor;

            if ($num < 20000 || $num > 80000) {
                continue;
            }

            try {
                $fecha = ExcelDate::excelToDateTimeObject((int) floor($num));
            } catch (Throwable $e) {
                continue;
            }

            return $fecha->format('Y-m-d');
        }

        return null;
    }

    /**
     * Extrae una fecha cuyo mes viene escrito en palabras (español). Ignora de
     * forma natural el día de la semana (p. ej. "martes,"). Devuelve "Y-m-d".
     */
    protected function parseFechaConMes(string $texto)
    {
        $norm = $this->normalizarTexto($texto);

        if (!preg_match('/(\d{1,2})\s+(?:de\s+)?([a-z]{3,})\.?\s+(?:de\s+)?(\d{2,4})/', $norm, $m)) {
            return null;
        }

        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
            'noviembre' => 11, 'diciembre' => 12,
            'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5,
            'jun' => 6, 'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9,
            'oct' => 10, 'nov' => 11, 'dic' => 12,
        ];

        $nombreMes = rtrim($m[2], '.');

        if (!isset($meses[$nombreMes])) {
            return null;
        }

        $dia = (int) $m[1];
        $mes = $meses[$nombreMes];
        $anio = (int) $m[3];

        if ($anio < 100) {
            $anio += 2000;
        }

        if (!checkdate($mes, $dia, $anio)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }

    /**
     * Convierte un valor de fecha (DateTime, texto d/m/Y, d-m-Y, d.m.Y, ISO
     * Y-m-d, mes en palabras o serial de Excel) en una fecha "Y-m-d".
     * Devuelve null si no se puede interpretar.
     */
    protected function parseFechaFlexible($valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        // Serial de Excel (ej. 46337 o 46337.0), cuando la celda llega cruda.
        if (is_numeric($texto)) {
            return $this->parseFechaSerialExcel([$texto]);
        }

        // Formato ISO: 2026-10-11, 2026/10/11 (con hora opcional).
        if (preg_match('#^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})#', $texto, $m)) {
            $anio = (int) $m[1];
            $mes = (int) $m[2];
            $dia = (int) $m[3];

            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }

        // Formato latino: 25/10/2026, 25-10-2026, 25.10.2026 (con hora opcional).
        if (preg_match('#(\d{1,2})[\s/\-.]+(\d{1,2})[\s/\-.]+(\d{2,4})#', $texto, $m)) {
            $dia = (int) $m[1];
            $mes = (int) $m[2];
            $anio = (int) $m[3];

            if ($anio < 100) {
                $anio += 2000;
            }

            foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $formato) {
                $candidato = DateTime::createFromFormat($formato, "$dia/$mes/$anio");
                $errores = DateTime::getLastErrors();
                $valida = $candidato && !($errores && ($errores['warning_count'] > 0 || $errores['error_count'] > 0));

                if ($valida) {
                    $candidato->setTime(0, 0);

                    return $candidato->format('Y-m-d');
                }
            }
        }

        // Mes escrito en palabras: "13 de octubre de 2026".
        $conMes = $this->parseFechaConMes($texto);

        if ($conMes !== null) {
            return $conMes;
        }

        // Último recurso.
        $timestamp = strtotime($texto);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
