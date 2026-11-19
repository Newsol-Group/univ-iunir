kernel.renderer.registrar_pagelet('filtro', function (info) {
    var id = '#' + info.id;

    return {
        onload: function () {

            $('#boton_buscar').prop("disabled", true);
            $('#formulario_filtro-ra option:first').val("").html(info.mensajes.mensaje_seleccione);
            $('#formulario_filtro-periodo option:first').val("").html(info.mensajes.mensaje_seleccione);
            $('#boton_buscar').prepend('<i class="icon-search"></i>');
            var $filtro = $('form').formulario();
            $('#boton_buscar').on('click', function () {
                guarani.cambiar_op({
                    href: info.url_buscar_horarios + '?' + $filtro.serialize()
                });
                return false;
            });

            if (($("#formulario_filtro-materia_descr").val() != "") && ($("#formulario_filtro-materia").val() != "")) {
                set_solo_lectura("formulario_filtro-materia_descr", true);
            } else {
                $("#formulario_filtro-materia_descr").val("");
                $("#formulario_filtro-materia").val("");
            }

            $("#formulario_filtro-materia_descr").autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: info.url_buscar_materias,
                        dataType: "json",
                        data: {
                            term: request.term,
                            carrera: $('#formulario_filtro-carrera').val(),
                            plan: null
                        },
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                appendTo: id,
                minLength: $("#js-ui-autocomplete-min-length").val(),
                select: materia_seleccionada,
                focus: materia_foco,
                change: function (event, ui) {
                    if ($("#formulario_filtro-materia").val() == '') {
                        $("#formulario_filtro-materia_descr").val("");
                    }
                },
                search: function (event, ui) {
                    kernel.ui.show_loading();
                },
                response: function (event, ui) {
                    kernel.ui.hide_loading();
                }

            });

            $('#limpiar_materia').on('click', function () {
                $('#boton_buscar').prop("disabled", false);
                limpiar_materia();
                $('#formulario_filtro-materia_descr').focus();
            });

            $filtro.find('input.date').datepicker("destroy");
            $filtro.find('input.date').datepicker({
                changeMonth: true,
                changeYear: true,
                firstDay: 0
            });

            $("#formulario_filtro-ra").on("change", function () {
                var $elem_periodo = $('#formulario_filtro-periodo');
                $elem_periodo.children().remove();

                $elem_periodo.append(
                        $('<option></option>').val("").html(info.mensajes.mensaje_seleccione)
                        );
                if ($(this).val() != "") {
                    $.ajax({
                        url: info.url_buscar_periodo,
                        dataType: 'json',
                        data: {ra: $('#formulario_filtro-ra').val()},
                        success: function (data) {
                            $elem_periodo.children().remove();
                            $.each(data, function (key, value) {
                                $elem_periodo.append(
                                        $('<option></option>').val(value['periodo_lectivo']).html(value['periodo_lectivo_nombre'])
                                        );
                            });
                            $('#boton_buscar').prop("disabled", true);
                            if ($elem_periodo.val() != '') {
                                $elem_periodo.prepend(
                                        $('<option></option>').val("").html(info.mensajes.mensaje_seleccione)
                                        );
                                $('#formulario_filtro-periodo option:first').attr("selected", "selected");
                            }
                            //$elem_periodo.focus();
                            //$("#formulario_filtro-carrera").focus();
                        }
                    });
                    limpiar_periodo();
                }


                var $elem_propuestas = $('#formulario_filtro-carrera');
                $elem_propuestas.children().remove();

                $elem_propuestas.append(
                        $('<option></option>').val("").html(info.mensajes.filtro_todos)
                        );
                if ($(this).val() != "") {
                    $.ajax({
                        url: info.url_buscar_carreras,
                        dataType: 'json',
                        type: 'GET',
                        data: {ra: $('#formulario_filtro-ra').val()},
                        success: function (data) {

                            $elem_propuestas.children().remove();
                            $.each(data.carrera, function (key, value) {
                                $elem_propuestas.append(
                                        $('<option></option>').val(value['_ID_']).html(value['nombre'])
                                        );
                            });

                            if ($('#formulario_filtro-carrera').val() != 'nohaycarreras') {
                                $elem_propuestas.prepend(
                                        $('<option></option>').val("").html(info.mensajes.mensaje_seleccione)
                                        );
                                $('#formulario_filtro-carrera option:first').attr("selected", "selected");
                            }
                            $elem_propuestas.focus();

                        }
                    });

                    limpiar_propuestas();
                    limpiar_planes();
                    limpiar_materia();

                }



            });
            $("#formulario_filtro-carrera").on("change", function () {
                limpiar_materia();
                cambia_estado_combo();
            });
            $("#formulario_filtro-periodo").on("change", function () {
                cambia_estado_combo();
            });
            
            $("#formulario_filtro-ubicacion").on("change", function () {
                cambia_estado_combo();
            });
            
            $("#formulario_filtro-dia_semana").on("change", function () {
                cambia_estado_combo($("#formulario_filtro-dia_semana"));
            });
            
            $("#formulario_filtro-turno").on("change", function () {
                cambia_estado_combo();
            });
            
            $("#formulario_filtro-anio_cursada").on("change", function () {
                cambia_estado_combo();
            });
            
            $("#boton_limpiar").on("click", function () {
                var $elem = $('#formulario_filtro-ra');
                limpiar_pantalla();                
                $elem.focus();
                $('#boton_buscar').prop("disabled", true);
                return false;
            });

            if ($("#formulario_filtro-carrera").val() == "") {
                preparar_propuestas();
            } else {
                var carrera_seleccionada = $("#formulario_filtro-carrera").val();
                var $elem_propuestas = $('#formulario_filtro-carrera');
                $elem_propuestas.children().remove();
                $elem_propuestas.append($('<option></option>').val("").html(info.mensajes.filtro_todos));
                $.ajax({
                    url: info.url_buscar_carreras,
                    dataType: 'json',
                    type: 'GET',
                    data: {ra: $('#formulario_filtro-ra').val()},
                    success: function (data) {
                        $elem_propuestas.children().remove();
                        $.each(data.carrera, function (key, value) {
                            $elem_propuestas.append(
                                    $('<option></option>').val(value['_ID_']).html(value['nombre'])
                                    );
                        });
                        $elem_propuestas.prepend($('<option></option>').val("").html(info.mensajes.mensaje_seleccione));
                        $("#formulario_filtro-carrera option[value=" + carrera_seleccionada + "]").attr("selected", true);
                    }
                });

            }

        }//onload

    };


    function preparar_propuestas() {
        var $elem_planes = $('#formulario_filtro-carrera');
        $elem_planes.children().remove();
        $elem_planes.append(
                $('<option></option>').val("").html(info.mensajes.mensaje_seleccione)
                );
    }

    function limpiar_propuestas() {
        $('#formulario_filtro-carrera').val('');
        $('#formulario_filtro-carrera_descr').val('');
        set_solo_lectura("formulario_filtro-carrera_descr", false);
    }
    function limpiar_planes() {
        var $elem_planes = $('#formulario_filtro-plan');
        $elem_planes.children().remove();
        $elem_planes.append(
                $('<option></option>').val("").html(info.mensajes.filtro_todos)
                );
    }


    function limpiar_periodo() {
        $('#formulario_filtro-periodo').val('');
        $('#formulario_filtro-periodo').val('');
        set_solo_lectura("formulario_filtro-periodo_descr", false);
    }
    function limpiar_materia() {
        $('#formulario_filtro-materia').val('');
        $('#formulario_filtro-materia_descr').val('');
        set_solo_lectura("formulario_filtro-materia_descr", false);
    }

    function materia_seleccionada(event, ui)
    {
        var id = ui.item.id;
        var label = ui.item.label;

        $("#formulario_filtro-materia").val(id);
        $("#formulario_filtro-materia_descr").val(label);
        set_solo_lectura("formulario_filtro-materia_descr", true);
        $('#boton_buscar').prop("disabled", false);
        event.preventDefault();
    }

    function materia_foco(event)
    {
        event.preventDefault();
    }

    function set_solo_lectura(elemId, solo_lectura) {
        if (solo_lectura) {
            $('#' + elemId).attr('readonly', 'readonly');
        } else {
            $('#' + elemId).prop("readonly", false);
        }
    }
    
    function check_combos_obligatorios_vacios(){
        var flag = false;
        if ($("#formulario_filtro-ra").val() == ""){
            flag = true;
        }
        if ($("#formulario_filtro-carrera").val() == ""){
            flag = true;
        }
        if ($("#formulario_filtro-periodo").val() == ""){
            flag = true;
        }
        return flag;
    }
    //combo = $("#formulario_filtro-anio_cursada") EJEMPLO
    function cambia_estado_combo() {
        var flag = check_combos_obligatorios_vacios();
        if (!flag) {
            $('#boton_buscar').prop("disabled", false);
        } else {
            $('#boton_buscar').prop("disabled", true);
        }
    }
    function limpiar_pantalla(){
        $("#formulario_filtro-ra option").attr("selected", false);
        $("#formulario_filtro-ubicacion option").attr("selected", false);
        $("#formulario_filtro-carrera option").attr("selected", false);
        $("#formulario_filtro-anio_cursada option").attr("selected", false);
        $("#formulario_filtro-tipo_mesa option").attr("selected", false);
        $("#formulario_filtro-dia_semana option").attr("selected", false);
        $("#formulario_filtro-turno option").attr("selected", false);
        $("#formulario_filtro-materia_descr").val('');
        $("#formulario_filtro-periodo option").attr("selected", false);
    }
});