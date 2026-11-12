{{--
    Vuelve a abrir el modal cuyo formulario se envió cuando el servidor devuelve
    errores de validación. Cada formulario incluye un campo oculto _modal con
    el id de su modal.
--}}
@if($errors->any() && old('_modal'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById(@json(old('_modal')));
            if (modal && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    </script>
@endif
