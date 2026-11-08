{{--
    The CI/RUC field plus its people-database lookup.

    The document goes first wherever a client is created, because it is the key
    that fills in the rest and it is also what tells us the person is already a
    client. One partial serves every mount, so a fix lands in both at once
    instead of drifting apart.

    Required: idDocumento, idNombre, idApellido.
    Optional: nameDocumento, idFechaNac, idFoco (what to focus after a hit),
              resetOn ("selector|evento" that clears the notice), yaRegistrado
              (the sentence appended when the document is already a client).
--}}
@php
    $idDocumento   = $idDocumento   ?? 'clie_ci';
    $nameDocumento = $nameDocumento ?? 'clie_ci';
    $idNombre      = $idNombre      ?? 'clie_nombre';
    $idApellido    = $idApellido    ?? 'clie_apellido';
    $idFechaNac    = $idFechaNac    ?? null;
    $idFoco        = $idFoco        ?? null;
    $resetOn       = $resetOn       ?? null;
    $yaRegistrado  = $yaRegistrado  ?? 'Buscalo en la lista de clientes.';
    $valor         = $valor         ?? null;
@endphp

<div class="cliente-lookup mb-4"
     data-lookup-url="{{ route('clientes.consultar.documento') }}"
     data-nombre="#{{ $idNombre }}"
     data-apellido="#{{ $idApellido }}"
     @if ($idFechaNac) data-fecha-nac="#{{ $idFechaNac }}" @endif
     @if ($idFoco) data-foco="#{{ $idFoco }}" @endif
     @if ($resetOn) data-reset-on="{{ $resetOn }}" @endif
     data-ya-registrado="{{ $yaRegistrado }}">

    <label for="{{ $idDocumento }}" class="d-block">
        CI / RUC <span class="text-danger" aria-hidden="true">*</span>
    </label>

    <div class="d-flex flex-column flex-sm-row" style="gap: 0.5rem;">
        <input type="text" class="form-control lookup-documento"
               id="{{ $idDocumento }}" name="{{ $nameDocumento }}"
               value="{{ old($nameDocumento, $valor) }}"
               inputmode="numeric" autocomplete="off"
               placeholder="1234567 o 80012345-1"
               aria-describedby="{{ $idDocumento }}-ayuda">
        <button type="button"
                class="btn btn-primary lookup-boton d-inline-flex align-items-center justify-content-center flex-shrink-0">
            <i class="fas fa-search mr-2" aria-hidden="true"></i>
            <span>Buscar</span>
        </button>
    </div>

    <small class="text-muted d-block mt-1" id="{{ $idDocumento }}-ayuda">
        Buscamos el nombre en la base de personas. Si no aparece, cargalo a mano.
    </small>
    <small class="text-danger d-none" id="error_{{ $nameDocumento }}"></small>
    <div class="alert mt-2 mb-0 py-2 px-3 d-none lookup-estado" role="status"></div>
</div>

