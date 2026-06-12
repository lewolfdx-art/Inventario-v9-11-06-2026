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
use Illuminate\Support\Facades\Log;

class ProductosImport implements ToModel, WithHeadingRow
{
    private $importados = 0;

    public function model(array $row)
    {
        // Limpiar valores (sin truncar)
        $sku = trim($row['sku'] ?? '');
        $modelo = trim($row['modelo'] ?? '');
        $nombre = trim($row['nombre'] ?? '');
        $categoriaNombre = strtoupper(trim($row['categoria'] ?? ''));
        $subcategoriaNombre = strtoupper(trim($row['subcategoria'] ?? ''));
        $marcaNombre = strtoupper(trim($row['marca'] ?? ''));
        $unidadCompraNombre = strtoupper(trim($row['unidad_compra'] ?? ''));
        $naturalezaNombre = strtolower(trim($row['naturaleza'] ?? ''));
        $estadoNombre = strtolower(trim($row['estado'] ?? ''));
        
        // Validar datos mínimos
        if (empty($sku)) {
            Log::warning('Fila omitida: SKU vacío');
            return null;
        }
        
        // Buscar o crear Categoría
        $categoria = Categoria::firstOrCreate(
            ['nombre' => $categoriaNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Buscar o crear Subcategoría
        $subcategoria = Subcategoria::firstOrCreate(
            [
                'nombre' => $subcategoriaNombre,
                'categoria_id' => $categoria->id
            ],
            ['descripcion' => 'Importado desde Excel']
        );

        // Buscar o crear Marca
        $marca = Marca::firstOrCreate(
            ['nombre' => $marcaNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Buscar o crear Unidad de Compra
        $unidadCompra = UnidadCompra::firstOrCreate(
            ['nombre' => $unidadCompraNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Buscar o crear Naturaleza
        $naturaleza = Naturaleza::firstOrCreate(
            ['nombre' => $naturalezaNombre],
            ['descripcion' => 'Importado desde Excel']
        );

        // Buscar Requerimientos
        $reqInventario = RequerimientoInventario::where('nombre', trim($row['req_inventario'] ?? 'No'))->first();
        $reqSerie = RequerimientoSerie::where('nombre', trim($row['req_serie'] ?? 'No'))->first();
        $reqLote = RequerimientoLote::where('nombre', trim($row['req_lote'] ?? 'No'))->first();
        $reqCalibracion = RequerimientoCalibracion::where('nombre', trim($row['req_calibracion'] ?? 'No'))->first();

        // Buscar o crear Estado
        $estado = Estado::firstOrCreate(
            ['nombre' => $estadoNombre],
            ['descripcion' => 'Estado del producto']
        );

        // Crear o actualizar Producto
        $producto = Producto::updateOrCreate(
            ['sku' => $sku],
            [
                'modelo' => $modelo,
                'nombre' => $nombre,
                'unidad_compra_id' => $unidadCompra->id,
                'naturaleza_id' => $naturaleza->id,
                'req_inventario_id' => $reqInventario ? $reqInventario->id : 2,
                'req_serie_id' => $reqSerie ? $reqSerie->id : 2,
                'req_lote_id' => $reqLote ? $reqLote->id : 2,
                'req_calibracion_id' => $reqCalibracion ? $reqCalibracion->id : 2,
                'estado_id' => $estado->id,
                'categoria_id' => $categoria->id,
                'subcategoria_id' => $subcategoria->id,
                'marca_id' => $marca->id,
                'descripcion' => $row['descripcion'] ?? null,
            ]
        );
        
        if ($producto->wasRecentlyCreated) {
            $this->importados++;
        }
        
        return $producto;
    }

    public function getImportados()
    {
        return $this->importados;
    }
}