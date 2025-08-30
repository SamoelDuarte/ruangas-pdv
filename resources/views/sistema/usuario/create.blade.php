@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <h1 class="h3 mb-0 text-gray-800">Novo Usuário</h1>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('usuario.index') }}">Usuários</a></li>
                <li class="breadcrumb-item active" aria-current="page">Novo Usuário</li>
            </ol>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('usuario.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                @error('password')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" value="{{ old('telefone') }}">
                                @error('telefone')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Função</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Selecione uma função</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="entrega_direta" name="entrega_direta" {{ old('entrega_direta') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="entrega_direta">
                                        Entrega Direta
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="ativo" name="ativo" {{ old('ativo', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ativo">
                                        Usuário Ativo
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_admissao" class="form-label">Data de Admissão</label>
                                <input type="date" class="form-control" id="data_admissao" name="data_admissao" value="{{ old('data_admissao') }}">
                                @error('data_admissao')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="data_demissao" class="form-label">Data de Demissão</label>
                                <input type="date" class="form-control" id="data_demissao" name="data_demissao" value="{{ old('data_demissao') }}">
                                @error('data_demissao')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*"
                                    onchange="showPreview(event)">
                                @error('foto')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="foto-preview" style="display: none; position: relative;">
                                <img id="preview-img" src="#" alt="Preview da Foto"
                                    style="max-width: 100px; border-radius: 10px;">
                                <span class="remove-preview" onclick="removePreview()"
                                    style="cursor:pointer; color: red; position: absolute; top: -10px; right: -10px; font-size: 20px;">X</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Criar Usuário</button>
                        <a href="{{ route('usuario.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function showPreview(event) {
            var file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('foto-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                removePreview();
            }
        }

        function removePreview() {
            document.getElementById('foto').value = '';
            document.getElementById('preview-img').src = '';
            document.getElementById('foto-preview').style.display = 'none';
        }
    </script>
@endsection

@section('scripts')
    <script>
        function showPreview(event) {
            var file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('foto-preview').style.display = 'flex';
                };
                reader.readAsDataURL(file);
            } else {
                removePreview();
            }
        }

        function removePreview() {
            document.getElementById('foto').value = '';
            document.getElementById('preview-img').src = '';
            document.getElementById('foto-preview').style.display = 'none';
        }

        // Buscar cidade e estado pelo CEP
        document.getElementById('cep').addEventListener('blur', function () {
            let cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                            document.getElementById('cidade').readOnly = true;
                            document.getElementById('estado').readOnly = true;
                        } else {
                            liberarCamposCidadeEstado();
                        }
                    })
                    .catch(() => {
                        liberarCamposCidadeEstado();
                    });
            } else {
                liberarCamposCidadeEstado();
            }
        });

        function liberarCamposCidadeEstado() {
            document.getElementById('cidade').readOnly = false;
            document.getElementById('estado').readOnly = false;
            document.getElementById('cidade').value = '';
            document.getElementById('estado').value = '';
        }
    </script>
@endsection
