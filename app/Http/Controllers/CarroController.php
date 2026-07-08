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

        $latestPingIds = TrackerPing::selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestPings = TrackerPing::whereIn('id', $latestPingIds)
            ->orderByDesc('received_at')
            ->get()
            ->keyBy('imei');

        $latestStayIds = TrackerAddressStay::selectRaw('MAX(id) as id')
            ->groupBy('imei');

        $latestStays = TrackerAddressStay::whereIn('id', $latestStayIds)
            ->get()
            ->keyBy('imei');

        $rows = [];

        foreach ($carros as $carro) {
            $ping = $carro->imei_rastreador ? $latestPings->get($carro->imei_rastreador) : null;
            $stay = $carro->imei_rastreador ? $latestStays->get($carro->imei_rastreador) : null;

            $rows[] = [
                'carro_id' => $carro->id,
                'nome' => $carro->nome,
                'placa' => $carro->placa,
                'modelo' => $carro->modelo,
                'imei' => $carro->imei_rastreador,
                'status' => $this->resolverStatusVeiculo($ping),
                'ignicao' => $ping?->ignition,
                'em_movimento' => $ping?->in_motion,
                'velocidade' => $ping?->speed,
                'altitude' => $ping?->altitude,
                'latitude' => $ping?->latitude,
                'longitude' => $ping?->longitude,
                'endereco' => $stay?->address_line ?? $ping?->address_line,
                'chegada_endereco' => optional($stay?->arrived_at)->toDateTimeString(),
                'saida_endereco' => optional($stay?->left_at)->toDateTimeString(),
                'permanencia_segundos' => $stay?->permanence_seconds ?? 0,
                'tipo_pacote' => $ping?->packet_type,
                'ultima_msg' => $ping?->raw_message,
                'recebido_em' => optional($ping?->received_at)->toDateTimeString(),
                'gps_em' => optional($ping?->gps_at)->toDateTimeString(),
            ];
        }

        // Inclui IMEIs sem carro vinculado para facilitar a associação inicial.
        foreach ($latestPings as $imei => $ping) {
            if ($carros->contains(fn (Carro $carro) => $carro->imei_rastreador === $imei)) {
                continue;
            }

            $stay = $latestStays->get($imei);

            $rows[] = [
                'carro_id' => null,
                'nome' => 'Rastreador nao vinculado',
                'placa' => null,
                'modelo' => null,
                'imei' => $imei,
                'status' => $this->resolverStatusVeiculo($ping),
                'ignicao' => $ping->ignition,
                'em_movimento' => $ping->in_motion,
                'velocidade' => $ping->speed,
                'altitude' => $ping->altitude,
                'latitude' => $ping->latitude,
                'longitude' => $ping->longitude,
                'endereco' => $stay?->address_line ?? $ping->address_line,
                'chegada_endereco' => optional($stay?->arrived_at)->toDateTimeString(),
                'saida_endereco' => optional($stay?->left_at)->toDateTimeString(),
                'permanencia_segundos' => $stay?->permanence_seconds ?? 0,
                'tipo_pacote' => $ping->packet_type,
                'ultima_msg' => $ping->raw_message,
                'recebido_em' => optional($ping->received_at)->toDateTimeString(),
                'gps_em' => optional($ping->gps_at)->toDateTimeString(),
            ];
        }

        return response()->json([
            'rows' => $rows,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    private function resolverStatusVeiculo(?TrackerPing $ping): string
    {
        if (!$ping) {
            return 'Sem dados';
        }

        $speed = (float) ($ping->speed ?? 0);

        if ($ping->in_motion === true || $speed > 3) {
            return 'Em movimento';
        }

        if ($ping->ignition === true) {
            return 'Parado ign ligado';
        }

        if ($ping->ignition === false) {
            return 'Parado ign desligado';
        }

        return 'Parado';
    }
}
