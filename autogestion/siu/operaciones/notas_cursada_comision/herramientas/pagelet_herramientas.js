kernel.renderer.registrar_pagelet('herramientas', function (info)
{
	var id = '#' + info.id;
        
	var fecha_inicio, fecha_fin;
	
	function get_escala_regularidad()
	{
		return guarani.escalas().recuperar(info.id_escala_regularidad);
	}

	function get_escala_promocion()
	{
		return guarani.escalas().recuperar(info.id_escala_promocion);
	}

	function create_date(date_string)
	{
		var fecha_split = date_string.split('/');
		return new Date(fecha_split[2], fecha_split[1] - 1, fecha_split[0]);
	}

	function controlar_btn_completar()
	{
            var campo = $('#preset_campo').val();
            
            // Tomo el valor del campo
            var valor = $.trim($('#preset_form').find('#js-valor-' + campo).val());
            // Si es vacío deshabilito el botón "Completar"
            if (valor === '') {
                $('#btnCompletar').prop('disabled', true);
            } else {
                $('#btnCompletar').prop("disabled", false);
            }
	}
        
        // Nos alerta que hay cambios sin guardar antes de salir de la página
        function setup_unload() {
            
            // https://stackoverflow.com/questions/28627111/how-to-call-a-function-before-leaving-page-with-javascript
            $(window).on('beforeunload', function () {
                // Si hay cambios pregunto antes de cambiar de página
                if (sessionStorage.hay_cambios == 'true') {
                    // Al retornar algo muestra el prompt, si no retornamos nada no lo muestra
                    return '¿Está usted seguro de que desea salir de esta página?';
                }
            });
            
        }
        
        // Le pongo la clase "no-ajax" a todos los links para que nos alerte que hay cambios sin guardar antes de salir de la página
        function setup_links() {
            $("a").addClass("no-ajax");
        }
        
        function cambiar_preset_valor(campo)
        {
            // Oculto y deshabilito todos los inputs menos el del campo en cuestion
            $('#preset_form').find('.preset_valor').prop("disabled", true).hide();
            $('#preset_form').find('#js-valor-' + campo).prop("disabled", false).show();
            // Cambio la propiedad "for" del "label"
            $('#preset_form').find('#js-label-preset-valor').attr("for", "js-valor-" + campo);
        }
                     
        // Tomo el campo seleccionado y muestro "preset_valor" correspondiente a dicho campo
        function setup_preset_valor() {
            // Tomo el campo a autocompletar (ej: Nota de regularidad)
            var campo = $('#preset_campo').val();
            cambiar_preset_valor(campo);
        }

	return {
		onload: function ()
		{
                    
                        // Si se autocompleto con éxito
                        if (info.mensajes.exito_autocompletar) {
                            kernel.ui.show_mensaje(info.mensajes.exito_autocompletar, {tipo: 'alert-success'});
                        }
                        
                        // Si hubo un error al autocompletar
                        if (info.mensajes.error_autocompletar) {
                            kernel.ui.show_mensaje(info.mensajes.error_autocompletar, {tipo: 'alert-error'});
                        }
                        
                        // Si no hubo cambios al autocompletar
                        if (info.mensajes.no_cambios_autocompletar) {
                            kernel.ui.show_mensaje(info.mensajes.no_cambios_autocompletar, {tipo: 'alert-info'});
                        }
                        
                        // Evento click del botón "Completar" del "Autocompletar"
			$('#btnCompletar').on("click", function (e)
			{
                            // Evito el submit del formulario
                            e.preventDefault();
                            autocompletar();

			});
                        
                        function autocompletar()
                        {
                            // Tomo el campo a autocompletar (ej: Nota de regularidad)
                            var campo = $('#preset_campo').val();
                            var campos_validos = [
                                info.autocompletar_campo_nota_cursada,
                                info.autocompletar_campo_nota_promocion,
                                info.autocompletar_campo_resultado_cursada,
                                info.autocompletar_campo_resultado_promocion,
                                info.autocompletar_campo_fecha,
                                info.autocompletar_campo_observaciones
                            ];
                            // Si el campo "Completar" es inválido
                            if ($.inArray(campo, campos_validos) === -1) {
                                kernel.ui.show_mensaje(info.mensajes.completar_invalido, {tipo: 'alert-error'});
                                return false;
                            }

                            // Tomo el campo "para" (ej: Todos los alumnos de la página)
                            var para = $('#autocompletar_para').val();
                            var para_validos = [
                                info.autocompletar_para_todos_pagina,
                                info.autocompletar_para_sin_datos_pagina,
                                info.autocompletar_para_todos,
                                info.autocompletar_para_sin_datos
                            ];
                            // Si el campo "para" es inválido
                            if ($.inArray(para, para_validos) === -1) {
                                kernel.ui.show_mensaje(info.mensajes.para_invalido, {tipo: 'alert-error'});
                                return false;
                            }

                            // Tomo el valor del campo
                            var val = $.trim($('#preset_form').find('#js-valor-' + campo).val());

                            // Si el valor es vacío salgo
                            if (val == '') {
                                kernel.ui.show_mensaje(info.mensajes.valor_vacio, {tipo: 'alert-error'});
                                return false;
                            }

                            // chequeo si el valor es una nota válida
                            if (campo === info.autocompletar_campo_nota_cursada && !get_escala_regularidad().validar(val)) {
                                kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
                                return false;
                            }

                            // chequeo si el valor es una nota válida
                            if (campo === info.autocompletar_campo_nota_promocion && !get_escala_promocion().validar(val)) {
                                kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
                                return false;
                            }
                            
                            // chequeo si el valor es una fecha válida
                            if (campo === info.autocompletar_campo_fecha && !fecha_valida(val)) {
                                kernel.ui.show_mensaje(info.mensajes.fecha_invalida, {tipo: 'alert-error'});
                                return false;
                            }
                            
                            // Si el campo es "Resultado de Regularidad" o "Resultado de Promoción"
                            if ($.inArray(campo, [info.autocompletar_campo_resultado_cursada, info.autocompletar_campo_resultado_promocion]) !== -1) {
                                
                                var resultados = [];
                                // Si el campo es "Resultado de Regularidad"
                                if (campo === info.autocompletar_campo_resultado_cursada) {
                                    resultados = info.resultados_regularidad;
                                } else {// Si el campo es "Resultado de Promoción"
                                    resultados = info.resultados_promocion;
                                }
                                // Si los campos "Resultado de Regularidad" o "Resultado de Promoción" son inválidos
                                if ($.inArray(val, resultados) === -1) {
                                    kernel.ui.show_mensaje(info.mensajes.resultado_invalido, {tipo: 'alert-error'});
                                    return false;
                                }
                            }
                            
                            var para_pagina_actual = [
                                info.autocompletar_para_todos_pagina,
                                info.autocompletar_para_sin_datos_pagina
                            ];
                            // Si el campo "para" es "Todos los alumnos de la página" o "Alumnos de la página sin datos"
                            // solo completo la página actual
                            if ($.inArray(para, para_pagina_actual) !== -1) {
                                
                                autocompletar_pagina(campo, val, para);
                                
                            } else {// Si el campo "para" es "Todos los alumnos" o "Alumnos sin datos" voy directo a la DB
                                
                                // Muestro prompt avisando que modificará a todos los alumnos de todas las páginas
                                kernel.ui.prompt({
                                    msg: info.mensajes.msg_prompt_modificara_todas_paginas,
                                    msg_ok: info.mensajes.aceptar,
                                    msg_cancel: info.mensajes.cancelar,
                                    ok: function ()
                                    {
                                        // Para evitar que muestre dos veces el prompt de abandonar página
                                        sessionStorage.hay_cambios = false;
                                        // Hago el submit del formulario (sin AJAX)
                                        $("#preset_form").submit();
                                    }
                                });
                                
                            }
                        }
                        
                        function autocompletar_pagina(campo, valor, para)
                        {
                            let selector_instancia = '';
                            let selector_campo = '';
                            let hubo_cambios = false;
                            switch (campo) {
                                case info.autocompletar_campo_nota_cursada:
                                    selector_instancia = '.js-renglon-regular';
                                    selector_campo = '[data-tipo="nota"]';
                                    break;
                                case info.autocompletar_campo_nota_promocion:
                                    selector_instancia = '.js-renglon-promocion';
                                    selector_campo = '[data-tipo="nota"]';
                                    break;
                                case info.autocompletar_campo_resultado_cursada:
                                    selector_instancia = '.js-renglon-regular';
                                    selector_campo = '[data-tipo="resultado"]';
                                    break;
                                case info.autocompletar_campo_resultado_promocion:
                                    selector_instancia = '.js-renglon-promocion';
                                    selector_campo = '[data-tipo="resultado"]';
                                    break;
                                case info.autocompletar_campo_fecha:
                                    selector_instancia = '.js-renglon-regular, .js-renglon-promocion';
                                    selector_campo = '[data-tipo="fecha"]';
                                    break;
                                case info.autocompletar_campo_observaciones:
                                    selector_instancia = '.js-renglon-regular, .js-renglon-promocion';
                                    selector_campo = '[data-tipo="observacion"]';
                                    break; 
                            }
                            
                            // solo_vacios es true si eligió "Alumnos de la página sin datos"
                            const solo_vacios = (para === info.autocompletar_para_sin_datos_pagina);
                            
                            // Guardo los datos anteriores (botón "Deshacer")
                            guardar_datos_anteriores();
                            $('#btn_undo_autocompletar').prop("disabled", false);
                            
                            $(selector_instancia).each(function () {
                                
                                // Si el campo existe para la instancia
                                if ($(this).find(selector_campo).length > 0) {
                                    
                                    // Si completa "Alumnos de la página sin datos"
                                    if (solo_vacios) {
                                        if ($(this).find(selector_campo).val() === '') {
                                            // Si el valor cambia entonces hubo cambios
                                            if ($(this).find(selector_campo).val() !== valor) {
                                                hubo_cambios = true;
                                            }
                                            $(this).find(selector_campo).val(valor).change().keyup();
                                        }
                                    // Si completa "Todos los alumnos de la página"
                                    } else {
                                       // Si el valor cambia entonces hubo cambios
                                       if ($(this).find(selector_campo).val() !== valor) {
                                            hubo_cambios = true;
                                       }
                                       $(this).find(selector_campo).val(valor).change().keyup();
                                    }
                                    
                                }
                                
                            });
                            
                            if (hubo_cambios) {
                                // Luego de "Autocompletar" se muestran todos los renglones de la página para que vea que se modificaron
                                $('#notas_cursada_filtrar_acta').val("todas-todas").change();
                                kernel.ui.show_mensaje(info.mensajes.msg_exito_autocompletar, {tipo: 'alert-success'});
                            } else {
                                kernel.ui.show_mensaje(info.mensajes.msg_no_cambios_autocompletar, {tipo: 'alert-info'});
                            }
                            
                        }
                        
                        $("#btn_undo_autocompletar").on("click", function (e) {
                            e.preventDefault();
                            // Recargo los datos anteriores (botón "Deshacer")
                            recargar_datos_anteriores();
                            $('#btn_undo_autocompletar').prop("disabled", true);
                        });

			fecha_inicio = create_date(info.fecha_inicio);
			fecha_fin = create_date(info.fecha_fin);
                        
                        // Agrego fecha máxima y mínima al datepicker (https://api.jqueryui.com/datepicker/#option-maxDate)
                        $('#js-valor-'+info.autocompletar_campo_fecha).datepicker({
                            minDate: fecha_inicio,
                            maxDate: fecha_fin
                        });
                        
			function fecha_valida(val)
			{
				var fecha = create_date(val);
				return (fecha.getTime() >= fecha_inicio.getTime() && fecha.getTime() <= fecha_fin.getTime());
			}

			$('#preset_campo').on('change', function ()
			{
                                // Vacío todos valores dentro de "Autocompletar" menos el resultado
				$('#preset_form').find('.preset_valor:not(.resultado)').val('');
                                
                                var campo = $(this).val();
                                
                                // Tomo el campo seleccionado y muestro "preset_valor" correspondiente a dicho campo
                                cambiar_preset_valor(campo);
                                
                                controlar_btn_completar();
			});
                        
			$('.preset_valor').on('keyup', function ()
			{
				controlar_btn_completar();
			});

			$('#preset_form .preset_valor').change(function ()
			{
				controlar_btn_completar();

				var campo = $('#preset_campo').val();
				var val;

				if (campo === info.autocompletar_campo_nota_cursada) {
                                        val = $.trim($('#preset_form').find('#js-valor-' + campo).val());
                                        
                                        // Si es vacío salgo
                                        if (val === '') {
                                            return false;
                                        }
                                        
					if (!get_escala_regularidad().validar(val)) {
						$('#preset_form').find('#js-valor-' + campo).val('');
						kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
					}
				}

				if (campo === info.autocompletar_campo_nota_promocion) {
					val = $.trim($('#preset_form').find('#js-valor-' + campo).val());
                                        
                                        // Si es vacío salgo
                                        if (val === '') {
                                            return false;
                                        }
                                        
					if (!get_escala_promocion().validar(val)) {
						$('#preset_form').find('#js-valor-' + campo).val('');
						kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
					}
				}

				if (campo === info.autocompletar_campo_fecha) {
					val = $.trim($('#preset_form').find('#js-valor-' + campo).val());
                                        
                                        // Si es vacío salgo
                                        if (val === '') {
                                            return false;
                                        }
                                        
					if (!fecha_valida(val)) {
						$('#preset_form').find('#js-valor-' + campo).val('');
						kernel.ui.show_mensaje(info.mensajes.fecha_invalida, {tipo: 'alert-error'});
					}
				}
			});

                    var datosAnterioresRegular = [];
                    var datosAnterioresPromocion = [];

                    function guardar_datos_anteriores() {

                        datosAnterioresRegular = [];
                        datosAnterioresPromocion = [];
                        
                        // Recorro todos los renglones de regular
                        $('.js-renglon-regular').each(function () {
                            // Si tiene el campo "Nota"
                            if ($(this).find("[data-tipo='nota']").length > 0) {

                                var datos_regular = {
                                    fecha: $(this).find("[data-tipo='fecha']").val(),
                                    nota: $(this).find("[data-tipo='nota']").val(),
                                    resultado: $(this).find("[data-tipo='resultado']").val(),
                                    condicion: $(this).find("[data-tipo='cond_regularidad']").val(),
                                    observacion: $(this).find("[data-tipo='observacion']").val()
                                };
                                
                                var renglon = $(this).data('renglon');
                                datosAnterioresRegular[renglon] = datos_regular;

                            }

                        });

                        // Recorro todos los renglones de promoción
                        $('.js-renglon-promocion').each(function () {
                            // Si tiene el campo "Nota"
                            if ($(this).find("[data-tipo='nota']").length > 0) {

                                var datos_promocion = {
                                    fecha: $(this).find("[data-tipo='fecha']").val(),
                                    nota: $(this).find("[data-tipo='nota']").val(),
                                    resultado: $(this).find("[data-tipo='resultado']").val(),
                                    observacion: $(this).find("[data-tipo='observacion']").val()
                                };
                                
                                var renglon = $(this).data('renglon');
                                datosAnterioresPromocion[renglon] = datos_promocion;

                            }

                        });

                    }

                    function recargar_datos_anteriores() {

                        $('.js-renglon-regular').each(function (index) {
                            var renglon = $(this).data('renglon');
                            var datos_regular = datosAnterioresRegular[renglon];
                            if (renglon) {
                                $(this).find("[data-tipo='fecha']").val(datos_regular['fecha']).change().keyup();
                                $(this).find("[data-tipo='nota']").val(datos_regular['nota']).change().keyup();
                                $(this).find("[data-tipo='resultado']").val(datos_regular['resultado']).change().keyup();
                                $(this).find("[data-tipo='cond_regularidad']").val(datos_regular['condicion']).change().keyup();
                                $(this).find("[data-tipo='observacion']").val(datos_regular['observacion']).change().keyup();
                            }
                        });

                        $('.js-renglon-promocion').each(function (index) {
                            var renglon = $(this).data('renglon');
                            var datos_promocion = datosAnterioresPromocion[renglon];
                            if (renglon) {
                                $(this).find("[data-tipo='fecha']").val(datos_promocion['fecha']).change().keyup();
                                $(this).find("[data-tipo='nota']").val(datos_promocion['nota']).change().keyup();
                                $(this).find("[data-tipo='resultado']").val(datos_promocion['resultado']).change().keyup();
                                $(this).find("[data-tipo='observacion']").val(datos_promocion['observacion']).change().keyup();
                            }
                        });

                        datosAnterioresRegular = [];
                        datosAnterioresPromocion = [];
                    }

                    $("#btn_undo_nota").on("click", function (e) {
                        e.preventDefault();
                        // Recargo los datos anteriores (botón "Deshacer")
                        recargar_datos_anteriores();
                        $('#btn_undo_nota').prop("disabled", true);
                    });

                    // Si se calculó notas con éxito
                    if (info.mensajes.exito_calcular_nota) {
                        kernel.ui.show_mensaje(info.mensajes.exito_calcular_nota, {tipo: 'alert-success'});
                    }
                    
                    // Si no hay alumnos para calcular nota
                    if (info.mensajes.sin_alumnos_calcular_nota) {
                        kernel.ui.show_mensaje(info.mensajes.sin_alumnos_calcular_nota, {tipo: 'alert-info'});
                    }

                    // Si hubo un error al calcular notas
                    if (info.mensajes.error_calcular_nota) {
                        kernel.ui.show_mensaje(info.mensajes.error_calcular_nota, {tipo: 'alert-error'});
                    }
                        
                    // Si hubo error en la regla de cálculo (notas negativas) muestro facebox
                    if (info.mensajes.error_regla_calculo) {
                        $.facebox(info.mensajes.error_regla_calculo);
                    }
                    
                    // Evento click del botón "Completar" del "Calcular Notas"
                    $("#btn_completar_nota").on("click", function (e) {

                        // Evito el submit del formulario
                        e.preventDefault();
                        calcular_notas();
                        
                    });

                    function calcular_notas()
                    {
                        // Tomo el campo "Instancia"
                        var instancia = $('#instancia').val();
                        var instancias_validas = [
                            info.inst_cursar,
                            info.inst_promocionar
                        ];
                        // Si el campo "Instancia" es inválido
                        if ($.inArray(instancia, instancias_validas) === -1) {
                            kernel.ui.show_mensaje(info.mensajes.instancia_invalida, {tipo: 'alert-error'});
                            return false;
                        }

                        // Tomo el campo "aplicar a" (ej: Todos los alumnos de la página)
                        var para = $('#aplicar_a').val();
                        var para_validos = [
                            info.autocompletar_para_todos_pagina,
                            info.autocompletar_para_sin_datos_pagina,
                            info.autocompletar_para_todos,
                            info.autocompletar_para_sin_datos
                        ];
                        // Si el campo "aplicar a" es inválido
                        if ($.inArray(para, para_validos) === -1) {
                            kernel.ui.show_mensaje(info.mensajes.aplicar_a_invalido, {tipo: 'alert-error'});
                            return false;
                        }

                        // Tomo el campo "regla de cálculo" (ej: Promedio de mejor nota de cada evaluación)
                        var regla_calculo = $('#regla_calculo').val();
                        // Si el campo "regla de cálculo" es inválido
                        if ($.inArray(regla_calculo, info.reglas_calculo) === -1) {
                            kernel.ui.show_mensaje(info.mensajes.regla_calculo_invalido, {tipo: 'alert-error'});
                            return false;
                        }
                        
                        var para_pagina_actual = [
                            info.autocompletar_para_todos_pagina,
                            info.autocompletar_para_sin_datos_pagina
                        ];
                        // Si el campo "aplicar a" es "Todos los alumnos de la página" o "Alumnos de la página sin datos"
                        // solo completo la página actual
                        if ($.inArray(para, para_pagina_actual) !== -1) {

                            calcular_notas_pagina(instancia, para, regla_calculo);

                        } else {// Si el campo "aplicar a" es "Todos los alumnos" o "Alumnos sin datos" voy directo a la DB

                            // Muestro prompt avisando que modificará a todos los alumnos de todas las páginas
                            kernel.ui.prompt({
                                msg: info.mensajes.msg_prompt_modificara_todas_paginas,
                                msg_ok: info.mensajes.aceptar,
                                msg_cancel: info.mensajes.cancelar,
                                ok: function ()
                                {
                                    // Para evitar que muestre dos veces el prompt de abandonar página
                                    sessionStorage.hay_cambios = false;
                                    // Hago el submit del formulario (sin AJAX)
                                    $("#calcular-notas").submit();
                                }
                            });

                        }

                       
                    }
                    
                    function calcular_notas_pagina(instancia, para, regla_calculo)
                    {
                        var alumnos = [];
                        var tipo_instancia;
                        // Guardo los datos anteriores (botón "Deshacer")
                        guardar_datos_anteriores();
                        $('#btn_undo_nota').prop("disabled", false);

                        // Si es Instancia Regularidad
                        if (instancia === info.inst_cursar) {
                            tipo_instancia = $('.js-renglon-regular');
                        // Si es Instancia Promoción
                        } else if (instancia === info.inst_promocionar) {
                            tipo_instancia = $('.js-renglon-promocion');
                        }

                        // solo_vacios es true si eligió "Alumnos de la página sin datos"
                        var solo_vacios = (para === info.autocompletar_para_sin_datos_pagina);

                        tipo_instancia.each(function () {

                            if ($(this).find("[data-tipo='nota']").length > 0) {

                                if (solo_vacios) {
                                    if ($(this).find("[data-tipo='nota']").val() === '') {
                                        alumnos.push($(this).data('renglon'));
                                    }
                                } else {
                                    alumnos.push($(this).data('renglon'));
                                }

                            }

                        });

                        // Si no hay alumnos para calcular notas
                        if (alumnos.length == 0) {
                            kernel.ui.show_mensaje(info.mensajes.msg_sin_alumnos_calcular_nota, {tipo: 'alert-info'});
                            return false;
                        }

                        kernel.ajax.call(info.url_calcular_notas, {
                            type: 'POST',
                            data: {
                                instancia: instancia,
                                regla_calculo: regla_calculo,
                                alumnos: alumnos
                            },
                            success: function (response)
                            {
                                    // Si hubo error muestro el mensaje
                                    if (response.cont.hubo_error) {
                                        kernel.ui.show_mensaje(response.cont.msg_error, {tipo: 'alert-error'});
                                        return false;
                                    }

                                    var alumnosNotas = response.cont.notas;
                                    var error_calculo_nota = false;
                                    var error_numero = '';
                                    // Si hay notas de alumnos
                                    if (Object.keys(alumnosNotas).length > 0) {

                                        tipo_instancia.each(function () {
                                            // Si hubo error al calcular notas
                                            if (alumnosNotas[$(this).data('renglon')] < 0) {
                                                error_calculo_nota = true;
                                                error_numero = alumnosNotas[$(this).data('renglon')];
                                            } else {// Si no hubo error al calcular notas
                                                // Si completa "Alumnos de la página sin datos"
                                                if (solo_vacios) {
                                                    if ($(this).find("[data-tipo='nota']").val() === '') {
                                                        $(this).find("[data-tipo='nota']").val(alumnosNotas[$(this).data('renglon')]).change().keyup();
                                                    }
                                                // Si completa "Todos los alumnos de la página"
                                                } else {
                                                    $(this).find("[data-tipo='nota']").val(alumnosNotas[$(this).data('renglon')]).change().keyup();
                                                }
                                            }
                                        });

                                    }

                                    // Si hubo error al calcular notas lo muestro en un popup
                                    if (error_calculo_nota === true) {
                                        var informacion = "";
                                        var mensaje_error = "";

                                        switch (error_numero) {
                                            case '-1':
                                                mensaje_error = info.mensajes.escalas_notas_deben_ser_numericas;
                                                break;
                                            case '-2':
                                                mensaje_error = info.mensajes.no_hay_evaluaciones_promediables;
                                                break;
                                            case '-3':
                                                mensaje_error = info.mensajes.escalas_notas_deben_ser_consistentes;
                                                break;
                                            case '-4':
                                                mensaje_error = info.mensajes.no_hay_evaluaciones_comision;
                                                break;
                                            default:
                                                mensaje_error = info.mensajes.error_desconocido;
                                        }

                                        informacion = $("<div style='font-weight: bold; text-align: center;'>"+info.mensajes.no_pueden_calcular_notas+"</div><br /><div>"+mensaje_error+"</div>");

                                        $.facebox(informacion);
                                    // si se calcularon las notas con exito
                                    } else {
                                        // Luego de "Calcular Notas" se muestran todos los renglones de la página para que vea que se modificaron
                                        $('#notas_cursada_filtrar_acta').val("todas-todas").change();
                                        kernel.ui.show_mensaje(info.mensajes.msg_exito_calcular_nota, {tipo: 'alert-success'});
                                    }
                            }
                        });
                    }
                    
                    // Espero 5 segundos antes de llamar a la función "setup_links" ya que sino no toma todos los links
                    const setup_links_timeout = setTimeout(setup_links, 5000);
                    setup_unload();
                    
                    // Tomo el campo seleccionado y muestro "preset_valor" correspondiente a dicho campo
                    setup_preset_valor();
			
                }};//onLoad
});