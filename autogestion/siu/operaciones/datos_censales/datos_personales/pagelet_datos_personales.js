var _info;

function copiar_domicilio(origen, destino)
{
    $('#form_datos_personales-calle_' + destino).val($('#form_datos_personales-calle_' + origen).val());
    $('#form_datos_personales-piso_' + destino).val($('#form_datos_personales-piso_' + origen).val());
    $('#form_datos_personales-dpto_' + destino).val($('#form_datos_personales-dpto_' + origen).val());
    $('#form_datos_personales-unidad_' + destino).val($('#form_datos_personales-unidad_' + origen).val());
    $('#form_datos_personales-loc_' + destino).val($('#form_datos_personales-loc_' + origen).val());
    $('#form_datos_personales-loc_' + destino + '_descr').val($('#form_datos_personales-loc_' + origen + '_descr').val());
    $('#form_datos_personales-cp_' + destino).val($('#form_datos_personales-cp_' + origen).val());
    $('#form_datos_personales-te_' + destino).val($('#form_datos_personales-te_' + origen).val());

    //--------------------------------------------------------------------------
    var origen_barrio;
    var destino_barrio;
    if (origen === 'per_lect') {
        origen_barrio = 'per_lec';
    } else {
        origen_barrio = origen;
    }
    if (destino === 'pers_alleg') {
        destino_barrio = 'alleg';
    } else {
        destino_barrio = destino;
    }
    $('#form_datos_personales-barrio_' + destino_barrio).val($('#form_datos_personales-barrio_' + origen_barrio).val());
    //--------------------------------------------------------------------------

    if (destino === 'pers_alleg') {
        $('#form_datos_personales-nro_' + destino).val($('#form_datos_personales-numero_' + origen).val());
    } else {
        $('#form_datos_personales-numero_' + destino).val($('#form_datos_personales-numero_' + origen).val());
    }

    if ($("#form_datos_personales-loc_" + destino + "_descr").val() !== '') {
        set_solo_lectura('form_datos_personales-loc_' + destino + '_descr', true);
        $("#loc_" + destino + "_descr_help").hide();
    } else {
        set_solo_lectura('form_datos_personales-loc_' + destino + '_descr', false);
        $("#loc_" + destino + "_descr_help").show();
    }

//	handle_combo_pais_localidad('form_datos_personales-pais_'+destino, 'form_datos_personales-loc_'+destino);
}

//------------------------------------------------------------------------------

