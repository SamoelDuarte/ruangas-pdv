@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <!-- Page Heading -->
        <div class="page-header-content py-3">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h1 class="h3 mb-0 text-gray-800">Entregadores</h1>
                @can('criar entregadores')
                    <a href="{{ route('entregador.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-plus text-white-50"></i> Novo Entregador
                    </a>
                @endcan
            </div>

            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item active" aria-current="page">Entregadores</li>
            </ol>
        </div>

        <!-- Content Row -->
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="table-entregadores">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                        <th>Trabalhando</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($entregadores as $entregador)
                                        <tr>
                                            <td>{{ $entregador->id }}</td>
                                            <td>{{ $entregador->nome }}</td>
                                            <td>{{ $entregador->email }}</td>
                                            <td>{{ $entregador->telefone }}</td>
                                            <td>
                                                <span class="badge {{ $entregador->ativo ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $entregador->ativo ? 'Ativo' : 'Inativo' }}
                                                </span>
                                            </td>
                                            <td>
                                                <input type="checkbox" class="checkbox-trabalhando"
                                                    data-id="{{ $entregador->id }}"
                                                    {{ $entregador->trabalhando ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                @can('editar entregadores')
                                                    <a href="{{ route('entregador.edit', $entregador->id) }}" class="btn"
                                                        title="Editar">
                                                        <i class="fa fa-pencil-alt"></i>
                                                    </a>
                                                @endcan

                                                @can('excluir entregadores')
                                                    <form action="{{ route('entregador.delete') }}" method="POST"
                                                        style="display:inline;">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $entregador->id }}">
                                                        <button type="submit" class="btn" title="Deletar">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="text-end mt-3">
                                <button id="btn-salvar-trabalhando" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Salvar Alterações
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnSalvar = document.getElementById('btn-salvar-trabalhando');

        if (btnSalvar) {
            btnSalvar.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.checkbox-trabalhando');
                const data = {};

                checkboxes.forEach(checkbox => {
                    const id = checkbox.dataset.id;
                    data[id] = checkbox.checked ? 1 : 0;
                });

                fetch("{{ route('entregador.salvarTrabalhando') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            trabalhando: data
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showToast('success', 'Status de trabalho atualizado com sucesso!');
                        } else {
                            showToast('error', result.message || 'Erro ao salvar.');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro na requisição.');
                    });
            });
        }
    });
</script>
