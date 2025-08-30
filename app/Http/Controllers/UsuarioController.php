<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar usuários')->only(['edit', 'update']);
        $this->middleware('permission:criar usuários')->only(['create', 'store']);
        $this->middleware('permission:excluir usuários')->only(['destroy']);
        $this->middleware('permission:listar usuários')->only(['index']);
        $this->middleware('permission:gerenciar usuários')->only(['permissions', 'updatePermissions']);
    }
    public function index()
    {
        // Recupera todos os usuários
        $users = User::all();

        // Retorna a view 'sistema.usuario.index' passando os usuários
        return view('sistema.usuario.index', compact('users'));
    }
    public function create()
    {
        $roles = Role::all();
        return view('sistema.usuario.create', compact('roles'));  // Retorna a view de criação de usuário
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'telefone' => 'nullable|string|max:20',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'data_admissao' => 'nullable|date',
                'data_demissao' => 'nullable|date',
                'role' => 'required|string|exists:roles,name', // Papel do usuário
            ]);

            // Upload da foto
            $fotoPath = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                $fotoPath = $request->file('foto')->store('fotos_usuarios', 'public');
            }

            // Criar usuário
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'telefone' => $validatedData['telefone'] ?? null,
                'foto' => $fotoPath,
                'data_admissao' => $validatedData['data_admissao'] ?? null,
                'data_demissao' => $validatedData['data_demissao'] ?? null,
                'entrega_direta' => $request->has('entrega_direta'),
                'ativo' => $request->has('ativo'),
            ]);

            // Atribuir papel ao usuário
            $user->assignRole($validatedData['role']);

            return redirect()->route('usuario.index')->with('success', 'Usuário criado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6',
                'telefone' => 'nullable|string|max:20',
                'data_admissao' => 'nullable|date',
                'data_demissao' => 'nullable|date',
                'foto' => 'nullable|image|max:2048',
                'role' => 'required|string|exists:roles,name',
            ]);

            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            } else {
                unset($data['password']);
            }

            $data['ativo'] = $request->has('ativo');
            $data['entrega_direta'] = $request->has('entrega_direta');

            if ($request->hasFile('foto')) {
                if ($user->foto) {
                    Storage::disk('public')->delete($user->foto);
                }
                $data['foto'] = $request->file('foto')->store('fotos_usuarios', 'public');
            }

            if ($request->input('remover_foto') == '1') {
                if ($user->foto) {
                    Storage::disk('public')->delete($user->foto);
                }
                $data['foto'] = null;
            }

            // Remove 'role' do array $data antes de atualizar o usuário
            $role = $data['role'];
            unset($data['role']);

            $user->update($data);
            
            // Sincroniza as roles (remove todas as anteriores e adiciona a nova)
            $user->syncRoles([$role]);

            return redirect()->route('usuario.index')->with('success', 'Usuário atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('usuario.index')->with('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }


    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        $roles = Role::all(); // Busca todas as funções disponíveis
        return view('sistema.usuario.edit', compact('usuario', 'roles'));
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Remove a foto se existir
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            
            $user->delete();
            
            return redirect()->route('usuario.index')->with('success', 'Usuário excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('usuario.index')->with('error', 'Erro ao excluir usuário: ' . $e->getMessage());
        }
    }

    public function permissions($id)
    {
        $user = User::findOrFail($id);
        $allPermissions = \Spatie\Permission\Models\Permission::all()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return end($parts); // Agrupa pela última palavra (ex: 'usuários', 'clientes')
        });
        
        return view('sistema.usuario.permissions', compact('user', 'allPermissions'));
    }

    public function updatePermissions(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $permissions = $request->input('permissions', []);
            
            // Sincroniza as permissões (remove todas as anteriores e adiciona as novas)
            $user->syncPermissions($permissions);
            
            return redirect()->route('usuario.permissions', $id)->with('success', 'Permissões atualizadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('usuario.permissions', $id)->with('error', 'Erro ao atualizar permissões: ' . $e->getMessage());
        }
    }
}
