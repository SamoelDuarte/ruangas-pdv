@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <h1 class="h3 mb-0 text-gray-800">Novo Produto</h1>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item"><a href="{{ route('produto.index') }}">Produtos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Novo Produto</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('produto.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor</label>
                                <input type="text" step="0.01" class="form-control money" id="valor" name="valor" required>
                            </div>

                            <div class="mb-3">
                                <label for="valor_min_app" class="form-label">Valor Mínimo no App</label>
                                <input type="text" step="0.01" class="form-control money" id="valor_min_app" name="valor_min_app">
                            </div>

                            <div class="mb-3">
                                <label for="valor_max_app" class="form-label">Valor Máximo no App</label>
                                <input type="text" step="0.01" class="form-control money" id="valor_max_app" name="valor_max_app">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="aplicativo" name="aplicativo" value="1">
                                <label class="form-check-label" for="aplicativo">Disponível no Aplicativo</label>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" checked>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*" onchange="showPreview(event)">
                            </div>

                            <div id="foto-preview" style="display: none; position: relative; width: max-content;">
                                <img id="preview-img" src="#" alt="Preview da Foto"
                                     style="max-width: 100px; border-radius: 10px; display: block;">
                                <span class="remove-preview" onclick="removePreview()"
                                      style="cursor:pointer; position: absolute; z-index: 10; background: white; border-radius: 50%; padding: 0 6px; line-height: 1;">×</span>
                            </div>
                            
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Produto</button>
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
