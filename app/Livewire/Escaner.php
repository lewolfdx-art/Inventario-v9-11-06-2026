<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Producto;
use App\Models\Unidad;
use App\Models\Movimiento;

class Escaner extends Component
{
    public $codigo = '';
    public $mensaje = '';
    public $contador = 0;
    public $ultimo_producto = null;
    public $entradas = 0;
    public $salidas = 0;
    public $ultima_unidad = null;
    
    protected $layout = 'components.layouts.app';
    
    public function mount()
    {
        $this->contador = session('contador_escaner', 0);
        $this->entradas = ceil($this->contador / 2);
        $this->salidas = floor($this->contador / 2);
    }
    
    /**
     * ✅ LIMPIAR SKU: Eliminar caracteres no deseados
     */
    private function normalizarSku($sku)
    {
        // Limpiar espacios
        $sku = trim($sku);
        
        // Eliminar prefijos como : :: > al inicio
        $sku = ltrim($sku, ':>');
        $sku = trim($sku);
        
        // Reemplazar apóstrofes, comillas y acentos por guiones
        $sku = str_replace(["'", "´", "`", '"', "’", "‘", ";"], '-', $sku);
        
        // Reemplazar espacios por guiones
        $sku = str_replace(' ', '-', $sku);
        
        // Eliminar caracteres no permitidos (solo letras, números y guiones)
        $sku = preg_replace('/[^a-zA-Z0-9\-]/', '', $sku);
        
        // Eliminar guiones duplicados
        $sku = preg_replace('/-+/', '-', $sku);
        
        // Eliminar guiones al inicio o final
        $sku = trim($sku, '-');
        
        return strtoupper($sku);
    }
    
    public function escanear()
    {
        if (empty($this->codigo)) {
            $this->mensaje = '⚠️ Por favor, escanea un código de barras';
            return;
        }
        
        $codigoNormalizado = $this->normalizarSku($this->codigo);
        
        // ✅ 1) Buscar primero una UNIDAD física (código propio: 0SKU-001)
        $unidad = $this->buscarUnidad($codigoNormalizado);
        if ($unidad) {
            $this->escanearUnidad($unidad);
            $this->codigo = '';
            $this->dispatch('focus-input');
            return;
        }
        
        // ✅ 2) Fallback: buscar producto por SKU (comportamiento anterior)
        $producto = $this->buscarProducto($codigoNormalizado);
        
        if (!$producto) {
            $this->mensaje = '❌ Producto no encontrado: ' . $this->codigo;
            $this->codigo = '';
            return;
        }
        
        // Incrementar contador
        $this->contador++;
        $this->entradas = ceil($this->contador / 2);
        $this->salidas = floor($this->contador / 2);
        
        $tipo = ($this->contador % 2 == 1) ? 'entrada' : 'salida';
        $stockAnterior = $producto->stock ?? 0;
        
        if ($tipo == 'entrada') {
            $nuevoStock = $stockAnterior + 1;
        } else {
            if ($stockAnterior <= 0) {
                $this->mensaje = '❌ Sin stock disponible para ' . $producto->nombre;
                $this->contador--;
                $this->entradas = ceil($this->contador / 2);
                $this->salidas = floor($this->contador / 2);
                $this->codigo = '';
                return;
            }
            $nuevoStock = $stockAnterior - 1;
        }
        
        $producto->stock = $nuevoStock;
        $producto->save();
        
        Movimiento::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => 1,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $nuevoStock,
        ]);
        
        session(['contador_escaner' => $this->contador]);
        $this->ultimo_producto = $producto;
        
        $icono = ($tipo == 'entrada') ? '📥' : '📤';
        $this->mensaje = "✅ {$icono} {$tipo} registrada: {$producto->nombre} (Stock: {$stockAnterior} → {$nuevoStock})";
        
        $this->codigo = '';
        $this->dispatch('focus-input');
    }
    
    public function limpiar()
    {
        $this->codigo = '';
        $this->mensaje = '';
        $this->ultimo_producto = null;
        $this->ultima_unidad = null;
        $this->contador = 0;
        $this->entradas = 0;
        $this->salidas = 0;
        session(['contador_escaner' => 0]);
        $this->dispatch('focus-input');
    }

    /**
     * ✅ Buscar una unidad física por su código (con fallbacks)
     */
    private function buscarUnidad($codigo)
    {
        $unidad = Unidad::where('codigo', $codigo)->first();

        if (!$unidad) {
            // Intentar sin guiones (algunos lectores omiten el "-")
            $sinGuion = str_replace('-', '', $codigo);
            $unidad = Unidad::join('productos', 'productos.id', '=', 'producto_unidades.producto_id')
                ->whereRaw('REPLACE(producto_unidades.codigo, "-", "") = ?', [$sinGuion])
                ->select('producto_unidades.*')
                ->first();
        }

        return $unidad;
    }

    /**
     * ✅ Buscar producto por SKU (comportamiento anterior, sin guiones y con/sin 0 inicial)
     */
    private function buscarProducto($skuNormalizado)
    {
        $producto = Producto::where('sku', $skuNormalizado)->first();

        if (!$producto) {
            $skuSinGuion = str_replace('-', '', $skuNormalizado);
            $producto = Producto::where('sku', $skuSinGuion)->first();
        }

        if (!$producto && str_starts_with($skuNormalizado, '0')) {
            $skuSinCero = substr($skuNormalizado, 1);
            $producto = Producto::where('sku', $skuSinCero)->first();
        }

        return $producto;
    }

    /**
     * ✅ Escaneo de una unidad física:
     *    disponible → SALIDA (estado "salido", stock -1)
     *    salido     → RETORNO / ENTRADA (estado "disponible", stock +1)
     */
    private function escanearUnidad(Unidad $unidad)
    {
        $producto = $unidad->producto;
        $stockAnterior = $producto->stock ?? 0;

        if ($unidad->estado === 'disponible') {
            if ($stockAnterior <= 0) {
                $this->mensaje = '❌ Sin stock disponible para ' . $producto->nombre . ' (unidad ' . $unidad->codigo . ')';
                $this->codigo = '';
                $this->dispatch('focus-input');
                return;
            }

            $nuevoStock = $stockAnterior - 1;
            $unidad->estado = 'salido';
            $tipo = 'salida';
            $icono = '📤';
        } else {
            $nuevoStock = $stockAnterior + 1;
            $unidad->estado = 'disponible';
            $tipo = 'entrada';
            $icono = '📥';
        }

        $unidad->save();
        $producto->stock = $nuevoStock;
        $producto->save();

        Movimiento::create([
            'producto_id' => $producto->id,
            'unidad_id'   => $unidad->id,
            'tipo'        => $tipo,
            'cantidad'    => 1,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo'    => $nuevoStock,
            'observaciones'  => 'Unidad ' . $unidad->codigo,
        ]);

        $this->ultimo_producto = $producto;
        $this->ultima_unidad = $unidad;

        $this->mensaje = "✅ {$icono} {$tipo} unidad {$unidad->codigo}: {$producto->nombre} (Stock: {$stockAnterior} → {$nuevoStock})";
    }
    
    public function render()
    {
        return view('livewire.escaner');
    }
}