kernel.renderer.registrar_pagelet('datos_personales', function (info) {
    var id = '#' + info.id;
    _info = info;
    
    var hubo_errores = false;

    return {
        onload: function () {
            //----arreglo jquery para mostrar ayuda 
            $("#form_datos_personales-identidad_genero-label").html($("#form_datos_personales-identidad_genero-label").text() + $("#ayuda_genero").html());
            $("#ayuda_genero").hide();
            
            $('#form_datos_personales-nombres_elegido-label').after($("#leyenda").text());
            $("#leyenda").hide();
            //---- Manejo de dependencias pueblos originarios ------------------
            cambio__pertenece_pueblo_originario();
            cambio__pueblo_originario();
            cambio__pueblo_originario_otro();
            
            cambio__identidad_genero();
            verifica_cuil_vacio();
            
            // Cambios en el combo "¿Pertenecés a algún pueblo originario?"
            $('#form_datos_personales-pertenece_pueblo_originario').on('change', function () {
                cambio__pertenece_pueblo_originario();
            });
            
            // Cambios en el combo "¿A cuál?"
            $('#form_datos_personales-pueblo_originario').on('change', function () {
                cambio__pueblo_originario();
            });
            
            // Cambios en el input "Nombre"
            $('#form_datos_personales-pueblo_originario_otro').on('change', function () {
                cambio__pueblo_originario_otro();
            });
            
            $('#form_datos_personales-sexo').attr('disabled', 'disabled');
            $('#form_datos_personales-tipo_documento').attr('disabled', 'disabled');
            
            //---- Copia de domicilio ------------------------------------------
            $('#btn_copiar_domicilio__proc__per_lect').on('click', function () {
                copiar_domicilio('per_lect', 'proc');
            });

            $('#btn_copiar_domicilio__pers_alleg__per_lect').on('click', function () {
                copiar_domicilio('per_lect', 'pers_alleg');
            });

            $('#btn_copiar_domicilio__pers_alleg__proc').on('click', function () {
                copiar_domicilio('proc', 'pers_alleg');
            });
            $('#form_datos_personales-identidad_genero').on('change', function () {
                cambio__identidad_genero();
            });
            $('#form_datos_personales-identidad_genero').tooltip('show');

            //------------------------------------------------------------------
            //---- Selector de localidad (período de clases) -------------------
            //------------------------------------------------------------------

            //---- Combo de países para el selector de localidades -------------
//			handle_combo_pais_localidad('form_datos_personales-pais_per_lect', 'form_datos_personales-loc_per_lect');

            //---- Lista de localidades (autocompletado) -----------------------
            if ($("#form_datos_personales-loc_per_lect_descr").val() != '') {
                set_solo_lectura('form_datos_personales-loc_per_lect_descr', true);
                $("#loc_per_lect_descr_help").hide();
            } else {
                $("#loc_per_lect_descr_help").show();
            }

            $("#form_datos_personales-loc_per_lect_descr").attr('campo_id', 'form_datos_personales-loc_per_lect');
            $("#form_datos_personales-loc_per_lect_descr").attr('ayuda', 'loc_per_lect_help');
            $("#form_datos_personales-loc_per_lect_descr").attr('campo_pais', 'form_datos_personales-pais_per_lect');
            $("#form_datos_personales-loc_per_lect_descr").attr('campo_cp', 'form_datos_personales-cp_per_lect');
            $("#form_datos_personales-loc_per_lect_descr").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: info.url_lista_localidades,
                        dataType: "json",
                        data: {
                            term: request.term,
                            pais: $('#form_datos_personales-pais_per_lect').val()
                        },
                        success: function (data) {
                            response(data);
                        }, 
                        // A value of 0 means there will be no timeout
                        timeout: 0 
                    });
                },
                minLength: $("#js-ui-autocomplete-min-length").val(),
                select: localidad_seleccionada_cp,
                focus: localidad_foco_cp

            });

            $('#btnSelectorLocPerLect').on('click', function () {
                $.facebox(info.selector_localidad);
                var _info = new cloneObject(info);
                _info['retorno_valor'] = 'form_datos_personales-loc_per_lect';
                _info['retorno_descr'] = 'form_datos_personales-loc_per_lect_descr';
                _info['retorno_cp'] = 'form_datos_personales-cp_per_lect';
                _info['ayuda'] = 'loc_per_lect_help';
                _info['campo_pais'] = 'form_datos_personales-pais_per_lect';
                inicializar_popup_localidades(_info);
            });

            $('#btnLimpiarLocPerLect').on('click', function () {
                $('#form_datos_personales-loc_per_lect').val('null');
                $('#form_datos_personales-loc_per_lect_descr').val('');
                $('#form_datos_personales-cp_per_lect').val('');
                set_solo_lectura('form_datos_personales-loc_per_lect_descr', false);
                $('#form_datos_personales-cp_per_lect').children().remove();
                set_solo_lectura('form_datos_personales-cp_per_lect', true);
                handle_combo_pais_localidad('form_datos_personales-pais_per_lect', 'form_datos_personales-loc_per_lect');
                $("#loc_per_lect_help").show();
            });

            $('#btnSelectorCP_PerLect').on('click', function () {
                var localidad = $('#form_datos_personales-loc_per_lect').val();
                if ((localidad == 'null') || (localidad == '')) {
                    return false;
                } else {
                    $.facebox(info.selector_cp);
                    var _info = new cloneObject(info);
                    _info['retorno_cp'] = 'form_datos_personales-cp_per_lect';
                    _info['localidad'] = localidad;
                    inicializar_popup_cps(_info);
                }
            });

            //------------------------------------------------------------------
            //---- Selector de localidad (procedencia) -------------------------
            //------------------------------------------------------------------

            //---- Combo de países para el selector de localidades -------------
