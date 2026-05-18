@extends('layouts.app')

@section('content')
<div class="container mx-auto p-8">
    <div class="bg-yellow-50 border border-yellow-200 rounded p-6">
        <h2 class="text-xl font-semibold mb-4">Adminer não encontrado</h2>
        <p>O arquivo <code>public/adminer.php</code> não foi encontrado no servidor.</p>
        <p class="mt-2">Para habilitar a interface do Adminer, faça o download do arquivo Adminer (ex: <code>adminer-4.x.php</code>) e coloque-o em <code>public/adminer.php</code>.</p>
        <p class="mt-4">Depois disso recarregue esta aba e a interface será exibida dentro do painel.</p>
    </div>
</div>
@endsection
