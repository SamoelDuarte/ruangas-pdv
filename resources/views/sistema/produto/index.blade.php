@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h1 class="h3 mb-0 text-gray-800">Produtos</h1>
                @can('criar produtos')
                    <a href="{{ route('produto.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-plus text-white-50"></i> Novo Produto
                    </a>
                @endcan
            </div>

            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item active" aria-current="page">Produtos</li>
            </ol>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="table-produto">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Valor</th>
                                        <th>Valor App</th>
                                        <th>Ativo</th>
                                        <th>Aplicativo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($produtos as $produto)
                                        <tr>
                                            <td>{{ $produto->id }}</td>
                                            <td>{{ $produto->nome }}</td>
                                            <td>R$ {{ number_format($produto->valor, 2, ',', '.') }}</td>
                                            <td>
                                                R$ {{ number_format($produto->valor_app, 2, ',', '.') }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $produto->ativo ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $produto->ativo ? 'Sim' : 'Não' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $produto->aplicativo ? 'bg-primary' : 'bg-secondary' }}">
                                                    {{ $produto->aplicativo ? 'Sim' : 'Não' }}
                                                </span>
                                            </td>
                                            <td>
                                                @can('editar produtos')
                                                    <a href="{{ route('produto.edit', $produto->id) }}" class="btn" title="Editar">
                                                        <i class="fa fa-pencil-alt"></i>
                                                    </a>
                                                @endcan

                                                @can('excluir produtos')
                                                    <form action="{{ route('produto.destroy', $produto->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
  

    <script>
        $(document).ready(function() {
            $('#table-produto').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
                }
            });
        });
    </script>
@endsection
