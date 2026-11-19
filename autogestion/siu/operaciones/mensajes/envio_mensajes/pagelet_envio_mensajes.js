kernel.renderer.registrar_pagelet('envio_mensajes', function (info) {

    var id = '#' + info.id;
    var indice_archivos = 1;
    var fecha_desde = $('#fecha_desde');
    var fecha_hasta = $('#fecha_hasta');
    var intervalo;


    function init_upload() {
        $('#js-btn-agregar-archivo').on("click", function () {
            indice_archivos++;
            var control_group = $('<div class="control-group">');
            var id = 'archivos[' + indice_archivos + ']';
            var controls = $('<div class="controls archivo">');
            var input = $('<input type="file" id="' + id + '" name="' + id + '" />');
            var borrar = $('<a class="pull-right" href="#"><i class="icon-remove"></i></a>').on("click", function () {
                control_group.remove();
                return false;
            });
            control_group.append(borrar);
            controls.append(input);
            control_group.append(controls);
            $('.js-adjuntos').after(control_group);
            return false;
        });
    }

    return {

        onload: function () {

            $('#js-desc-mesa li').hide();
            $('#js-desc-mesa').hide();
            $('#js-desc-comision li').hide();
            $('#js-desc-comision').hide();

            drops($('input[type=radio][checked=checked]').val());

            $(id).on('click', 'input[type=radio]', function () {
                drops($(this).val());
            });

            $('#js-form-enviar').submit(function () {
                var error = false;
                if ($('#radio_comisiones').is(':checked'))
                {
                    if ($('#js-comision-autocomplete').val() == '')
                    {
                        $('#js-comision').addClass('error');
                        error = true;
                    } else
                        $('#js-comision').removeClass('error');
                } else if ($('#radio_mesas').is(':checked'))
                {
                    if ($('#js-mesa-autocomplete').val() == '')
                    {
                        $('#js-mesa').addClass('error');
                        error = true;
                    } else
                        $('#js-mesa').removeClass('error');
                } else if ($('#radio_alumno').is(':checked'))
                {
                    if ($('#js-alumno-autocomplete').val() == '')
                    {
                        $('#js-alumno').addClass('error');
                        error = true;
                    } else {
                        $('#js-alumno').removeClass('error');
                    }

                }

                var fecha_desde = $('#fecha_desde');
                var fecha_hasta = $('#fecha_hasta');
                fecha_desde.removeClass('error');
                fecha_hasta.removeClass('error');
                var fecha_val = '';
                if (fecha_desde.val() == "" || fecha_desde.val() == null) {
                    fecha_desde.addClass('error');
                    error = true;
                } else {
                    fecha_val = fecha_desde.val();
                    var f_desde = new Date(fecha_val.substr(3, 2) + '/' + fecha_val.substr(0, 2) + '/' + fecha_val.substr(6, 4));
                }

                if (fecha_hasta.val() == "" || fecha_hasta.val() == null) {
                    fecha_hasta.addClass('error');
                    error = true;
                } else {
                    fecha_val = fecha_hasta.val();
                    var f_hasta = new Date(fecha_val.substr(3, 2) + '/' + fecha_val.substr(0, 2) + '/' + fecha_val.substr(6, 4));
                }

                if (f_desde >= f_hasta & !error) {
                    fecha_hasta.addClass('error');
                    fecha_desde.addClass('error');
                    error = true;
                }

                $('.js-obligatorio').each(function () {
                    if ($(this).val() == "" || $(this).val() == null) {
                        $(this).addClass('error');
                        error = true;
                    } else {
                        $(this).removeClass('error');
                    }
                });

                return !error;
            });

            $('#js-comision').change(function () {
                $('#js-desc-comision').show();
                $('#js-desc-comision li').hide();
                $('#O' + $(this).val()).show();
                if ($(this).val() == "") {

                    $('#js-descripcion').hide();
                    ocultar_subcomisiones();

                } else {

                    $('#js-descripcion').show();
                    $('#M' + $(this).val()).show();
                    mostrar_subcomisiones();

                }
            });

            $('#js-AnioAcademico').change(function () {
                $('#js-desc-comision').hide();
                $('#js-desc-mesa').hide();
                if ($('#js-AnioAcademico option:selected').val() != '') {
                    $.ajax({
                        url: info.url_combos, type: 'get',
                        data: { anioAcademico: $('#js-AnioAcademico option:selected').val() },
                        contentType: "application/json;",

                        success: function (data) {
                            var obj = JSON.parse(data);
                            $('#js-comision').html(obj.comisiones);
                            $('#js-desc-comision').html(obj.divcomisiones);
                            $('#js-mesa').html(obj.mesas);
                            $('#js-desc-mesa').html(obj.divmesas);

                        }
                    });
                } else {
                    $('#js-comision').html('<option value="">SELECCIONE COMISIÓN</option>');
                    $('#js-mesa').html('<option value="">SELECCIONE MESA</option>');
                }
                ocultar_subcomisiones();
            });

            $('#js-mesa').change(function () {
                $('#js-desc-mesa').show();
                $('#js-desc-mesa li').hide();
                if ($(this).val() == "") {
                    $('#js-descripcion').hide();
                } else {
                    $('#js-descripcion').show();
                    $('#M' + $(this).val()).show();
                }
            });

            $(id).find('input[type=date]').datepicker({minDate: 0});
            $('#fecha_desde').datepicker({defaultDate: info.default_fecha_desde});
            $('#fecha_hasta').datepicker({defaultDate: info.default_fecha_hasta});

            init_upload();

            if (info.comision) {
                $('#js-comision').val(info.comision);
                $('#js-comision').change();
                $('#titulo').focus();
            }

            $('#js-alumno').plugin_autocompletar({
                url: info.url_buscar_alumnos,
                select: function (event, ui)
                {
                    if (!ui) {
                        $('#js-alumno').addClass('error');
                    } else {
                        $('#js-alumno').removeClass('error');
                        $("#js-alumno").attr('readonly', true);

                    }
                }
            });
            $('#js-alumno').on('change',function(){
              bloquear();
            })
            $("#FormMensaje").on('click',function (){
                if (!$('#radio_alumno').is(':checked') && !$('#radio_mesas').is(':checked') && !$('#radio_comisiones').is(':checked')) {
                    error = "Debe Seleccionar a quien Enviar";
                    kernel.ui.show_mensaje(error, { tipo: 'alert-error', until_interaction: true });
                    intervalo = setInterval(changeColor, 500);
                    return false;
                }
                resultFD = VerificarFecha(fecha_desde.val());
                resultFH = VerificarFecha(fecha_hasta.val());
                if (resultFD == false || resultFH == false) {
                    return false;
                } else if (resultFD > resultFH) {
                    console.log(resultFD, resultFH);
                    error = "La fecha DESDE no puede ser <b>mayor</b> a la fecha HASTA";
                    kernel.ui.show_mensaje(error, { tipo: 'alert-error', until_interaction: true });
                    return false;
                }
            });
            $("#bclear").on('click',function(){
                $('#js-alumno').val('').css('background-color','white');
                $("#js-alumno").attr('readonly', false);
                $('#js-alumno-autocomplete').val('');
                bloquear();
            })
        }
    }

    function drops(valor) {

        if (valor == "M") {
            ocultar_subcomisiones();
            ocultar_comisiones();
            ocultar_alumno();
            mostrar_AnioAcademico();
            mostrar_mesas();
        }

        if (valor == "O") {
            ocultar_mesas();
            ocultar_alumno();
            mostrar_AnioAcademico();
            mostrar_comisiones();
            mostrar_subcomisiones();
        }

        if (valor == "A") {
            ocultar_subcomisiones();
            ocultar_comisiones();
            ocultar_mesas();
            ocultar_AnioAcademico();
            mostrar_alumno();
        }

    }

    function ocultar_comisiones() {
        $('#js-comisiones').hide();
        $('#js-comision').removeClass('js-obligatorio');
        $('#js-comision').removeClass('error');
        $('#js-desc-comision li').hide();
        $('#js-desc-comision').hide();
    }

    function mostrar_comisiones() {
        clearInterval(intervalo);
        $('#js-comisiones').show();
        $('#js-comision').addClass('js-obligatorio');
        if ($('#js-desc-comision').html() != "") {
            $('#js-desc-comision').show();
        }
        $('#O' + $('#js-comision').val()).show();
    }

    function mostrar_subcomisiones() {
        if ($('#js-comision').val() != '') {

            kernel.ajax.call(info.url_subcomisiones, {
                type: 'post',
                data: {
                    anio_academico: $('#js-AnioAcademico').val(),
                    comision_hash: $('#js-comision').val()
                },
                success: function (response) {
                    if (response.cont.exito) {

                        if (response.cont.tiene_subcomisiones) {
                            $("#js-subcomision").html(response.cont.subcomisiones);
                            $("#js-subcomision").addClass('js-obligatorio');
                            $("#js-subcomisiones").show();
                        } else {
                            ocultar_subcomisiones();
                        }

                    } else {

                        kernel.ui.show_mensaje(info.error_subcomisiones, { tipo: 'alert-error', until_interaction: true });

                    }

                }
            });

        }

    }

    function ocultar_subcomisiones() {
        $("#js-subcomision").html('<option value="' + info.TODA_COMISION + '">' + info.todas_msj + '</option>');
        $("#js-subcomision").removeClass('js-obligatorio');
        $("#js-subcomisiones").hide();
    }

    function ocultar_AnioAcademico() {
        $('#js-AniosAcademicos').hide();
        $('#js-AnioAcademico').removeClass('js-obligatorio');
        $('#js-AnioAcademico').removeClass('error');
        $('#js-desc-AnioAcademico li').hide();
        $('#js-desc-AnioAcademico').hide();
    }

    function mostrar_AnioAcademico() {
        $('#js-AniosAcademicos').show();
        $('#js-AnioAcademico').addClass('js-obligatorio');
        $('#js-desc-AnioAcademico').show();
        $('#O' + $('#js-AnioAcademico').val()).show();
    }

    function ocultar_mesas() {
        $('#js-mesas').hide();
        $('#js-mesa').removeClass('js-obligatorio');
        $('#js-mesa').removeClass('error');
        $('#js-desc-mesa li').hide();
        $('#js-desc-mesa').hide();
    }

    function mostrar_mesas() {
        clearInterval(intervalo);
        $('#js-mesas').show();
        $('#js-mesa').addClass('js-obligatorio');
        if ($('#js-desc-mesa').html() != "") {
            $('#js-desc-mesa').show();
        }
        $('#M' + $('#js-mesa').val()).show();
    }

    function ocultar_alumno() {
        $('#js-alumnos').hide();
        $('#js-alumno-autocomplete').removeClass('js-obligatorio');
        $('#js-alumno-autocomplete').removeClass('error');
    }

    function mostrar_alumno() {
        clearInterval(intervalo);
        $('#js-alumnos').show();
        $('#js-alumno-autocomplete').addClass('js-obligatorio');
    }

    function VerificarFecha(fecha) {
        var Nfecha = fecha.split("/");
        var dia = Nfecha[0];
        var mes = Nfecha[1];
        var anio = Nfecha[2];
        var fechaReal = new Date(anio, mes, '0');
        if ((dia - 0) > (fechaReal.getDate() - 0)) {
            error = "La Fecha ingresada es invalida";
            kernel.ui.show_mensaje(error, { tipo: 'alert-error', until_interaction: true });
            return false;
        }
        return anio+'-'+mes+'-'+dia;
    }

    function changeColor() {
        if ($('.radio').attr('style')) {
            $('.radio').removeAttr('style');
        } else {
            $('.radio').css('color', 'red');
        }
    }
    function bloquear(){
        res = $("#js-alumno-autocomplete").val();
        if(res != ""){
            $("#js-alumno").attr('disabled', true);
        }else{
            $("#js-alumno").attr('disabled', false);
        }
    
    }

});