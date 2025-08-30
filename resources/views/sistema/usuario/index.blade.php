@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <!-- Page Heading -->
        <div class="page-header-content py-3">

            <div class="d-sm-flex align-items-center justify-content-between">
                <h1 class="h3 mb-0 text-gray-800">Usuários</h1>
                @can('criar usuários')
                    <a href="{{ route('usuario.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-plus text-white-50"></i> Novo Usuário
                    </a>
                @endcan
            </div>

            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usuários</li>
            </ol>

        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Content Row -->
        <div class="row">
            <!-- Content Column -->
            <div class="col-lg-12 mb-4">
                <!-- Project Card Example -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-device">
                            <!-- Tabela de Usuários -->
                            <table class="table table-bordered" id="table-device">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Função</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td>{{ $user->id }}</td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if($user->roles->count() > 0)
                                                    <span class="badge badge-primary">
                                                        {{ ucfirst($user->roles->first()->name) }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">Sem função</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($user->ativo)
                                                    <span class="badge badge-success">Ativo</span>
                                                @else
                                                    <span class="badge badge-danger">Inativo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <!-- Botões de ação com controle de permissões -->
                                                @can('editar usuários')
                                                    <a href="{{ route('usuario.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                                        <i class="fa fa-pencil-alt"></i>
                                                    </a>
                                                @endcan

                                                @can('gerenciar usuários')
                                                    <a href="{{ route('usuario.permissions', $user->id) }}" class="btn btn-sm btn-outline-info" title="Gerenciar Permissões">
                                                        <i class="fa fa-key"></i>
                                                    </a>
                                                @endcan

                                                @can('excluir usuários')
                                                    <form action="{{ route('usuario.destroy', $user->id) }}" method="POST"
                                                        style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
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
        // Código JS adicional, se necessário.
    </script>
@endsection
