@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <h1 class="h3 mb-0 text-gray-800">Novo Cliente</h1>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('cliente.index') }}">Clientes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Novo Cliente</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('cliente.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- Coluna 1 -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone">
                            </div>
                            <div class="mb-3">
                                <label for="logradouro" class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="logradouro" name="logradouro"
                                    autocomplete="off">

                            </div>

                            <div class="mb-3">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" name="numero">
                            </div>

                        </div>

                        <!-- Coluna 2 -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" required>
                            </div>

                            <div class="mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade">
                            </div>

                            <div class="mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro">
                            </div>
                            <div class="mb-3">
                                <label for="complemento" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complemento" name="complemento">
                            </div>
                        </div>

                        <!-- Coluna 3 -->
                        <div class="col-md-4">

                            <div class="mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento">
                            </div>
                            <div class="mb-3">
                                <label for="referencia" class="form-label">Ponto de Referência</label>
                                <input type="text" class="form-control" id="referencia" name="referencia">
                            </div>
                            <div class="mb-3">
                                <label for="observacao" class="form-label">Observação</label>
                                <textarea class="form-control" id="observacao" name="observacao" rows="4"></textarea>
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjtRzX47y95pI2XlmJrsXgka8SHSMLtQw&libraries=places">
    </script>

    <script src="{{ asset('/assets/admin/js/utils.js') }}"></script>
    <script>
        // buscarEnderecoPorCep('#cep', {
        //     logradouro: '#logradouro',
        //     bairro: '#bairro',
        //     cidade: '#cidade'
        // });

        document.addEventListener('DOMContentLoaded', function() {
            initGoogleAutocomplete('#logradouro', {
                logradouro: '#logradouro',
                bairro: '#bairro',
                cidade: '#cidade',
                estado: '#estado',
                cep: '#cep',
                numero: '#numero'
            });
        });
    </script>
@endsection
