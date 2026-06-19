<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Producto;
use App\Models\Movimiento;

class Escaner extends Component
{
    public $codigo = '';
    public $mensaje = '';
    public $contador = 0;
    public $ultimo_producto = null;
    public $scan_count = 0;
    
    // ✅ Usar el layout existente
    protected $layout = 'components.layouts.app';
    
    public function mount()
    {
        $this->contador = session('contador_escaner', 0);
        $this->scan_count = $this->contador;
    }
    
    private function normalizarSku($sku)
    {
        $sku = str_replace(["'", "´", "`", '"', "’", "‘", " "], '-', $sku);
        $sku = str_replace(' ', '', $sku);
        return strtoupper($sku);
    }
    
    public function escanear()
    {
        if (empty($this->codigo)) {
            $this->mensaje = '⚠️ Por favor, escanea un código de barras';
            return;
        }
        
        $skuNormalizado = $this->normalizarSku($this->codigo);
        
        $producto = Producto::where('sku', $skuNormalizado)->first();
        
        if (!$producto) {
            $skuSinGuion = str_replace('-', '', $skuNormalizado);
            $producto = Producto::where('sku', $skuSinGuion)->first();
        }
        
        if (!$producto) {
            $this->mensaje = '❌ Producto no encontrado: ' . $this->codigo;
            $this->codigo = '';
            return;
        }
        
        $this->contador++;
        $this->scan_count = $this->contador;
        
        $tipo = ($this->contador % 2 == 1) ? 'entrada' : 'salida';
        $stockAnterior = $producto->stock ?? 0;
        
        if ($tipo == 'entrada') {
            $nuevoStock = $stockAnterior + 1;
        } else {
            if ($stockAnterior <= 0) {
                $this->mensaje = '❌ Sin stock disponible para ' . $producto->nombre;
                $this->contador--;
                $this->scan_count = $this->contador;
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
        $this->mensaje = "✅ {$tipo} registrada: {$producto->nombre} (Stock: {$stockAnterior} → {$nuevoStock})";
        
        $this->codigo = '';
        $this->dispatch('focus-input');
    }
    
    public function limpiar()
    {
        $this->codigo = '';
        $this->mensaje = '';
        $this->ultimo_producto = null;
        $this->contador = 0;
        $this->scan_count = 0;
        session(['contador_escaner' => 0]);
        $this->dispatch('focus-input');
    }
    
    public function render()
    {
        return view('livewire.escaner');
    }
}