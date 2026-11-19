kernel.renderer.registrar_pagelet('edicion_notas', function (info) {
	var id = '#' + info.id;
	var hay_alumnos = info.hay_alumnos;
	var undos = new Array();
	var eliminar_alumno = function () {
		var tr = $(this).closest('tr');
		kernel.ui.prompt({
			msg: '¿Está seguro que desea quitar al alumno de la evaluación?',
			msg_ok: 'Aceptar',
			ok: function () {
				kernel.ajax.call(info.url_eliminar, {
					type: 'POST',
					async: false,
					data: {
						evaluacion: info.evaluacion,
						alumno: tr.data('alumno'),
						ultimo_cambio: tr.data('ultimo-cambio')
					},
					success: function (response) {
						if (response.result == 0) {
							vaciar_undos();
							tr.remove();
                                                        if ($('#lista_alumnos tbody tr').length == 0) 
                                                            $('#lista_alumnos').html(response.html);
							$('.js-eliminar_alumno').on("click", eliminar_alumno);
							kernel.ui.show_mensaje(response.cont, {tipo: 'alert-success', until_interaction: false});
							info.alumnos = response.alumnos;
						}
						else if (response.result < 0) {
							guarani.cambiar_op({
								href: info.url_index,
								mensaje_post_navegacion: {
									mensaje: paquete.cont,
									options: { tipo: 'alert-error' }
								}
							});
						}
						else {
							vaciar_undos();
							$('#lista_alumnos').html(response.html);
							$('.js-eliminar_alumno').on("click", eliminar_alumno);
							kernel.ui.show_mensaje(response.cont, {tipo: 'alert-error', until_interaction: true});
						}
                                                // Si luego de autocompletar se modifica algún campo, entonces se deshabilita el botón "Deshacer"
                                                $('#autocompletar_undo').prop("disabled", true);
					}
				});
			}
		});
	};
	var guardar_alumnos = function () {
		var alumnos = new Array();
		$('#lista_alumnos tbody tr').each(function () {
			var tr = $(this);
			var alumno = tr.data('alumno');
			var nota = tr.find('[name="nota"]').val();
			var resultado = tr.find('select.js-resultado-select').val();
			var corregido_por = tr.find('[name="corregido_por"]').val();
			var observaciones = tr.find('[name="observaciones"]').val();
			if (typeof(info.alumnos[alumno]) != 'undefined' &&
				((typeof(info.alumnos[alumno]['nota']) != 'undefined' && info.alumnos[alumno]['nota'] != nota) ||
                                        (typeof(info.alumnos[alumno]['resultado']) != 'undefined' && info.alumnos[alumno]['resultado'] != resultado) ||
					(typeof(info.alumnos[alumno]['corregido_por']) != 'undefined' && info.alumnos[alumno]['corregido_por'] != corregido_por) ||
					(typeof(info.alumnos[alumno]['observaciones']) != 'undefined' && info.alumnos[alumno]['observaciones'] != observaciones)))
				alumnos.push({
					alumno: alumno,
					nota: nota,
					resultado: resultado,
					corregido_por: corregido_por,
					observaciones: observaciones,
					ultimo_cambio: tr.data('ultimo-cambio')
				});
		});
		if (alumnos.length == 0) {
			kernel.ui.show_mensaje('No ha hecho ninguna modificación.', {tipo: 'alert-error'});
			return;
		}
		kernel.ajax.call(info.url_guardar, {
			type: 'POST',
			async: false,
			data: {
				evaluacion: info.evaluacion,
				alumnos: alumnos
			},
			success: function (response) {
				if (response.result == 0) {
					vaciar_undos();
					$('#lista_alumnos').html(response.html);
					$('.js-eliminar_alumno').on("click", eliminar_alumno);
					if (response.fallidos)
						kernel.ui.show_mensaje(response.cont, {tipo: 'alert-error', until_interaction: true});
					else
						kernel.ui.show_mensaje(response.cont, {tipo: 'alert-success', until_interaction: false});
					info.alumnos = response.alumnos;
				}
				else if (response.result < 0) {
					guarani.cambiar_op({
						href: info.url_index,
						mensaje_post_operacion: {
							mensaje: paquete.cont,
							options: { tipo: 'alert-error' }
						}
					});
				}
				else
					kernel.ui.show_mensaje(response.cont, {tipo: 'alert-error', until_interaction: true});
			}
		});
	};

	function agregar_error_nota_css(element) {
        if (element.hasClass("error_nota")) {
            return;
        }
        element.attr('data-original-title', info.mensajes.nota_invalida);
        element.tooltip({container: 'body'});
        element.tooltip('enable');
        element.tooltip('show');
        element.addClass("error_nota");
        return;
    }

    function quitar_error_nota_css(element) {
        if (!element.hasClass("error_nota")) {
            return;
        }
        element.tooltip('hide');
        element.tooltip('disable');
        element.removeClass("error_nota");
        return;
    }

    var validar_nota = function () {
        var element = $(this);
        var nota = $(this).val();
        var escala = guarani.escalas().recuperar(info.id_escala);
            var id = $(this).attr('id');

        if (escala.validar(nota) || nota == "") {
            quitar_error_nota_css(element);
            if (id == 'autocompletar_nota_input')
                $('#autocompletar').removeClass('disabled')
        }
        else{
            agregar_error_nota_css(element);
            if (id == 'autocompletar_nota_input')
                $('#autocompletar').addClass('disabled')
        }
        // Si luego de autocompletar se modifica algún campo, entonces se deshabilita el botón "Deshacer"
        $('#autocompletar_undo').prop("disabled", true);
    };

    var cambiar_nota = function (event) {
        var element = $(this);
        var nota = $(this).val();
        var input_nota = $(this).attr("id");
        var escala = guarani.escalas().recuperar(info.id_escala);
        var nota_formateada = '';
        var fila = $(this).parents("tr");

        if (nota != '')
        {
            if (escala.get_tipo() == info.escala_continua.toLocaleLowerCase())
            {
                if (escala.validar(nota)) {
                    var nota_formateada = escala.formatear(nota);
                    $('#' + input_nota).val(nota_formateada);
                    quitar_error_nota_css(element);
                } else {
                    agregar_error_nota_css(element);
                }
            }
            
            // En caso de NO ser el campo autocompletar nota relleno la descripcion
            if (input_nota != 'autocompletar_nota_input')
                // Si la nota es de tipo DISCRETA, no necesito formatearla para encontrar
                // la descripcion del resultado y rellenar el html.
                if (escala.get_tipo() == info.escala_discreta.toLocaleLowerCase()) {
                    cambiar_resultado(fila, escala.get_resultado(nota), escala.get_resultado_descr(nota));
                } else {
                    cambiar_resultado(fila, escala.get_resultado(nota_formateada), escala.get_resultado_descr(nota_formateada));
                }
                
        } else {   // Si la nota es vacia o no es valida relleno con blanco la descripcion.
            if (input_nota != 'autocompletar_nota_input') {
                cambiar_resultado(fila, "", "");
            }
            quitar_error_nota_css(element);
        }
        // Si luego de autocompletar se modifica algún campo, entonces se deshabilita el botón "Deshacer"
        $('#autocompletar_undo').prop("disabled", true);
    };
    
    function cambiar_resultado(fila, resultado, resultado_descr) {
        if ($(fila).find(".js-resultado-select").length) {
            $(fila).find(".js-resultado-select").val(resultado);
        } else if ($(fila).find(".js-resultado-text").length) {
            $(fila).find(".js-resultado-text").html(resultado_descr);
        }
    }

    var resultado_change = function (event) {
        $(this).parents("tr").find("select.nota").val("");
        $(this).parents("tr").find("input.nota_input").val("");
        // Si luego de autocompletar se modifica algún campo, entonces se deshabilita el botón "Deshacer"
        $('#autocompletar_undo').prop("disabled", true);
    }
    
    var corregido_por_change = function (event) {
        // Si luego de autocompletar se modifica algún campo, entonces se deshabilita el botón "Deshacer"
        $('#autocompletar_undo').prop("disabled", true);
    }
    
    var observaciones_change = function (event) {
        // Si luego de autocompletar se modifica algún campo, entonces se deshabilita el botón "Deshacer"
        $('#autocompletar_undo').prop("disabled", true);
    }
    
	function vaciar_undos() {
		undos = new Array();
		$('#autocompletar_undo').attr('disabled', 'disabled');
	}

	function agregar_alumnos(response) {
		if (response.result == 0) {
			if (response.agregados == 0)
				kernel.ui.show_mensaje('No hay cambios para realizar.', {tipo: 'alert-error', until_interaction: true});
			else {
				vaciar_undos();
				$('#lista_alumnos').html(response.html);
				$('.js-eliminar_alumno').on("click", eliminar_alumno);
			}
			info.alumnos = response.alumnos;
		}
		else if (response.result < 0) {
			guarani.cambiar_op({
				href: info.url_index,
				mensaje_post_operacion: {
					mensaje: paquete.cont,
					options: { tipo: 'alert-error' }
				}
			});
		}
		else
			kernel.ui.show_mensaje(response.cont, {tipo: 'alert-error', until_interaction: true});
	}

	return {
		onload: function () {
			//CABECERA
			$('#js-colapsar-alumnos').on("click", function () {
				$('#js-detalles:visible').slideUp();
				$('#js-autocompletar:visible').slideUp();
				var div = $('#js-alumnos');
				div.is(':visible') ? div.slideUp() : div.slideDown();
			});
			$('#js-colapsar-detalles').on("click", function () {
				$('#js-alumnos:visible').slideUp();
				$('#js-autocompletar:visible').slideUp();
				var div = $('#js-detalles');
				div.is(':visible') ? div.slideUp() : div.slideDown();
			});
			$('#js-colapsar-autocompletar').on("click", function () {
				$('#js-alumnos:visible').slideUp();
				$('#js-detalles:visible').slideUp();
				var div = $('#js-autocompletar');
				div.is(':visible') ? div.slideUp() : div.slideDown();
			});
			$('#ver_escala').on("click", function () {
				kernel.ajax.call(info.url_escala, {success: function (paquete) {
					$.facebox(paquete.html);
				}});
			});

			//AGREGAR ALUMNOS
			$('#js-agregar_alumnos').on('change', function () {
				$('#js-agregar_alumno:visible').slideUp();
				$('#js-agregar_comision:visible').slideUp();
				$('#js-agregar_subcomision:visible').slideUp();
				$('#js-agregar_resultado:visible').slideUp();
				$('#' + $(this).val()).slideDown();
			});
			$('#js-alumno').plugin_autocompletar({url: info.url_buscar_alumnos});
			$('#js-boton_agregar_alumno').on("click", function () {
				var alumno = $('#js-alumno-autocomplete').val();
				if (!alumno) {
					$('#js-alumno').addClass('error');
					return;
				}
				else
					$('#js-alumno').removeClass('error');
				kernel.ajax.call(info.url_agregar_alumno, {
					type: 'post',
					async: false,
					data: {
						evaluacion: info.evaluacion,
						alumno: alumno
					},
					success: function (response) {
						agregar_alumnos(response);
						$('#js-alumno').focus();
					}
				});
			});
			$('#js-boton_agregar_comision').on("click", function () {
				kernel.ajax.call(info.url_agregar_comision, {
					type: 'post',
					async: false,
					data: {
						evaluacion: info.evaluacion
					},
					success: function (response) {
						agregar_alumnos(response);
					}
				});
			});
			$('#js-boton_info_subcomision').on("click", function () {
				kernel.ajax.call(info.url_subcomision, {
					data: {
						evaluacion: info.evaluacion,
						subcomision: $('#js-subcomision').val()
					},
					success: function (response) {
						$.facebox(response.html);
					}
				});
			});
			$('#js-boton_agregar_subcomision').on("click", function () {
				kernel.ajax.call(info.url_agregar_subcomision, {
					type: 'post',
					async: false,
					data: {
						evaluacion: info.evaluacion,
						subcomision: $('#js-subcomision').val()
					},
					success: function (response) {
						agregar_alumnos(response);
					}
				});
			});
			$('#js-boton_agregar_resultado').on("click", function () {
				kernel.ajax.call(info.url_agregar_resultado, {
					type: 'post',
					async: false,
					data: {
						evaluacion: info.evaluacion,
						resultado: $('#js-resultado').val(),
						origen: $('#js-evaluacion').val()
					},
					success: function (response) {
						agregar_alumnos(response);
					}
				});
			});
                        
			//AUTOCOMPLETAR
			$('#autocompletar_campo').on('change', function () {
				$('#js-autocompletar_nota:visible').hide();
				$('#js-autocompletar_nota_input:visible').hide();
				$('#js-autocompletar_corregido_por:visible').hide();
				$('#js-autocompletar_resultado:visible').hide();
				$('#js-autocompletar_' + $(this).val()).show();
			});
                        
            $('#autocompletar').on("click", function () {

                if (!$(this).hasClass('disabled')) {
                    var error = $('#js-autocompletar_error');
                    error.hide();
                    var hubo_cambios = false;
                    var campo = $('#autocompletar_campo').val();
                    var solo_vacio = $('#autocompletar_solo_vacio').is(':checked');
                    var valor = $('#autocompletar_' + campo).val();
                    var undo = {
                        tipo: campo
                    };

                    var filas_undo = $('#lista_alumnos tbody tr').clone();
                                
                    // Recorro los renglones actuales
                    $('#lista_alumnos tbody tr').each(function (i)
                    {
                        // Se guardan los valores anteriores al autocompletar en atributos data.
                        $(this).data("notaAnt", ($(filas_undo[i]).find(".nota_input").length)? $(filas_undo[i]).find(".nota_input").val() : $(filas_undo[i]).find(".nota").val());
                        $(this).data("resultadoAnt", $(filas_undo[i]).find(".js-resultado-select").val());
                        $(this).data("corregidoAnt", $(filas_undo[i]).find(".corregido_por").val());
                        $(this).data("observacionesAnt", $(filas_undo[i]).find(".observaciones").val());

                        // Si se trata de "corregido_por"
                        if (campo === 'corregido_por') {

                            if (valor.length < 20)
                            {
                                var corregido_por_obj = $(this).find(".corregido_por");
                                // Si "Completar sólo vacíos" esta tildado y "Corregido por" ya tiene un valor, entonces salteo dicha fila
                                if (solo_vacio && $(corregido_por_obj).val() != '') {
                                    return;
                                }
                                $(corregido_por_obj).val(valor);
                                hubo_cambios = true;
                                
                            } else {
                                info.texto_invalido = info.texto_invalido.replace('%max_textarea%', 20);
                                error.text(info.texto_invalido);
                                error.show();
                            }

                        } else {// Si se trata de la nota o resultado

                            var nota_obj = ($(this).find(".nota_input").length)? $(this).find(".nota_input") : $(this).find(".nota");
                            var nota_val = $(nota_obj).val();
                            var resultado_obj = $(this).find(".js-resultado-select");
                            var resultado_val = $(resultado_obj).val();

                            // Si "Completar sólo vacíos" esta tildado y la nota o resultado tiene un valor, entonces salteo dicha fila
                            if (solo_vacio && (nota_val != '' || resultado_val != '')) {
                                return;
                            }

                            if (campo === 'resultado') {
                                $(resultado_obj).val(valor);
                                $(resultado_obj).trigger( "change" );
                                $(resultado_obj).trigger( "keyup" );
                                $(resultado_obj).trigger( "focusout" );
                            } else {
                                $(nota_obj).val(valor);
                                $(nota_obj).trigger( "change" );
                                $(nota_obj).trigger( "keyup" );
                                $(nota_obj).trigger( "focusout" );
                            }
                            hubo_cambios = true;
                        }
                    });

                    if (hubo_cambios)
                    {
                        undos.push(undo);
                        $('#autocompletar_undo').prop("disabled", false);
                    }
                }
            });

            $('#autocompletar_undo').on("click", function () {
                
                $('#js-autocompletar_error').hide();
                
                // Recorro los renglones actuales para ponerle los valores anteriores
                $('#lista_alumnos tbody tr').each(function ()
                {
                    var nota_obj = ($(this).find(".nota_input").length)? $(this).find(".nota_input") : $(this).find(".nota");
                    $(nota_obj).val($(this).data("notaAnt"));
                    $(this).find(".nota").val($(this).data("notaAnt"));
                    $(this).find(".js-resultado-select").val($(this).data("resultadoAnt"));
                    $(this).find(".corregido_por").val($(this).data("corregidoAnt"));
                    $(this).find(".observaciones").val($(this).data("observacionesAnt"));
                    
                });
                            
                var undo = undos.pop();
                var tipo = undo.tipo;
                
                if (undos.length == 0){
                    $('#autocompletar_undo').attr('disabled', 'disabled');
                }
            });

            //NOTA ONCHANGE
            $(id).on('change', 'select.nota', cambiar_nota);

            //RESULTADO ONCHANGE
            $(id).on('change', 'select.js-resultado-select', resultado_change);

            //CORREGIDO POR KEYUP
            $(id).on('keyup', 'input.corregido_por', corregido_por_change);
            
            //OBSERVACIONES POR KEYUP
            $(id).on('keyup', 'input.observaciones', observaciones_change);

            //VALIDACION Y FORMATEO NOTA
            $(id).on('focusout', 'input.nota_input', cambiar_nota);
            $(id).on('keyup', 'input.nota_input', validar_nota);
            $(id).on('focusout', 'input.autocompletar_nota', cambiar_nota);
            $(id).on('keyup', 'input.autocompletar_nota', validar_nota);
            //ELIMINAR
            $('.js-eliminar_alumno').on("click", eliminar_alumno);
            //GUARDAR
            $(id).on('click', '#js-guardar_alumnos', guardar_alumnos);

			// $('.js-btn-back').on('click', function () {
			// 	guarani.cambiar_op({
			// 		href: info.url_index
			// 	});
			// 	return false;
			// });
		}
	}
});