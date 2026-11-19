kernel.renderer.registrar_pagelet('acta', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#btn_encabezados').on('click', function() {
				$('#js-encabezados').toggle();
				if ($('#js-encabezados').css('display') == "none"){
					$(this).text(info.mostrar_encabezado);
				} else {
					$(this).text(info.ocultar_encabezado);
				}
			});

			$('#js-generar_pdf').on('click', function() {
				var url = info.url_generar_pdf;
				window.open(url);
				return false;
			});

			$('#js-generar_xls').on('click', function() {
				var url = info.url_generar_xls;
				window.open(url);
				return false;
			});
		}
	}
})