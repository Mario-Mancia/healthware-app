{{--
    Campo de DUI del paciente con confirmación del nombre.

    Variables (todas opcionales):
      $nombre     name del input            (por defecto dui_paciente)
      $id         id del input              (por defecto dui_paciente)
      $valor      valor inicial             (por defecto old($nombre))
      $requerido  true / false              (por defecto true)
      $etiqueta   texto de la etiqueta      (por defecto "DUI del paciente")

    El script public/js/hw/hw-dui.js consulta la ruta pacientes.dui y muestra
    debajo el nombre del paciente para confirmarlo.
--}}
@php
    $campoNombre = $nombre ?? 'dui_paciente';
    $campoId = $id ?? 'dui_paciente';
    $campoValor = $valor ?? old($campoNombre);
    $campoRequerido = $requerido ?? true;
@endphp
<div class="hw-dui" data-hw-dui data-dui-url="{{ route('pacientes.dui', ['dui' => '__DUI__']) }}">
    <div class="form-floating">
        <input type="text"
               name="{{ $campoNombre }}"
               id="{{ $campoId }}"
               class="form-control"
               placeholder="00000000-0"
               maxlength="10"
               inputmode="numeric"
               autocomplete="off"
               value="{{ $campoValor }}"
               data-dui-paciente
               @if($campoRequerido) required @endif>
        <label for="{{ $campoId }}">{{ $etiqueta ?? 'DUI del paciente' }}</label>
    </div>
    <div class="hw-dui-resultado" data-dui-resultado aria-live="polite"></div>
</div>
