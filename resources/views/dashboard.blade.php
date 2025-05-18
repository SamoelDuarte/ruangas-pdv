@extends('layouts.app')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<style>
    /* Efeito de hover no card */
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
    }

    /* Gradiente para o header */
    .bg-gradient-primary {
        background: linear-gradient(45deg, #007bff, #0056b3);
    }

    /* Animações */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

@section('content')
<div class="container">
    <h1 class="text-center my-4 display-4 fw-bold text-primary animate__animated animate__fadeInDown">
        🎉 Sorteios Atuais 🎉
    </h1>

    <!-- Input para buscar o ganhador -->
    <div class="row my-4">
        <div class="col-md-6">
            <label for="sorteioSelect" class="form-label">Escolha o Sorteio</label>
            <select id="sorteioSelect" class="form-control">
                <option value="">Selecione um Sorteio</option>
                @foreach ($dados as $sorteio)
                    <option value="{{ $sorteio['sorteio_id'] }}">{{ $sorteio['nome'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="numeroInput" class="form-label">Digite o Número</label>
            <input type="text" id="numeroInput" class="form-control" placeholder="Número do Ganhador">
        </div>
        <div class="col-12 text-center my-3">
            <button id="buscarGanhador" class="btn btn-primary">Buscar Ganhador</button>
        </div>
    </div>

    <!-- Resultado da Busca -->
    <div id="resultado" class="text-center"></div>

    <div class="row">
        @foreach ($dados as $sorteio)
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card shadow-sm mb-4 hover-card animate__animated animate__fadeInUp animate__delay-{{ $loop->index }}00ms">
                    <div class="card-header bg-gradient-primary text-white text-center py-3">
                        <h5 class="card-title mb-0">{{ $sorteio['nome'] }}</h5>
                    </div>
                    <div class="card-body bg-light">
                        <div class="d-flex flex-column align-items-center">
                            <p class="text-muted"><strong>N°:</strong> {{ $sorteio['numero_sorteio'] }}</p>
                            <p class="text-muted"><i class="fas fa-calendar-alt"></i> <strong>Data de Início:</strong> {{ $sorteio['data_inicio'] }}</p>
                            <p class="text-muted"><i class="fas fa-calendar-check"></i> <strong>Data de Término:</strong> {{ $sorteio['data_termino'] }}</p>
                            <p class="text-info"><i class="fas fa-user-friends"></i> <strong>Total de Inscritos:</strong> {{ $sorteio['total_clientes'] }}</p>
                            <p class="text-success"><i class="fas fa-star"></i> <strong>Total de Participantes:</strong> {{ $sorteio['total_participantes'] }}</p>
                        </div>
                    </div>
                    <div class="card-footer text-center bg-white">
                        <button class="btn btn-primary btn-sm px-4 py-2 rounded-pill shadow-sm">
                            <i class="fas fa-info-circle"></i> Mais Detalhes
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#buscarGanhador').click(function() {
            var sorteio = $('#sorteioSelect').val();
            var numero = $('#numeroInput').val();

            if (sorteio && numero) {
                $.ajax({
                    url: '/buscar-ganhador', // A rota que será chamada
                    method: 'GET',
                    data: {
                        sorteio: sorteio,
                        numero: numero
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#resultado').html('<div class="alert alert-success">O ganhador é: ' + response.cliente.telefone + '</div>');
                        } else {
                            $('#resultado').html('<div class="alert alert-danger">Número não encontrado ou inválido!</div>');
                        }
                    },
                    error: function() {
                        $('#resultado').html('<div class="alert alert-danger">Erro ao buscar o ganhador. Tente novamente.</div>');
                    }
                });
            } else {
                $('#resultado').html('<div class="alert alert-warning">Por favor, preencha todos os campos!</div>');
            }
        });
    });
</script>
@endsection
