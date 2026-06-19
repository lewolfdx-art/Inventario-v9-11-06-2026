<div>
    <div class="p-6 max-w-4xl mx-auto w-full">
        <!-- Título -->
        <h1 class="text-3xl font-bold text-center mb-2">🔍 SISTEMA DE INVENTARIO</h1>
        <p class="text-gray-600 text-center mb-8">Escanea el código de barras para registrar movimientos</p>
        
        <!-- Tarjeta principal -->
        <div class="bg-white rounded-lg shadow-xl p-8 border border-gray-200">
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-700 mb-4">📷 Escanea el código de barras</div>
                
                <!-- Input de escaneo -->
                <div class="my-4">
                    <input 
                        type="text" 
                        wire:model.live="codigo" 
                        id="scan-input"
                        class="w-full max-w-md text-center text-2xl p-4 border-2 border-blue-500 rounded-lg focus:outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-300"
                        placeholder="Código de barras"
                        autofocus
                    >
                </div>
                
                <!-- Código mostrado -->
                <div class="text-2xl font-mono bg-gray-100 p-4 rounded-lg inline-block min-w-[200px]">
                    {{ $codigo ?: 'HER\'155' }}
                </div>
                
                <!-- Mensaje de estado -->
                @if($mensaje)
                    @php
                        $clase = 'mensaje-info';
                        if (str_contains($mensaje, '✅')) $clase = 'mensaje-exito';
                        elseif (str_contains($mensaje, '❌')) $clase = 'mensaje-error';
                        elseif (str_contains($mensaje, '⚠️')) $clase = 'mensaje-advertencia';
                    @endphp
                    <div class="mt-4 p-4 rounded-lg {{ $clase }}">
                        {{ $mensaje }}
                    </div>
                @endif
                
                <!-- Contadores -->
                <div class="mt-6 flex justify-center gap-12">
                    <div class="text-center">
                        <div class="text-sm font-semibold text-gray-600 uppercase">📥 ENTRADA</div>
                        <div class="text-3xl font-bold text-green-600">
                            Escaneos: {{ $contador }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm font-semibold text-gray-600 uppercase">📤 SALIDA</div>
                        <div class="text-3xl font-bold text-red-600">
                            Escaneos: {{ $contador }}
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="mt-6 flex justify-center gap-4">
                    <button wire:click="limpiar" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                        🗑️ Limpiar
                    </button>
                    <button wire:click="escanear" class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                        🔄 Escanear
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Último producto escaneado -->
        @if($ultimo_producto)
            <div class="mt-6 bg-blue-50 border border-blue-200 p-4 rounded-lg">
                <p class="font-semibold text-blue-800">📦 Último producto:</p>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div>
                        <span class="text-sm text-gray-600">Nombre:</span>
                        <span class="font-medium">{{ $ultimo_producto->nombre }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">SKU:</span>
                        <span class="font-mono font-medium">{{ $ultimo_producto->sku }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Stock actual:</span>
                        <span class="font-bold {{ $ultimo_producto->stock <= 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $ultimo_producto->stock }}
                        </span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Modelo:</span>
                        <span class="font-medium">{{ $ultimo_producto->modelo }}</span>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            Sistema de Inventario - 2026
        </div>
    </div>

    <style>
        #scan-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
        .mensaje-exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .mensaje-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .mensaje-advertencia { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    </style>

    <script>
        // ✅ Detector automático de escaneo
        let timeoutId = null;
        let ultimoValor = '';
        
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('scan-input');
            if (input) {
                input.focus();
                
                // Detectar cuando se escribe en el input
                input.addEventListener('input', function() {
                    // Limpiar el timeout anterior
                    clearTimeout(timeoutId);
                    
                    // Si el valor es diferente al anterior
                    if (this.value !== ultimoValor) {
                        ultimoValor = this.value;
                        
                        // Esperar 200ms para asegurar que el escáner terminó de escribir
                        timeoutId = setTimeout(() => {
                            if (this.value.trim() !== '') {
                                // Ejecutar la función escanear de Livewire
                                @this.escanear();
                            }
                        }, 200);
                    }
                });
                
                // También detectar Enter manual
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (this.value.trim() !== '') {
                            @this.escanear();
                        }
                    }
                });
            }
        });
        
        document.addEventListener('focus-input', function() {
            const input = document.getElementById('scan-input');
            if (input) {
                input.focus();
            }
        });
    </script>
</div>