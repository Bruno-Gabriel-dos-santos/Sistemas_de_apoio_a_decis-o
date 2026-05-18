@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">{{ $pagina->titulo }}</h1>
    <div class="mb-2 text-gray-600">{{ $pagina->descricao }}</div>
    <div class="prose max-w-none mb-4">{!! $pagina->conteudo !!}</div>
    <a href="{{ route('paginas_sistemas.edit', [$sistema->id, $pagina->id]) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Editar</a>
    <a href="{{ route('paginas_sistemas.index', $sistema->id) }}" class="ml-2 text-gray-600">Voltar</a>
</div>
@endsection 