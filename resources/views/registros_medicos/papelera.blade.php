@extends('layouts.app_layout')

@section('titulo', 'Papelera de expedientes')

@section('contenido')
    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">delete_sweep</span>
                </div>
                <div>
                    <h1 class="hw-title">Papelera de expedientes</h1>
                    <p class="hw-subtitle">Registros médicos eliminados. Solo el administrador puede restaurarlos.</p>
                </div>
            </div>
            <a href="{{ route('registros_medicos') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver a expedientes
            </a>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Registros médicos eliminados</h2>
                    <p class="hw-surface-description">Al restaurarlos vuelven a aparecer en el expediente del paciente.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $registrosMedicos->count() }} registros</span>
                </div>
            </div>
            @if($registrosMedicos->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    <h3>La papelera está vacía</h3>
                    <p>Los registros médicos eliminados aparecerán aquí.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="hw-column-id">ID</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th>Fecha del registro</th>
                                <th>Eliminado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($registrosMedicos as $item)
                                <tr>
                                    <td class="hw-id">#{{ $item->id }}</td>
                                    <td>{{ trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? '')) ?: '—' }}</td>
                                    <td>{{ trim(($item->doctor?->usuario?->nombres ?? '') . ' ' . ($item->doctor?->usuario?->apellidos ?? '')) ?: '—' }}</td>
                                    <td>{{ $item->fecha_registro ? $item->fecha_registro->format('d/m/Y H:i') : '—' }}</td>
                                    <td>{{ $item->deleted_at ? $item->deleted_at->format('d/m/Y H:i') : '—' }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('registros_medicos.restaurar', ['id' => $item->id]) }}" class="d-inline"
                                              data-confirmar="¿Restaurar este registro médico?">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-tonal btn-sm">
                                                <span class="material-symbols-outlined">restore_from_trash</span>
                                                Restaurar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/paciente/papelera.js') }}"></script>
@endpush
