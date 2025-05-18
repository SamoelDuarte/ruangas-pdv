@extends('layouts.app')

@section('content')
    <h1>Lista de Sorteios</h1>

    <!-- Botão para abrir o modal de adicionar sorteio -->
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSorteioModal">Adicionar Sorteio</button>

    @if ($sorteios->count())
        <!-- Tabela com DataTable -->
        <table id="sorteiosTable" class="table table-striped" style="width:100%; margin-top: 20px;">
            <thead>
                <tr>
                    <th>Nome do Sorteio</th>
                    <th>Data de Início</th>
                    <th>Data de Término</th>
                    <th>Número do Sorteio</th>
                    <th>Valor Mínimo</th>
                    <th>Valor Máximo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sorteios as $sorteio)
                    <tr>
                        <td>{{ $sorteio->nome }}</td>
                        <td>{{ $sorteio->data_inicio_formatada }}</td>
                        <td>{{ $sorteio->data_termino_formatada }}</td>
                        <td>{{ $sorteio->numero_sorteio }}</td>
                        <td>{{ $sorteio->numero_min }}</td>
                        <td>{{ $sorteio->numero_max }}</td>
                        <td>
                            @if ($sorteio->clientes->isEmpty())
                                <!-- Botão de Deletar -->
                                <form action="{{ route('sorteio.destroy', $sorteio->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Deletar</button>
                                </form>
                            @else
                                <!-- Se houver clientes associados, não mostra o botão de deletar -->
                                <button class="btn btn-danger" disabled>Deletar</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Não há sorteios registrados.</p>
    @endif

    <!-- Modal para adicionar sorteio -->
    <div class="modal fade" id="addSorteioModal" tabindex="-1" aria-labelledby="addSorteioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSorteioModalLabel">Adicionar Sorteio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSorteioForm" action="{{ route('sorteio.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Sorteio</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="data_inicio" class="form-label">Data de Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                        </div>
                        <div class="mb-3">
                            <label for="data_termino" class="form-label">Data de Término</label>
                            <input type="date" class="form-control" id="data_termino" name="data_termino" required>
                        </div>
                        <div class="mb-3">
                            <label for="numero_sorteio" class="form-label">Número do Sorteio</label>
                            <input type="number" class="form-control" id="numero_sorteio" name="numero_sorteio" required>
                        </div>
                        <div class="mb-3">
                            <label for="numero_min" class="form-label">Número Mínimo </label>
                            <input type="number" class="form-control" id="numero_min" name="numero_min" required>
                        </div>
                        <div class="mb-3">
                            <label for="numero_max" class="form-label">Número Máximo</label>
                            <input type="number" class="form-control" id="numero_max" name="numero_max" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Sorteio</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Adicionando o DataTables -->
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializando o DataTable
            $('#sorteiosTable').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "language": {
                    "search": "Pesquisar:",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ sorteios",
                    "infoEmpty": "Mostrando 0 a 0 de 0 sorteios",
                    "infoFiltered": "(filtrado de _MAX_ sorteios no total)"
                }
            });
        });
    </script>
@endsection
