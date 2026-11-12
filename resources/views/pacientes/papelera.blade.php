@extends('layouts.app_layout')

@section('titulo', 'Papelera de pacientes')

@section('contenido')
    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">delete_sweep</span>
                </div>
                <div>
                    <h1 class="hw-title">Papelera de pacientes</h1>
                    <p class="hw-subtitle">Pacientes eliminados. Al restaurarlos recuperan su historial clínico.</p>
                </div>
            </div>
            <a href="{{ route('pacientes') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver a pacientes
            </a>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Pacientes eliminados</h2>
                    <p class="hw-surface-description">Solo el administrador puede restaurarlos.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $pacientes->count() }} registros</span>
                </div>
            </div>
            @if($pacientes->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    <h3>La papelera está vacía</h3>
                    <p>Los pacientes eliminados aparecerán aquí.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="hw-column-id">ID</th>
                                <th>Paciente</th>
                                <th>DUI</th>
                                <th>Eliminado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pacientes as $item)
                                <tr>
                                    <td class="hw-id">#{{ $item->id }}</td>
                                    <td>{{ trim($item->nombres . ' ' . $item->apellidos) }}</td>
                                    <td class="hw-mono">{{ $item->numero_identificacion }}</td>
                                    <td>{{ $item->deleted_at ? $item->deleted_at->format('d/m/Y H:i') : '—' }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('pacientes.restaurar', ['id' => $item->id]) }}" class="d-inline"
                                              data-confirmar="¿Restaurar a este paciente?">
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
