kernel.renderer.registrar_pagelet('zona', function (info) {
	var id = '#' + info.id;

	return {
		onload: function () {
                    
			// Al cargar la zona marco que no hay cambios (ver: https://www.w3schools.com/html/html5_webstorage.asp)
			sessionStorage.hay_cambios = false;
                
			$(id + ' .js-combo-nav').on('change', function () {
                            
                                var url = $(this).find(':selected').data('link');
                            
                                // Si hay cambios pregunto antes de cambiar de zona
                                if (sessionStorage.hay_cambios == 'true') {

                                    // Muestro prompt preguntando si desea cambiar de zona
                                    kernel.ui.prompt({
                                        msg: info.mensajes.msg_prompt_perdera_cambios,
                                        msg_ok: info.mensajes.aceptar,
                                        msg_cancel: info.mensajes.cancelar,
                                        ok: function ()
                                        {
                                            guarani.cambiar_op({href: url});
                                        }
                                    });

                                } else {// Si no hay cambios cambio de zona de una
                                    guarani.cambiar_op({href: url});
                                }          
				
				
			});
                        
			$('#js-colapsar-detalles-zona').on('click', function () {
				$('#zona-detalle').slideToggle('slow');
			});
		}
	};
}, '_comun/zonas');