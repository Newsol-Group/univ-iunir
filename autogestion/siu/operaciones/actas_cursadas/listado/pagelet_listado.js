kernel.renderer.registrar_pagelet('listado', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			function filtro_actividades() {
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			}

			$('#js-filtro_actividades').plugin_autocompletar({
				url: info.url_buscar_actividades,
				select: filtro_actividades,
				valor_default: {
					id: info.actividad_elemento_default,
					value: info.actividad_nombre_default != "" ? info.actividad_nombre_default : 'Todas'
				}
			});

			$('#js-limpiar_actividades').on('click', function() {
				$('#js-filtro_actividades').val('Todas');
				$('#js-filtro_actividades-autocomplete').val('');
				filtro_actividades();
			});

			$('#js-filtro_periodo_lectivo').on('change', function() {
				kernel.ui.show_loading();
				window.setTimeout(filtrar, 100);
			});

			function filtrar()
			{
				kernel.ajax.load(info.url_listado+'?actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+$('#js-filtro_periodo_lectivo').val(), id, {
					historia: true,
					type: 'get',
					data: {},
					success: function() {
						kernel.renderer.pagelet('listado').onload();
					}
				});
				kernel.ui.hide_loading();
			}

			$('tr.link-js').on('click', function() {
				guarani.cambiar_op({
					href: info.url_acta_detalle+'?url_acta='+$(this).data('link')+'&actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+$('#js-filtro_periodo_lectivo').val()
				});
			});

			$('#js-generar_pdf').on('click', function() {
				var url = info.url_generar_pdf+'?actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+ $('#js-filtro_periodo_lectivo').val();
				window.open(url);
				return false;
			});

			$('#js-generar_xls').on('click', function() {
				var url = info.url_generar_xls+'?actividad='+$('#js-filtro_actividades-autocomplete').val()+'&per_lec='+ $('#js-filtro_periodo_lectivo').val();
				window.open(url);
				return false;
			});
		}
	}
})