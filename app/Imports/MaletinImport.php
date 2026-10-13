<?php

// app/Imports/MaletinImport.php

namespace App\Imports;

use App\Imports\Concerns\ParseaFechas;
use App\Models\Maletin;
use App\Support\Firma;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class MaletinImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas
{
    use Importable;
    use ParseaFechas;

    protected $categoriaId;

    protected $subcategoriaId;

    protected $cleanExisting;

    /** Imágenes de firma extraídas del Excel: ['entregado' => ?dataUrl, 'recibido' => ?dataUrl]. */
    protected array $firmasImagenes = ['entregado' => null, 'recibido' => null];

    public function __construct($categoriaId = null, $subcategoriaId = null, $cleanExisting = true)
    {
        $this->categoriaId = $categoriaId;
        $this->subcategoriaId = $subcategoriaId;
        $this->cleanExisting = $cleanExisting;
    }

    public function setFirmasImagenes(array $imagenes): static
    {
        $this->firmasImagenes = array_merge($this->firmasImagenes, $imagenes);

        return $this;
    }

    /**
     * Extrae las imágenes de firma embebidas en el Excel. En Excel la firma es
     * un objeto de dibujo (no un valor de celda), por lo que se lee con
     * PhpSpreadsheet y se asigna según su columna de anclaje: C:G = ENTREGADO
     * POR, O:S = RECIBIDO POR (mismo criterio que la exportación).
     *
     * @return array{entregado: ?string, recibido: ?string}
     */
    public static function extraerImagenesFirmas(string $path): array
    {
        $resultado = ['entregado' => null, 'recibido' => null];

        if (! is_file($path)) {
            return $resultado;
        }

        try {
            $ss = IOFactory::load($path);
        } catch (\Throwable $e) {
            return $resultado;
        }

        foreach ($ss->getAllSheets() as $hoja) {
            foreach ($hoja->getDrawingCollection() as $dibujo) {
                $coordenada = $dibujo->getCoordinates();

                if (! $coordenada || ! preg_match('/^([A-Z]+)\d+$/', $coordenada, $m)) {
                    continue;
                }

                $columna = Coordinate::columnIndexFromString($m[1]);
                $dataUrl = self::dibujoADataUrl($dibujo);

                if ($dataUrl === null) {
                    continue;
                }

                if ($columna >= 3 && $columna <= 7 && $resultado['entregado'] === null) {
                    $resultado['entregado'] = $dataUrl;
                } elseif ($columna >= 15 && $columna <= 19 && $resultado['recibido'] === null) {
                    $resultado['recibido'] = $dataUrl;
                }
            }
        }

        return $resultado;
    }

    /** Convierte un objeto de dibujo a data URL ("data:image/...;base64,..."). */
    protected static function dibujoADataUrl($dibujo): ?string
    {
        $binario = null;
        $mime = 'image/png';

        if ($dibujo instanceof Drawing) {
            $ruta = $dibujo->getPath();

            // Puede ser un archivo real o un stream "zip://...#xl/media/...".
            if (is_string($ruta) && $ruta !== '') {
                $binario = @file_get_contents($ruta);

                $info = (is_string($binario) && $binario !== '')
                    ? @getimagesizefromstring($binario)
                    : false;

                if ($info && ! empty($info['mime'])) {
                    $mime = $info['mime'];
                }
            }
        }

        if (($binario === false || $binario === null || $binario === '') && method_exists($dibujo, 'getImageResource')) {
            $recurso = @$dibujo->getImageResource();

            if ($recurso) {
                ob_start();
                imagepng($recurso);
                $binario = ob_get_clean();
                $mime = 'image/png';
            }
        }

        if ($binario === false || $binario === null || $binario === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binario);
    }

    public function collection(Collection $rows)
    {
        $data = array_values($rows->toArray());

        // Si la hoja no es un checklist (no contiene el título ni el equipo
        // de pruebas), se ignora. Así la importación NO depende de que la hoja
        // se llame "Hoja2": funciona con cualquier nombre ("Checklist", etc.)
        // y en archivos con varias hojas procesa únicamente la del checklist.
        if (! $this->esChecklist($data)) {
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
        $entregadoPorNombres = null;
        $entregadoPorDni = null;
        $recibidoPorNombres = null;
        $recibidoPorDni = null;
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
            if (($procesandoAccesorios || $this->esItemAccesorio($item)) && ! empty($item) && ! empty($contenido)) {
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
                            'incluido' => ! $this->hasFalta($contenido) && ! $this->hasFalta($item),
                            'estado_devolucion' => $this->determinarEstadoDevolucion($contenido),
                        ];
                    }
                }
            }

            // ============================================
            // PROCESAR ADICIONALES
            // ============================================
            if ($procesandoAdicionales && ! empty($item) && ! empty($contenido)) {
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
                        'incluido' => ! $this->hasFalta($contenido) && ! $this->hasFalta($item),
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
        // LEER EL BLOQUE DE FIRMAS (N. y A. / DNI)
        // ============================================
        $firmas = $this->leerBloqueFirmas($data);
        $entregadoPorNombres = $firmas['entregado_por_nombres'];
        $entregadoPorDni = $firmas['entregado_por_dni'];
        $recibidoPorNombres = $firmas['recibido_por_nombres'];
        $recibidoPorDni = $firmas['recibido_por_dni'];

        // ============================================
        // CREAR O ACTUALIZAR MALETÍN
        // ============================================
        // Firmas (imágenes) extraídas del Excel, si las trae
        $imagenEntregado = $this->firmasImagenes['entregado'] ?? null;
        $imagenRecibido = $this->firmasImagenes['recibido'] ?? null;

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
            $maletin->entregado_por_nombres = $entregadoPorNombres;
            $maletin->entregado_por_dni = $entregadoPorDni;
            $maletin->recibido_por_nombres = $recibidoPorNombres;
            $maletin->recibido_por_dni = $recibidoPorDni;

            if ($imagenEntregado) {
                $maletin->entregado_por_firma = Firma::guardar($imagenEntregado, 'firmas/maletines', $maletin->entregado_por_firma);
            }
            if ($imagenRecibido) {
                $maletin->recibido_por_firma = Firma::guardar($imagenRecibido, 'firmas/maletines', $maletin->recibido_por_firma);
            }

            $maletin->save();

        } elseif (! $maletin) {
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
                'entregado_por_nombres' => $entregadoPorNombres,
                'entregado_por_dni' => $entregadoPorDni,
                'recibido_por_nombres' => $recibidoPorNombres,
                'recibido_por_dni' => $recibidoPorDni,
                'entregado_por_firma' => $imagenEntregado ? Firma::guardar($imagenEntregado, 'firmas/maletines') : null,
                'recibido_por_firma' => $imagenRecibido ? Firma::guardar($imagenRecibido, 'firmas/maletines') : null,
            ]);
        }

        // ============================================
        // AGREGAR NUEVOS DATOS
        // ============================================

        // Agregar componentes
        if (! empty($componentes)) {
            foreach ($componentes as $componente) {
                $maletin->componentesEquipo()->create($componente);
            }
        } else {
            // Componentes por defecto si no se encontraron
            $maletin->componentesEquipo()->createMany([
                [
                    'item_numero' => 1,
                    'cantidad' => 1,
                    'descripcion' => 'EQUIPO DE PRUEBAS: '.$nombreMaletin,
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
        if (! empty($accesorios)) {
            foreach ($accesorios as $accesorio) {
                $maletin->accesoriosSet()->create($accesorio);
            }
        }

        // Agregar adicionales
        if (! empty($adicionales)) {
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
        if (empty($item)) {
            return false;
        }

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
            $principal = (int) $matches[1];
            $secundario = (int) $matches[2];

            // 3.1 -> 301, 3.01 -> 301, 3.11 -> 311
            if ($secundario < 10) {
                return (int) ($principal.'0'.$secundario);
            }

            return (int) ($principal.$secundario);
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

            if ($n === '' || ! str_contains($n, $norm)) {
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
            if (! $sig) {
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
     * Lee el bloque de firmas del checklist: los dos "N. y A." (nombres y
     * apellidos) y los dos "DNI N°". La columna izquierda corresponde a quien
     * entrega (ENTREGADO POR) y la derecha a quien recibe (RECIBIDO POR).
     *
     * Nota: la firma dibujada/pegada como imagen en Excel es un objeto de
     * dibujo (no un valor de celda), por lo que no se puede importar; solo se
     * importan los nombres y el DNI.
     *
     * @return array{entregado_por_nombres: ?string, entregado_por_dni: ?string, recibido_por_nombres: ?string, recibido_por_dni: ?string}
     */
    protected function leerBloqueFirmas(array $data): array
    {
        $resultado = [
            'entregado_por_nombres' => null,
            'entregado_por_dni' => null,
            'recibido_por_nombres' => null,
            'recibido_por_dni' => null,
        ];

        foreach ($data as $fila) {
            $filaArray = array_values($fila);

            if ($resultado['entregado_por_nombres'] === null && $resultado['recibido_por_nombres'] === null) {
                $nombres = array_values(array_filter(
                    $this->leerValoresEtiquetados($filaArray, 'n. y a'),
                    fn ($v) => $v !== null && $v !== ''
                ));

                if (! empty($nombres)) {
                    $resultado['entregado_por_nombres'] = mb_substr($nombres[0], 0, 150);

                    if (isset($nombres[1])) {
                        $resultado['recibido_por_nombres'] = mb_substr($nombres[1], 0, 150);
                    }
                }
            }

            if ($resultado['entregado_por_dni'] === null && $resultado['recibido_por_dni'] === null) {
                $dnis = array_values(array_filter(
                    $this->leerValoresEtiquetados($filaArray, 'dni'),
                    fn ($v) => $v !== null && $v !== ''
                ));

                if (! empty($dnis)) {
                    $resultado['entregado_por_dni'] = $this->limpiarDni($dnis[0]);

                    if (isset($dnis[1])) {
                        $resultado['recibido_por_dni'] = $this->limpiarDni($dnis[1]);
                    }
                }
            }
        }

        return $resultado;
    }

    /**
     * Busca en una fila las celdas que contienen la etiqueta y devuelve el
     * primer valor no vacío situado a su derecha (sin cruzar a otra etiqueta
     * de la misma fila). Devuelve un valor por cada aparición de la etiqueta.
     */
    protected function leerValoresEtiquetados(array $filaArray, string $etiqueta): array
    {
        $norm = $this->normalizarTexto($etiqueta);
        $valores = [];
        $total = count($filaArray);

        foreach ($filaArray as $i => $celda) {
            if (! str_contains($this->normalizarTexto($celda), $norm)) {
                continue;
            }

            $valor = null;

            for ($j = $i + 1; $j < $total; $j++) {
                $txt = trim((string) ($filaArray[$j] ?? ''));

                if ($txt === '') {
                    continue;
                }

                $normTxt = $this->normalizarTexto($txt);

                if (str_contains($normTxt, 'n. y a') || str_contains($normTxt, 'dni') ||
                    str_contains($normTxt, 'entregado por') || str_contains($normTxt, 'recibido por')) {
                    break;
                }

                $valor = $txt;
                break;
            }

            $valores[] = $valor;
        }

        return $valores;
    }

    /**
     * Deja solo los dígitos del DNI (máximo 8). Devuelve null si no hay dígitos.
     */
    protected function limpiarDni($valor): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $valor);

        return $digitos === '' ? null : substr($digitos, 0, 8);
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
            if (! str_contains($this->normalizarTexto($celda), $norm)) {
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
                $valida = $candidato && ! ($errores && ($errores['warning_count'] > 0 || $errores['error_count'] > 0));

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
