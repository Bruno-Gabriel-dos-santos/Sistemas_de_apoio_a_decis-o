@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Cards de Navegação -->
        <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8">
            <!-- Card Monitor -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                <a href="{{ route('monitor.index') }}" class="block">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">Monitor</h2>
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-gray-600">Monitore recursos do sistema, processos em execução e desempenho em tempo real.</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Informações do Sistema -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Informações do Sistema</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600">Sistema Operacional</div>
                        <div class="font-medium">{{ $systemInfo['os'] }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600">Kernel</div>
                        <div class="font-medium">{{ $systemInfo['kernel'] }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600">Hostname</div>
                        <div class="font-medium">{{ $systemInfo['hostname'] }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600">Uptime</div>
                        <div class="font-medium">{{ $systemInfo['uptime'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uso de Recursos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- CPU -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">CPU</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Load 1min</span>
                                <span class="font-medium">{{ number_format($cpuUsage['load_1'], 2) }}</span>
                            </div>
                            <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($cpuUsage['load_1'] * 100, 100) }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Load 5min</span>
                                <span class="font-medium">{{ number_format($cpuUsage['load_5'], 2) }}</span>
                            </div>
                            <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($cpuUsage['load_5'] * 100, 100) }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Load 15min</span>
                                <span class="font-medium">{{ number_format($cpuUsage['load_15'], 2) }}</span>
                            </div>
                            <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($cpuUsage['load_15'] * 100, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Memória -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Memória</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total</span>
                            <span class="font-medium">{{ $memoryInfo['total'] }} GB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Usado</span>
                            <span class="font-medium">{{ $memoryInfo['used'] }} GB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Livre</span>
                            <span class="font-medium">{{ $memoryInfo['free'] }} GB</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $memoryInfo['usage_percent'] }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 text-right">{{ $memoryInfo['usage_percent'] }}% em uso</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disco -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Disco (/)</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total</span>
                            <span class="font-medium">{{ $diskUsage['total'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Usado</span>
                            <span class="font-medium">{{ $diskUsage['used'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Livre</span>
                            <span class="font-medium">{{ $diskUsage['free'] }}</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $diskUsage['usage_percent'] }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-gray-600 text-right">{{ $diskUsage['usage_percent'] }}% em uso</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Processos em Execução -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Processos em Execução</h3>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['cpu'] }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $process['mem'] }}%</td>
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

<script>
    // Atualizar informações a cada 5 segundos
    setInterval(function() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                document.querySelector('.py-12').innerHTML = doc.querySelector('.py-12').innerHTML;
            });
    }, 5000);
</script>
@endsection 