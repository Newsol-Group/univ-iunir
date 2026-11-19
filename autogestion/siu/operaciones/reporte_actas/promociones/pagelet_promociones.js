kernel.renderer.registrar_pagelet('promociones', function(info) {
	var id = '#' + info.id;

	function listado()
	{
		$('tr.promocion').on('click', function() {
			guarani.cambiar_op({
				href: info.url_detalle
					+'?url_acta='+$(this).data('link')
					+'&actividad='+$('#js-filtro_actividades-autocomplete').val()
					+'&per_lec='+$('#js-filtro_periodo_lectivo').val()
					+'&acta='+$('#js-filtro_acta-autocomplete').val()
					+'&origen=P'
			});
		});
		var actas = new Array();
		actas.push({id: '', label: 'Todas'});
		$('tr.promocion').each(function() {
			actas.push({id: $(this).data('acta').toString(), label: $(this).data('acta').toString()});
		});
		var input = $('#js-filtro_acta');
		input.plugin_autocompletar({
			url: actas,
			select: function(event, ui) {
				var acta = ui;
				if (acta)
				{
					$('tr.promocion').each(function() {
						if ($(this).data('acta') == acta)
							$(this).show();
						else
							$(this).hide();
					});
				}
				else
				{
					$('tr.promocion').each(function() {
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
			$('tr.promocion').each(function() {
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

			$('#js-filtro_periodo_lectivo').on('change', function() {
				filtrar();
			});

			function filtrar()
			{
				var url = info.url_filtrar_actas+'?origen=P&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+$('#js-filtro_periodo_lectivo').val();
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
				$('tr.promocion').each(function() {
					$(this).show();
				});
			});

			$('#promociones-encabezado').on('click', function() {
				$('#js-encabezados').toggle();
				if ($('#js-encabezados').css('display') == "none")
					$(this).text(info.mostrar_encabezado);
				else
					$(this).text(info.ocultar_encabezado);
			});

			$('#promociones-generar_pdf_listado').on('click', function() {
				window.open(info.url_generar_pdf+'?origen=P&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+$('#js-filtro_periodo_lectivo').val());
				return false;
			});

			$('#promociones-generar_xls_listado').on('click', function() {
				window.open(info.url_generar_xls+'?origen=P&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+$('#js-filtro_periodo_lectivo').val());
				return false;
			});

			$('#promociones-generar_pdf_detalle').on('click', function() {
				window.open(info.url_generar_pdf);
				return false;
			});

			$('#promociones-generar_xls_detalle').on('click', function() {
				window.open(info.url_generar_xls);
				return false;
			});
		}
	};
});