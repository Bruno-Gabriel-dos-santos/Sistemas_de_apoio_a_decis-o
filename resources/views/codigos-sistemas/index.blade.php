@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800 mb-8">Estruturação de Codigos e Sistemas Especificos e de Gerenciamento</h2>
                
                <!-- Grid de Cards -->
                <div class="grid gap-6">
                    <!-- Card Códigos -->
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2">Códigos</h3>
                                <p class="text-blue-100">Armazenamento de codigos e projetos em diferentes linguagens, biblioteca de funções e funcionadlidades.</p>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="{{ route('codigos.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition-colors duration-200">
                                    Explorar
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card Sistemas -->
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2">Sistemas</h3>
                                <p class="text-purple-100">Acesse sistemas Especificos e Gerais, Acesso a integração de sistemas e APIS, Funcionadlidades do sistema e implementações.</p>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="{{ route('sistemas.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-blue-600 rounded-lg font-semibold hover:bg-purple-50 transition-colors duration-200">
                                    Explorar
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card Terminal -->
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2">Terminal</h3>
                                <p class="text-gray-300">Acesse ferramentas de linha de comando, scripts e utilitários de terminal.</p>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="{{ route('terminal.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-blue-600 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200">
                                    Explorar
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 