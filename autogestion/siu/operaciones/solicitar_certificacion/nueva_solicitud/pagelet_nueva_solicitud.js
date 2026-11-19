kernel.renderer.registrar_pagelet('nueva_solicitud', function(info) {
    var id = '#' + info.id;

    return {
        onload: function() {
			
			// https://stackoverflow.com/questions/7080269/javascript-before-leaving-the-page
			/*window.onbeforeunload = function(){
				return 'Are you sure you want to leave?';
			};*/

			$("#js-cuil").focus();
			
			if(info.error){
				
				kernel.ui.show_mensaje(info.mensajes.error_msj, {
                    tipo: 'alert-error',
                    timeout: 10000
                });
				
			}
			
			$( id ).on( "click", "#js-btn-confirmar", function() {
				$("#js-form-nueva-solicitud").submit();
			});
			
		}
	}
});
