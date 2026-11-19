kernel.renderer.registrar_pagelet('listado', function(info) {
    var id = '#' + info.id;

    return {
        onload: function() {
			
			if(info.guardado_ok){
				
				kernel.ui.show_mensaje(info.mensajes.guardado_ok_msj, {
                    tipo: 'alert-success',
                    timeout: 10000
                });
				
			}
			
		}
	}
});
