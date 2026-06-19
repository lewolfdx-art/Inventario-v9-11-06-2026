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

    public function buscar(Request $request)
    {
        $sku = $request->sku;
        $sku = str_replace(["'", "´", "`"], '-', $sku);

        $producto = Producto::with(['categoria', 'marca', 'estado'])->where('sku', $sku)->first();

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'producto' => $producto
        ]);
    }

    public function registrar(Request $request)
    {
        $sku = str_replace(["'", "´", "`"], '-', $request->sku);

        $producto = Producto::where('sku', $sku)->first();

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // ✅ OBTENER EL CONTADOR DE LA SESIÓN
        $contador = session()->get('contador_escaneos', 0);
        $contador++;
        session()->put('contador_escaneos', $contador);

        // ✅ ALTERNAR AUTOMÁTICAMENTE: IMPAR → SALIDA, PAR → ENTRADA
        if ($contador % 2 == 1) {
            $tipo = 'salida';
            $icono = '📤';
            $mensaje = 'SALIDA';
        } else {
            $tipo = 'entrada';
            $icono = '📥';
            $mensaje = 'ENTRADA';
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