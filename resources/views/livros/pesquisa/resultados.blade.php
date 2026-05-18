@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Resultados da Pesquisa</h2>
            @if($query)
                <p class="text-muted">Resultados para: "{{ $query }}"</p>
            @endif
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('livros.index') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Pesquisa
            </a>
        </div>
    </div>

    @if($livros->isEmpty())
        <div class="alert alert-info">
            Nenhum livro encontrado para os critérios de pesquisa.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Categoria</th>
                        <th>Data de Publicação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($livros as $livro)
                        <tr>
                            <td>{{ $livro->titulo }}</td>
                            <td>{{ $livro->autor }}</td>
                            <td>{{ $livro->categoria }}</td>
                            <td>{{ $livro->data_publicacao->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('livros.download', $livro->id) }}" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="Baixar PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger delete-book" 
                                            data-id="{{ $livro->id }}"
                                            title="Excluir Livro">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $livros->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.querySelectorAll('.delete-book').forEach(button => {
    button.addEventListener('click', function() {
        const bookId = this.dataset.id;
        
        if (confirm('Tem certeza que deseja excluir este livro?')) {
            fetch(`/livros/${bookId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('tr').remove();
                    if (document.querySelectorAll('tbody tr').length === 0) {
                        location.reload();
                    }
                } else {
                    alert('Erro ao excluir o livro. Por favor, tente novamente.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir o livro. Por favor, tente novamente.');
            });
        }
    });
});
</script>
@endpush
@endsection 