//			handle_combo_pais_localidad('form_datos_personales-pais_proc', 'form_datos_personales-loc_proc');

            //---- Lista de localidades (autocompletado) -----------------------
            if ($("#form_datos_personales-loc_proc_descr").val() != '') {
                set_solo_lectura('form_datos_personales-loc_proc_descr', true);
                $("#loc_proc_descr_help").hide();
            } else {
                $("#loc_proc_descr_help").show();
            }

            $("#form_datos_personales-loc_proc_descr").attr('campo_id', 'form_datos_personales-loc_proc');
            $("#form_datos_personales-loc_proc_descr").attr('ayuda', 'loc_proc_descr_help');
            $("#form_datos_personales-loc_proc_descr").attr('campo_pais', 'form_datos_personales-pais_proc');
            $("#form_datos_personales-loc_proc_descr").attr('campo_cp', 'form_datos_personales-cp_proc');
            $("#form_datos_personales-loc_proc_descr").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: info.url_lista_localidades,
                        dataType: "json",
                        data: {
                            term: request.term,
                            pais: $('#form_datos_personales-pais_proc').val()
                        },
                        success: function (data) {
                            response(data);
                        }, 
                        // A value of 0 means there will be no timeout
                        timeout: 0 
                    });
                },
                minLength: $("#js-ui-autocomplete-min-length").val(),
                select: localidad_seleccionada_cp,
                focus: localidad_foco_cp
            });

            $('#btnSelectorLocProc').on('click', function () {
                $.facebox(info.selector_localidad);
                var _info = new cloneObject(info);
                _info['retorno_valor'] = 'form_datos_personales-loc_proc';
                _info['retorno_descr'] = 'form_datos_personales-loc_proc_descr';
                _info['retorno_cp'] = 'form_datos_personales-cp_proc';
                _info['ayuda'] = 'loc_proc_descr_help';
                _info['campo_pais'] = 'form_datos_personales-pais_proc';
                inicializar_popup_localidades(_info);
            });

            $('#btnLimpiarLocProc').on('click', function () {
                $('#form_datos_personales-loc_proc').val('null');
                $('#form_datos_personales-loc_proc_descr').val('');
                $('#form_datos_personales-cp_proc').val('');
                set_solo_lectura('form_datos_personales-loc_proc_descr', false);
                $('#form_datos_personales-cp_proc').children().remove();
                set_solo_lectura('form_datos_personales-cp_proc', true);
                handle_combo_pais_localidad('form_datos_personales-pais_proc', 'form_datos_personales-loc_proc');
                $("#loc_proc_descr_help").show();
            });

            $('#btnSelectorCP_Proc').on('click', function () {
                var localidad = $('#form_datos_personales-loc_proc').val();
                if ((localidad == 'null') || (localidad == '')) {
                    return false;
                } else {
                    $.facebox(info.selector_cp);
                    var _info = new cloneObject(info);
                    _info['retorno_cp'] = 'form_datos_personales-cp_proc';
                    _info['localidad'] = localidad;
                    inicializar_popup_cps(_info);
                }
            });

            //------------------------------------------------------------------
            //---- Selector de localidad (persona allegada) --------------------
            //------------------------------------------------------------------

            //---- Combo de países para el selector de localidades -------------
