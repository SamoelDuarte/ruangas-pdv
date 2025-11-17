@extends('sistema.layouts.app')

@section('content')
    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Configuração de Horários</h2>
        
        <form action="{{ route('schedule.update') }}" method="POST">
            @csrf
            <div class="container-fluid">
                <div class="row g-3">
                    @php
                        $days = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
                    @endphp

                    @foreach ($days as $index => $day)
                        @php
                            $slot = $availability->firstWhere('day_of_week', $day);
                        @endphp

                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" id="{{ 'header-' . $index }}"
                                    style="background-color: {{ $slot && $slot->start_time ? '#e2ffee' : '#ffe5e2' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>{{ ucfirst($day) }}</strong>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input day-toggle" type="checkbox" 
                                                id="{{ 'switch-' . $index }}"
                                                name="days[{{ $day }}][active]"
                                                onchange="toggleStatus(this, '{{ 'header-' . $index }}', '{{ 'inputs-' . $index }}')"
                                                {{ $slot && $slot->start_time ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" id="{{ 'inputs-' . $index }}">
                                    <div class="mb-3">
                                        <label for="{{ 'start-' . $index }}" class="form-label">
                                            <i class="fas fa-clock me-1"></i>Hora de início
                                        </label>
                                        <input type="time" 
                                            class="form-control time-input"
                                            id="{{ 'start-' . $index }}"
                                            name="days[{{ $day }}][start_time]"
                                            value="{{ $slot->start_time ?? '' }}"
                                            {{ $slot && $slot->start_time ? '' : 'disabled' }}>
                                        <small class="text-muted">HH:MM</small>
                                    </div>
                                    <div class="mb-2">
                                        <label for="{{ 'end-' . $index }}" class="form-label">
                                            <i class="fas fa-clock me-1"></i>Hora de término
                                        </label>
                                        <input type="time" 
                                            class="form-control time-input"
                                            id="{{ 'end-' . $index }}"
                                            name="days[{{ $day }}][end_time]"
                                            value="{{ $slot->end_time ?? '' }}"
                                            {{ $slot && $slot->start_time ? '' : 'disabled' }}>
                                        <small class="text-muted">HH:MM</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="text-center mt-5">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save me-2"></i>Salvar Alterações
                </button>
                <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>

    <style>
        .time-input {
            font-size: 1.1rem;
            padding: 0.75rem;
            text-align: center;
            letter-spacing: 0.1rem;
            font-family: 'Courier New', monospace;
        }
        
        .time-input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .time-input:enabled:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .card {
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        
        .card-header {
            transition: background-color 0.3s;
            border-bottom: 2px solid transparent;
        }
        
        .form-check-input {
            cursor: pointer;
            width: 2.5rem;
            height: 1.5rem;
        }
        
        .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>

    <script>
        function toggleStatus(element, headerId, inputsId) {
            const header = document.getElementById(headerId);
            const inputsContainer = document.getElementById(inputsId);
            const inputs = inputsContainer.querySelectorAll('.time-input');
            
            if (element.checked) {
                header.style.backgroundColor = '#e2ffee';
                inputs.forEach(input => {
                    input.disabled = false;
                });
            } else {
                header.style.backgroundColor = '#ffe5e2';
                inputs.forEach(input => {
                    input.disabled = true;
                    input.value = '';
                });
            }
        }
        
        // Permitir edição dos inputs de hora com validação
        document.querySelectorAll('.time-input').forEach(input => {
            input.addEventListener('input', function(e) {
                // Remove caracteres não numéricos
                let value = this.value.replace(/[^\d:]/g, '');
                
                // Formata como HH:MM
                if (value.length === 2 && !value.includes(':')) {
                    value = value + ':';
                }
                
                // Limita a 5 caracteres (HH:MM)
                if (value.length > 5) {
                    value = value.substring(0, 5);
                }
                
                this.value = value;
            });
            
            // Validar formato ao sair do campo
            input.addEventListener('blur', function(e) {
                const value = this.value;
                const timeRegex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
                
                if (value && !timeRegex.test(value)) {
                    this.classList.add('is-invalid');
                    this.title = 'Formato inválido. Use HH:MM (00:00 a 23:59)';
                } else {
                    this.classList.remove('is-invalid');
                    this.title = '';
                }
            });
            
            // Remover validação ao focar
            input.addEventListener('focus', function(e) {
                this.classList.remove('is-invalid');
            });
        });
    </script>
@endsection
