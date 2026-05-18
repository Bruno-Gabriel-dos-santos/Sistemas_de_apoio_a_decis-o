@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Monitor do Sistema</h2>

                <!-- Informações do Sistema -->
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Informações do Sistema</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-700">Sistema Operacional</h4>
                            <p class="text-gray-600">{{ $systemInfo['os'] }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-700">Kernel</h4>
                            <p class="text-gray-600">{{ $systemInfo['kernel'] }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-700">Hostname</h4>
                            <p class="text-gray-600">{{ $systemInfo['hostname'] }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-700">Uptime</h4>
                            <p class="text-gray-600">{{ $systemInfo['uptime'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Uso de Recursos -->
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Uso de Recursos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- CPU -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium mb-3">CPU</h4>
                            <div class="space-y-2">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600">Load 1min</span>
                                        <span>{{ number_format($cpuUsage['load_1'], 2) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($cpuUsage['load_1'] * 100, 100) }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600">Load 5min</span>
                                        <span>{{ number_format($cpuUsage['load_5'], 2) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($cpuUsage['load_5'] * 100, 100) }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600">Load 15min</span>
                                        <span>{{ number_format($cpuUsage['load_15'], 2) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($cpuUsage['load_15'] * 100, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Memória -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium mb-3">Memória</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total</span>
                                    <span>{{ $memoryInfo['total'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Em uso</span>
                                    <span>{{ $memoryInfo['used'] }} ({{ $memoryInfo['usage_percent'] }}%)</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Livre</span>
                                    <span>{{ $memoryInfo['free'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $memoryInfo['usage_percent'] }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Disco -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium mb-3">Disco</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total</span>
                                    <span>{{ $diskUsage['total'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Em uso</span>
                                    <span>{{ $diskUsage['used'] }} ({{ $diskUsage['usage_percent'] }}%)</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Livre</span>
                                    <span>{{ $diskUsage['free'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $diskUsage['usage_percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Processos -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Processos em Execução</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPU %</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MEM %</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comando</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($processes as $process)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['user'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['pid'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['cpu'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['mem'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['command'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Atualiza as informações a cada 5 segundos
    setInterval(function() {
        location.reload();
    }, 5000);
</script>
@endsection 