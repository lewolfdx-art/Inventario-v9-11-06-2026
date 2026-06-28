<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Movimiento;
use Illuminate\Http\Request;

class EscaneoController extends Controller
{
    private $contadorEscaneos = 0;

    public function index()
    {
        return view('escaneo.index');
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

    public function buscar(Request $request)
    {
        $sku = $this->normalizarSku($request->sku);

        $producto = Producto::with(['categoria', 'marca', 'estado'])->where('sku', $sku)->first();

        // Si no se encuentra, buscar sin guiones
        if (!$producto) {
            $skuSinGuion = str_replace('-', '', $sku);
            $producto = Producto::where('sku', $skuSinGuion)->first();
        }

        // Si no se encuentra, buscar con 0 al inicio (si el SKU empieza con 0)
        if (!$producto && str_starts_with($sku, '0')) {
            $skuSinCero = substr($sku, 1);
            $producto = Producto::where('sku', $skuSinCero)->first();
        }

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado: ' . $request->sku
            ], 404);
        }

        return response()->json([
            'success' => true,
            'producto' => $producto
        ]);
    }

    public function registrar(Request $request)
    {
        $sku = $this->normalizarSku($request->sku);

        $producto = Producto::where('sku', $sku)->first();

        // Si no se encuentra, buscar sin guiones
        if (!$producto) {
            $skuSinGuion = str_replace('-', '', $sku);
            $producto = Producto::where('sku', $skuSinGuion)->first();
        }

        // Si no se encuentra, buscar con 0 al inicio (si el SKU empieza con 0)
        if (!$producto && str_starts_with($sku, '0')) {
            $skuSinCero = substr($sku, 1);
            $producto = Producto::where('sku', $skuSinCero)->first();
        }

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado: ' . $request->sku
            ], 404);
        }

        // OBTENER EL CONTADOR DE LA SESIÓN
        $contador = session()->get('contador_escaneos', 0);
        $contador++;
        session()->put('contador_escaneos', $contador);

        // ALTERNAR AUTOMÁTICAMENTE: IMPAR → ENTRADA, PAR → SALIDA
        if ($contador % 2 == 1) {
            $tipo = 'entrada';
            $icono = '📥';
            $mensaje = 'ENTRADA';
        } else {
            $tipo = 'salida';
            $icono = '📤';
            $mensaje = 'SALIDA';
        }

        $stockAnterior = $producto->stock ?? 0;

        if ($tipo === 'entrada') {
            $nuevoStock = $stockAnterior + 1;
        } else {
            if ($stockAnterior <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sin stock disponible para SALIDA'
                ], 400);
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

        return response()->json([
            'success' => true,
            'producto' => $producto,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $nuevoStock,
            'tipo' => $tipo,
            'contador' => $contador,
            'mensaje' => $icono . ' ' . $mensaje . ' #' . $contador . ' registrada'
        ]);
    }
}