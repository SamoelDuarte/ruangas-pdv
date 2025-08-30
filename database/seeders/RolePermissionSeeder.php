<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Criar permissões
        $permissions = [
            'ver clientes',
            'criar clientes',
            'editar clientes',
            'excluir clientes',
            'gerenciar clientes',
            'listar clientes',
            'ver usuários',
            'criar usuários',
            'editar usuários',
            'excluir usuários',
            'gerenciar usuários',
            'listar usuários',
            'ver entregadores',
            'criar entregadores',
            'editar entregadores',
            'excluir entregadores',
            'gerenciar entregadores',
            'listar entregadores',
            'gerenciar dispositivos',
            'ver produtos',
            'criar produtos',
            'editar produtos',
            'excluir produtos',
            'gerenciar produtos',
            'listar produtos',
            'ver pedidos',
            'criar pedidos',
            'editar pedidos',
            'excluir pedidos',
            'gerenciar pedidos',
            'listar pedidos',
            'gerenciar mensagens',
            'ver carros',
            'criar carros',
            'editar carros',
            'excluir carros',
            'gerenciar carros',
            'listar carros',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Criar funções e atribuir permissões
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $atendenteRole = Role::firstOrCreate(['name' => 'atendente']);
        $entregadorRole = Role::firstOrCreate(['name' => 'entregador']);

        // Admin tem todas as permissões
        $adminRole->syncPermissions($permissions);

        // Atendente tem permissões relacionadas a clientes e pedidos
        $atendenteRole->syncPermissions([
            'ver clientes', 'criar clientes', 'editar clientes', 'listar clientes',
            'ver pedidos', 'criar pedidos', 'editar pedidos', 'listar pedidos'
        ]);

        // Entregador tem permissões limitadas
        $entregadorRole->syncPermissions([
            'ver pedidos', 'listar pedidos', 
            'ver clientes', 'listar clientes'
        ]);

        // Atribuir função Admin ao usuário padrão
        $admin = User::where('email', 'ruangasacesso5@gmail.com')->first();
        if ($admin) {
            $admin->assignRole('admin');
        }
    }
}
