var _info;

kernel.renderer.registrar_pagelet('documentacion', function(info) {
	_info = info;
	var id = '#' + info.id;
	return {
		onload: function() {
			$('.js-btnEliminar').on('click', function() {
				if (confirm(info.msj_confirmar_eliminar)) {
					return true;
				}
				return false;
			});
		}
	};
});