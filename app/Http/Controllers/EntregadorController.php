<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entregador;

class EntregadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar entregadores')->only(['edit', 'update']);
        $this->middleware('permission:criar entregadores')->only(['create', 'store']);
        $this->middleware('permission:excluir entregadores')->only(['delete']);
        $this->middleware('permission:listar entregadores')->only(['index']);
    }
    public function index()
    {
        $entregadores = Entregador::all();
        return view('sistema.entregador.index', compact('entregadores'));
    }

    public function create()
    {
        return view('sistema.entregador.create');
    }

    public function listar()
    {
        $entregadores = Entregador::where('trabalhando', 1)
            ->where('ativo', 1)
            ->get()
            ->map(function ($entregador) {
                $entregador->pedidos_do_dia = $entregador->pedidos()
                    ->whereDate('created_at', today())
                    ->count();
                return $entregador;
            });
    
        return response()->json($entregadores);
    }
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:entregadores',
            'senha' => 'required|string|min:6',
            'telefone' => 'nullable|string|max:20',
        ]);

        Entregador::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'senha' => bcrypt($request->senha),
            'telefone' => $request->telefone,
            'ativo' => $request->has('ativo'),
        ]);

        return redirect()->route('entregador.index')->with('success', 'Entregador criado com sucesso.');
    }

    public function edit($id)
    {
        $entregador = Entregador::findOrFail($id);
        return view('sistema.entregador.edit', compact('entregador'));
    }

    public function update(Request $request, $id)
    {
        $entregador = Entregador::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:entregadores,email,' . $id,
            'senha' => 'nullable|string|min:6',
            'telefone' => 'nullable|string|max:20',
        ]);

        $entregador->nome = $request->nome;
        $entregador->email = $request->email;
        $entregador->telefone = $request->telefone;
        $entregador->ativo = $request->has('ativo');

        if ($request->filled('senha')) {
            $entregador->senha = bcrypt($request->senha);
        }

        $entregador->save();

        return redirect()->route('entregador.index')->with('success', 'Entregador atualizado com sucesso.');
    }

    public function delete(Request $request)
    {
        $entregador = Entregador::findOrFail($request->id);
        $entregador->delete();

        return response()->json(['success' => true]);
    }

    public function toggleAtivo(Request $request)
    {
        $entregador = Entregador::findOrFail($request->id);
        $entregador->ativo = !$entregador->ativo;
        $entregador->save();

        return response()->json(['ativo' => $entregador->ativo]);
    }
    public function salvarTrabalhando(Request $request)
    {
        $dados = $request->input('trabalhando');

        foreach ($dados as $id => $trabalhando) {
            Entregador::where('id', $id)->update(['trabalhando' => $trabalhando]);
        }

        return response()->json(['success' => true]);
    }
}
