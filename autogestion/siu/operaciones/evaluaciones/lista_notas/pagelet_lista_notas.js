kernel.renderer.registrar_pagelet('lista_notas', function (info) {
	var id = '#' + info.id;

	return {
		onload: function () {
			$('#ver_escala').on("click", function () {
				kernel.ajax.call(info.url_escala, {success: function (paquete) {
					$.facebox(paquete.html);
				}});
			});

			$('#js-detalle-evaluacion').on('click', function () {
				$('#evaluacion-detalle').slideToggle('slow');
			});
		}
	}
});