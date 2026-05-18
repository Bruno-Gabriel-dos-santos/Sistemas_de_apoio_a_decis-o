@extends('layouts.app')

@section('styles')
<style>
    /* Estilos para o layout da página inicial */
    .relative { position: relative; }
    .overflow-hidden { overflow: hidden; }
    .bg-white { background-color: #ffffff; }
    .max-w-7xl { max-width: 80rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }
    .z-10 { z-index: 10; }
    .pb-8 { padding-bottom: 2rem; }
    
    /* Estilos responsivos */
    @media (min-width: 640px) {
        .sm\:pb-16 { padding-bottom: 4rem; }
        .sm\:text-center { text-align: center; }
        .sm\:mt-12 { margin-top: 3rem; }
        .sm\:px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .sm\:text-lg { font-size: 1.125rem; }
        .sm\:mt-5 { margin-top: 1.25rem; }
        .sm\:max-w-xl { max-width: 36rem; }
        .sm\:mx-auto { margin-left: auto; margin-right: auto; }
        .sm\:h-72 { height: 18rem; }
    }
    
    @media (min-width: 768px) {
        .md\:pb-20 { padding-bottom: 5rem; }
        .md\:mt-16 { margin-top: 4rem; }
        .md\:text-xl { font-size: 1.25rem; }
        .md\:py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .md\:text-lg { font-size: 1.125rem; }
        .md\:px-10 { padding-left: 2.5rem; padding-right: 2.5rem; }
        .md\:h-96 { height: 24rem; }
    }
    
    @media (min-width: 1024px) {
        .lg\:max-w-2xl { max-width: 42rem; }
        .lg\:w-full { width: 100%; }
        .lg\:pb-28 { padding-bottom: 7rem; }
        .lg\:mt-20 { margin-top: 5rem; }
        .lg\:px-8 { padding-left: 2rem; padding-right: 2rem; }
        .lg\:mx-0 { margin-left: 0; margin-right: 0; }
        .lg\:text-left { text-align: left; }
        .lg\:absolute { position: absolute; }
        .lg\:inset-y-0 { top: 0; bottom: 0; }
        .lg\:right-0 { right: 0; }
        .lg\:w-1/2 { width: 50%; }
        .lg\:h-full { height: 100%; }
    }
    
    @media (min-width: 1280px) {
        .xl\:pb-32 { padding-bottom: 8rem; }
        .xl\:mt-28 { margin-top: 7rem; }
    }
    
    /* Estilos de texto */
    .text-4xl { font-size: 2.25rem; }
    .tracking-tight { letter-spacing: -0.025em; }
    .font-extrabold { font-weight: 800; }
    .text-gray-900 { color: #111827; }
    .text-indigo-600 { color: #4f46e5; }
    .text-base { font-size: 1rem; }
    .text-gray-500 { color: #6b7280; }
    
    /* Estilos de botões e links */
    .rounded-md { border-radius: 0.375rem; }
    .shadow { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); }
    .border { border-width: 1px; }
    .border-transparent { border-color: transparent; }
    .font-medium { font-weight: 500; }
    .bg-indigo-600 { background-color: #4f46e5; }
    .hover\:bg-indigo-700:hover { background-color: #4338ca; }
    .text-white { color: #ffffff; }
    
    /* Estilos do container de imagem */
    .bg-indigo-100 { background-color: #e0e7ff; }
    .h-56 { height: 14rem; }
    .w-full { width: 100%; }
    .flex { display: flex; }
    .items-center { align-items: center; }
    .justify-center { justify-content: center; }
</style>
@endsection

@section('content')
<div class="relative bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto">
        <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
            <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                <div class="sm:text-center lg:text-left">
                    <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                        <span class="block">Bem-vindo ao</span>
                        <span class="block text-indigo-600">Sistema de Apoio</span>
                    </h1>
                    <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                        Um sistema completo para gerenciamento e suporte às suas necessidades.
                    </p>
                    <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                        @guest
                            <div class="rounded-md shadow">
                                <a href="{{ route('login') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                                    Fazer Login
                                </a>
                            </div>
                        @else
                            <div class="rounded-md shadow">
                                <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                                    Ir para Dashboard
                                </a>
                            </div>
                        @endguest
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
        <div class="h-56 w-full bg-indigo-100 sm:h-72 md:h-96 lg:w-full lg:h-full">
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-1/2 h-1/2 text-indigo-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 100-12 6 6 0 000 12zm0-9a1 1 0 011 1v3a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>
</div>
@endsection
