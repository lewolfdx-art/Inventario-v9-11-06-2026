<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold"> Resumen de Maletines</h2>
            <span class="text-2xl font-bold text-primary-600">{{ $total }}</span>
        </div>

        <div class="mt-4 grid grid-cols-4 gap-4">
            <div class="text-center p-3 bg-success-50 rounded-lg">
                <p class="text-2xl font-bold text-success-600">{{ $estados['activo'] }}</p>
                <p class="text-sm text-gray-600">Activos</p>
            </div>
            <div class="text-center p-3 bg-warning-50 rounded-lg">
                <p class="text-2xl font-bold text-warning-600">{{ $estados['prestado'] }}</p>
                <p class="text-sm text-gray-600">Prestados</p>
            </div>
            <div class="text-center p-3 bg-info-50 rounded-lg">
                <p class="text-2xl font-bold text-info-600">{{ $estados['devuelto'] }}</p>
                <p class="text-sm text-gray-600">Devueltos</p>
            </div>
            <div class="text-center p-3 bg-danger-50 rounded-lg">
                <p class="text-2xl font-bold text-danger-600">{{ $estados['mantenimiento'] }}</p>
                <p class="text-sm text-gray-600">Mantenimiento</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div class="p-3 bg-primary-50 rounded-lg">
                <p class="text-sm text-gray-600">Con productos asociados</p>
                <p class="text-xl font-bold text-primary-600">{{ $conProductos }}</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">Sin productos asociados</p>
                <p class="text-xl font-bold text-gray-600">{{ $sinProductos }}</p>
            </div>
        </div>

        @if($ultimos->isNotEmpty())
            <div class="mt-4">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Últimos maletines creados</h3>
                <ul class="space-y-1">
                    @foreach($ultimos as $maletin)
                        <li class="text-sm flex justify-between">
                            <span>{{ $maletin->nombre }}</span>
                            <span class="text-gray-500">{{ $maletin->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>