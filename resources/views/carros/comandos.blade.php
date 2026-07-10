@extends('sistema.layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-terminal me-2"></i>Central de Comandos TCP</h4>
            <small class="text-muted">Escolha o carro, envie o comando e acompanhe o historico de estado.</small>
        </div>
        <a href="{{ route('carros.rastreamento') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-map-marked-alt me-1"></i> Ir para rastreamento
        </a>
    </div>

    @if (!$commandsConfigured)
        <div class="alert alert-warning">
            Configure <strong>TRACKER_TCP_COMMAND_BLOCK</strong> e <strong>TRACKER_TCP_COMMAND_UNBLOCK</strong> no arquivo .env para usar os comandos padrao.
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white">
            <strong>Novo comando</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('carros.comandos.enviar') }}" id="formComandoTracker">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Carro</label>
                        <select name="carro_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach ($carros as $carro)
                                @php
                                    $placa = $carro->placa ? ' - ' . $carro->placa : '';
                                    $imei = $carro->imei_rastreador ? ' (IMEI ' . $carro->imei_rastreador . ')' : ' (Sem IMEI)';
                                @endphp
                                <option value="{{ $carro->id }}" {{ old('carro_id') == $carro->id ? 'selected' : '' }}>
                                    {{ $carro->nome . $placa . $imei }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo de comando</label>
                        <select name="action" class="form-select" id="actionCommand" required>
                            <option value="block" {{ old('action') === 'block' ? 'selected' : '' }}>Bloquear</option>
                            <option value="unblock" {{ old('action') === 'unblock' ? 'selected' : '' }}>Desbloquear</option>
                            <option value="custom" {{ old('action') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                        </select>
                    </div>

                    <div class="col-md-5" id="customNameWrap" style="display: none;">
                        <label class="form-label">Nome do comando personalizado</label>
                        <input type="text" name="custom_name" class="form-control" maxlength="50" value="{{ old('custom_name') }}" placeholder="Ex.: status_manual" />
                    </div>

                    <div class="col-12" id="customPayloadWrap" style="display: none;">
                        <label class="form-label">Comando bruto (sera enviado no socket)</label>
                        <textarea name="command_payload" class="form-control" rows="3" placeholder="Ex.: +RESP:GTxxx,...$">{{ old('command_payload') }}</textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Enviar comando
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">Limpar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Historico de comandos</strong>
            <small class="text-muted">Ultimos 120 registros</small>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Carro</th>
                        <th>IMEI</th>
                        <th>Comando</th>
                        <th>Payload</th>
                        <th>Status</th>
                        <th>Enviado em</th>
                        <th>Erro/Resposta</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historico as $item)
                        @php
                            $statusClass = 'secondary';
                            if ($item->status === 'pending') {
                                $statusClass = 'warning text-dark';
                            } elseif ($item->status === 'sent') {
                                $statusClass = 'success';
                            } elseif ($item->status === 'failed') {
                                $statusClass = 'danger';
                            }
                        @endphp
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ optional($item->requested_at)->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td>{{ $item->carro?->nome ?? '-' }}{{ $item->carro?->placa ? ' - ' . $item->carro->placa : '' }}</td>
                            <td>{{ $item->imei }}</td>
                            <td>{{ $item->command_name }}</td>
                            <td style="max-width: 320px; white-space: normal; word-break: break-all;">{{ $item->command_payload }}</td>
                            <td><span class="badge bg-{{ $statusClass }}">{{ strtoupper($item->status) }}</span></td>
                            <td>{{ optional($item->sent_at)->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td style="max-width: 320px; white-space: normal; word-break: break-word;">
                                {{ $item->error_message ?: ($item->response_payload ?: '-') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Nenhum comando enviado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleCustomFields() {
        const action = document.getElementById('actionCommand').value;
        const customNameWrap = document.getElementById('customNameWrap');
        const customPayloadWrap = document.getElementById('customPayloadWrap');

        const isCustom = action === 'custom';
        customNameWrap.style.display = isCustom ? 'block' : 'none';
        customPayloadWrap.style.display = isCustom ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const actionSelect = document.getElementById('actionCommand');
        const form = document.getElementById('formComandoTracker');

        toggleCustomFields();
        actionSelect.addEventListener('change', toggleCustomFields);

        form.addEventListener('submit', function (event) {
            const action = actionSelect.value;
            const message = action === 'block'
                ? 'Deseja realmente bloquear este veiculo?'
                : action === 'unblock'
                    ? 'Deseja realmente desbloquear este veiculo?'
                    : 'Deseja realmente enviar este comando personalizado?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
</script>
@endsection
