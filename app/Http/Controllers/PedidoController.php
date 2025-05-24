<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Entregador;
use App\Models\FormaPagamento;
use App\Models\HistoricoStatusPedido;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\PedidoFormaPagamento;
use App\Models\StatusPedido;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function index()
    {
        $entregadores = Entregador::entregadoresTrabalhandoHoje();
        $situacoes = StatusPedido::all();

        return view('sistema.pedido.index', compact('entregadores', 'situacoes'));
    }
    public function removerEntregador(Pedido $pedido)
    {
        $pedido->entregador_id = null; // remove o entregador
        $pedido->save();

        return response()->json(['success' => true]);
    }
    public function atualizarTipo(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->tipo_pedido = $request->tipo_pedido;
        $pedido->save();

        return response()->json(['success' => true]);
    }
    public function alterarSituacao(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        // Atualiza o status do pedido
        $pedido->status_pedido_id = $request->status_pedido_id;
        $pedido->save();

        // Registra o histórico da mudança de status
        HistoricoStatusPedido::create([
            'pedido_id' => $pedido->id,
            'status' => $request->status_pedido_id,
            'mudanca_por' =>  Auth::user()->name
        ]);

        // Obtém o status atualizado
        $status = StatusPedido::findOrFail($request->status_pedido_id);

        // Retorna a resposta com a descrição e cor do status
        return response()->json([
            'descricao' => $status->descricao,
            'cor' => $status->cor
        ]);
    }
    public function atribuirEntregador(Request $request, Pedido $pedido)
    {
        $pedido->entregador_id = $request->entregador_id;
        $pedido->status_pedido_id = 6; // Em andamento
        $pedido->save();

        return response()->json([
            'entregador_nome' => $pedido->entregador->nome,
            'status_descricao' => $pedido->statusPedido->descricao,
            'status_cor' => $pedido->statusPedido->cor
        ]);
    }



    public function filtro(Request $request)
    {
        $query = Pedido::with(['cliente', 'entregador', 'statusPedido']);

        // Filtro: Data Início e Fim
        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->data_inicio)->startOfDay(),
                Carbon::parse($request->data_fim)->endOfDay()
            ]);
        }

        // Filtro: Telefone
        if ($request->filled('telefone')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('telefone', 'like', '%' . $request->telefone . '%');
            });
        }

        // Filtro: Entregador
        if ($request->filled('entregador_id')) {
            $query->where('entregador_id', $request->entregador_id);
        }

        // Filtro: Situação
        if ($request->filled('situacao_id')) {
            $query->where('status_pedido_id', $request->situacao_id);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->get();

        return response()->json($pedidos);
    }


    public function create()
    {
        $formasPagamento = FormaPagamento::all();

        return view('sistema.pedido.create', compact('formasPagamento'));
    }


    public function store(Request $request)
    {
        $dados = $request->all();

        // Codificar o telefone informado para buscar no banco de dados
        $telefoneCodificado = base64_encode(preg_replace('/\D/', '', $dados['telefone']));

        // Buscar o cliente pelo telefone codificado
        $cliente = Cliente::where('telefone', $telefoneCodificado)->first();

        // Se cliente não existir, cria um novo
        if (!$cliente) {
            $cliente = Cliente::create([
                'nome'             => $dados['nome'],
                'telefone'         => $dados['telefone'],
                'cep'              => $dados['cep'] ?? null,
                'numero'           => $dados['numero'] ?? null,
                'logradouro'       => $dados['logradouro'] ?? null,
                'bairro'           => $dados['bairro'] ?? null,
                'cidade'           => $dados['cidade'] ?? null,
                'complemento'      => $dados['complemento'] ?? null,
                'referencia'       => $dados['referencia'] ?? null,
                'data_nascimento'  => $dados['data_nascimento'] ?? null,
                'observacao'       => $dados['observacao'] ?? null,
            ]);
        }

        DB::beginTransaction();

        try {
            $pedido = Pedido::create([
                'tipo_pedido'         => $dados['tipo_pedido'],
                'motivo_cancelamento' => $dados['motivo_cancelamento'] ?? null,
                'mensagem'            => $dados['mensagem'] ?? null,
                'valor_total'         => $dados['total'],
                'desconto'            => $dados['desconto'] ?? 0,
                'notifica_mensagem'   => $dados['notifica_mensagem'] ?? false,
                'cliente_id'          => $cliente->id,
                'status_pedido_id'    => 6, // Status inicial padrão
            ]);

            HistoricoStatusPedido::create([
                'pedido_id'   => $pedido->id,
                'status' =>  1,
                'mudanca_por' =>  Auth::user()->name
            ]);

            // Adicionar os itens do pedido
            foreach ($request->produtos as $item) {
                ItemPedido::create([
                    'pedido_id'     => $pedido->id,
                    'produto_id'    => $item['codigo'],
                    'quantidade'    => $item['quantidade'],
                    'preco_unitario' => $item['valorUnitario'],
                ]);
            }

            $totalPedido = (float)$dados['total'];
            $totalPago = 0;
            $pagamentoDinheiroIndex = null;

            // Copia os pagamentos para um array normal
            $pagamentos = $request->pagamentos;

            foreach ($pagamentos as $index => $pagamento) {
                $valor = (float)$pagamento['valor'];
                $totalPago += $valor;

                if ($pagamento['formaPagamento'] == "1") {
                    $pagamentoDinheiroIndex = $index;
                }
            }

            $troco = 0;

            // Se teve pagamento em dinheiro e pagou a mais
            if ($pagamentoDinheiroIndex !== null && $totalPago > $totalPedido) {
                $valorTroco = $totalPago - $totalPedido;
                $troco = $valorTroco;

                // Ajusta os valores no array
                $pagamentos[$pagamentoDinheiroIndex]['valor_recebido'] = (float)$pagamentos[$pagamentoDinheiroIndex]['valor'];
                $pagamentos[$pagamentoDinheiroIndex]['troco'] = $valorTroco;
            }

            // Agora salva os pagamentos
            foreach ($pagamentos as $pagamento) {
                PedidoFormaPagamento::create([
                    'pedido_id'           => $pedido->id,
                    'forma_pagamento_id'  => $pagamento['formaPagamento'],
                    'valor'               => $pagamento['valor'],
                    'valor_recebido'      => $pagamento['valor_recebido'] ?? null,
                    'troco'               => $pagamento['troco'] ?? null,
                ]);
            }


            DB::commit();

            return redirect()->route('pedido.create')->with('success', 'Pedido criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao criar pedido: ' . $e->getMessage());
        }
    }
    public function cancelar(Request $request, Pedido $pedido)
    {
        $request->validate([
            'motivo_cancelamento' => 'required|string|max:255',
        ]);

        // Buscar o status "cancelado"
        $statusCancelado = StatusPedido::where('descricao', 'cancelado')->first();

        if (!$statusCancelado) {
            return response()->json(['error' => 'Status "cancelado" não encontrado.'], 400);
        }

        // Atualizar o pedido
        $pedido->update([
            'status_pedido_id' => $statusCancelado->id,
            'motivo_cancelamento' => $request->motivo_cancelamento,
        ]);

        // Criar histórico de status
        $pedido->historicoStatus()->create([
            'status' => 'cancelado',
            'mudanca_por' =>  Auth::user()->name
        ]);

        return response()->json(['success' => true]);
    }
}
