<?php
// app/Imports/MaletinImport.php

namespace App\Imports;

use App\Models\Maletin;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MaletinImport implements ToCollection, SkipsEmptyRows, WithCalculatedFormulas
{
    use Importable;

    protected $categoriaId;
    protected $subcategoriaId;
    protected $cleanExisting;

    public function __construct($categoriaId = null, $subcategoriaId = null, $cleanExisting = true)
    {
        $this->categoriaId = $categoriaId;
        $this->subcategoriaId = $subcategoriaId;
        $this->cleanExisting = $cleanExisting;
    }

    public function collection(Collection $rows)
    {
        $data = array_values($rows->toArray());

        // Si la hoja no es un checklist (no contiene el título ni el equipo
        // de pruebas), se ignora. Así la importación NO depende de que la hoja
        // se llame "Hoja2": funciona con cualquier nombre ("Checklist", etc.)
        // y en archivos con varias hojas procesa únicamente la del checklist.
        if (!$this->esChecklist($data)) {
            return;
        }
        
        // Variables
        $nombreMaletin = null;
        $observaciones = '';
        $fechaDevolucion = null;
        $fechaAtencion = null;
        $fechaSolicitud = null;
        $responsableSolicitante = null;
        $proyectoCliente = null;
        $componentes = [];
        $accesorios = [];
        $adicionales = [];
        
        $procesandoAccesorios = false;
        $procesandoAdicionales = false;
        $seccionAdicionales = false;
        
        // ============================================
        // PASADA 1: DETECTAR EL NOMBRE DEL MALETÍN
        // ============================================
        foreach ($data as $fila) {
            $filaArray = array_values($fila);
            
            // Buscar el título del checklist en cualquier columna
            foreach ($filaArray as $celda) {
                $contenido = trim((string) ($celda ?? ''));
                
                if ($contenido === '') {
                    continue;
                }
                
                if (stripos($this->normalizarTexto($contenido), 'check list de entrega') === false) {
                    continue;
                }
                
                if (preg_match('/CHECK LIST DE ENTREGA\s*-\s*(.+?)(?:\s+Versi(?:ó|o)n.*)?$/i', $contenido, $matches)) {
                    $nombreMaletin = trim($matches[1]);
                }
                break 2;
            }
        }
        
        // Si no se encontró en el título, buscar en los componentes
        if (empty($nombreMaletin)) {
            foreach ($data as $fila) {
                $filaArray = array_values($fila);
                $contenido = trim($filaArray[2] ?? '');
                
                if (stripos($contenido, 'EQUIPO DE PRUEBAS:') !== false) {
                    if (preg_match('/EQUIPO DE PRUEBAS:\s*(.+?)(?:\s+S\/N|\s+N\/S|\s*$)/i', $contenido, $matches)) {
                        $nombreMaletin = trim($matches[1]);
                        break;
                    }
                }
            }
        }
        
        if (empty($nombreMaletin)) {
            $nombreMaletin = 'Maletín Importado';
        }
        
        // Limpiar nombre
        $nombreMaletin = preg_replace('/\s+Versión.*$/i', '', $nombreMaletin);
        $nombreMaletin = preg_replace('/\s+Fecha.*$/i', '', $nombreMaletin);
        $nombreMaletin = trim($nombreMaletin);
        
        // ============================================
        // PASADA 2: PROCESAR LOS DATOS
        // ============================================
        foreach ($data as $index => $fila) {
            $filaArray = array_values($fila);
            
            $item = trim($filaArray[0] ?? '');
            $cantidad = trim($filaArray[1] ?? '');
            $contenido = trim($filaArray[2] ?? '');
            
            if (empty($contenido) && empty($item)) {
                continue;
            }
            
            // Buscar observaciones (la etiqueta puede estar en cualquier columna; el
            // texto va a su derecha y, si es largo, en las siguientes 2 filas)
            if ($this->contieneEtiqueta($filaArray, 'observaciones')) {
                $observaciones = $this->leerObservaciones($filaArray, $data, $index);
                continue;
            }
            
            // ⏬ FECHAS ETIQUETADAS (FECHA DE PEDIDO, FECHA DE ATENCIÓN, FECHA DE DEVOLUCIÓN).
            // Se lee cada una a partir de la columna de su etiqueta: la fila de
            // FECHA DE PEDIDO y FECHA DE ATENCIÓN comparten fila (A9 y J9).
            if (empty($fechaDevolucion)) {
                $fechaDevolucion = $this->parseFechaEtiquetada($filaArray, 'fecha de devolucion');
            }
            if (empty($fechaAtencion)) {
                $fechaAtencion = $this->parseFechaEtiquetada($filaArray, 'fecha de atencion');
            }
            if (empty($fechaSolicitud)) {
                $fechaSolicitud = $this->parseFechaEtiquetada($filaArray, 'fecha de pedido');
            }

            // Las filas de etiquetas no son items: no seguir procesándolas.
            if ($this->contieneEtiqueta($filaArray, 'fecha de devolucion') ||
                $this->contieneEtiqueta($filaArray, 'fecha de atencion') ||
                $this->contieneEtiqueta($filaArray, 'fecha de pedido')) {
                continue;
            }
            
            // ⏬ NOMBRE DEL PROYECTO Y CLIENTE
            if (empty($proyectoCliente)) {
                $leido = $this->leerCampoEtiquetado($filaArray, 'nombre del proyecto y cliente');
                if ($leido !== null) {
                    $proyectoCliente = $leido;
                    continue;
                }
            }
            
            // ⏬ RESPONSABLE SOLICITANTE
            if (empty($responsableSolicitante)) {
                $leido = $this->leerCampoEtiquetado($filaArray, 'responsable solicitante');
                if ($leido !== null) {
                    $responsableSolicitante = $leido;
                    continue;
                }
            }
            
            // ============================================
            // COMPONENTES (items 1, 2)
            // ============================================
            if (stripos($contenido, 'EQUIPO DE PRUEBAS:') !== false && 
                (stripos($item, '1') === 0 || $item == '1' || $item == '1.00')) {
                $componentes[] = [
                    'item_numero' => 1,
                    'cantidad' => $this->extractCantidad($cantidad),
                    'descripcion' => $contenido,
                    'incluido' => true,
                    'estado_devolucion' => $this->determinarEstadoDevolucion($contenido),
                ];
                continue;
            }
            
            if (stripos($contenido, 'MALETA DE TRANSPORTE') !== false && 
                (stripos($item, '2') === 0 || $item == '2' || $item == '2.00')) {
                $componentes[] = [
                    'item_numero' => 2,
                    'cantidad' => $this->extractCantidad($cantidad),
                    'descripcion' => $contenido,
                    'incluido' => true,
                    'estado_devolucion' => $this->determinarEstadoDevolucion($contenido),
                ];
                continue;
            }
            
            // ============================================
            // DETECTAR SECCIONES
            // ============================================
            // Sección de accesorios (3.x): el título puede estar en cualquier columna
            if ($this->contieneEtiqueta($filaArray, 'set de accesorios de conexion')) {
                $procesandoAccesorios = true;
                $procesandoAdicionales = false;
                continue;
            }
            
            // Sección de adicionales (4.x): el título puede estar en cualquier columna
            if ($this->contieneEtiqueta($filaArray, 'paquete adicional de accesorios')) {
                $procesandoAccesorios = false;
                $procesandoAdicionales = true;
                $seccionAdicionales = true;
                continue;
            }
            
            // ============================================
            // PROCESAR ACCESORIOS
            // ============================================
            if (($procesandoAccesorios || $this->esItemAccesorio($item)) && !empty($item) && !empty($contenido)) {
                // Detectar items de accesorios: 3.01, 3.02, 3.1, 3.11, etc.
                if ($this->esItemAccesorio($item)) {
                    $itemNum = $this->extractItemNumber($item);
                    
                    if ($itemNum > 0) {
                        // Limpiar el contenido
                        $contenido = $this->limpiarContenido($contenido);
                        
                        $accesorios[] = [
                            'item_numero' => $itemNum,
                            'cantidad' => $this->extractCantidad($cantidad),
                            'descripcion' => $contenido,
                            'incluido' => !$this->hasFalta($contenido) && !$this->hasFalta($item),
                            'estado_devolucion' => $this->determinarEstadoDevolucion($contenido),
                        ];
                    }
                }
            }
            
            // ============================================
            // PROCESAR ADICIONALES
            // ============================================
            if ($procesandoAdicionales && !empty($item) && !empty($contenido)) {
                // Dentro de la sección "PAQUETE ADICIONAL DE ACCESORIOS" toda fila
                // con item y contenido es un adicional, aunque el item no siga el
                // esquema 4.xx (al agregarlo a mano es fácil que quede como 1.xx).
                if (preg_match('/^4[\.\s]/', $item) || preg_match('/^4\.\d+$/', $item)) {
                    $itemNum = $this->extractItemNumber($item);
                } else {
                    $itemNum = 400 + (count($adicionales) + 1);
                }

                if ($itemNum > 0) {
                    $contenido = $this->limpiarContenido($contenido);

                    $adicionales[] = [
                        'item_numero' => $itemNum,
                        'cantidad' => $this->extractCantidad($cantidad),
                        'descripcion' => $contenido,
                        'incluido' => !$this->hasFalta($contenido) && !$this->hasFalta($item),
                        'estado_devolucion' => $this->determinarEstadoDevolucion($contenido),
                    ];
                }
            }
        }

        // La sección "PAQUETE ADICIONAL DE ACCESORIOS" debe verse siempre en el
        // sistema (Ver/Editar), aunque el Excel traiga las filas 4.01 - 4.03 en
        // blanco. Si la sección existe y no hay adicionales con contenido, se
        // crean esas 3 filas vacías para conservar la estructura de la plantilla.
        if ($seccionAdicionales && empty($adicionales)) {
            foreach ([401, 402, 403] as $num) {
                $adicionales[] = [
                    'item_numero' => $num,
                    'cantidad' => 1,
                    'descripcion' => null,
                    'incluido' => false,
                    'estado_devolucion' => null,
                ];
            }
        }

        // ============================================
        // CREAR O ACTUALIZAR MALETÍN
        // ============================================
        // Buscar si ya existe un maletín con ese nombre
        $maletin = Maletin::where('nombre', $nombreMaletin)->first();
        
        if ($maletin && $this->cleanExisting) {
            // ✅ ELIMINAR DATOS EXISTENTES
            $maletin->componentesEquipo()->delete();
            $maletin->accesoriosSet()->delete();
            $maletin->accesoriosAdicionales()->delete();
            
            // Actualizar observaciones
            $maletin->observaciones = $observaciones;
            $maletin->fecha_solicitud = $fechaSolicitud;
            $maletin->fecha_devolucion = $fechaDevolucion;
            $maletin->fecha_atencion = $fechaAtencion;
            $maletin->responsable_solicitante = $responsableSolicitante;
            $maletin->proyecto_cliente = $proyectoCliente;
            $maletin->save();
            
        } elseif (!$maletin) {
            // Crear nuevo maletín
            $maletin = Maletin::create([
                'nombre' => $nombreMaletin,
                'estado' => 'activo',
                'observaciones' => $observaciones,
                'fecha_solicitud' => $fechaSolicitud,
                'fecha_devolucion' => $fechaDevolucion,
                'fecha_atencion' => $fechaAtencion,
                'responsable_solicitante' => $responsableSolicitante,
                'proyecto_cliente' => $proyectoCliente,
            ]);
        }
        
        // ============================================
        // AGREGAR NUEVOS DATOS
        // ============================================
        
        // Agregar componentes
        if (!empty($componentes)) {
            foreach ($componentes as $componente) {
                $maletin->componentesEquipo()->create($componente);
            }
        } else {
            // Componentes por defecto si no se encontraron
            $maletin->componentesEquipo()->createMany([
                [
                    'item_numero' => 1,
                    'cantidad' => 1,
                    'descripcion' => 'EQUIPO DE PRUEBAS: ' . $nombreMaletin,
                    'incluido' => true,
                ],
                [
                    'item_numero' => 2,
                    'cantidad' => 1,
                    'descripcion' => 'MALETA DE TRANSPORTE CON RUEDAS PARA USO PESADO',
                    'incluido' => true,
                ],
            ]);
        }
        
        // Agregar accesorios
        if (!empty($accesorios)) {
            foreach ($accesorios as $accesorio) {
                $maletin->accesoriosSet()->create($accesorio);
            }
        }
        
        // Agregar adicionales
        if (!empty($adicionales)) {
            foreach ($adicionales as $adicional) {
                $maletin->accesoriosAdicionales()->create($adicional);
            }
        }
        
        return $maletin;
    }
    
    /**
     * Verifica si el item es un accesorio (empieza con 3.)
     */
    protected function esItemAccesorio($item)
    {
        if (empty($item)) return false;
        
        // 3.01, 3.02, 3.1, 3.11, 3.00
        return preg_match('/^3[\.\s]/', $item) || 
               preg_match('/^3\.\d+$/', $item);
    }
    
    /**
     * Limpia el contenido (quita guiones, espacios extras, etc.)
     */
    protected function limpiarContenido($contenido)
    {
        // Quitar guiones al inicio
        $contenido = preg_replace('/^-\s*/', '', $contenido);
        
        // Quitar espacios extras
        $contenido = preg_replace('/\s+/', ' ', $contenido);
        
        return trim($contenido);
    }
    
    /**
     * Extrae el número de item: 3.01 -> 301, 3.1 -> 301, 3.11 -> 311
     */
    protected function extractItemNumber($text)
    {
        if (empty($text)) {
            return 0;
        }
        
        $text = trim($text);
        
        // Si es "3.01" -> 301, "3.1" -> 301
        if (preg_match('/(\d+)\.(\d+)/', $text, $matches)) {
            $principal = (int)$matches[1];
            $secundario = (int)$matches[2];
            
            // 3.1 -> 301, 3.01 -> 301, 3.11 -> 311
            if ($secundario < 10) {
                return (int)($principal . '0' . $secundario);
            }
            
            return (int)($principal . $secundario);
        }
        
        // Si es "3" -> 3
        if (is_numeric($text)) {
            return (int) $text;
        }
        
        return 0;
    }
    
    /**
     * Extrae la cantidad: "01" -> 1, "03" -> 3, "9" -> 9
     */
    protected function extractCantidad($text)
    {
        if (empty($text)) {
            return 1;
        }
        
        $text = trim($text);
        
        if (is_numeric($text)) {
            return (int) $text;
        }
        
        return 1;
    }
    
    /**
     * Verifica si tiene "FALTA"
     */
    protected function hasFalta($text)
    {
        return stripos($text, 'FALTA') !== false;
    }

    /**
     * Determina si la hoja corresponde a un checklist de maletín.
     * Busca el título "CHECK LIST DE ENTREGA" o una fila "EQUIPO DE PRUEBAS:"
     * sin depender del nombre de la hoja.
     */
    protected function esChecklist(array $data): bool
    {
        foreach ($data as $fila) {
            foreach (array_values($fila) as $celda) {
                $contenido = $this->normalizarTexto($celda);

                if ($contenido === '') {
                    continue;
                }

                if (str_contains($contenido, 'check list de entrega') ||
                    str_contains($contenido, 'equipo de pruebas')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Normaliza un texto: convierte a UTF-8 si viene de Windows-1252,
     * quita acentos y pasa a minúsculas. Útil para comparar el contenido
     * del Excel (que puede traer tildes en codificación antigua).
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
     * Detecta si alguna celda de la fila contiene la etiqueta
     * "FECHA DE DEVOLUCIÓN" (con o sin tilde).
     */
    protected function contieneEtiquetaDevolucion(array $filaArray)
    {
        foreach ($filaArray as $celda) {
            if (str_contains($this->normalizarTexto($celda), 'fecha de devolucion')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta si alguna celda de la fila contiene la etiqueta
     * "FECHA DE ATENCIÓN" (con o sin tilde).
     */
    protected function contieneEtiquetaAtencion(array $filaArray)
    {
        foreach ($filaArray as $celda) {
            if (str_contains($this->normalizarTexto($celda), 'fecha de atencion')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta si alguna celda de la fila contiene la etiqueta dada
     * (comparando sin acentos ni mayúsculas).
     */
    protected function contieneEtiqueta(array $filaArray, string $etiqueta)
    {
        $norm = $this->normalizarTexto($etiqueta);

        foreach ($filaArray as $celda) {
            if (str_contains($this->normalizarTexto($celda), $norm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lee el valor que hay junto a una etiqueta de la fila.
     * Ejem: para "NOMBRE DEL PROYECTO Y CLIENTE:" en A6 devuelve lo que
     * esté escrito en la misma celda después de los dos puntos, o en las
     * celdas de la derecha (B6, C6, ...). Devuelve null si no encuentra
     * la etiqueta o si no hay ningún valor.
     */
    protected function leerCampoEtiquetado(array $filaArray, string $etiqueta)
    {
        $norm = $this->normalizarTexto($etiqueta);

        foreach ($filaArray as $i => $celda) {
            $txt = trim((string) ($celda ?? ''));
            $n = $this->normalizarTexto($celda);

            if ($n === '' || !str_contains($n, $norm)) {
                continue;
            }

            $partes = [];

            // Valor escrito en la misma celda después de los dos puntos
            if (preg_match('/:\s*(.+)$/u', $txt, $m) && trim($m[1]) !== '') {
                $partes[] = trim($m[1]);
            }

            // Celdas a la derecha de la etiqueta
            foreach (array_slice(array_values($filaArray), $i + 1) as $v) {
                $vv = trim((string) ($v ?? ''));
                if ($vv !== '') {
                    $partes[] = $vv;
                }
            }

            if ($partes !== []) {
                return implode(' ', array_unique($partes));
            }
        }

        return null;
    }

    /**
     * Arma el texto de OBSERVACIONES: toma lo escrito en la misma fila de la
     * etiqueta (a su derecha) y, si el texto es largo, las siguientes 1-2 filas
     * de continuación (se detiene ante filas de firmas o de items).
     */
    protected function leerObservaciones(array $filaArray, array $data, $index): string
    {
        $partes = [];

        // Texto en la misma fila (excepto la propia etiqueta)
        foreach ($filaArray as $celda) {
            $v = trim((string) ($celda ?? ''));
            if ($v === '' || $this->contieneEtiqueta([$v], 'observaciones')) {
                continue;
            }
            $partes[] = $v;
        }

        // Filas de continuación
        $claves = [
            'entregado por', 'recibido por', 'n. y a', 'dni',
            'equipo de pruebas', 'maleta de transporte', 'set de accesorios',
            'paquete adicional', 'fecha de', 'item', 'contenido',
        ];

        for ($d = 1; $d <= 2; $d++) {
            $sig = $data[$index + $d] ?? null;
            if (!$sig) {
                break;
            }

            $vals = array_values($sig);
            $texto = trim(implode(' ', array_filter(array_map(fn ($x) => trim((string) ($x ?? '')), $vals))));

            if ($texto === '') {
                continue;
            }

            $norm = $this->normalizarTexto($texto);
            $corta = false;

            foreach ($claves as $k) {
                if (str_contains($norm, $k)) {
                    $corta = true;
                    break;
                }
            }

            // Fila que parece un item (A numérico + B cantidad)
            $a = trim((string) ($vals[0] ?? ''));
            $b = trim((string) ($vals[1] ?? ''));
            if ($a !== '' && is_numeric($a) && $b !== '') {
                $corta = true;
            }

            if ($corta) {
                break;
            }

            $partes[] = $texto;
        }

        return trim(preg_replace('/\s+/', ' ', implode(' ', $partes)));
    }

    /**
     * Extrae la fecha escrita a partir de la celda que contiene la etiqueta.
     * Ejem: para "FECHA DE ATENCIÓN" en J9 devuelve la fecha de N9, y no la
     * de D9 (FECHA DE PEDIDO) que está antes en la misma fila.
     * Devuelve "Y-m-d" o null.
     */
    protected function parseFechaEtiquetada(array $filaArray, string $etiqueta)
    {
        $norm = $this->normalizarTexto($etiqueta);

        foreach ($filaArray as $i => $celda) {
            if (!str_contains($this->normalizarTexto($celda), $norm)) {
                continue;
            }

            // Desde la columna de la etiqueta hacia la derecha, pero sin pasar
            // a otra etiqueta de fecha de la misma fila (FECHA DE PEDIDO y
            // FECHA DE ATENCIÓN comparten fila); así no se toma la fecha de la
            // etiqueta vecina cuando la propia está vacía.
            $fin = count($filaArray);
            for ($j = $i + 1; $j < count($filaArray); $j++) {
                if ($this->esEtiquetaFecha($filaArray[$j])) {
                    $fin = $j;
                    break;
                }
            }

            $trozo = array_values(array_slice($filaArray, $i, $fin - $i));

            return $this->parseFechaDevolucion($trozo);
        }

        return null;
    }

    /**
     * Indica si una celda contiene alguna de las etiquetas de fecha del
     * checklist (pedido / atención / devolución).
     */
    protected function esEtiquetaFecha($celda): bool
    {
        $n = $this->normalizarTexto($celda);

        foreach (['fecha de pedido', 'fecha de atencion', 'fecha de devolucion'] as $etq) {
            if (str_contains($n, $etq)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrae la fecha de devolución de la fila etiquetada.
     * Ejem: "FECHA DE DEVOLUCIÓN: 25/10/2026" o "FECHA DE DEVOLUCIÓN" en A y la fecha en B/C.
     * Devuelve "Y-m-d" o null.
     */
    protected function parseFechaDevolucion(array $filaArray)
    {
        $texto = implode(' ', array_filter(array_map('trim', array_map('strval', $filaArray))));

        // 1) Formato numérico: 25/10/2026, 25-10-2026, 25.10.2026 ...
        if (preg_match('/(\d{1,2})[\s\/\-.]+(\d{1,2})[\s\/\-.]+(\d{2,4})/', $texto, $m)) {
            $dia = $m[1];
            $mes = $m[2];
            $anio = (int) $m[3];

            $formatos = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'd.m.y'];

            foreach ($formatos as $formato) {
                $candidato = \DateTime::createFromFormat($formato, "$dia/$mes/$anio");
                $errores = \DateTime::getLastErrors();
                $valida = $candidato && !($errores && ($errores['warning_count'] > 0 || $errores['error_count'] > 0));

                if ($valida) {
                    $candidato->setTime(0, 0);

                    return $candidato->format('Y-m-d');
                }
            }
        }

        // 2) Serial de Excel: cuando el usuario escribe una fecha real, la celda
        //    llega como número (p. ej. 46337 = 11/11/2026) porque el importador
        //    entrega el valor crudo, sin formato.
        $serial = $this->parseFechaSerialExcel($filaArray);

        if ($serial !== null) {
            return $serial;
        }

        // 3) Formato con el mes escrito: "13 de octubre de 2026",
        //    "martes, 13 de octubre de 2026" o "13 octubre 2026".
        return $this->parseFechaConMes($texto);
    }

    /**
     * Interpreta el primer valor numérico del tramo como serial de fecha de
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
                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) floor($num));
            } catch (\Throwable $e) {
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
     * Determina el estado actual de devolución de un ítem según el texto
     * del contenido (igual que hoy se deduce "FALTA" para `incluido`).
     * Valores: ok | falta | danado
     */
    protected function determinarEstadoDevolucion($contenido)
    {
        $texto = $this->normalizarTexto($contenido);

        if (str_contains($texto, 'danado') || str_contains($texto, 'daniado') ||
            str_contains($texto, 'mal estado') || str_contains($texto, 'inoperativo') ||
            str_contains($texto, 'deteriorado') || str_contains($texto, 'no funciona') ||
            str_contains($texto, 'roto')) {
            return 'danado';
        }

        if (str_contains($texto, 'falta')) {
            return 'falta';
        }

        return 'ok';
    }
}