<x-filament::widget>
    <x-filament::card>
        <h2 class="text-lg font-bold mb-4">📊 Resumen General del Sistema</h2>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-primary-50 rounded-lg">
                <p class="text-3xl font-bold text-primary-600">{{ $totalProductos ?? 0 }}</p>
                <p class="text-sm text-gray-600">Total Productos</p>
            </div>
            <div class="text-center p-4 bg-success-50 rounded-lg">
                <p class="text-3xl font-bold text-success-600">{{ $totalMaletines ?? 0 }}</p>
                <p class="text-sm text-gray-600">Total Maletines</p>
            </div>
            <div class="text-center p-4 bg-info-50 rounded-lg">
                <p class="text-3xl font-bold text-info-600">{{ $totalMarcas ?? 0 }}</p>
                <p class="text-sm text-gray-600">Total Marcas</p>
            </div>
            <div class="text-center p-4 bg-warning-50 rounded-lg">
                <p class="text-3xl font-bold text-warning-600">{{ $totalCategorias ?? 0 }}</p>
                <p class="text-sm text-gray-600">Total Categorías</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div class="p-4 bg-danger-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-danger-600">{{ $stockCritico ?? 0 }}</p>
                <p class="text-sm text-gray-600">⚠️ Stock Crítico (≤ 3)</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-gray-600">{{ $sinStock ?? 0 }}</p>
                <p class="text-sm text-gray-600">🚫 Sin Stock</p>
            </div>
        </div>

        @if(isset($categorias) && $categorias->isNotEmpty())
            <div class="mt-4 pt-4 border-t">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">📈 Productos por categoría</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($categorias as $cat)
                        <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm text-primary-700">
                            {{ $cat->nombre }}: {{ $cat->productos_count }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>