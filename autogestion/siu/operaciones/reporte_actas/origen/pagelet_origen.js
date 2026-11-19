kernel.renderer.registrar_pagelet('origen', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#js-filtro_origen').on('change', function() {
				guarani.cambiar_op({
					href: info.url_index+'?origen='+$(this).val()
				});
			});
		}
	}
});