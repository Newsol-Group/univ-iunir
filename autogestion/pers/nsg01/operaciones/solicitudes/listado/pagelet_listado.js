kernel.renderer.registrar_pagelet('listado', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#solicitudes_historicas').on('change', function() {
				guarani.cambiar_op({
					href: info.url_listado+'?historicas='+($(this).is(':checked') ? 'true' : 'false')
				});
			});
			
			if (info.mensaje_cambio) {
				$('#btn-nueva-solicitud').on('click', function() {
					return confirm(info.mensaje_cambio);
				});
			}

            if(info.hubo_error){
                kernel.ui.show_mensaje(info.msj_error, {tipo: 'alert-error'});
            }
		}
	};
});