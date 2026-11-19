kernel.renderer.registrar_pagelet('examenes', function(info) {
	var id = '#' + info.id;

	function listado()
	{
		$('tr.examen').on('click', function() {
			guarani.cambiar_op({
				href: info.url_detalle
					+'?url_acta='+$(this).data('link')
					+'&actividad='+$('#js-filtro_actividades-autocomplete').val()
					+'&turno='+$('#js-filtro_turno_examen').val()
					+'&acta='+$('#js-filtro_acta-autocomplete').val()
					+'&origen=E'
			});
		});
		var actas = new Array();
		actas.push({id: '', label: 'Todas'});
		$('tr.examen').each(function() {
			actas.push({id: $(this).data('acta').toString(), label: $(this).data('acta').toString()});
		});
		var input = $('#js-filtro_acta');
		input.plugin_autocompletar({
			url: actas,
			select: function(event, ui) {
				var acta = ui;
				if (acta)
				{
					$('tr.examen').each(function() {
						if ($(this).data('acta') == acta)
							$(this).show();
						else
							$(this).hide();
					});
				}
				else
				{
					$('tr.examen').each(function() {
						$(this).show();
					});
				}
			},
			valor_default: {
				id: info.acta ? info.acta : '',
				value: info.acta ? info.acta : 'Todas'
			},
			min_length: 1
		});
		if (info.acta)
		{
			$('tr.examen').each(function() {
				if ($(this).data('acta') == info.acta)
					$(this).show();
				else
					$(this).hide();
			});
		}
	}

	return {
		onload: function() {
			$('#js-filtro_actividades').plugin_autocompletar({
				url: info.url_filtrar_actividades,
				select: filtrar,
				valor_default: {
					id: info.id_actividad,
					value: info.nombre_actividad ? info.nombre_actividad : 'Todas'
				}
			});

			$('#js-limpiar_actividades').on('click', function() {
				$('#js-filtro_actividades').val('Todas');
				$('#js-filtro_actividades-autocomplete').val('');
                                $('#js-filtro_actividades-autocomplete').trigger('change');
				filtrar();
			});
                        
                        
                        (function() {
                            var ev = new $.Event('style'),
                            orig = $.fn.css;
                            $.fn.css = function() {
                                $(this).trigger(ev);
                                return orig.apply(this, arguments);
                            }
                        })();

                        $('#js-filtro_actividades').on('style', function(e) {
                            $('#js-filtro_actividades-autocomplete').trigger('change');
                        });
                        
                        $('#js-filtro_actividades-autocomplete').on('change', function ()
			{
                            if($(this).val() != '') { 
                                $('#js-filtro_actividades').prop('readonly', true); }
                            else { 
                                $('#js-filtro_actividades').prop('readonly', false); }
			});

			$('#js-filtro_turno_examen').on('change', function() {
				filtrar();
			});

			function filtrar()
			{
				var url = info.url_filtrar_actas+'?origen=E&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&turno='+$('#js-filtro_turno_examen').val();
				kernel.ajax.call(url, {
					async: false,
					success: function(paquete) {
						$('#listado').html(paquete.html);
						listado();
					}
				});
			}

			listado();

			$('#js-limpiar_actas').on('click', function() {
				$('#js-filtro_acta').val('Todas');
				$('#js-filtro_acta-autocomplete').val('');
				$('tr.examen').each(function() {
					$(this).show();
				});
			});

			$('#examenes-encabezado').on('click', function() {
				$('#js-encabezados').toggle();
				if ($('#js-encabezados').css('display') == "none")
					$(this).text(info.mostrar_encabezado);
				else
					$(this).text(info.ocultar_encabezado);
			});

			$('#examenes-generar_pdf_listado').on('click', function() {
				window.open(info.url_generar_pdf+'?origen=E&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&turno='+$('#js-filtro_turno_examen').val());
				return false;
			});

			$('#examenes-generar_xls_listado').on('click', function() {
				window.open(info.url_generar_xls+'?origen=E&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&turno='+$('#js-filtro_turno_examen').val());
				return false;
			});

			$('#examenes-generar_pdf_detalle').on('click', function() {
				window.open(info.url_generar_pdf);
				return false;
			});

			$('#examenes-generar_xls_detalle').on('click', function() {
				window.open(info.url_generar_xls);
				return false;
			});
		}
	};
});