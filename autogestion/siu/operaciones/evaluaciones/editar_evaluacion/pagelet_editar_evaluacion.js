kernel.renderer.registrar_pagelet('editar_evaluacion', function (info) {
    var id = '#' + info.id;

    return {
        onload: function () {

            $(id).find('#js-colapsar-info-mesa').on("click", function () {
                var detalle = $('#js-detalle-materia');
                detalle.is(':visible') ? detalle.slideUp() : detalle.slideDown();
            });

            var $form = $('form').formulario();

            $form.find('input.date').datepicker('destroy');
            $form.find('input.date').datepicker(
                    {
                        changeMonth: true,
                        changeYear: true,
                        firstDay: 0,
                        yearRange: "-1:+3"
                    });
                  
            // Si existen alumnos para dicha evaluación, no permito la modificación de los campos Tipo, y Escala .
            if (info.con_alumnos_cargados) {
               // Tipo
                $("#nueva_eval-evaluacion_tipo").after('<input type="hidden" name="nueva_eval[evaluacion_tipo]" value="'+$("#nueva_eval-evaluacion_tipo").val()+'">');
                $("#nueva_eval-evaluacion_tipo").attr('disabled', true);
                $("#nueva_eval-evaluacion_tipo").prop('disabled', true);
                $("#nueva_eval-evaluacion_tipo").tooltip({'title': info.mensajes.fecha_evaluacion_no_modificable});
                // Escala
                $("#nueva_eval-escala_nota").after('<input type="hidden" name="nueva_eval[escala_nota]" value="'+$("#nueva_eval-escala_nota").val()+'">');
                $("#nueva_eval-escala_nota").attr('disabled', true);
                $("#nueva_eval-escala_nota").prop('disabled', true);
                $("#nueva_eval-escala_nota").tooltip({'title': info.mensajes.fecha_evaluacion_no_modificable});
               
                     
            }

            $('.js-btn-back').on('click', function () {
                guarani.cambiar_op({
                    href: info.url_index
                });
                return false;
            });

            $('#submit').on('click', function () {
                var data = $form.serializeArray();

                kernel.ajax.call(info.url_actualizar_evaluacion, {
                    type: 'post',
                    data: data,
                    success: function (response) {
//						if (response.estado == 'ok') {
//
//							guarani.cambiar_op({
//								href: info.url_index,
//								mensaje_post_navegacion: {
//									mensaje: response.cont,
//									options: {tipo: 'alert-success'}
//								}
//							});
//						}
                        if (response.estado == 'error') {
                            $(id).html(response.cont);
                            kernel.ui.show_mensaje(response.mensaje, {tipo: 'alert-error'});
                            if (response.error_hora_fin) {
                                var control_group_hora_fin = $("#nueva_eval-hora_fin").parents('.control-group');
                                control_group_hora_fin.addClass('error');
                                control_group_hora_fin.find('.text-error').html(response.error_hora_fin);
                            }
                            if (response.error_fecha) {
                                var control_group_fecha = $("#nueva_eval-fecha").parents('.control-group');
                                control_group_fecha.addClass('error');
                                control_group_fecha.find('.text-error').html(response.error_fecha);
                            }
                            //kernel.renderer.pagelet('nueva_evaluacion').onload();
                        }
                    }
                })
            });
        }
    }
});


//			$('#submit').on('click', function() {
//				var error = false;
//				$('.obligatorio').each(function() {
//					if (!$(this).val())
//					{
//						error = true;
//						$(this).addClass('error');
//					}
//					else
//						$(this).removeClass('error');
//				});
//				var hora_fin = $('#hora_fin'), minutos_fin = $('#minutos_fin');
//				if (hora_fin.val() && !minutos_fin.val())
//				{
//					error = true;
//					minutos_fin.addClass('error');
//					hora_fin.removeClass('error');
//				}
//				else if (!hora_fin.val() && minutos_fin.val())
//				{
//					error = true;
//					hora_fin.addClass('error');
//					minutos_fin.removeClass('error');
//				}
//				else
//				{
//					hora_fin.removeClass('error');
//					minutos_fin.removeClass('error');
//				}
//
//				if (!error)
//				{
//					var relacionadas = new Array();
//					$('#relacionadas input[name="relacionadas"]:checked').each(function(index, value) {
//						relacionadas.push($(value).val());
//					});
//					var instancias = new Array();
//					$('#instancias input[name="instancias"]:checked').each(function(index, value) {
//						instancias.push($(value).val());
//					});
//					kernel.ajax.call(info.url_editar_evaluacion, {
//						type: 'post',
//						async: false,
//						data: {
//							evaluacion: info.evaluacion,
//							fecha: $('#fecha').val(),
//							hora_inicio: $('#hora_inicio').val(),
//							minutos_inicio: $('#minutos_inicio').val(),
//							hora_fin: $('#hora_fin').val(),
//							minutos_fin: $('#minutos_fin').val(),
//							nombre: $('#nombre').val(),
//							descripcion: $('#descripcion').val(),
//							tipo: $('#tipo').val(),
//							visible: $('input[name=visible]:checked').val(),
//							escala: $('#escala').val(),
//							promediable: $('input[name=promediable]:checked').val(),
//							relacionadas: relacionadas,
//							instancias: instancias
//						},
//						success: function(paquete) {
//						/}
//					});
//				}
//			});
