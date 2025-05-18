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

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Criar funções e atribuir permissões
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $atendenteRole = Role::firstOrCreate(['name' => 'atendente']);

        // Admin tem todas as permissões
        $adminRole->syncPermissions($permissions);

        // Atendente tem apenas algumas permissões
        $atendenteRole->syncPermissions(['ver clientes', 'criar clientes']);

        // Atribuir função Admin ao usuário padrão
        $admin = User::where('email', 'ruangasacesso5@gmail.com')->first();
        if ($admin) {
            $admin->assignRole('admin');
        }
    }
}
