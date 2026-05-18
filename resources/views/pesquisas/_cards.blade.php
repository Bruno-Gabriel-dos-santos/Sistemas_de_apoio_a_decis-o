<div id="pesquisas-cards-wrapper">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($pesquisas as $pesquisa)
            <a href="{{ route('pesquisas.show', $pesquisa->id) }}" class="block bg-white rounded-lg shadow p-4 hover:bg-blue-50 transition cursor-pointer">
                <img src="{{ asset('storage/' . $pesquisa->capa) }}" alt="Capa" class="w-full h-40 object-cover rounded mb-2">
                <h3 class="text-xl font-bold">{{ $pesquisa->titulo }}</h3>
                <p class="text-gray-600">{{ $pesquisa->descricao }}</p>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-sm text-gray-500">{{ $pesquisa->autor }}</span>
                    <span class="text-sm text-gray-400">{{ \Carbon\Carbon::parse($pesquisa->data)->format('d/m/Y') }}</span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">
        {!! $pesquisas->links() !!}
    </div>
</div> 