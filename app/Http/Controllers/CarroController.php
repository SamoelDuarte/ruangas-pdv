<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use Illuminate\Http\Request;

class CarroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:gerenciar carros')->only(['index', 'store', 'update', 'destroy']);
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
        ]);

        Carro::create([
            'nome' => $request->nome,
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
        ]);

        $carro->update([
            'nome' => $request->nome,
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
}
