@extends('sistema.layouts.app')

@section('content')
<style>
    .rastreamento-page {
        min-height: calc(100vh - 140px);
    }

    #mapaRastreamento {
        height: calc(100vh - 260px);
        min-height: 420px;
    }

    .tracker-bottom-panel {
        position: fixed;
        left: 16px;
        right: 16px;
        bottom: 0;
        z-index: 1050;
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.18);
        overflow: hidden;
    }

    .tracker-bottom-panel.minimized .tracker-panel-body {
        display: none;
    }

    .tracker-bottom-panel.minimized .tracker-drag-handle {
        opacity: 0;
        pointer-events: none;
    }

    .tracker-panel-header {
        background: #111827;
        color: #fff;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        user-select: none;
    }

    .tracker-panel-body {
        max-height: calc(100vh - 230px);
        overflow: auto;
        background: #fff;
    }

    .tracker-drag-handle {
        height: 10px;
        cursor: ns-resize;
        background: linear-gradient(to bottom, #cfd4da 0, #cfd4da 2px, transparent 2px, transparent 100%);
    }

    .tracker-panel-toggle {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
    }

    .tracker-panel-toggle:focus {
        outline: none;
    }

    @media (max-width: 991px) {
        .tracker-bottom-panel {
            left: 8px;
            right: 8px;
        }

        #mapaRastreamento {
            height: calc(100vh - 230px);
            min-height: 340px;
        }
    }
</style>

<div class="container-fluid py-3 rastreamento-page">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1"><i class="fas fa-map-marked-alt me-2"></i>Rastreamento de Veiculos</h4>
                <small class="text-muted">Mapa em tempo real com ultima telemetria, endereco e permanencia por local.</small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-dark" id="ultimaAtualizacao">Atualizando...</span>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="carregarRastreamento(true)">
                    <i class="fas fa-sync"></i> Atualizar
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-0">
            <div id="mapaRastreamento"></div>
        </div>
    </div>
</div>

