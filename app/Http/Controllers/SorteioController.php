<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\NumeroDaSorte;
use App\Models\Sorteio;
use Illuminate\Http\Request;

class SorteioController extends Controller
{
    /**
     * Exibe a página de sorteio com os clientes.
     *
     * @return \Illuminate\View\View
     */
    public function cliente($telefone)
    {
        // Buscar o cliente pelo telefone e pegar o mais recente, com base no 'created_at' ou 'updated_at'
        $cliente = Cliente::where('telefone', $telefone)
            ->latest() // Isso irá garantir que pegue o cliente mais recente
            ->firstOrFail();
        // dd($cliente);

        // Buscar os números já escolhidos para o sorteio do cliente
        $numerosEscolhidos = NumeroDaSorte::whereHas('cliente', function ($query) use ($cliente) {
            $query->where('sorteio_id', $cliente->sorteio_id);
        })->pluck('numero')
            ->map(fn($numero) => (string) $numero) // Converte para string, se necessário
            ->toArray();

             // Buscar os números já escolhidos para o sorteio do cliente
        $numerosEscolhidosPeloCliente = NumeroDaSorte::whereHas('cliente', function ($query) use ($cliente) {
            $query->where('sorteio_id', $cliente->sorteio_id)->where('cliente_id', $cliente->id);
        })->pluck('numero')
            ->map(fn($numero) => (string) $numero) // Converte para string, se necessário
            ->toArray();

        // Quantidade máxima de números permitidos para o cliente
        $quantidadeNumeros = $cliente->quantidade_numeros;

        // Verificar se o cliente já escolheu a quantidade máxima de números
        if (count($numerosEscolhidosPeloCliente) >= $quantidadeNumeros) {
            // Se já atingiu o limite, redireciona para a view 'sorteio.link_usado'

            return view('sorteio.link_usado', compact('cliente', 'numerosEscolhidos', 'quantidadeNumeros'));
        }

        // Caso contrário, exibe a página de sorteio
        return view('sorteio.cliente', compact('cliente', 'numerosEscolhidos', 'quantidadeNumeros'));
    }


    public function index()
    {
        // Pega todos os sorteios, incluindo os detalhes dos clientes
        $sorteios = Sorteio::all();

        return view('sorteio.index', compact('sorteios'));
    }

    public function store(Request $request)
    {
        // Validação dos dados do formulário
        $request->validate([
            'nome' => 'required|string|max:255',
            'data_inicio' => 'required|date',
            'data_termino' => 'required|date|after_or_equal:data_inicio',
            'numero_sorteio' => 'required|integer|unique:sorteios,numero_sorteio',
            'numero_min' => 'required|integer',
            'numero_max' => 'required|integer',
        ]);

        // Criar um novo sorteio
        Sorteio::create([
            'nome' => $request->nome,
            'cliente_id' => $request->cliente_id,
            'data_inicio' => $request->data_inicio,
            'data_termino' => $request->data_termino,
            'numero_sorteio' => $request->numero_sorteio,
            'numero_min' => $request->numero_min,
            'numero_max' => $request->numero_max,
        ]);

        return redirect()->route('sorteio.index')->with('success', 'Sorteio criado com sucesso!');
    }

    public function destroy($id)
    {
        $sorteio = Sorteio::findOrFail($id);

        // Verifique se o sorteio tem clientes associados
        if ($sorteio->clientes->isEmpty()) {
            $sorteio->delete();
            return redirect()->route('sorteio.index')->with('success', 'Sorteio deletado com sucesso!');
        }

        return redirect()->route('sorteio.index')->with('error', 'Não é possível deletar, o sorteio possui clientes associados!');
    }

    public function salvarNumeroSorte(Request $request, $id)
    {
        // Recuperar o cliente pelo ID
        $cliente = Cliente::findOrFail($id);

        // Buscar os números já escolhidos para o sorteio do cliente
        $numerosEscolhidos = NumeroDaSorte::whereHas('cliente', function ($query) use ($cliente) {
            $query->where('sorteio_id', $cliente->sorteio_id)->where('cliente_id', $cliente->id);
        })->pluck('numero')
            ->map(fn($numero) => (string) $numero)
            ->toArray();

        // Quantidade máxima de números permitidos para o cliente
        $quantidadeNumeros = $cliente->quantidade_numeros;

        // Verificar se o cliente já atingiu o limite de números
        if (count($numerosEscolhidos) >= $quantidadeNumeros) {
            // Redireciona para a página de "link usado" caso já tenha atingido o limite
            return view('sorteio.link_usado', compact('cliente', 'numerosEscolhidos', 'quantidadeNumeros'));
        }

        // Validar os números que estão sendo enviados no formulário
        $numeros = $request->input('numeros_sorte');
        foreach ($numeros as $numero) {
            // Caso o número não tenha sido escolhido, salva o número
            NumeroDaSorte::create([
                'cliente_id' => $cliente->id,
                'numero' => $numero,
                'sorteio_id' => $cliente->sorteio_id,
            ]);
        }

        // Caso contrário, retorna para a página cliente
        return view('sorteio.agradecer_cliente', compact('numeros'));
    }

    public function buscarGanhador(Request $request)
    {
        $sorteio = $request->input('sorteio');
        $numero = $request->input('numero');

        // dd($request->all());

        // Buscando o número sorteado na tabela numero_da_sorte e associando com o cliente e sorteio
        $numeroDaSorte = NumeroDaSorte::with('cliente')->where('numero', $numero)
            ->where('sorteio_id', $sorteio)
            ->first();


        if ($numeroDaSorte) {
            // Obtendo o cliente associado ao número sorteado
            $cliente = $numeroDaSorte->cliente;

            // Verificando se o cliente existe e retornando os dados
            if ($cliente) {
                return response()->json([
                    'success' => true,
                    'cliente' => $cliente
                ]);
            }
        }

        // Caso o número ou o cliente não sejam encontrados
        return response()->json(['success' => false]);
    }
}
