@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <h1 class="h3 mb-0 text-gray-800">Editar Produto</h1>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('produto.index') }}">Produtos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar Produto</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('produto.update', $produto->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="{{ $produto->nome }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor</label>
                                <input type="text" step="0.01" class="form-control money" id="valor" name="valor" value="{{ $produto->valor !== null ? number_format($produto->valor, 2, ',', '.') : '' }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor do App</label>
                                <input type="text" step="0.01" class="form-control money" id="valor_app" name="valor_app" value="{{ $produto->valor_app !== null ? number_format($produto->valor_app, 2, ',', '.') : '' }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="valor_min_app" class="form-label">Valor Mínimo no App</label>
                                <input type="text" step="0.01" class="form-control money" id="valor_min_app" name="valor_min_app" value="{{ $produto->valor_min_app !== null ? number_format($produto->valor_min_app, 2, ',', '.') : '' }}">
                            </div>

                            <div class="mb-3">
                                <label for="valor_max_app" class="form-label">Valor Máximo no App</label>
                                <input type="text" step="0.01" class="form-control money" id="valor_max_app" name="valor_max_app" value="{{ $produto->valor_max_app !== null ? number_format($produto->valor_max_app, 2, ',', '.') : '' }}">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="aplicativo" name="aplicativo" value="1" {{ $produto->aplicativo ? 'checked' : '' }}>
                                <label class="form-check-label" for="aplicativo">Disponível no Aplicativo</label>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" {{ $produto->ativo ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*" onchange="showPreview(event)">
                            </div>

                            <div id="foto-preview" style="position: relative; width: max-content; {{ $produto->foto ? '' : 'display: none;' }}">
                                <img id="preview-img" 
                                     src="{{ $produto->foto ? asset('storage/' . $produto->foto) : '#' }}" 
                                     alt="Preview da Foto"
                                     style="max-width: 100px; border-radius: 10px; display: block;">
                                <span class="remove-preview" onclick="removePreview()"
                                      style="cursor:pointer; position: absolute; z-index: 10; background: white; border-radius: 50%; padding: 0 6px; line-height: 1;">×</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Atualizar Produto</button>
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
    </script>
@endsection