@once
@push('scripts')
<script>
(function () {
    // Every .cliente-lookup on the page wires itself from its own data
    // attributes, so a new mount needs no JavaScript of its own.
    function cablear(raiz) {
        const $raiz = $(raiz);
        const $documento = $raiz.find('.lookup-documento');
        const $boton = $raiz.find('.lookup-boton');
        const $estado = $raiz.find('.lookup-estado');
        const url = $raiz.data('lookup-url');

        // The targets live outside this block, so they are resolved lazily:
        // in a modal they may not exist yet when this runs.
        function destino(clave) {
            const selector = $raiz.data(clave);
            return selector ? $(selector) : $();
        }

        let enCurso = null;
        let ultimaConsulta = '';

        function mostrarEstado(tipo, html) {
            $estado
                .removeClass('d-none alert-success alert-warning alert-info')
                .addClass(tipo)
                .html(html);
        }

        function limpiarEstado() {
            $estado.addClass('d-none').empty();
        }

        function cargando(activo) {
            $boton.prop('disabled', activo);
            $boton.find('i')
                .toggleClass('fa-search', !activo)
                .toggleClass('fa-spinner fa-spin', activo);
            $boton.find('span').text(activo ? 'Buscando...' : 'Buscar');
        }

        function buscar() {
            const documento = $.trim($documento.val());

            if (documento === '') {
                mostrarEstado('alert-warning', 'Escribí un CI o RUC para buscar.');
                $documento.trigger('focus');
                return;
            }

            // Evita repetir la misma consulta cuando el campo no cambió.
            if (documento === ultimaConsulta && enCurso === null) {
                return;
            }

            if (enCurso) { enCurso.abort(); }

            ultimaConsulta = documento;
            cargando(true);
            mostrarEstado('alert-info', 'Buscando la persona...');

            enCurso = $.getJSON(url, { documento: documento })
                .done(function (respuesta) {
                    if (respuesta.estado === 'encontrado') {
                        const datos = respuesta.datos || {};

                        destino('nombre').val(datos.nombre || '');
                        destino('apellido').val(datos.apellido || '');
                        if (datos.documento) { $documento.val(datos.documento); }
                        if (datos.fecha_nacimiento) {
                            destino('fecha-nac').val(datos.fecha_nacimiento);
                        }

                        const nombre = $.trim((datos.nombre || '') + ' ' + (datos.apellido || ''));
                        // A RUC that is not ACTIVO still gets found, and the
                        // receipt would be issued against it without anyone
                        // noticing, so the whole notice turns into a warning.
                        const situacion = $.trim(datos.estado || '').toUpperCase();
                        const irregular = situacion !== '' && situacion !== 'ACTIVO';
                        const esEmpresa = datos.tipo === 'empresa';

                        mostrarEstado(irregular ? 'alert-warning' : 'alert-success', 'Encontrado: <strong></strong>.');
                        $estado.find('strong').text(nombre);

                        if (irregular) {
                            $estado.append(' Figura como <strong></strong> en la base: confirmá antes de emitirle.');
                            $estado.find('strong').last().text(situacion);
                        }

                        if (esEmpresa) {
                            $estado.append(' Es una empresa, así que el apellido queda vacío.');
                        } else if (!datos.apellido) {
                            // Some records keep the whole name in one field.
                            // Splitting "APELLIDO APELLIDO NOMBRE NOMBRE" by
                            // guessing gets it wrong often enough to ask.
                            $estado.append(' Vino el nombre completo en un solo campo: separá el apellido si hace falta.');
                        }

                        // A company has no birth date, so saying one is missing
                        // would be noise on every single company.
                        if (!esEmpresa && !datos.fecha_nacimiento && destino('fecha-nac').length) {
                            $estado.append(' Falta la fecha de nacimiento: cargala a mano.');
                        }

                        const $foco = destino('foco');
                        if ($foco.length) { $foco.trigger('focus'); }
                        return;
                    }

                    if (respuesta.estado === 'ya_registrado') {
                        const cliente = respuesta.cliente || {};
                        mostrarEstado('alert-warning', 'Ese documento ya es cliente: <strong></strong>. ');
                        $estado.find('strong').text(cliente.nombre || '');
                        $estado.append(document.createTextNode($raiz.data('ya-registrado') || ''));
                        return;
                    }

                    mostrarEstado('alert-warning', respuesta.message || 'No encontramos ese documento. Cargá los datos a mano.');
                    destino('nombre').trigger('focus');
                })
                .fail(function (xhr, estadoTexto) {
                    if (estadoTexto === 'abort') { return; }

                    ultimaConsulta = '';
                    const respuesta = xhr.responseJSON || {};
                    mostrarEstado(
                        'alert-warning',
                        respuesta.message || 'No pudimos hacer la búsqueda. Cargá los datos a mano.'
                    );
                })
                .always(function () {
                    enCurso = null;
                    cargando(false);
                });
        }

        $boton.on('click', buscar);

        // Enter busca en lugar de enviar el formulario.
        $documento.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscar();
            }
        });

        $documento.on('blur', function () {
            if ($.trim($(this).val()) !== '') { buscar(); }
        });

        $documento.on('input', limpiarEstado);

        const reset = $raiz.data('reset-on');
        if (reset) {
            const partes = String(reset).split('|');
            $(partes[0]).on(partes[1], function () {
                limpiarEstado();
                ultimaConsulta = '';
            });
        }
    }

    $(function () {
        $('.cliente-lookup').each(function () { cablear(this); });
    });
})();
</script>
@endpush
@endonce
