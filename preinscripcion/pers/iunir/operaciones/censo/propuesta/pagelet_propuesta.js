function cerrar_popup() {
    $.facebox.close();
}

kernel.renderer.registrar_pagelet('propuesta', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {

            $('#js-div_mensaje').hide();

            $('#btnSelectorCarrera').on('click', function () {
                $.facebox(info.selector_propuesta);
            });

            inicializar_popup();

            $('.js-observaciones-icon').on('click', function () {
                var id = $(this).data('id');
                $(".js-observaciones#" + id).toggle();
            });

            $('.btnPagarMCP').on('click', function () {
                var ok = accion_pagar_mcp($(this).data('propuesta_nombre')); //confirm("¿Está seguro que desea proceder al pago de la/s propuesta/s " + $(this).data('propuestas_nombres') + "?");
                const $btn = $(this);

                if (ok) {
                    $('#js-div_mensaje').html(info.msj_preparando_pago);
                    $('#js-div_mensaje').show();

                    $.ajax({
                        url: info.url_pagar_mcp,
                        type: 'post',
                        dataType: 'json',
                        data:
                        {
                            id: $(this).data('id'),
                            codfor: $(this).data('codfor'),
                            nrofor: $(this).data('nrofor')
                        },
                        async: true,
                        success: function (data) {
                            $('#js-div_mensaje').hide();
                            $('#js-div_mensaje').html('');

                            $form = $('#payForm_' + data.preinscripcion_propuesta);
                            if (!$form) return;
                            $form.find('input[name="TransaccionComercioId"]').val(data.trans_id);
                            $form.submit();
                        }
                    });
                }
            });

            $('.btnPagarMP').on('click', function () {
                var ok = accion_pagar_mp($(this).data('prop_nombre'));
                var link = $(this).data('link');
                if (ok) {
                    $.ajax({
                        url: info.url_pagar_mp,
                        type: 'post',
                        dataType: 'json',
                        data: { id: $(this).data('id') },
                        async: true,
                        success: function (data) {
                            if (data.link_err) {
                                alert('Pago no disponible');
                                return;
                            }

                            window.open(link, '_blank', 'noopener,noreferrer');
                        }
                    });
                }
            });

            $('.btnEliminar').on('click', function () {
                var ok = accion_eliminar($(this).data('tipo_de_propuesta'), $(this).data('propuesta_nombre'));
                if (ok) {
                    $.ajax({
                        url: info.accion_eliminar,
                        type: 'post',
                        dataType: 'json',
                        data: { id: $(this).data('id') },
                        async: true,
                        success: function (data) {
                            location.reload();
                        }
                    });
                }
            });

            $('.btnFinalizar').on('click', function () {
                var ok = accion_finalizar($(this).data('propuestas_nombres')); //confirm("¿Está seguro que desea finalizar la propuesta?");
                const $btn = $(this);
                $btn.prop('disabled', true);

                if (ok) {

                    $('#js-div_mensaje').html(info.msj_finalizando);
                    $('#js-div_mensaje').show();
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: info.accion_finalizar,
                        type: 'post',
                        dataType: 'json',
                        data: { id: $(this).data('id') },
                        async: true,
                        success: function (data) {

                            $('#js-div_mensaje').hide();
                            $('#js-div_mensaje').html('');

                            if (data.estado && data.estado === 'error') {
                                alert(data.mensaje);
                                $btn.prop('disabled', false);

                            } else {
                                location.reload();
                            }
                        }
                    });
                }
            });

            setInterval(async () => {
                for (const predata of info.preinscripciones) {
                    const r = await fetch('https://preinscripcion.iunir.edu.ar/pagos/webhook.php?from=pre', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            pid: predata.id_preinscripcion,
                            ppid: predata.preinscripcion_propuesta
                        })
                    });

                    const data = await r.json();
                    const pe_disabled = data.estado_pago == 3;

                    const $btn_pagar = $('#btn-pagar-' + predata.preinscripcion_propuesta);
                    if ($btn_pagar) {
                        $btn_pagar.prop('disabled', pe_disabled);
                    }
                    
                    const $btn_eliminar = $('#btn-eliminar-' + predata.preinscripcion_propuesta);
                    if ($btn_eliminar) {
                       $btn_eliminar.prop('disabled', pe_disabled);
                    }
                }
            }, 3000); 
        }
    }

    function accion_pagar_mcp(propuesta_nombre) {
        var mensaje = info.msj_confirmar_pagar_mcp + propuesta_nombre;
        return confirm(mensaje);
    }

    function accion_pagar_mp(propuesta_nombre) {
        var mensaje = info.msj_confirmar_pagar_mp + propuesta_nombre;
        return confirm(mensaje);
    }


    function accion_finalizar(propuestas_nombres) {
        var mensaje = info.msj_confirmar_finalizar + propuestas_nombres + '?';
        return confirm(mensaje);
    }

    function accion_eliminar(tipo_de_propuesta, propuesta_nombre) {
        var mensaje;
        if ((tipo_de_propuesta === parseInt(info.tipo_POSGRADO)) && (parseInt(info.cantidad_insc_posgrado) === 1)) {
            mensaje = info.msj_una_insc_posgrado_baja;
        } else {
            mensaje = info.msj_confirmar_baja + propuesta_nombre + '?';
        }
        if (confirm(mensaje)) {
            return true;
        }
        return false;
    }

    function inicializar_popup() {
        registrar_cascadas();

        $(document).on("click", "#guardar_propuesta", function (event) {

            event.preventDefault();

            $(this).attr('disabled', true);
            $(this).prop('disabled', true);
            $(this).addClass('disabled');

            if (validar()) {
                $('#form_propuesta').submit();
                cerrar_popup();
                return true;
            } else {
                $(this).attr('disabled', false);
                $(this).prop('disabled', false);
                $(this).removeClass('disabled');
                return false;
            }

        });
    }

    function validar() {
        var valido;

        valido = validar_campo('responsable_academica');
        valido = valido & validar_campo('propuesta');
        valido = valido & validar_campo('ubicacion');
        valido = valido & validar_campo('modalidad');

        return valido;
    }

    function validar_campo(campo) {
        var valor = $('#form_propuesta-' + campo).val();
        if ((valor === no_seleccion) || (valor === null)) {
            $('#msg-' + campo).html(info.error_vacio);
            $('#cg-' + campo).addClass('error');
            return false;
        } else {
            $('#msg-' + campo).html('');
            $('#cg-' + campo).removeClass('error');
            return true;
        }
    }

    //--------------------------------------------------------------------------
    //---- Manejo de cascadas --------------------------------------------------
    //--------------------------------------------------------------------------

    function registrar_cascadas() {
        $(document).on('change', '#form_propuesta-responsable_academica', function () {
            cambio_responsable_academica();
        });

        $(document).on('change', '#form_propuesta-propuesta', function () {
            cambio_propuesta();
        });

        $(document).on('change', '#form_propuesta-ubicacion', function () {
            cambio_ubicacion();
        });
    }

    //--------------------------------------------------------------------------

    function cambio_responsable_academica() {
        var ra = $('#form_propuesta-responsable_academica').val();
        if (ra !== no_seleccion) {
            buscar_propuestas(ra);
            set_solo_lectura('form_propuesta-propuesta', false);
        } else {
            resetear_propuestas();
        }
        resetear_ubicaciones();
        resetear_modalidades();
    }

    function cambio_propuesta() {
        var propuesta = $('#form_propuesta-propuesta').val();
        if (propuesta !== no_seleccion) {
            buscar_ubicaciones(propuesta);
            set_solo_lectura('form_propuesta-ubicacion', false);
        } else {
            resetear_ubicaciones();
            resetear_modalidades();
        }
    }

    function cambio_ubicacion() {
        var ubicacion = $('#form_propuesta-ubicacion').val();
        var propuesta = $('#form_propuesta-propuesta').val();
        if (ubicacion !== no_seleccion) {
            buscar_modalidades(propuesta, ubicacion);
            set_solo_lectura('form_propuesta-modalidad', false);
        } else {
            resetear_modalidades();
        }
    }

    //--------------------------------------------------------------------------

    function resetear_propuestas() {
        set_solo_lectura('form_propuesta-propuesta', true);
        $('#form_propuesta-propuesta').children().remove();
        resetear_ubicaciones();
        resetear_modalidades();
    }

    function resetear_ubicaciones() {
        set_solo_lectura('form_propuesta-ubicacion', true);
        $('#form_propuesta-ubicacion').children().remove();
    }

    function resetear_modalidades() {
        set_solo_lectura('form_propuesta-modalidad', true);
        $('#form_propuesta-modalidad').children().remove();
    }

    //--------------------------------------------------------------------------

    function buscar_propuestas(_responsable_academica) {
        $.ajax({
            url: info.url_propuestas,
            dataType: 'json',
            data: { ra: _responsable_academica },
            type: 'post',
            success: function (data) {
                $('#form_propuesta-propuesta').children().remove();
                $.each(data, function (key, value) {
                    $('#form_propuesta-propuesta').append(
                        $('<option></option>').val(key).html(value)
                    );
                });
                ordenar_combo('form_propuesta-propuesta');
            }
        });
    }

    function buscar_ubicaciones(_propuesta) {
        $.ajax({
            url: info.url_ubicaciones,
            dataType: 'json',
            data: { propuesta: _propuesta },
            type: 'post',
            success: function (data) {
                $('#form_propuesta-ubicacion').children().remove();
                $.each(data, function (key, value) {
                    $('#form_propuesta-ubicacion').append(
                        $('<option></option>').val(key).html(value)
                    );
                });
                ordenar_combo('form_propuesta-ubicacion');
            }
        });
    }

    function buscar_modalidades(_propuesta, _ubicacion) {
        $.ajax({
            url: info.url_modalidades,
            dataType: 'json',
            data: { propuesta: _propuesta, ubicacion: _ubicacion },
            type: 'post',
            success: function (data) {
                $('#form_propuesta-modalidad').children().remove();
                $.each(data, function (key, value) {
                    $('#form_propuesta-modalidad').append(
                        $('<option></option>').val(key).html(value)
                    );
                });
                ordenar_combo('form_propuesta-modalidad');
            }
        });
    }

});