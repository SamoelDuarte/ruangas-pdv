<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Models\TrackerAddressStay;
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
            'rastreamento',
            'dadosRastreamento',
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

    public function dadosRastreamento()
    {
        $carros = Carro::orderBy('nome')->get(['id', 'nome', 'placa', 'modelo', 'imei_rastreador']);

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

            $ignicao = $pingInf?->ignition ?? $pingAny?->ignition;
            $velocidade = $pingFri?->speed ?? $pingAny?->speed;
            $emMovimento = $pingAny?->in_motion;
            $status = $this->resolverStatusComposto($ignicao, $emMovimento, $velocidade);

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

            $ignicao = $pingInf?->ignition ?? $pingAny->ignition;
            $velocidade = $pingFri?->speed ?? $pingAny->speed;
            $emMovimento = $pingAny->in_motion;
            $status = $this->resolverStatusComposto($ignicao, $emMovimento, $velocidade);

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

    private function normalizeImei(string $imei): string
    {
        return preg_replace('/\D+/', '', trim($imei)) ?? '';
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