//			handle_combo_pais_localidad('form_datos_personales-pais_pers_alleg', 'form_datos_personales-loc_pers_alleg');

            //---- Lista de localidades (autocompletado) -----------------------
            if ($("#form_datos_personales-loc_pers_alleg_descr").val() != '') {
                set_solo_lectura('form_datos_personales-loc_pers_alleg_descr', true);
                $("#loc_pers_alleg_descr_help").hide();
            } else {
                $("#loc_pers_alleg_descr_help").show();
            }

            $("#form_datos_personales-loc_pers_alleg_descr").attr('campo_id', 'form_datos_personales-loc_pers_alleg');
            $("#form_datos_personales-loc_pers_alleg_descr").attr('ayuda', 'loc_pers_alleg_descr_help');
            $("#form_datos_personales-loc_pers_alleg_descr").attr('campo_pais', 'form_datos_personales-pais_pers_alleg');
            $("#form_datos_personales-loc_pers_alleg_descr").attr('campo_cp', 'form_datos_personales-cp_pers_alleg');
            $("#form_datos_personales-loc_pers_alleg_descr").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: info.url_lista_localidades,
                        dataType: "json",
                        data: {
                            term: request.term,
                            pais: $('#form_datos_personales-pais_pers_alleg').val()
                        },
                        success: function (data) {
                            response(data);
                        }, 
                        // A value of 0 means there will be no timeout
                        timeout: 0 
                    });
                },
                minLength: $("#js-ui-autocomplete-min-length").val(),
                select: localidad_seleccionada_cp,
                focus: localidad_foco_cp
            });

            $('#btnSelectorLocPersAlleg').on('click', function () {
                $.facebox(info.selector_localidad);
                var _info = new cloneObject(info);
                _info['retorno_valor'] = 'form_datos_personales-loc_pers_alleg';
                _info['retorno_descr'] = 'form_datos_personales-loc_pers_alleg_descr';
                _info['retorno_cp'] = 'form_datos_personales-cp_pers_alleg';
                _info['ayuda'] = 'loc_pers_alleg_descr_help';
                _info['campo_pais'] = 'form_datos_personales-pais_pers_alleg';
                inicializar_popup_localidades(_info);
            });

            $('#btnLimpiarLocPersAlleg').on('click', function () {
                $('#form_datos_personales-loc_pers_alleg').val('null');
                $('#form_datos_personales-loc_pers_alleg_descr').val('');
                $('#form_datos_personales-cp_pers_alleg').val('');
                set_solo_lectura('form_datos_personales-loc_pers_alleg_descr', false);
                $('#form_datos_personales-cp_pers_alleg').children().remove();
                set_solo_lectura('form_datos_personales-cp_pers_alleg', true);
                handle_combo_pais_localidad('form_datos_personales-pais_pers_alleg', 'form_datos_personales-loc_pers_alleg');
                $("#loc_pers_alleg_descr_help").show();
            });

            $('#btnSelectorCP_PersAlleg').on('click', function () {
                var localidad = $('#form_datos_personales-loc_pers_alleg').val();
                if ((localidad == 'null') || (localidad == '')) {
                    return false;
                } else {
                    $.facebox(info.selector_cp);
                    var _info = new cloneObject(info);
                    _info['retorno_cp'] = 'form_datos_personales-cp_pers_alleg';
                    _info['localidad'] = localidad;
                    inicializar_popup_cps(_info);
                }
            });

            //---- Si corresponde, se activa el autosave -----------------------
            if (info.autosave) {
                $(id).autosave();
            }

            //---- Submit ------------------------------------------------------
            $('#form_datos_personales').submit(function () {
                
                // Si hubo errores muestro mensaje y salgo
                if (hubo_errores) {
                    kernel.ui.show_mensaje(info.mensajes.guardado_datos_error, {
                        tipo: 'alert-error',
                        until_interaction: false,
                        timeout: 5000,
                        fadeout: 2000
                    });
                    return false;
                }
                
                var hubo_cambios = formulario_hubo_cambios();
                if (hubo_cambios) {
                    var $form = $(this);
                    kernel.ajax.load($form.attr('action'), id, {
                        historia: false,
                        type: 'POST',
                        data: $form.serializeArray(),
                        success: function (response) {
                            if (response.info_estado.exitoso) {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-success',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                });
                            } else {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-error',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                });
                            }
                            // Si corresponde, se desactiva el autosave
                            if (info.autosave) {
                                $(id).autosave('disable');
                            }
                            kernel.renderer.pagelet('datos_personales').onload();
                        }
                    });
                } else {
                    kernel.ui.show_mensaje(info.msj_sin_cambios, {
                        tipo: 'alert-info',
                        until_interaction: false,
                        timeout: 5000,
                        fadeout: 2000
                    });
                }
                return false;
            });
        }
    };

    /**
     * Cambio del campo "¿Pertenecés a algún pueblo originario?"
     */
    function cambio__pertenece_pueblo_originario()
    {
        hubo_errores = false;
        
        // Si ¿Pertenecés a algún pueblo originario? es 'Sí'
        if ($('#form_datos_personales-pertenece_pueblo_originario').val() === info.si) {
            
            // Pongo la etiqueta "¿A cuál?" como obligatoria
            $('#form_datos_personales-pueblo_originario-label label').removeClass('optional').addClass('label-obligatorio');
            // Muestro "¿A cuál?"
            mostrar('div_pueblo_originario');
            cambio__pueblo_originario();
            
        } else {
            
            // Pongo la etiqueta "¿A cuál?" como opcional
            $('#form_datos_personales-pueblo_originario-label label').removeClass('label-obligatorio').addClass('optional');
            // Oculto "¿A cuál?" reseteando el estado
            ocultar('div_pueblo_originario', true);
            
        }
    }

    /**
     * Cambio del campo "¿A cuál?"
     */
    function cambio__pueblo_originario()
    {
        hubo_errores = false;
        
        // Le quito el error a "¿A cuál?"
        $("#form_datos_personales-pueblo_originario").parents(".control-group").find('.help-inline').remove();
        $("#form_datos_personales-pueblo_originario").parents(".control-group").removeClass("error");
        
        var mostrar_pueblo_originario_otro = false;
        
        // Si ¿Pertenecés a algún pueblo originario? es 'Sí'
        if ($('#form_datos_personales-pertenece_pueblo_originario').val() === info.si) {
            
            // Si ¿A cuál? es '-- Seleccione --'
            if ($('#form_datos_personales-pueblo_originario').val() === 'null') {
                
                $("#form_datos_personales-pueblo_originario").parents(".control-group").addClass("error");
                $("#form_datos_personales-pueblo_originario").parents(".control-group").append("<span class='help-inline'>"+info.mensajes.vacio+"</span>");
                hubo_errores = true;
                
            // Si ¿A cuál? es 'Otros Especifique'
            } else if ($('#form_datos_personales-pueblo_originario').val() === info.pueblo_originario_otros) {
                mostrar_pueblo_originario_otro = true;
            }
            
        }
        
        // Si se debe mostrar "Nombre"
        if (mostrar_pueblo_originario_otro) {
            // Pongo la etiqueta "Nombre" como obligatoria
            $('#form_datos_personales-pueblo_originario_otro-label label').removeClass('optional').addClass('label-obligatorio');
            // Muestro "Nombre"
            mostrar('div_otro_pueblo_originario');
            cambio__pueblo_originario_otro();
        } else {
            // Pongo la etiqueta "Nombre" como opcional
            $('#form_datos_personales-pueblo_originario_otro-label label').removeClass('optional').addClass('label-obligatorio');
            // Oculto "Nombre" reseteando el estado
            ocultar('div_otro_pueblo_originario', true);
        }
    }
    
    /**
     * Cambio del input "Nombre"
     */
    function cambio__pueblo_originario_otro()
    {
        hubo_errores = false;
        
        // Le quito el error a "Nombre"
        $("#form_datos_personales-pueblo_originario_otro").parents(".control-group").find('.help-inline').remove();
        $("#form_datos_personales-pueblo_originario_otro").parents(".control-group").removeClass("error");
        
        // Si ¿Pertenecés a algún pueblo originario? es 'Sí'
        if ($('#form_datos_personales-pertenece_pueblo_originario').val() === info.si) {
            // Si ¿A cuál? es 'Otros Especifique'
            if ($('#form_datos_personales-pueblo_originario').val() === info.pueblo_originario_otros) {
                // Si Nombre es vacío
                if ($('#form_datos_personales-pueblo_originario_otro').val().trim().length === 0) {
                    $("#form_datos_personales-pueblo_originario_otro").parents(".control-group").addClass("error");
                    $("#form_datos_personales-pueblo_originario_otro").parents(".control-group").append("<span class='help-inline'>"+info.mensajes.vacio+"</span>");
                    hubo_errores = true;
                }
            }
        }
    }

    /**
     * Cambio del campo "especifique"
     */
    function cambio__identidad_genero() {
        if ($("#form_datos_personales-identidad_genero").val() != info.identidad_genero_especifique) {
            ocultar('form_datos_personales-identidad_genero_otro', true);
            ocultar('form_datos_personales-identidad_genero_otro-label', true);
        } else {
            mostrar('form_datos_personales-identidad_genero_otro');
            mostrar('form_datos_personales-identidad_genero_otro-label');
            $("#form_datos_personales-identidad_genero_otro").focus();

        }
    }
    //--------------------------------------------------------------------------
    //---- Función autocompletar (localidades) ---------------------------------
    //--------------------------------------------------------------------------

    function localidad_seleccionada_cp(event, ui)
    {
        var campo_id = event.target.attributes.campo_id.nodeValue;
        var campo_descr = event.target.attributes.id.nodeValue;
        var ayuda = event.target.attributes.ayuda.nodeValue;
        var campo_pais = event.target.attributes.campo_pais.nodeValue;
        var campo_cp = event.target.attributes.campo_cp.nodeValue;

        var localidad = ui.item.value.localidad;
        var localidad_descr = ui.item.value.localidad_descr;

        chequear_cp_unico(localidad, campo_cp);

        $("#" + campo_id).val(localidad);
        $("#" + campo_descr).val(localidad_descr);
        $("#" + ayuda).hide();
        $("#" + campo_pais).hide();
        event.preventDefault();

        set_solo_lectura(campo_descr, true);
    }

    function localidad_foco_cp()
    {
        event.preventDefault();
    }

    /**
     * Si la localidad tiene un único código postal, lo completa automáticamente
     */
    function chequear_cp_unico(_localidad, _campo_cp)
    {
        $.ajax({
            url: info.url_cp_unico,
            dataType: 'json',
            data: {localidad: _localidad},
            type: 'post',
            success: function (data) {
                $('#' + _campo_cp).val(data.cp);
            }
        });
    }

    /**
     * Si el CUIL viene con datos y no tiene errores lo desactivo
     */
    function verifica_cuil_vacio() {
        let cuil_con_datos = $("#form_datos_personales-cuil").val() !== "";
        let cuil_con_error = $("#form_datos_personales-cuil").parents(".control-group").hasClass("error");
        
        // Si el CUIL viene con datos y no tiene errores lo desactivo
        if (cuil_con_datos && !cuil_con_error) {
            $('#form_datos_personales-cuil').prop( "readonly", true );
        }
    }

});