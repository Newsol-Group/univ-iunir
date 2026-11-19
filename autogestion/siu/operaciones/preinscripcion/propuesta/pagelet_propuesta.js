var no_seleccion_preinsc = '';

function set_solo_lectura(elemId, solo_lectura)
{
    if (solo_lectura) {
        $('#' + elemId).attr('readonly', 'readonly');
    } else {
        $('#' + elemId).removeAttr("readonly");
    }
}

/*
 * Ordena las opciones de un combo. Necesario cuando se carga vía AJAX.
 */
function order_combo(idSelect)
{
    var opcion_no_seleccion = $('#' + idSelect + " option[value='']");
    var ops = $('#' + idSelect + " option:not(option[value=''])");

    // Se comparan todos los elementos del array
    ops.sort(function (a, b) {
        return ($(a).html().toUpperCase() < $(b).html().toUpperCase()) ? -1 : ($(a).html().toUpperCase() > $(b).html().toUpperCase()) ? 1 : 0;
    });

    // Primero se agrega la opción de no selección
    var html;
    if (opcion_no_seleccion.length > 0) {
        html = "<option value='" + $(opcion_no_seleccion[0]).val() + "'>" + $(opcion_no_seleccion[0]).html() + "</option>";
    } else {
        html = '';
    }

    // Se agregan las opciones ordenadas por descripción.
    for (var i = 0; i < ops.length; i++) {
        html += "<option value='" + $(ops[i]).val() + "'>" + $(ops[i]).html() + "</option>";
    }

    // Se insertan las opciones en el select
    $('#' + idSelect).html(html);
}

function cerrar_popup()
{
    $.facebox.close();
}

kernel.renderer.registrar_pagelet('propuesta', function (info)
{
    var id = '#' + info.id;

    return {
        onload: function ()
        {
            $('#btnSelectorCarrera').on('click', function ()
            {
                $.facebox(info.selector_propuesta);
                inicializar_popup();
            });

            $('.btnEliminar').on('click', function ()
            {
                var ok = accion_eliminar($(this).data('propuesta_nombre'));
                if (ok) {
                    $.ajax({
                        url: info.accion_eliminar,
                        type: 'post',
                        dataType: 'json',
                        data: {id: $(this).data('id')},
                        async: true,
                        success: function (data)
                        {
                            location.reload();
                        }
                    });
                }
            });
          $('.btnFinalizar').on('click', function ()
            {
                var ok = accion_finalizar($(this).data('propuesta_nombre'));
                if (ok) {
                    $.ajax({
                        url: info.accion_finalizar,
                        type: 'post',
                        dataType: 'json',
                        data: {id: $(this).data('id')},
                        async: true,
                        success: function (data)
                        {
                            location.reload();
                        }
                    });
                }
            });

$('.btnReabrir').on('click', function ()
            {
                    $.ajax({
                        url: info.accion_finalizar,
                        type: 'post',
                        dataType: 'json',
                        data: {id: $(this).data('id')},
                        async: true,
                        success: function (data)
                        {
                            location.reload();
                        }
                    });
                
            });
            if (info.autosave) {
                $(id).autosave();
            }
        }
    }

    function accion_eliminar(propuesta_nombre)
    {
        var mensaje = info.msj_confirmar_baja + propuesta_nombre + '?';
        if (confirm(mensaje)) {
            return true;
        }
        return false;
    }   function accion_finalizar(propuesta_nombre)
    {
        var mensaje = info.msj_confirmar_finalizar + propuesta_nombre + '?';
        if (confirm(mensaje)) {
            return true;
        }
        return false;
    }

    function inicializar_popup()
    {
        $('#form_propuesta-responsable_academica').val('');
        registrar_cascadas();

        $('#form_propuesta').submit(function ()
        {
            if (validar()) {
                return true;
            } else {
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
        if ((valor === no_seleccion_preinsc) || (valor === null)) {
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
        $('#form_propuesta-responsable_academica').on('change', function () {
            cambio_responsable_academica();
        });
        $('#form_propuesta-propuesta').on('change', function () {
            cambio_propuesta();
        });
        $('#form_propuesta-ubicacion').on('change', function () {
            cambio_ubicacion();
        });

    }

    //--------------------------------------------------------------------------

    function cambio_responsable_academica()
    {
        var ra = $('#form_propuesta-responsable_academica').val();
        if (ra !== no_seleccion_preinsc) {
            buscar_propuestas(ra);
            set_solo_lectura('form_propuesta-propuesta', false);
        } else {
            resetear_propuestas();
        }
    }

    function cambio_propuesta()
    {
        var propuesta = $('#form_propuesta-propuesta').val();
        if (propuesta !== no_seleccion_preinsc) {
            buscar_ubicaciones(propuesta);
            set_solo_lectura('form_propuesta-ubicacion', false);
        } else {
            resetear_ubicaciones();
        }
    }

    function cambio_ubicacion()
    {
        var propuesta = $('#form_propuesta-propuesta').val();
        var ubicacion = $('#form_propuesta-ubicacion').val();
        if ((propuesta !== no_seleccion_preinsc) && (ubicacion !== no_seleccion_preinsc)) {
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
    }

    function resetear_ubicaciones()
    {
        set_solo_lectura('form_propuesta-ubicacion', true);
        $('#form_propuesta-ubicacion').children().remove();
        resetear_modalidades();
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
                order_combo('form_propuesta-propuesta');
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
                order_combo('form_propuesta-ubicacion');
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
                order_combo('form_propuesta-modalidad');
            }
        });
    }

});
