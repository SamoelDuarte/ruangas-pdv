@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <h1 class="h3 mb-0 text-gray-800">Novo Entregador</h1>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('entregador.index') }}">Entregadores</a></li>
                <li class="breadcrumb-item active" aria-current="page">Novo Entregador</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('entregador.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- Coluna 1 -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone">
                            </div>
                        </div>

                        <!-- Coluna 2 -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" checked>
                                <label class="form-check-label" for="ativo">
                                    Ativo
                                </label>
                            </div>
                        </div>
                       
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Entregador</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')

    <script src="{{ asset('/assets/admin/js/utils.js') }}"></script>
    
@endsection