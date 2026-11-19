kernel.renderer.registrar_pagelet('cabecera', function (info)
{
	var id = '#' + info.id;

	function bind_escala_notas(btn_id, btn_url)
	{
		var escala_notas_cargada = false;
		var tabla_notas = $("<table style='margin-top: 16px'><thead><th>" + info.msg_valor + "</th><th>" + info.msg_descripcion + "</th><th>" + info.msg_resultado + "</th></thead><tbody></tbody></table>");
		tabla_notas.addClass('table').addClass('table-condensed');
		$(btn_id).on("click", function ()
		{
			if (!escala_notas_cargada) {
				kernel.ui.show_loading();
				$.ajax({
					url: btn_url,
					success: function (res)
					{
						$(JSON.parse(res)).each(function ()
						{
							var escala = $(this)[0];
							var fila = $('<tr>');
							fila.append($('<td>' + escala.nota + '</td>'));
							fila.append($('<td>' + escala.descripcion + '</td>'));
							fila.append($('<td>' + escala.resultado + '</td>'));
							tabla_notas.find('tbody').append(fila);
						});
						escala_notas_cargada = true;
						kernel.ui.hide_loading();
						$.facebox(tabla_notas);
					}
				});
			} else {
				$.facebox(tabla_notas);
			}
		});
	}

	return {
		onload: function ()
		{
			bind_escala_notas('#ver_escala_regularidad', info.url_escala_regularidad);
			bind_escala_notas('#ver_escala_promocion', info.url_escala_promocion);
			$(id).find('button#js-colapsar-info-mesa').on("click", function ()
			{
				var barra = $('.js-barra-autocompletar');
				if (barra.is(':visible')) {
					barra.slideUp();
				}

				var detalle = $('.js-detalle-materia');

				if (detalle.is(':visible')) {
					detalle.slideUp();
				} else {
					detalle.slideDown();
				}
			});

			$(id).find('button#js-colapsar-autocompletar').on("click", function ()
			{
				var detalle = $('.js-detalle-materia');

				if (detalle.is(':visible')) {
					detalle.slideUp();
				}

				if($('.js-barra-calcular-notas').is(':visible')) {
					$('.js-barra-calcular-notas').slideUp();
				}
			   
				var barra = $('.js-barra-autocompletar');

				if (barra.is(':visible')) {
					barra.slideUp();
				} else {
					barra.slideDown();
				}
			});
			
			$(id).find('button#js-colapsar-barra-calcular-notas').on("click", function ()
			{
				var detalle = $('.js-detalle-materia');

				if (detalle.is(':visible')) {
					detalle.slideUp();
				}
				
				if($('.js-barra-autocompletar').is(':visible')) {
					$('.js-barra-autocompletar').slideUp();
				}

				var barra = $('.js-barra-calcular-notas');

				if (barra.is(':visible')) {
					barra.slideUp();
				} else {
					barra.slideDown();
				}
			});
			
		}
	}
})