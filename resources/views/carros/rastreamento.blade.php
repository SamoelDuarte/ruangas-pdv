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

    .tracker-marker-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        transform: translateY(-8px);
    }

    .tracker-marker-plate {
        background: #111;
        color: #fff;
        border-radius: 8px;
        padding: 2px 7px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 3px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        white-space: nowrap;
        max-width: 110px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tracker-marker-car {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        color: #fff;
        font-size: 14px;
    }

    .tracker-sos-row {
        background: rgba(220, 53, 69, 0.12) !important;
        box-shadow: inset 0 0 0 1px rgba(220, 53, 69, 0.18);
    }

    .tracker-sos-banner {
        display: none;
        position: fixed;
        top: 18px;
        right: 20px;
        z-index: 1200;
        max-width: 440px;
        width: calc(100% - 32px);
        background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
        color: #fff;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(185, 28, 28, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.22);
        padding: 12px 16px;
    }

    .tracker-sos-banner.show {
        display: block;
        animation: trackerSosPulse 0.9s ease-in-out infinite alternate;
    }

    @keyframes trackerSosPulse {
        from {
            transform: scale(1);
            box-shadow: 0 12px 30px rgba(185, 28, 28, 0.35);
        }
        to {
            transform: scale(1.01);
            box-shadow: 0 16px 36px rgba(239, 68, 68, 0.45);
        }
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

<div id="alertaSosGlobal" class="tracker-sos-banner" aria-live="assertive">
    <div class="d-flex align-items-start justify-content-between gap-3">
        <div>
            <div class="fw-bold"><i class="fas fa-bell me-2"></i>Alerta SOS ativo</div>
            <div id="alertaSosTexto" class="small mt-1">Veículo em situação de emergência.</div>
        </div>
        <button type="button" class="btn btn-sm btn-light text-danger fw-bold" onclick="desligarAlertaSos('GLOBAL')">
            <i class="fas fa-volume-mute me-1"></i>Desligar alerta
        </button>
    </div>
</div>

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
                            <th>Comando</th>
                            <th>Status</th>
                            <th>Igni</th>
                            <th>Vel. km/h</th>
                            <th>Bateria Veiculo</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Endereco</th>
                            <th>Chegada</th>
                            <th>Saida</th>
                            <th>Permanencia</th>
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

    let somAlertaSosAtivo = false;
    let somAlertaSosInterval = null;
    let somAlertaSosContext = null;
    let somAlertaSosOscilador = null;
    let somAlertaSosGanho = null;
    const STORAGE_SOS_DESLIGADOS = 'tracker_sos_desligados_v1';

    function obterSosDesligados() {
        try {
            const raw = localStorage.getItem(STORAGE_SOS_DESLIGADOS);
            if (!raw) {
                return {};
            }
            return JSON.parse(raw) || {};
        } catch (error) {
            return {};
        }
    }

    function salvarSosDesligados(dados) {
        try {
            localStorage.setItem(STORAGE_SOS_DESLIGADOS, JSON.stringify(dados));
        } catch (error) {
            // Ignora falhas de armazenamento local.
        }
    }

    function isSosDesligadoParaImei(imei, recebidoEm) {
        const chave = String(imei || '').trim();
        if (!chave) {
            return false;
        }

        const dados = obterSosDesligados();
        const item = dados[chave];
        if (!item) {
            return false;
        }

        if (recebidoEm) {
            const novoTs = new Date(recebidoEm).getTime();
            const desligadoTs = new Date(item.desligadoEm || 0).getTime();
            if (!Number.isNaN(novoTs) && !Number.isNaN(desligadoTs) && novoTs > desligadoTs) {
                delete dados[chave];
                salvarSosDesligados(dados);
                return false;
            }
        }

        return true;
    }

    function iniciarSomAlertaSos() {
        if (somAlertaSosAtivo) {
            return;
        }

        const AudioCtor = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtor) {
            return;
        }

        somAlertaSosContext = new AudioCtor();
        somAlertaSosOscilador = somAlertaSosContext.createOscillator();
        somAlertaSosGanho = somAlertaSosContext.createGain();

        somAlertaSosOscilador.type = 'sawtooth';
        somAlertaSosOscilador.frequency.value = 980;
        somAlertaSosGanho.gain.value = 0.0001;

        somAlertaSosOscilador.connect(somAlertaSosGanho);
        somAlertaSosGanho.connect(somAlertaSosContext.destination);
        somAlertaSosOscilador.start();

        let toneOn = true;
        somAlertaSosInterval = setInterval(() => {
            if (!somAlertaSosContext || !somAlertaSosOscilador || !somAlertaSosGanho) {
                return;
            }

            if (somAlertaSosContext.state === 'suspended') {
                somAlertaSosContext.resume();
            }

            toneOn = !toneOn;
            const volume = toneOn ? 0.03 : 0.0001;
            const freq = toneOn ? 980 : 760;
            somAlertaSosOscilador.frequency.setValueAtTime(freq, somAlertaSosContext.currentTime);
            somAlertaSosGanho.gain.setTargetAtTime(volume, somAlertaSosContext.currentTime, 0.08);
        }, 700);

        somAlertaSosAtivo = true;
    }

    function pararSomAlertaSos() {
        if (somAlertaSosInterval) {
            clearInterval(somAlertaSosInterval);
            somAlertaSosInterval = null;
        }

        if (somAlertaSosOscilador) {
            try {
                somAlertaSosOscilador.stop();
            } catch (error) {
                // Ignora caso o oscilador já tenha sido parado.
            }
            somAlertaSosOscilador = null;
        }

        if (somAlertaSosContext) {
            try {
                somAlertaSosContext.close();
            } catch (error) {
                // Ignora casos de contexto já encerrado.
            }
            somAlertaSosContext = null;
        }

        somAlertaSosGanho = null;
        somAlertaSosAtivo = false;
    }

    function desligarAlertaSos(imei) {
        const chave = String(imei || '').trim();
        if (!chave || chave === 'GLOBAL') {
            const dados = obterSosDesligados();
            const ativos = document.querySelectorAll('.tracker-sos-row');
            ativos.forEach((linha) => {
                const imeiLinha = linha.dataset.imei || '';
                if (imeiLinha) {
                    dados[String(imeiLinha)] = { desligadoEm: new Date().toISOString() };
                }
            });
            salvarSosDesligados(dados);
            pararSomAlertaSos();
            const banner = document.getElementById('alertaSosGlobal');
            if (banner) {
                banner.classList.remove('show');
            }
            return;
        }

        const dados = obterSosDesligados();
        dados[chave] = {
            desligadoEm: new Date().toISOString(),
        };
        salvarSosDesligados(dados);
        pararSomAlertaSos();
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
        if (status === 'Alerta SOS') {
            return 'badge bg-danger';
        }
        return 'badge bg-dark';
    }

    function corDoMarcador(row) {
        const permanencia = parseInt(row.permanencia_segundos || 0, 10);

        if (row.sos_ativo) {
            return '#d72638';
        }

        if (row.status === 'Em movimento') {
            return '#6f2cff'; // roxo
        }

        // Qualquer veiculo parado por 10+ min deve ficar vermelho.
        if (permanencia >= 600) {
            return '#b00020'; // vermelho forte
        }

        if (row.status === 'Parado ign desligado') {
            return '#ff6b6b'; // vermelho fraco
        }

        if (row.status === 'Parado ign ligado' || row.status === 'Parado') {
            return '#111111'; // preto
        }

        return '#111111';
    }

    function textoPlacaMarker(row) {
        if (row.placa && row.placa.trim() !== '') {
            return row.placa.trim();
        }

        if (row.imei && row.imei.length >= 4) {
            return `IMEI ${row.imei.slice(-4)}`;
        }

        return 'SEM PLACA';
    }

    function construirIconeCarro(row) {
        const cor = corDoMarcador(row);
        const placa = textoPlacaMarker(row)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const html = `
            <div class="tracker-marker-wrap">
                <div class="tracker-marker-plate">${placa}</div>
                <div class="tracker-marker-car" style="background:${cor};">
                    <i class="fas fa-car-side"></i>
                </div>
            </div>
        `;

        return L.divIcon({
            className: 'tracker-car-icon',
            html,
            iconSize: [90, 48],
            iconAnchor: [45, 42],
            popupAnchor: [0, -34],
        });
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

            const icone = construirIconeCarro(row);

            if (marcadores[imei]) {
                marcadores[imei].setLatLng([lat, lng]);
                marcadores[imei].setPopupContent(popup);
                marcadores[imei].setIcon(icone);
            } else {
                marcadores[imei] = L.marker([lat, lng], { icon: icone }).addTo(mapa).bindPopup(popup);
            }

            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            const groupBounds = L.latLngBounds(bounds);
            mapa.fitBounds(groupBounds.pad(0.2));
        }
    }

    function renderAlertaSosGlobal(rows) {
        const banner = document.getElementById('alertaSosGlobal');
        const texto = document.getElementById('alertaSosTexto');
        if (!banner || !texto) {
            return;
        }

        const ativos = rows.filter((row) => {
            const imei = String(row.imei || '');
            return !!row.sos_ativo && !isSosDesligadoParaImei(imei, row.recebido_em);
        });

        if (!ativos.length) {
            banner.classList.remove('show');
            texto.textContent = 'Veículo em situação de emergência.';
            return;
        }

        const nomes = ativos
            .map((row) => row.placa || row.nome || `IMEI ${String(row.imei || '').slice(-4)}`)
            .filter(Boolean);

        texto.textContent = nomes.length ? `Veículos em alerta: ${nomes.join(', ')}` : 'Veículo em situação de emergência.';
        banner.classList.add('show');
    }

    function renderTabela(rows) {
        const tbody = document.getElementById('tbodyRastreamento');

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="15" class="text-center py-4 text-muted">Sem dados de rastreamento ainda.</td></tr>';
            return;
        }

        let html = '';

        rows.forEach((row) => {
            const bloqueado = row.tracker_bloqueado === true;
            const comandoPendente = row.tracker_comando_status === 'pending';
            const comandosHabilitados = row.tracker_comandos_habilitados === true;
            const temCarro = row.carro_id != null;
            const temImei = !!row.imei;
            const acao = bloqueado ? 'unblock' : 'block';
            const rotulo = bloqueado ? 'Desbloquear' : 'Bloquear';
            const pergunta = bloqueado ? 'Deseja realmente desbloquear?' : 'Deseja realmente bloquear?';
            const imeiNormalizado = row.imei ? String(row.imei) : '';
            const sosAtivo = !!row.sos_ativo && !isSosDesligadoParaImei(imeiNormalizado, row.recebido_em);

            let botaoComando = '<span class="text-muted small">Indisponivel</span>';

            if (!comandosHabilitados) {
                botaoComando = '<span class="text-muted small">Configurar comando</span>';
            } else if (!temCarro || !temImei) {
                botaoComando = '<span class="text-muted small">Sem IMEI</span>';
            } else {
                const classeBotao = bloqueado ? 'btn-outline-success' : 'btn-outline-danger';
                const disabled = comandoPendente ? 'disabled' : '';
                const legenda = comandoPendente ? 'Enviando...' : rotulo;
                botaoComando = `
                    <div class="d-grid gap-1">
                        <button type="button" class="btn btn-sm ${classeBotao}" ${disabled}
                            onclick="enviarComandoBloqueio(${row.carro_id}, '${acao}', '${pergunta}')">
                            ${legenda}
                        </button>
                        <small class="text-muted">${row.tracker_comando_status || '-'}</small>
                    </div>
                `;
            }

            const statusMarkup = sosAtivo
                ? `<div class="d-flex flex-column gap-1">
                    <span class="${classeStatus('Alerta SOS')}">Alerta SOS</span>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="desligarAlertaSos('${imeiNormalizado}')">Desligar alerta</button>
                  </div>`
                : `<span class="${classeStatus(row.status)}">${row.status}</span>`;

            html += `
                <tr class="${sosAtivo ? 'tracker-sos-row' : ''}" data-imei="${imeiNormalizado}">
                    <td>${row.placa || '-'}</td>
                    <td>${row.modelo || row.nome || '-'}</td>
                    <td>${row.imei || '-'}</td>
                    <td>${botaoComando}</td>
                    <td>${statusMarkup}</td>
                    <td>${textoIgnicao(row.ignicao)}</td>
                    <td>${row.velocidade ?? '-'}</td>
                    <td>${row.tensao_veiculo != null ? `${Number(row.tensao_veiculo).toFixed(2)} V` : '-'}</td>
                    <td>${row.latitude ?? '-'}</td>
                    <td>${row.longitude ?? '-'}</td>
                    <td style="min-width: 280px;">${row.endereco || '-'}</td>
                    <td>${formatarData(row.chegada_endereco)}</td>
                    <td>${formatarData(row.saida_endereco)}</td>
                    <td>${formatarDuracao(row.permanencia_segundos)}</td>
                    <td>${formatarData(row.recebido_em)}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    async function enviarComandoBloqueio(carroId, action, confirmationText) {
        if (!window.confirm(confirmationText)) {
            return;
        }

        try {
            const response = await fetch(`/carros/${carroId}/rastreamento/bloqueio`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ action }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Falha ao enviar comando');
            }

            showToast('success', data.message || 'Comando enviado');
            await carregarRastreamento(false);
        } catch (error) {
            showToast('error', error.message || 'Falha ao enviar comando');
            console.error(error);
        }
    }

    async function carregarRastreamento(manual = false) {
        try {
            const response = await fetch('{{ route('carros.rastreamento.dados') }}');
            const data = await response.json();
            const rows = Array.isArray(data.rows) ? data.rows : [];
            const temAlertaSosAtivo = rows.some((row) => {
                const imei = String(row.imei || '');
                return !!row.sos_ativo && !isSosDesligadoParaImei(imei, row.recebido_em);
            });

            renderTabela(rows);
            renderAlertaSosGlobal(rows);
            atualizarMarcadores(rows);
            document.getElementById('ultimaAtualizacao').textContent = `Atualizado: ${formatarData(data.updated_at)}`;

            if (temAlertaSosAtivo) {
                iniciarSomAlertaSos();
            } else {
                pararSomAlertaSos();
            }

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
