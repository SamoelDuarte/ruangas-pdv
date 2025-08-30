@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <h1 class="h3 mb-0 text-gray-800">Gerenciar Permissões - {{ $user->name }}</h1>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('usuario.index') }}">Usuários</a></li>
                <li class="breadcrumb-item active" aria-current="page">Permissões</li>
            </ol>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('usuario.permissions.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                    <label class="form-check-label fw-bold" for="select-all">
                                        Selecionar Todas as Permissões
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($allPermissions as $module => $permissions)
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0 text-capitalize">{{ $module }}</h5>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input module-select" 
                                                   id="module-{{ $module }}" 
                                                   data-module="{{ $module }}">
                                            <label class="form-check-label" for="module-{{ $module }}">
                                                Selecionar todas de {{ $module }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @foreach($permissions as $permission)
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       class="form-check-input permission-checkbox module-{{ $module }}" 
                                                       id="permission-{{ $permission->id }}" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}"
                                                       {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                    {{ ucfirst($permission->name) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Salvar Permissões</button>
                        <a href="{{ route('usuario.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Selecionar todas as permissões
        const selectAllCheckbox = document.getElementById('select-all');
        const allPermissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        
        selectAllCheckbox.addEventListener('change', function() {
            allPermissionCheckboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            // Atualizar também os checkboxes de módulo
            document.querySelectorAll('.module-select').forEach(function(moduleCheckbox) {
                moduleCheckbox.checked = selectAllCheckbox.checked;
            });
        });

        // Selecionar permissões por módulo
        document.querySelectorAll('.module-select').forEach(function(moduleCheckbox) {
            moduleCheckbox.addEventListener('change', function() {
                const module = this.dataset.module;
                const modulePermissions = document.querySelectorAll('.module-' + module);
                
                modulePermissions.forEach(function(checkbox) {
                    checkbox.checked = moduleCheckbox.checked;
                });
                
                updateSelectAllState();
            });
        });

        // Atualizar estado do "Selecionar Todas" quando permissões individuais são alteradas
        allPermissionCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateModuleSelectState(this);
                updateSelectAllState();
            });
        });

        function updateModuleSelectState(changedCheckbox) {
            // Encontrar o módulo da permissão alterada
            const classes = changedCheckbox.className.split(' ');
            const moduleClass = classes.find(cls => cls.startsWith('module-'));
            
            if (moduleClass) {
                const module = moduleClass.replace('module-', '');
                const moduleCheckbox = document.querySelector(`[data-module="${module}"]`);
                const modulePermissions = document.querySelectorAll('.module-' + module);
                
                // Verificar se todas as permissões do módulo estão selecionadas
                const allChecked = Array.from(modulePermissions).every(cb => cb.checked);
                moduleCheckbox.checked = allChecked;
            }
        }

        function updateSelectAllState() {
            const allChecked = Array.from(allPermissionCheckboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        }

        // Estado inicial dos checkboxes de módulo
        document.querySelectorAll('.module-select').forEach(function(moduleCheckbox) {
            const module = moduleCheckbox.dataset.module;
            const modulePermissions = document.querySelectorAll('.module-' + module);
            const allChecked = Array.from(modulePermissions).every(cb => cb.checked);
            moduleCheckbox.checked = allChecked;
        });

        // Estado inicial do "Selecionar Todas"
        updateSelectAllState();
    });
</script>
@endsection
