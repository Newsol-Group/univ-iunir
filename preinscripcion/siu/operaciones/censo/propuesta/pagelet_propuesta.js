function cerrar_popup()
{
    $.facebox.close();
}

kernel.renderer.registrar_pagelet('propuesta', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {

            $('#btnSelectorCarrera').on('click', function () {
                $.facebox(info.selector_propuesta);
            });

            inicializar_popup();

            $('.js-observaciones-icon').on('click', function () {
                var id = $(this).data('id');
                $(".js-observaciones#"+id).toggle();
            });

            $('.btnEliminar').on('click', function () {
                var ok = accion_eliminar($(this).data('tipo_de_propuesta'), $(this).data('propuesta_nombre'));
                if (ok) {
                    $.ajax({
                        url: info.accion_eliminar,
                        type: 'post',
                        dataType: 'json',
                        data: {id: $(this).data('id')},
                        async: true,
                        success: function (data) {
                            location.reload();
                        }
                    });
                }
            });
            
        }
    }

    function accion_eliminar(tipo_de_propuesta, propuesta_nombre)
    {
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

    function inicializar_popup()
    {
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

    function validar()
    {
        var valido;

        valido = validar_campo('responsable_academica');
        valido = valido & validar_campo('propuesta');
        valido = valido & validar_campo('ubicacion');
        valido = valido & validar_campo('modalidad');

        return valido;
    }

    function validar_campo(campo)
    {
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

    function registrar_cascadas()
    {
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

    function cambio_responsable_academica()
    {
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

    function cambio_propuesta()
    {
        var propuesta = $('#form_propuesta-propuesta').val();
        if (propuesta !== no_seleccion) {
            buscar_ubicaciones(propuesta);
            set_solo_lectura('form_propuesta-ubicacion', false);
        } else {
            resetear_ubicaciones();
            resetear_modalidades();
        }
    }

    function cambio_ubicacion()
    {
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

    function resetear_propuestas()
    {
        set_solo_lectura('form_propuesta-propuesta', true);
        $('#form_propuesta-propuesta').children().remove();
        resetear_ubicaciones();
        resetear_modalidades();
    }

    function resetear_ubicaciones()
    {
        set_solo_lectura('form_propuesta-ubicacion', true);
        $('#form_propuesta-ubicacion').children().remove();
    }

    function resetear_modalidades()
    {
        set_solo_lectura('form_propuesta-modalidad', true);
        $('#form_propuesta-modalidad').children().remove();
    }

    //--------------------------------------------------------------------------

    function buscar_propuestas(_responsable_academica)
    {
        $.ajax({
            url: info.url_propuestas,
            dataType: 'json',
            data: {ra: _responsable_academica},
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

    function buscar_ubicaciones(_propuesta)
    {
        $.ajax({
            url: info.url_ubicaciones,
            dataType: 'json',
            data: {propuesta: _propuesta},
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

    function buscar_modalidades(_propuesta, _ubicacion)
    {
        $.ajax({
            url: info.url_modalidades,
            dataType: 'json',
            data: {propuesta: _propuesta, ubicacion: _ubicacion},
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