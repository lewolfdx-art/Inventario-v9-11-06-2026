<x-filament-panels::page>
    @if($mostrarHistorial)
        {{-- ✅ RESUMEN DE TOTALES --}}
        <div class="mb-6 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-200 dark:border-blue-800 p-5">
            <h3 class="text-lg font-bold text-blue-700 dark:text-blue-400 flex items-center gap-2 mb-4">
                <span class="text-2xl">📊</span>
                Resumen de Guías
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-l-4 border-blue-500 shadow-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">Total</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totales['total'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-l-4 border-gray-400 shadow-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">Borrador</div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $totales['borrador'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-l-4 border-yellow-500 shadow-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">Pendiente</div>
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $totales['pendiente'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-l-4 border-blue-500 shadow-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">Aprobado</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totales['aprobado'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-l-4 border-green-500 shadow-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">Entregado</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $totales['entregado'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-l-4 border-red-500 shadow-sm">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase">Devuelto</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $totales['devuelto'] }}</div>
                </div>
            </div>
        </div>

        {{-- ✅ HISTORIAL --}}
        <div class="mb-6 rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 border-2 border-gray-200 dark:border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <span class="text-2xl">🕐</span>
                    Historial de Últimas Modificaciones
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Mostrando últimas 20</span>
            </div>

            {{-- SCROLL PROPIO --}}
            <div class="max-h-96 overflow-y-auto pr-2 space-y-2" style="scrollbar-width: thin;">
                @forelse($historial as $guia)
                    @php
                        $colorEstado = match($guia->estado) {
                            'borrador' => 'gray',
                            'pendiente' => 'warning',
                            'aprobado' => 'info',
                            'entregado' => 'success',
                            'devuelto' => 'danger',
                            default => 'gray',
                        };
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $colorEstado }}-100 text-{{ $colorEstado }}-800 dark:bg-{{ $colorEstado }}-900 dark:text-{{ $colorEstado }}-300">
                                        {{ ucfirst($guia->estado) }}
                                    </span>
                                    <span class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300">
                                        {{ $guia->codigo }}
                                    </span>
                                </div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $guia->nombre_proyecto ?? 'Sin nombre' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    👤 {{ $guia->responsable_solicitante ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    🕐 {{ $guia->updated_at?->format('d/m/Y H:i') }}
                                </div>
                                <a href="/admin/guia-requerimientos/{{ $guia->id }}/edit"
                                   class="inline-block mt-1 text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                    Editar
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 dark:text-gray-400 p-8">
                        No hay guías registradas aún
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>