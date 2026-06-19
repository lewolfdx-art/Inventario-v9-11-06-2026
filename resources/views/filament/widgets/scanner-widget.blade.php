<div class="fi-wi p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
            📋 Escáner de código de barras
        </h2>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">Modo actual:</span>
            <span class="px-3 py-1 text-sm rounded-full {{ $contador_escaneos % 2 == 0 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                {{ $contador_escaneos % 2 == 0 ? '📥 ENTRADA' : '📤 SALIDA' }}
            </span>
            <span class="text-xs text-gray-400">(#{{ $contador_escaneos }})</span>
        </div>
    </div>

    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🔍 Código de barras
                </label>
                <input 
                    type="text" 
                    wire:model="sku" 
                    wire:keydown.enter="procesarEscaneo"
                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-lg"
                    placeholder="Escanea un código de barras..."
                    autofocus
                    id="scanner-input"
                >
                <p class="text-xs text-gray-400 mt-1">💡 Cada escaneo alterna: SALIDA → ENTRADA → SALIDA → ENTRADA</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Cantidad
                </label>
                <input 
                    type="number" 
                    wire:model="cantidad"
                    min="1"
                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>
        </div>

        <div class="flex justify-between gap-2">
            <button 
                wire:click="reiniciarContador"
                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-sm"
            >
                🔄 Reiniciar contador
            </button>
            <button 
                wire:click="procesarEscaneo"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
            >
                🔍 Escanear
            </button>
        </div>
    </div>

    @if($ultimo_escaneo)
    <div class="mt-4 p-4 rounded-lg {{ $tipo === 'entrada' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-orange-50 dark:bg-orange-900/20' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-bold text-gray-900 dark:text-gray-100">
                    #{{ $ultimo_escaneo['numero'] }} {{ $tipo === 'entrada' ? '📥' : '📤' }} {{ $ultimo_escaneo['producto']->nombre }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    SKU: {{ $ultimo_escaneo['producto']->sku }}
                </p>
                <p class="text-sm font-semibold">
                    Stock: {{ $ultimo_escaneo['stock_anterior'] }} → {{ $ultimo_escaneo['stock_nuevo'] }}
                </p>
            </div>
            <div class="text-4xl">
                {{ $tipo === 'entrada' ? '📥' : '📤' }}
            </div>
        </div>
    </div>
    @endif

    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-400">
        <div>💡 El lector debe estar configurado como "Teclado USB (HID)"</div>
        <div>🔄 Escaneo 1: SALIDA | Escaneo 2: ENTRADA | Escaneo 3: SALIDA...</div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function () {
        document.getElementById('scanner-input')?.focus();

        Livewire.on('scanner-cleared', function () {
            setTimeout(function() {
                document.getElementById('scanner-input')?.focus();
            }, 100);
        });
    });
</script>