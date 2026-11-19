kernel.renderer.registrar_pagelet('edicion_asistencias', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			calcular_totales();

			$(id).on('click', '.box-asistencia.tipo-carga1', function ()
			{
				var box = $(this);
                                
                                // Si hay justificación cargada deshabilito la modificación de inasistencias
                                if (box.hasClass('justificada')) {
                                    box.find('.check-asistencia input').prop('disabled', true);
                                    return false;
                                }
                                
				if (box.hasClass('ausente')) {
					box.find('.check-asistencia input').prop('checked', true);
					box.removeClass('ausente');
				} else {
					box.find('.check-asistencia input').prop('checked', false);
					box.addClass('ausente');
				}
				calcular_totales();
			});

			$(id).on('change', 'select.combo-fracciones', function ()
			{
				var box = $(this).parents('.box-asistencia');
				var combo = $(this);

                                // Si hay justificación cargada deshabilito la modificación de inasistencias
                                if (box.hasClass('justificada')) {
                                    combo.val(1);
                                    combo.prop('disabled', true);
                                }
                                
				if (combo.val() !== "0") {
					box.addClass('ausente');
				} else {
					box.removeClass('ausente');
				}
				calcular_totales();
			});

			$(id).on('change', 'select.combo-horas', function ()
			{
				var box = $(this).parents('.box-asistencia');
				var combo = $(this);

                                // Si hay justificación cargada deshabilito la modificación de inasistencias
                                if (box.hasClass('justificada')) {
                                    combo.val(1);
                                    combo.prop('disabled', true);
                                }
                                
				if (combo.val() !== "0") {
					box.addClass('ausente');
				} else {
					box.removeClass('ausente');
				}

				calcular_totales();
			});



			$(id).find('.form-renglones').submit(function ()
			{
				var $form = $(this);
				kernel.ajax.call($form.attr('action'), {
					type: 'POST',
					data: $form.serializeArray(),
					success: function (response)
					{
						kernel.ui.show_mensaje(response.cont);
					}
				});
				return false;
			});

			function calcular_totales()
			{
				var total = $('.box-asistencia').length;
				var ausentes = $('.box-asistencia.ausente').length;
				var presentes = total - ausentes;

				$('#js-asistencia-total').text(total);
				$('#js-asistencia-presentes').text(presentes);
				$('#js-asistencia-ausentes').text(ausentes);
			}
		}
	}
})