<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Models\TrackerAddressStay;
use App\Models\TrackerCommand;
use App\Models\TrackerPing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:gerenciar carros')->only([
            'index',
            'store',
            'update',
            'destroy',
            'buscar',
            'listar',
            'comandos',
            'enviarComando',
            'rastreamento',
            'dadosRastreamento',
            'alternarBloqueioRastreador',
        ]);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carros = Carro::latest()->get();
        return view('carros.index', compact('carros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'placa' => 'nullable|string|max:20',
            'modelo' => 'nullable|string|max:255',
            'imei_rastreador' => 'nullable|string|max:32|unique:carros,imei_rastreador',
        ]);

        Carro::create([
            'nome' => trim((string) $request->nome),
            'placa' => $request->filled('placa') ? Str::upper(trim((string) $request->placa)) : null,
            'modelo' => $request->filled('modelo') ? trim((string) $request->modelo) : null,
            'imei_rastreador' => $request->filled('imei_rastreador') ? preg_replace('/\D+/', '', (string) $request->imei_rastreador) : null,
        ]);

        return redirect()->route('carros.index')->with('success', 'Carro criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Carro $carro)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'placa' => 'nullable|string|max:20',
            'modelo' => 'nullable|string|max:255',
            'imei_rastreador' => 'nullable|string|max:32|unique:carros,imei_rastreador,' . $carro->id,
        ]);

        $carro->update([
            'nome' => trim((string) $request->nome),
            'placa' => $request->filled('placa') ? Str::upper(trim((string) $request->placa)) : null,
            'modelo' => $request->filled('modelo') ? trim((string) $request->modelo) : null,
            'imei_rastreador' => $request->filled('imei_rastreador') ? preg_replace('/\D+/', '', (string) $request->imei_rastreador) : null,
        ]);

        return redirect()->route('carros.index')->with('success', 'Carro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Carro $carro)
    {
        $carro->delete();
        return redirect()->route('carros.index')->with('success', 'Carro deletado com sucesso!');
    }

    /**
     * Buscar carros de forma assíncrona
     */
    public function buscar(Request $request)
    {
        $termo = $request->query('q', '');
        
        $carros = Carro::where('nome', 'like', '%' . $termo . '%')
                      ->latest()
                      ->get();
        
        return response()->json([
            'carros' => $carros
        ]);
    }

    /**
     * Listar todos os carros
     */
    public function listar()
    {
        $carros = Carro::latest()->get();
        
        return response()->json([
            'carros' => $carros
        ]);
    }

    public function rastreamento()
    {
        return view('carros.rastreamento');
    }

    public function comandos()
    {
        $carros = Carro::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'placa', 'modelo', 'imei_rastreador']);

        $historico = TrackerCommand::query()
            ->with(['carro:id,nome,placa'])
            ->latest('id')
            ->limit(120)
            ->get();

        return view('carros.comandos', [
            'carros' => $carros,
            'historico' => $historico,
            'commandsConfigured' => $this->trackerCommandsConfigured(),
        ]);
    }

    public function enviarComando(Request $request)
    {
        $payload = $request->validate([
            'carro_id' => 'required|exists:carros,id',
            'action' => 'required|in:block,unblock,custom',
            'custom_name' => 'nullable|string|max:50',
            'command_payload' => 'nullable|string|max:1000',
        ]);

        $carro = Carro::findOrFail((int) $payload['carro_id']);
        $imei = $this->normalizeImei((string) ($carro->imei_rastreador ?? ''));

        if ($imei === '') {
            return redirect()
                ->route('carros.comandos')
                ->with('error', 'O carro selecionado nao possui IMEI vinculado.');
        }

        $pendingCommand = TrackerCommand::query()
            ->where('imei', $imei)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pendingCommand !== null) {
            return redirect()
                ->route('carros.comandos')
                ->with('error', 'Ja existe um comando pendente para este rastreador. Aguarde o envio.');
        }

        $action = (string) $payload['action'];
        $commandName = $action;
        $commandPayload = '';
        $targetBlocked = false;

        if ($action === 'custom') {
            $commandPayload = trim((string) ($payload['command_payload'] ?? ''));
            $commandName = trim((string) ($payload['custom_name'] ?? '')) ?: 'custom';

            if ($commandPayload === '') {
                return redirect()
                    ->route('carros.comandos')
                    ->with('error', 'Informe o comando bruto para envio personalizado.');
            }
        } else {
            if (!$this->trackerCommandsConfigured()) {
                return redirect()
                    ->route('carros.comandos')
                    ->with('error', 'Configure TRACKER_TCP_COMMAND_BLOCK e TRACKER_TCP_COMMAND_UNBLOCK no .env.');
            }

            $template = (string) config("tracker_tcp.commands.{$action}", '');
            $commandPayload = $this->buildTrackerCommandPayload($template, $carro, $action);
            $targetBlocked = $action === 'block';
        }

        TrackerCommand::create([
            'carro_id' => $carro->id,
            'imei' => $imei,
            'command_name' => $commandName,
            'target_blocked' => $targetBlocked,
            'command_payload' => $commandPayload,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return redirect()
            ->route('carros.comandos')
            ->with('success', 'Comando enfileirado com sucesso. Aguarde o listener TCP enviar.');
    }

    public function dadosRastreamento()
    {
        $carros = Carro::orderBy('nome')->get(['id', 'nome', 'placa', 'modelo', 'imei_rastreador']);
        $trackerCommandsConfigured = $this->trackerCommandsConfigured();

        $latestAnyIds = TrackerPing::selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestAny = TrackerPing::whereIn('id', $latestAnyIds)->get();

        $latestFriIds = TrackerPing::where('packet_type', 'GTFRI')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestFri = TrackerPing::whereIn('id', $latestFriIds)->get();

        $latestInfIds = TrackerPing::where('packet_type', 'GTINF')
            ->selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestInf = TrackerPing::whereIn('id', $latestInfIds)->get();

        $latestStayIds = TrackerAddressStay::selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestStays = TrackerAddressStay::whereIn('id', $latestStayIds)
            ->get();

        $latestCommandIds = TrackerCommand::selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestCommands = TrackerCommand::whereIn('id', $latestCommandIds)->get();

        $latestAnyByImei = $latestAny->mapWithKeys(function (TrackerPing $ping) {
            return [$this->normalizeImei($ping->imei) => $ping];
        });

        $latestFriByImei = $latestFri->mapWithKeys(function (TrackerPing $ping) {
            return [$this->normalizeImei($ping->imei) => $ping];
        });

        $latestInfByImei = $latestInf->mapWithKeys(function (TrackerPing $ping) {
            return [$this->normalizeImei($ping->imei) => $ping];
        });

        $latestStaysByImei = $latestStays->mapWithKeys(function (TrackerAddressStay $stay) {
            return [$this->normalizeImei($stay->imei) => $stay];
        });

        $latestCommandsByImei = $latestCommands->mapWithKeys(function (TrackerCommand $command) {
            return [$this->normalizeImei($command->imei) => $command];
        });

        $carrosByImei = $carros
            ->filter(fn (Carro $carro) => !empty($carro->imei_rastreador))
            ->mapWithKeys(function (Carro $carro) {
                return [$this->normalizeImei((string) $carro->imei_rastreador) => $carro];
            });

        $rows = [];

        foreach ($carros as $carro) {
            $imeiNormalizado = $this->normalizeImei((string) ($carro->imei_rastreador ?? ''));
            $pingAny = $imeiNormalizado !== '' ? $latestAnyByImei->get($imeiNormalizado) : null;
            $pingFri = $imeiNormalizado !== '' ? $latestFriByImei->get($imeiNormalizado) : null;
            $pingInf = $imeiNormalizado !== '' ? $latestInfByImei->get($imeiNormalizado) : null;
            $stay = $imeiNormalizado !== '' ? $latestStaysByImei->get($imeiNormalizado) : null;
            $lastCommand = $imeiNormalizado !== '' ? $latestCommandsByImei->get($imeiNormalizado) : null;

            $ignicao = $pingInf?->ignition ?? $pingAny?->ignition;
            $velocidade = $pingFri?->speed ?? $pingAny?->speed;
            $emMovimento = $pingAny?->in_motion;
            $status = $this->resolverStatusComposto($ignicao, $emMovimento, $velocidade);
            $bloqueado = $this->resolveTrackerBlockedState($lastCommand);

            $rows[] = [
                'carro_id' => $carro->id,
                'nome' => $carro->nome,
                'placa' => $carro->placa,
                'modelo' => $carro->modelo,
                'imei' => $carro->imei_rastreador,
                'status' => $status,
                'ignicao' => $ignicao,
                'em_movimento' => $emMovimento,
                'velocidade' => $velocidade,
                'tensao_veiculo' => $pingInf?->tensao_veiculo ?? $pingAny?->tensao_veiculo,
                'latitude' => $pingFri?->latitude,
                'longitude' => $pingFri?->longitude,
                'endereco' => $stay?->address_line ?? $pingFri?->address_line,
                'chegada_endereco' => optional($stay?->arrived_at)->toDateTimeString(),
                'saida_endereco' => optional($stay?->left_at)->toDateTimeString(),
                'permanencia_segundos' => $stay?->permanence_seconds ?? 0,
                'tipo_pacote' => $pingAny?->packet_type,
                'ultima_msg' => $pingAny?->raw_message,
                'recebido_em' => optional($pingAny?->received_at)->toDateTimeString(),
                'gps_em' => optional($pingFri?->gps_at)->toDateTimeString(),
                'tracker_bloqueado' => $bloqueado,
                'tracker_comando_status' => $lastCommand?->status,
                'tracker_comando_em' => optional($lastCommand?->sent_at ?? $lastCommand?->requested_at)->toDateTimeString(),
                'tracker_comandos_habilitados' => $trackerCommandsConfigured,
            ];
        }

        // Inclui IMEIs sem carro vinculado para facilitar a associação inicial.
        foreach ($latestAnyByImei as $imeiNormalizado => $pingAny) {
            if ($carrosByImei->has($imeiNormalizado)) {
                continue;
            }

            $pingFri = $latestFriByImei->get($imeiNormalizado);
            $pingInf = $latestInfByImei->get($imeiNormalizado);
            $stay = $latestStaysByImei->get($imeiNormalizado);
            $lastCommand = $latestCommandsByImei->get($imeiNormalizado);

            $ignicao = $pingInf?->ignition ?? $pingAny->ignition;
            $velocidade = $pingFri?->speed ?? $pingAny->speed;
            $emMovimento = $pingAny->in_motion;
            $status = $this->resolverStatusComposto($ignicao, $emMovimento, $velocidade);
            $bloqueado = $this->resolveTrackerBlockedState($lastCommand);

            $rows[] = [
                'carro_id' => null,
                'nome' => 'Rastreador nao vinculado',
                'placa' => null,
                'modelo' => null,
                'imei' => $pingAny->imei,
                'status' => $status,
                'ignicao' => $ignicao,
                'em_movimento' => $emMovimento,
                'velocidade' => $velocidade,
                'tensao_veiculo' => $pingInf?->tensao_veiculo ?? $pingAny->tensao_veiculo,
                'latitude' => $pingFri?->latitude,
                'longitude' => $pingFri?->longitude,
                'endereco' => $stay?->address_line ?? $pingFri?->address_line,
                'chegada_endereco' => optional($stay?->arrived_at)->toDateTimeString(),
                'saida_endereco' => optional($stay?->left_at)->toDateTimeString(),
                'permanencia_segundos' => $stay?->permanence_seconds ?? 0,
                'tipo_pacote' => $pingAny->packet_type,
                'ultima_msg' => $pingAny->raw_message,
                'recebido_em' => optional($pingAny->received_at)->toDateTimeString(),
                'gps_em' => optional($pingFri?->gps_at)->toDateTimeString(),
                'tracker_bloqueado' => $bloqueado,
                'tracker_comando_status' => $lastCommand?->status,
                'tracker_comando_em' => optional($lastCommand?->sent_at ?? $lastCommand?->requested_at)->toDateTimeString(),
                'tracker_comandos_habilitados' => $trackerCommandsConfigured,
            ];
        }

        usort($rows, function (array $left, array $right) {
            $leftTime = $left['recebido_em'] ?? '';
            $rightTime = $right['recebido_em'] ?? '';

            return strcmp($rightTime, $leftTime);
        });

        return response()->json([
            'rows' => $rows,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    public function alternarBloqueioRastreador(Request $request, Carro $carro)
    {
        $payload = $request->validate([
            'action' => 'required|in:block,unblock',
        ]);

        $imei = $this->normalizeImei((string) ($carro->imei_rastreador ?? ''));
        if ($imei === '') {
            return response()->json([
                'message' => 'Este veiculo nao possui IMEI de rastreador vinculado.',
            ], 422);
        }

        if (!$this->trackerCommandsConfigured()) {
            return response()->json([
                'message' => 'Configure TRACKER_TCP_COMMAND_BLOCK e TRACKER_TCP_COMMAND_UNBLOCK antes de enviar comandos.',
            ], 422);
        }

        $pendingCommand = TrackerCommand::query()
            ->where('imei', $imei)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pendingCommand !== null) {
            return response()->json([
                'message' => 'Ja existe um comando pendente para este rastreador.',
            ], 409);
        }

        $action = $payload['action'];
        $template = (string) config("tracker_tcp.commands.{$action}", '');

        $command = TrackerCommand::create([
            'carro_id' => $carro->id,
            'imei' => $imei,
            'command_name' => $action,
            'target_blocked' => $action === 'block',
            'command_payload' => $this->buildTrackerCommandPayload($template, $carro, $action),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => $action === 'block'
                ? 'Comando de bloqueio enfileirado para envio no listener TCP.'
                : 'Comando de desbloqueio enfileirado para envio no listener TCP.',
            'command_id' => $command->id,
            'status' => $command->status,
        ], 201);
    }

    private function normalizeImei(string $imei): string
    {
        return preg_replace('/\D+/', '', trim($imei)) ?? '';
    }

    private function trackerCommandsConfigured(): bool
    {
        return trim((string) config('tracker_tcp.commands.block', '')) !== ''
            && trim((string) config('tracker_tcp.commands.unblock', '')) !== '';
    }

    private function buildTrackerCommandPayload(string $template, Carro $carro, string $action): string
    {
        return strtr($template, [
            '{imei}' => $this->normalizeImei((string) ($carro->imei_rastreador ?? '')),
            '{placa}' => (string) ($carro->placa ?? ''),
            '{action}' => $action,
            '{timestamp}' => now()->format('YmdHis'),
        ]);
    }

    private function resolveTrackerBlockedState(?TrackerCommand $command): bool
    {
        if ($command === null) {
            return false;
        }

        if (in_array($command->status, ['failed', 'cancelled'], true)) {
            return false;
        }

        return (bool) $command->target_blocked;
    }

    private function resolverStatusComposto($ignicao, $emMovimento, $velocidade): string
    {
        if ($ignicao === null && $emMovimento === null && $velocidade === null) {
            return 'Sem dados';
        }

        $speed = (float) ($velocidade ?? 0);

        if ($emMovimento === true || $speed > 3) {
            return 'Em movimento';
        }

        if ($ignicao === true || $ignicao === 1 || $ignicao === '1') {
            return 'Parado ign ligado';
        }

        if ($ignicao === false || $ignicao === 0 || $ignicao === '0') {
            return 'Parado ign desligado';
        }

        return 'Parado';
    }
}