<div id="painelVeiculos" class="tracker-bottom-panel minimized" aria-expanded="false">
    <div id="dragHandle" class="tracker-drag-handle" title="Arraste para redimensionar"></div>
    <div id="headerPainelVeiculos" class="tracker-panel-header" role="button" tabindex="0" aria-controls="corpoPainelVeiculos">
        <span><i class="fas fa-truck me-2"></i>Veiculos</span>
        <div class="d-flex align-items-center gap-2">
            <span class="small d-none d-md-inline">Status: Em movimento | Parado ign ligado | Parado ign desligado</span>
            <button type="button" id="btnTogglePainel" class="tracker-panel-toggle" aria-label="Expandir ou minimizar painel">
                <i id="iconePainel" class="fas fa-chevron-up"></i>
                <span id="textoPainel">Expandir</span>
            </button>
        </div>
    </div>
    <div id="corpoPainelVeiculos" class="tracker-panel-body">
        <div class="card border-0 rounded-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="tabelaRastreamento">
                    <thead class="table-light">
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>IMEI</th>
                            <th>Status</th>
                            <th>Igni</th>
                            <th>Vel. km/h</th>
                            <th>Alt.</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Endereco</th>
                            <th>Chegada</th>
                            <th>Saida</th>
                            <th>Permanencia</th>
                            <th>Pacote</th>
                            <th>Ultimo Ping</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRastreamento">
                        <tr>
                            <td colspan="15" class="text-center py-4 text-muted">Carregando dados de rastreamento...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let mapa;
    let marcadores = {};
    let painelExpandido = false;
    let painelAltura = 320;
    let arrastando = false;
    let alturaInicial = 0;
    let mouseYInicial = 0;

    function iniciarMapa() {
        mapa = L.map('mapaRastreamento', {
            zoomControl: true,
            minZoom: 3,
        }).setView([-23.5505, -46.6333], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(mapa);

        setTimeout(() => mapa.invalidateSize(), 120);
    }

    function ajustarEspacoPagina() {
        const painel = document.getElementById('painelVeiculos');
        const espaco = painelExpandido ? painel.offsetHeight : 56;
        document.body.style.paddingBottom = `${espaco}px`;
        if (mapa) {
            setTimeout(() => mapa.invalidateSize(), 120);
        }
    }

    function atualizarEstadoPainel() {
        const painel = document.getElementById('painelVeiculos');
        const textoPainel = document.getElementById('textoPainel');
        const iconePainel = document.getElementById('iconePainel');

        if (painelExpandido) {
            painel.classList.remove('minimized');
            painel.style.height = `${painelAltura}px`;
            painel.setAttribute('aria-expanded', 'true');
            textoPainel.textContent = 'Minimizar';
            iconePainel.className = 'fas fa-chevron-down';
        } else {
            painel.classList.add('minimized');
            painel.style.height = '56px';
            painel.setAttribute('aria-expanded', 'false');
            textoPainel.textContent = 'Expandir';
            iconePainel.className = 'fas fa-chevron-up';
        }

        ajustarEspacoPagina();
    }

    function alternarPainel() {
        painelExpandido = !painelExpandido;
        atualizarEstadoPainel();
    }

    function configurarPainelArrastavel() {
        const painel = document.getElementById('painelVeiculos');
        const header = document.getElementById('headerPainelVeiculos');
        const toggle = document.getElementById('btnTogglePainel');
        const dragHandle = document.getElementById('dragHandle');

        header.addEventListener('click', function (event) {
            if (event.target.closest('#btnTogglePainel')) {
                return;
            }
            alternarPainel();
        });

        header.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                alternarPainel();
            }
        });

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            alternarPainel();
        });

        dragHandle.addEventListener('mousedown', function (event) {
            event.preventDefault();
            if (!painelExpandido) {
                painelExpandido = true;
                atualizarEstadoPainel();
            }

            arrastando = true;
            alturaInicial = painel.offsetHeight;
            mouseYInicial = event.clientY;
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', function (event) {
            if (!arrastando) {
                return;
            }

            const delta = mouseYInicial - event.clientY;
            const minAltura = 160;
            const maxAltura = Math.max(220, window.innerHeight - 160);

            painelAltura = Math.max(minAltura, Math.min(maxAltura, alturaInicial + delta));
            painel.style.height = `${painelAltura}px`;
            ajustarEspacoPagina();
        });

        document.addEventListener('mouseup', function () {
            if (!arrastando) {
                return;
            }
            arrastando = false;
            document.body.style.userSelect = '';
        });

        window.addEventListener('resize', function () {
            if (painelExpandido) {
                const maxAltura = Math.max(220, window.innerHeight - 160);
                painelAltura = Math.min(painelAltura, maxAltura);
            }
            atualizarEstadoPainel();
        });

        atualizarEstadoPainel();
    }

    function formatarData(dataHora) {
        if (!dataHora) {
            return '-';
        }
        const data = new Date(dataHora.replace(' ', 'T'));
        if (Number.isNaN(data.getTime())) {
            return dataHora;
        }
        return data.toLocaleString('pt-BR');
    }

    function formatarDuracao(segundos) {
        const total = parseInt(segundos || 0, 10);
        const h = Math.floor(total / 3600);
        const m = Math.floor((total % 3600) / 60);
        const s = total % 60;
        return `${h}h ${m}m ${s}s`;
    }

    function textoIgnicao(valor) {
        if (valor === true || valor === 1) {
            return 'Ligada';
        }
        if (valor === false || valor === 0) {
            return 'Desligada';
        }
        return '-';
    }

    function classeStatus(status) {
        if (status === 'Em movimento') {
            return 'badge bg-success';
        }
        if (status === 'Parado ign ligado') {
            return 'badge bg-warning text-dark';
        }
        if (status === 'Parado ign desligado') {
            return 'badge bg-secondary';
        }
        return 'badge bg-dark';
    }

    function atualizarMarcadores(rows) {
        const bounds = [];

        rows.forEach((row) => {
            const lat = parseFloat(row.latitude);
            const lng = parseFloat(row.longitude);
            const imei = row.imei || `carro-${row.carro_id || Math.random()}`;

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            const popup = `
                <strong>${row.placa || row.nome || 'Sem identificacao'}</strong><br>
                Modelo: ${row.modelo || '-'}<br>
                Status: ${row.status}<br>
                Velocidade: ${row.velocidade ?? '-'} km/h<br>
                Endereco: ${row.endereco || 'Endereco ainda nao resolvido'}<br>
                GPS: ${formatarData(row.gps_em)}
            `;

            if (marcadores[imei]) {
                marcadores[imei].setLatLng([lat, lng]);
                marcadores[imei].setPopupContent(popup);
            } else {
                marcadores[imei] = L.marker([lat, lng]).addTo(mapa).bindPopup(popup);
            }

            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            const groupBounds = L.latLngBounds(bounds);
            mapa.fitBounds(groupBounds.pad(0.2));
        }
    }

    function renderTabela(rows) {
        const tbody = document.getElementById('tbodyRastreamento');

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="15" class="text-center py-4 text-muted">Sem dados de rastreamento ainda.</td></tr>';
            return;
        }

        let html = '';

        rows.forEach((row) => {
            html += `
                <tr>
                    <td>${row.placa || '-'}</td>
                    <td>${row.modelo || row.nome || '-'}</td>
                    <td>${row.imei || '-'}</td>
                    <td><span class="${classeStatus(row.status)}">${row.status}</span></td>
                    <td>${textoIgnicao(row.ignicao)}</td>
                    <td>${row.velocidade ?? '-'}</td>
                    <td>${row.altitude ?? '-'}</td>
                    <td>${row.latitude ?? '-'}</td>
                    <td>${row.longitude ?? '-'}</td>
                    <td style="min-width: 280px;">${row.endereco || '-'}</td>
                    <td>${formatarData(row.chegada_endereco)}</td>
                    <td>${formatarData(row.saida_endereco)}</td>
                    <td>${formatarDuracao(row.permanencia_segundos)}</td>
                    <td>${row.tipo_pacote || '-'}</td>
                    <td>${formatarData(row.recebido_em)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    async function carregarRastreamento(manual = false) {
        try {
            const response = await fetch('{{ route('carros.rastreamento.dados') }}');
            const data = await response.json();
            const rows = Array.isArray(data.rows) ? data.rows : [];

            renderTabela(rows);
            atualizarMarcadores(rows);
            document.getElementById('ultimaAtualizacao').textContent = `Atualizado: ${formatarData(data.updated_at)}`;

            if (manual) {
                showToast('success', 'Dados de rastreamento atualizados');
            }
        } catch (error) {
            document.getElementById('ultimaAtualizacao').textContent = 'Falha na atualizacao';
            showToast('error', 'Nao foi possivel carregar os dados de rastreamento');
            console.error(error);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        iniciarMapa();
        configurarPainelArrastavel();
        carregarRastreamento(false);
        setInterval(() => carregarRastreamento(false), 15000);
    });
</script>
@endsection
