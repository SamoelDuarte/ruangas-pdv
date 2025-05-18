<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar produtos')->only(['edit', 'update']);
        $this->middleware('permission:criar produtos')->only(['create', 'store']);
        $this->middleware('permission:excluir produtos')->only(['delete']);
        $this->middleware('permission:listar produtos')->only(['index']);
    }
    public function index()
    {
        $produtos = Produto::orderBy('nome')->get();
        return view('sistema.produto.index', compact('produtos'));
    }

    public function create()
    {
        return view('sistema.produto.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'valor' => 'required',
            'valor_app' => 'required|nullable',
            'valor_min_app' => 'nullable',
            'valor_max_app' => 'nullable',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'aplicativo' => 'boolean',
            'ativo' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('produtos', 'public');
        }

        Produto::create($data);

        return redirect()->route('produto.index')->with('success', 'Produto criado com sucesso!');
    }

    public function edit($id)
    {
        $produto = Produto::findOrFail($id);
        return view('sistema.produto.edit', compact('produto'));
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'valor' => 'required',
            'valor_app' => 'required',
            'valor_min_app' => 'nullable',
            'valor_max_app' => 'nullable',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aplicativo' => 'boolean',
            'ativo' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('foto')) {
            if ($produto->foto && Storage::disk('public')->exists($produto->foto)) {
                Storage::disk('public')->delete($produto->foto);
            }
            $data['foto'] = $request->file('foto')->store('produtos', 'public');
        }

        $produto->update($data);

        return redirect()->route('produto.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $produto = Produto::findOrFail($id);

        // Deleta a foto se existir
        if ($produto->foto && file_exists(public_path('uploads/' . $produto->foto))) {
            unlink(public_path('uploads/' . $produto->foto));
        }

        $produto->delete();

        return redirect()->route('produto.index')->with('success', 'Produto deletado com sucesso!');
    }


    public function toggleAtivo(Request $request)
    {
        $produto = Produto::findOrFail($request->id);
        $produto->ativo = !$produto->ativo;
        $produto->save();

        return response()->json(['ativo' => $produto->ativo]);
    }
    public function buscar($codigo)
    {
        $produto = Produto::where('id', $codigo)->first();

        if (!$produto) {
            return response()->json(['erro' => 'Produto não encontrado'], 404);
        }

        return response()->json([
            'nome' => $produto->nome,
            'valor' => $produto->valor
        ]);
    }
}
