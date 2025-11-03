<?php

namespace App\Http\Controllers;

use App\Models\LimiteKm;
use App\Models\TrocaOleo;
use App\Models\Carro;
use Illuminate\Http\Request;

class LimiteKmController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:gerenciar carros')->only(['store', 'update', 'destroy']);
    }

    /**
     * Obter o limite de KM global
     */
    public function getGlobal()
    {
        $limiteKm = LimiteKm::first();

        return response()->json([
            'limiteKm' => $limiteKm ? $limiteKm->km_limite : null,
        ]);
    }

    /**
     * Obter o limite de KM global e a última troca de óleo de um carro
     */
    public function show($carroId)
    {
        $limiteKm = LimiteKm::first();
        $ultimaTroca = TrocaOleo::where('carro_id', $carroId)
                               ->latest('data_troca')
                               ->first();

        // Obter KM atual
        $carro = Carro::find($carroId);
        if (!$carro) {
            return response()->json([
                'limiteKm' => null,
                'ultimaTroca' => null,
                'kmAtual' => 0,
                'kmAtualOdometro' => 0
            ]);
        }

        // ALGORITMO SIMPLES:
        // 1. Se há troca, pega abastecimentos APÓS a data da troca
        // 2. Se não há troca, pega TODOS os abastecimentos
        // 3. Se há abastecimentos, soma: último KM - primeiro KM
        $query = $carro->abastecimentos()->orderBy('data_abastecimento', 'asc');
        
        if ($ultimaTroca) {
            // Filtrar apenas abastecimentos POSTERIORES à data da troca
            $query = $query->where('data_abastecimento', '>', $ultimaTroca->data_troca);
        }
        
        $abastecimentos = $query->get();

        \Log::info("=== DEBUG LimiteKmController::show ===");
        \Log::info("Carro ID: " . $carroId);
        \Log::info("Última Troca: " . ($ultimaTroca ? $ultimaTroca->data_troca : 'null'));
        \Log::info("Total de abastecimentos após troca: " . count($abastecimentos));
        
        foreach ($abastecimentos as $idx => $abast) {
            \Log::info("Abastecimento $idx - Data: " . $abast->data_abastecimento . ", KM Atual: " . $abast->km_atual);
        }

        // Calcular KM RODADO após a troca
        // Se há abastecimentos: KM máximo - KM primeiro
        // Se não há abastecimentos: 0
        $kmAtual = 0;
        
        if (count($abastecimentos) > 0) {
            $primeiroAbast = $abastecimentos[0];
            
            // Pegar o KM máximo entre todos os abastecimentos
            $kmMaximo = 0;
            foreach ($abastecimentos as $abast) {
                $km = (int) $abast->km_atual;
                if ($km > $kmMaximo) {
                    $kmMaximo = $km;
                }
            }
            
            $kmPrimeiro = (int) $primeiroAbast->km_atual;
            
            $kmAtual = $kmMaximo - $kmPrimeiro;
            
            \Log::info("Primeiro abastecimento KM: " . $kmPrimeiro);
            \Log::info("KM Máximo: " . $kmMaximo);
            \Log::info("KM Rodado (máximo - primeiro): " . $kmAtual);
        } else {
            \Log::info("Nenhum abastecimento após troca");
        }
        
        \Log::info("KM Atual Final: " . $kmAtual);
        
        // Também pegar o KM máximo do odômetro para referência
        $ultimoAbastecimentoTotal = $carro->abastecimentos()->orderBy('data_abastecimento', 'desc')->first();
        $kmAtualOdometro = $ultimoAbastecimentoTotal ? (int) $ultimoAbastecimentoTotal->km_atual : 0;
        
        \Log::info("KM Atual Odômetro: " . $kmAtualOdometro);
        \Log::info("=== FIM DEBUG ===");

        return response()->json([
            'limiteKm' => $limiteKm ? $limiteKm->km_limite : null,
            'ultimaTroca' => $ultimaTroca ? $ultimaTroca->toArray() : null,
            'kmAtual' => $kmAtual,
            'kmAtualOdometro' => $kmAtualOdometro
        ]);
    }

    /**
     * Salvar ou atualizar o limite de KM global
     */
    public function store(Request $request)
    {
        $request->validate([
            'km_limite' => 'required|numeric|min:0',
        ]);

        LimiteKm::truncate(); // Remove todas as linhas anteriores
        $limiteKm = LimiteKm::create([
            'km_limite' => $request->km_limite,
        ]);

        return response()->json([
            'success' => true,
            'limiteKm' => $limiteKm,
            'message' => 'Limite de KM salvo com sucesso!'
        ]);
    }

    /**
     * Registrar troca de óleo
     */
    public function registrarTroca(Request $request)
    {
        $request->validate([
            'carro_id' => 'required|exists:carros,id',
            'data_troca' => 'nullable|date',
            'observacoes' => 'nullable|string',
        ]);

        $trocaOleo = TrocaOleo::create([
            'carro_id' => $request->carro_id,
            'data_troca' => $request->data_troca ? $request->data_troca . ' ' . now()->format('H:i:s') : now(),
            'observacoes' => $request->observacoes,
        ]);

        return response()->json([
            'success' => true,
            'trocaOleo' => $trocaOleo,
            'message' => 'Troca de óleo registrada com sucesso!'
        ]);
    }
}
