<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Marca;
use App\Models\UnidadCompra;
use App\Models\Naturaleza;
use App\Models\RequerimientoInventario;
use App\Models\RequerimientoSerie;
use App\Models\RequerimientoLote;
use App\Models\RequerimientoCalibracion;
use App\Models\Estado;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Log;
use App\Imports\Concerns\ParseaFechas;

class ProductosImport implements ToModel, WithHeadingRow, WithStartRow
{
    use ParseaFechas;

    private $importados = 0;

    /**
     * Constructor solo para testear que el archivo se recarga bien.
     */
    public function __construct()
    {
        Log::info('=== ProductosImport v2.0 cargado ===');
        Log::info('headingRow: ' . $this->headingRow());
        Log::info('startRow: ' . $this->startRow());
    }

    /**
     * Fila donde están los ENCABEZADOS (1-indexed).
     *
     * productos_todos_....xlsx  →  fila 3
     * OyT_A1.xlsx               →  fila 1
     */
    public function headingRow(): int
    {
        return 3;
    }

    /**
     * Fila donde empiezan los DATOS (1-indexed).
     *
     * productos_todos_....xlsx  →  fila 4
     * OyT_A1.xlsx               →  fila 2
     */
    public function startRow(): int
    {
        return 4;
    }

    public function model(array $row)
    {
        // Log para debug
        Log::info('Fila recibida:', $row);

        // ---------------------------
        // MAPEO FLEXIBLE DE COLUMNAS
        // ---------------------------
        $sku    = $this->get($row, ['sku']);
        $nombre = $this->get($row, ['nombre']);
        $modelo = $this->get($row, ['modelo']);
        $serie  = $this->get($row, ['serie']);

        $categoriaNombre    = strtoupper($this->get($row, ['categoria', 'categoría', 'categoria_familia']));
        $subcategoriaNombre = strtoupper($this->get($row, ['subcategoria', 'subcategoría']));
        $marcaNombre        = strtoupper($this->get($row, ['marca']));
        $unidadNombre       = strtoupper($this->get($row, ['unidad_compra', 'unidad']));
        $naturalezaNombre   = strtolower($this->get($row, ['naturaleza']));
        $estadoNombre       = strtolower($this->get($row, ['estado']));

        $reqInventario  = trim($this->get($row, ['req_inventario', 'requiere_inventario']));
        $reqSerie       = trim($this->get($row, ['req_serie', 'requiere_serie']));
        $reqLote        = trim($this->get($row, ['req_lote', 'requiere_lote']));
        $reqCalibracion = trim($this->get($row, ['req_calibracion', 'req_calibración', 'requiere_calibracion', 'requiere_calibración']));

        // Campos de fabricación / inventario
        $añoFabricacion = $this->get($row, ['año_fabricacion', 'ano_fabricacion', 'anio_fabricacion', 'año', 'anio']);
        $stock          = $this->get($row, ['stock', 'stock_actual', 'cantidad']);

        // Calibración y mantenimiento
        $ubicacion       = $this->get($row, ['ubicacion', 'ubicación']);
        $frecuencia      = $this->get($row, ['frecuencia_mantenimiento', 'frecuencia_de_mantenimiento', 'frecuencia']);
        $ultimaCalibracion = $this->get($row, ['fecha_ultima_calibracion', 'ultima_calibracion', 'última_calibración']);
        $ultimoMantenimiento = $this->get($row, ['fecha_ultimo_mantenimiento', 'ultimo_mantenimiento', 'último_mantenimiento']);

        // Validar SKU
        if (empty($sku)) {
            Log::warning('Fila omitida: SKU vacío', $row);
            return null;
        }

        // Fallback nombre
        if (empty($nombre)) {
            $nombre = $sku;
        }

        // Fallback categoría
        if (empty($categoriaNombre)) {
            $categoriaNombre = 'OTROS';
        }
        $categoria = Categoria::firstOrCreate(
            ['nombre' => $categoriaNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Subcategoría
        if (empty($subcategoriaNombre)) {
            $subcategoriaNombre = 'S/M';
        }
        $subcategoria = Subcategoria::firstOrCreate(
            [
                'nombre' => $subcategoriaNombre,
                'categoria_id' => $categoria->id
            ],
            ['descripcion' => 'Importado desde Excel']
        );

        // Marca
        if (empty($marcaNombre)) {
            $marcaNombre = 'S/M';
        }
        $marca = Marca::firstOrCreate(
            ['nombre' => $marcaNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Unidad
        if (empty($unidadNombre)) {
            $unidadNombre = 'UNIDAD';
        }
        $unidadCompra = UnidadCompra::firstOrCreate(
            ['nombre' => $unidadNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Naturaleza (fallback tangible)
        if (empty($naturalezaNombre)) {
            $naturalezaNombre = 'tangible';
        }
        $naturaleza = Naturaleza::firstOrCreate(
            ['nombre' => $naturalezaNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Estado (fallback activo)
        if (empty($estadoNombre)) {
            $estadoNombre = 'activo';
        }
        $estado = Estado::firstOrCreate(
            ['nombre' => $estadoNombre],
            ['descripcion' => 'Estado del producto']
        );

        // Requerimientos (fallback "No")
        if (empty($reqInventario)) $reqInventario = 'No';
        if (empty($reqSerie)) $reqSerie = 'No';
        if (empty($reqLote)) $reqLote = 'No';
        if (empty($reqCalibracion)) $reqCalibracion = 'No';

        $reqInv = RequerimientoInventario::where('nombre', $reqInventario)->first();
        $reqSer = RequerimientoSerie::where('nombre', $reqSerie)->first();
        $reqLot = RequerimientoLote::where('nombre', $reqLote)->first();
        $reqCal = RequerimientoCalibracion::where('nombre', $reqCalibracion)->first();

        // Campos opcionales: solo se escriben si el archivo trae un valor,
        // para no borrar datos existentes al reimportar un archivo reducido.
        $extra = [];

        if ($añoFabricacion !== '' && is_numeric($añoFabricacion)) {
            $extra['año_fabricacion'] = (int) $añoFabricacion;
        }
        if ($stock !== '' && is_numeric($stock)) {
            $extra['stock'] = (int) $stock;
        }
        if ($ubicacion !== '') {
            $extra['ubicacion'] = $this->normalizarUbicacion($ubicacion);
        }
        if ($frecuencia !== '') {
            $frecuenciaNormalizada = $this->normalizarFrecuencia($frecuencia);
            if ($frecuenciaNormalizada !== null) {
                $extra['frecuencia_mantenimiento'] = $frecuenciaNormalizada;
            }
        }
        if (($fecha = $this->parseFechaFlexible($ultimaCalibracion)) !== null) {
            $extra['fecha_ultima_calibracion'] = $fecha;
        }
        if (($fecha = $this->parseFechaFlexible($ultimoMantenimiento)) !== null) {
            $extra['fecha_ultimo_mantenimiento'] = $fecha;
        }

        // Crear/actualizar producto
        $producto = Producto::updateOrCreate(
            ['sku' => $sku],
            array_merge([
                'modelo' => $modelo ?? '',
                'nombre' => $nombre,
                'serie' => $serie ?: null,
                'unidad_compra_id' => $unidadCompra->id,
                'naturaleza_id' => $naturaleza->id,
                'req_inventario_id' => $reqInv ? $reqInv->id : 3,
                'req_serie_id' => $reqSer ? $reqSer->id : 3,
                'req_lote_id' => $reqLot ? $reqLot->id : 3,
                'req_calibracion_id' => $reqCal ? $reqCal->id : 2,
                'estado_id' => $estado->id,
                'categoria_id' => $categoria->id,
                'subcategoria_id' => $subcategoria->id,
                'marca_id' => $marca->id,
                'descripcion' => $this->get($row, ['descripcion']),
                'observacion' => $this->get($row, ['observacion', 'observaciones']),
            ], $extra)
        );

        if ($producto->wasRecentlyCreated) {
            $this->importados++;
        }

        // Historial de recalibraciones (una fila por recalibración en el Excel).
        $recFecha = $this->get($row, ['recalibracion_fecha', 'recalibracion_-_fecha', 'recalibraciones_fecha']);
        $recFechaDate = $this->parseFechaFlexible($recFecha);

        if ($recFechaDate !== null) {
            $recProx = $this->get($row, ['recalibracion_proxima', 'recalibracion_-_proxima', 'recalibraciones_proxima']);
            $recObs  = $this->get($row, ['recalibracion_observaciones', 'recalibracion_-_observaciones', 'recalibraciones_observaciones']);
            $recPor  = $this->get($row, ['recalibracion_realizada_por', 'recalibracion_-_realizada_por', 'recalibraciones_realizada_por']);

            $producto->recalibraciones()->updateOrCreate(
                ['fecha_recalibracion' => $recFechaDate],
                [
                    'proxima_recalibracion' => $this->parseFechaFlexible($recProx),
                    'observaciones' => $recObs !== '' ? $recObs : null,
                    'realizada_por_nombre' => $recPor !== '' ? $recPor : null,
                ]
            );
        }

        return $producto;
    }

    /**
     * Normaliza el valor de ubicación al enum de la BD.
     */
    private function normalizarUbicacion($value): string
    {
        $v = $this->normalize((string) $value);

        if (str_contains($v, 'alquil') || str_contains($v, 'renta')) {
            return 'alquilado';
        }
        if (str_contains($v, 'campo') || str_contains($v, 'obra') || str_contains($v, 'terreno')) {
            return 'campo';
        }

        return 'almacen';
    }

    /**
     * Normaliza el valor de frecuencia de mantenimiento al enum de la BD.
     */
    private function normalizarFrecuencia($value): ?string
    {
        $v = $this->normalize((string) $value);

        if ($v === '') {
            return null;
        }
        if (str_contains($v, 'vida') || str_contains($v, 'unica')) {
            return 'vida_util_unica';
        }
        if (str_contains($v, '2') || str_contains($v, 'dos')) {
            return '2_anios';
        }
        if (str_contains($v, '1') || str_contains($v, 'uno') || str_contains($v, 'anual')) {
            return '1_anio';
        }

        return null;
    }

    /**
     * Obtener el primer valor no vacío de varias llaves candidatas.
     */
    private function get(array $row, array $keys)
    {
        foreach ($keys as $key) {
            foreach ($row as $rowKey => $value) {
                if ($this->normalize($rowKey) === $this->normalize($key)) {
                    if (is_string($value)) $value = trim($value);
                    if ($value !== null && $value !== '') {
                        return $value;
                    }
                }
            }
        }
        return '';
    }

    /**
     * Normaliza una cadena: sin acentos, minúsculas, sin espacios.
     */
    private function normalize($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(
            ['á','é','í','ó','ú','ü','ñ',' ','-'],
            ['a','e','i','o','u','u','n','_','_'],
            $str
        );
        return $str;
    }

    public function getImportados()
    {
        return $this->importados;
    }
}