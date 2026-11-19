kernel.renderer.registrar_pagelet('nueva_evaluacion', function (info) {
	var id = '#' + info.id;

	return {
		onload: function () {
                        $('form').keypress(function(e){
                            if(e.which == 13){
				return false; 
                            }
                        });	
			var $form = $('form').formulario();

			$form.find('input.date').datepicker('destroy');
			$form.find('input.date').datepicker(
				{
					changeMonth: true,
					changeYear: true,
					firstDay: 0,
					yearRange: "-1:+3"
					//maxDate: "+0"
				});
			$('.js-btn-back').on('click', function () {
				guarani.cambiar_op({
					href: info.url_index
				});
				return false;
			});

			$('#btn-crear').on('click'/*)
			 $(id).on('submit', 'form'*/, function () {
				var data = $form.serializeArray();

				kernel.ajax.call($form.attr('action'), {
					type: 'post',
					data: data,
					//historia: false,
					success: function (response) {

//						//alert('vuelve' + response.estado);
//						if (response.estado == 'ok') {
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
							if(response.error_hora_fin){
								var control_group_hora_fin = $("#nueva_eval-hora_fin").parents('.control-group');
								control_group_hora_fin.addClass('error');
								control_group_hora_fin.find('.text-error').html(response.error_hora_fin);
							}
							if (response.error_fecha) {
								var control_group_fecha = $("#nueva_eval-fecha").parents('.control-group');
								control_group_fecha.addClass('error');
								control_group_fecha.find('.text-error').html(response.error_fecha);
							}
							kernel.ui.show_mensaje(response.mensaje, {tipo: 'alert-error'});
							//kernel.renderer.pagelet('nueva_evaluacion').onload();
						}


//							guarani.cambiar_op({
//								href: info.url_index,
//								mensaje_post_navegacion: {
//									mensaje: response.cont,
//									options: {tipo : 'alert-error'}
//								}
//							});
//						else
//							kernel.ui.show_mensaje(paquete.cont, {tipo: 'alert-error'});
						//alert('hola');
						//guarani.cambiar_op({href:url});
						//on_succes();
						//console.log(response.cont);
						//kernel.renderer.pagelet('comisiones').onload();
						//kernel.ui.show_mensaje(info.mensajes.mensaje_exito);

					}
				});
				return false;
			});


			//$('#fecha').datepicker({defaultDate: info.fecha});
			//$('#visible_si').attr('checked', true);
			//$('#promediable_si').attr('checked', true);

			$('#submit').on('click', function () {
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

				if (!error) {
					var relacionadas = new Array();
					$('#relacionadas input[name="relacionadas"]:checked').each(function (index, value) {
						relacionadas.push($(value).val());
					});
					var instancias = new Array();
					$('#instancias input[name="instancias"]:checked').each(function (index, value) {
						instancias.push($(value).val());
					});
					kernel.ajax.call(info.url_crear_evaluacion, {
						type: 'post',
						async: false,
						data: {
							comision: info.comision,
							fecha: $('#fecha').val(),
							hora_inicio: $('#hora_inicio').val(),
							minutos_inicio: $('#minutos_inicio').val(),
							hora_fin: $('#hora_fin').val(),
							minutos_fin: $('#minutos_fin').val(),
							nombre: $('#nombre').val(),
							descripcion: $('#descripcion').val(),
							tipo: $('#tipo').val(),
							visible: $('input[name=visible]:checked').val(),
							escala: $('#escala').val(),
							promediable: $('input[name=promediable]:checked').val(),
							relacionadas: relacionadas,
							instancias: instancias
						},
						success: function (paquete) {
							if (paquete.result == 0)
								guarani.cambiar_op({
									href: info.url_index,
									mensaje_post_navegacion: {
										mensaje: paquete.cont,
										options: {tipo: 'alert-success'}
									}
								});
							else if (paquete.result < 0)
								guarani.cambiar_op({
									href: info.url_index,
									mensaje_post_navegacion: {
										mensaje: paquete.cont,
										options: {tipo: 'alert-error'}
									}
								});
							else
								kernel.ui.show_mensaje(paquete.cont, {tipo: 'alert-error'});
						}
					});
				}
			});
		}
	}
});