<?php

namespace App\Http\Controllers;

use App\Models\Abastecimento;
use App\Models\Carro;
use Illuminate\Http\Request;

class AbastecimentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:gerenciar carros');
    }

    public function store(Request $request)
    {
        $request->validate([
            'carro_id' => 'required|exists:carros,id',
            'litros_abastecido' => 'required|numeric|min:0.01',
            'preco_por_litro' => 'required|numeric|min:0.01',
            'km_atual' => 'required|numeric|min:0',
            'data_abastecimento' => 'required|date',
        ]);

        Abastecimento::create($request->all());

        return response()->json(['success' => true, 'message' => 'Abastecimento registrado com sucesso!']);
    }

    public function destroy(Abastecimento $abastecimento)
    {
        $abastecimento->delete();
        return response()->json(['success' => true, 'message' => 'Abastecimento excluído com sucesso!']);
    }

    public function getAbastecimentos($carroId, Request $request)
    {
        $dataInicio = $request->get('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->get('data_fim', now()->format('Y-m-d'));

        $carro = Carro::with(['abastecimentos' => function($query) use ($dataInicio, $dataFim) {
            $query->whereBetween('data_abastecimento', [$dataInicio, $dataFim])
                  ->orderBy('data_abastecimento', 'desc');
        }])->findOrFail($carroId);

        $abastecimentos = $carro->abastecimentos;
        
        // Calcular estatísticas
        $stats = $this->calcularEstatisticas($abastecimentos);

        return response()->json([
            'carro' => $carro,
            'abastecimentos' => $abastecimentos,
            'stats' => $stats,
            'periodo' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim
            ]
        ]);
    }

    private function calcularEstatisticas($abastecimentos)
    {
        if ($abastecimentos->count() < 2) {
            return [
                'total_km_rodado' => 0,
                'total_litros_abastecido' => $abastecimentos->sum('litros_abastecido'),
                'preco_medio_por_litro' => $abastecimentos->avg('preco_por_litro') ?: 0,
                'total_pago' => $abastecimentos->sum(function($ab) { return $ab->total_pago; }),
                'valor_gasto_por_km' => 0,
                'media_km_por_litro' => 0,
            ];
        }

        $kmMaximo = $abastecimentos->max('km_atual');
        $kmMinimo = $abastecimentos->min('km_atual');
        $totalKmRodado = $kmMaximo - $kmMinimo;
        
        $totalLitros = $abastecimentos->sum('litros_abastecido');
        $totalPago = $abastecimentos->sum(function($ab) { return $ab->total_pago; });
        $precoMedio = $abastecimentos->avg('preco_por_litro');
        
        return [
            'total_km_rodado' => $totalKmRodado,
            'total_litros_abastecido' => $totalLitros,
            'preco_medio_por_litro' => $precoMedio,
            'total_pago' => $totalPago,
            'valor_gasto_por_km' => $totalKmRodado > 0 ? $totalPago / $totalKmRodado : 0,
            'media_km_por_litro' => $totalLitros > 0 ? $totalKmRodado / $totalLitros : 0,
        ];
    }
}
