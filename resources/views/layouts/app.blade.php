<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap CSS e JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        /* Estilos personalizados */
        .bg-gray-800 { background-color: #1f2937; }
        .bg-gray-700 { background-color: #374151; }
        .bg-gray-100 { background-color: #f3f4f6; }
        .text-gray-300 { color: #d1d5db; }
        .text-gray-600 { color: #4b5563; }
        .text-white { color: #ffffff; }
        .hover\:bg-gray-700:hover { background-color: #374151; }
        .hover\:text-white:hover { color: #ffffff; }
        .rounded-md { border-radius: 0.375rem; }
        .font-medium { font-weight: 500; }
        .text-sm { font-size: 0.875rem; }
        .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .space-x-4 > * + * { margin-left: 1rem; }
        .min-h-screen { min-height: 100vh; }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @yield('styles')
</head>
<body class="min-h-screen bg-gray-100" x-data="{ mobileMenuOpen: false }">
    <nav class="bg-gray-800">
        <div class="mx-auto px-4 sm:px-6 lg:px-8" style="width: 90%">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="/" class="text-white font-bold text-xl">Sistema de Apoio</a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="/" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Home</a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="{{ route('databases.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Databases</a>
                                <a href="{{ route('codigos-sistemas.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Códigos e Sistemas</a>
                                <a href="{{ route('livros.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Livros</a>
                                <a href="{{ route('arquivos.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Arquivos e Backup</a>
                                <a href="{{ route('estudos.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Estudos e Pesquisas</a>
                                <a href="{{ route('relatorios-situacionais.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Relatórios Situacionais</a>
                                <a href="{{ route('financeiro.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Financeiro</a>
                                <a href="{{ route('diario.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Diário</a>
                            @endauth
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        @guest
                            <a href="{{ route('login') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        @else
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="ml-4">
                                @csrf
                                <button type="submit" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                    Sair
                                </button>
                            </form>
                        @endguest
                    </div>
                </div>
                <!-- Menu mobile -->
                <div class="md:hidden">
                    <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-300 hover:text-white focus:outline-none focus:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Menu mobile content -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="/" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Home</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                    <a href="{{ route('databases.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Databases</a>
                    <a href="{{ route('codigos-sistemas.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Códigos e Sistemas</a>
                    <a href="{{ route('livros.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Livros</a>
                    <a href="{{ route('arquivos.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Arquivos e Backup</a>
                    <a href="{{ route('estudos.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Estudos e Pesquisas</a>
                    <a href="{{ route('relatorios-situacionais.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Relatórios Situacionais</a>
                    <a href="{{ route('financeiro.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Financeiro</a>
                    <a href="{{ route('diario.index') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Diário</a>
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Login</a>
                @else
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-gray-300 hover:bg-gray-700 hover:text-white block w-full text-left px-3 py-2 rounded-md text-base font-medium">
                            Sair
                        </button>
                    </form>
                @endguest
            </div>
        </div>
    </nav>

    <main class="py-4">
        @if(session('success'))
            <div class="container">
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container">
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('logout-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Enviando formulário de logout...');
            this.submit();
        });

        // Configuração do CSRF token para requisições AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        window.WS_URLS = @json($websocket_urls ?? []);
        window.STREAM_UPLOAD_TOKEN = @json($upload_token ?? null);
        window.STREAM_UPLOAD_TOKEN_EXPIRES_AT = @json($token_expires_at ?? null);
        window.STREAM_UPLOAD_MAX_ACTIVE = @json(config('upload.max_active_files', 4));
        window.STREAM_UPLOAD_LIMIT_MESSAGE = 'Limite de uploads simultâneos atingido. Aguarde os envios em andamento terminarem antes de iniciar um novo.';

        // Função para mostrar mensagens de sucesso
        function showSuccess(message) {
            Swal.fire({
                title: 'Sucesso!',
                text: message,
                icon: 'success',
                confirmButtonText: 'OK'
            });
        }

        // Função para mostrar mensagens de erro
        function showError(message) {
            Swal.fire({
                title: 'Erro!',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    </script>
    <script src="{{ asset('js/streaming-uploader.js') }}"></script>
    @yield('scripts')
</body>
</html> 