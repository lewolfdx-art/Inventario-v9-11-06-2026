<?php
// app/Imports/MaletinImport.php

namespace App\Imports;

use App\Models\Maletin;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MaletinImport implements WithMultipleSheets, WithCalculatedFormulas
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

    public function sheets(): array
    {
        return [
            'Hoja2' => new FirstSheetImport($this->categoriaId, $this->subcategoriaId, $this->cleanExisting),
        ];
    }
}

class FirstSheetImport implements ToCollection, SkipsEmptyRows
{
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
        $data = $rows->toArray();
        
        // Variables
        $nombreMaletin = null;
        $observaciones = '';
        $componentes = [];
        $accesorios = [];
        $adicionales = [];
        
        $procesandoAccesorios = false;
        $procesandoAdicionales = false;
        
        // ============================================
        // PASADA 1: DETECTAR EL NOMBRE DEL MALETÍN
        // ============================================
        foreach ($data as $fila) {
            $filaArray = array_values($fila);
            $contenido = trim($filaArray[2] ?? '');
            
            // Buscar el título del checklist
            if (stripos($contenido, 'CHECK LIST DE ENTREGA') !== false) {
                if (preg_match('/CHECK LIST DE ENTREGA\s*-\s*(.+?)(?:\s*$|\s+Versión)/i', $contenido, $matches)) {
                    $nombreMaletin = trim($matches[1]);
                }
                break;
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
            
            // Buscar observaciones
            if (stripos($contenido, 'OBSERVACIONES') !== false) {
                $observaciones = $contenido;
                continue;
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
                ];
                continue;
            }
            
            // ============================================
            // DETECTAR SECCIONES
            // ============================================
            if (stripos($contenido, 'SET DE ACCESORIOS DE CONEXIÓN') !== false) {
                $procesandoAccesorios = true;
                $procesandoAdicionales = false;
                continue;
            }
            
            if (stripos($contenido, 'PAQUETE ADICIONAL DE ACCESORIOS') !== false) {
                $procesandoAccesorios = false;
                $procesandoAdicionales = true;
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
                        ];
                    }
                }
            }
            
            // ============================================
            // PROCESAR ADICIONALES
            // ============================================
            if ($procesandoAdicionales && !empty($item) && !empty($contenido)) {
                if (preg_match('/^4[\.\s]/', $item) || preg_match('/^4\.\d+$/', $item)) {
                    $itemNum = $this->extractItemNumber($item);
                    
                    if ($itemNum > 0) {
                        $contenido = $this->limpiarContenido($contenido);
                        
                        $adicionales[] = [
                            'item_numero' => $itemNum,
                            'cantidad' => $this->extractCantidad($cantidad),
                            'descripcion' => $contenido,
                            'incluido' => !$this->hasFalta($contenido) && !$this->hasFalta($item),
                        ];
                    }
                }
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
            $maletin->save();
            
        } elseif (!$maletin) {
            // Crear nuevo maletín
            $maletin = Maletin::create([
                'nombre' => $nombreMaletin,
                'estado' => 'activo',
                'observaciones' => $observaciones,
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
}