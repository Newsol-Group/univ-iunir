kernel.renderer.registrar_pagelet('encuesta', function(info) {
    var id = '#' + info.id;
    var alto_iframe = info.alto_iframe_defecto;
    
    function registrar_listeners() {
        kernel.evts.escuchar("cargar_encuesta", cargar_encuesta);

        //http://stackoverflow.com/questions/935127/how-to-access-parent-iframe-from-javascript/15601531#15601531
        //https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
        window.addEventListener('message', function(event) {

            // Si el dominio de Kolla es igual al configurado en 'url_kolla' de 'instalacion/config.php'
            if(event.origin === info.dominio_kolla)
            {
                
                // Si se cargo la encuesta para responder, o se hizo click en el botón 'Guardar' de la encuesta
                if (event.data.name == 'encuesta_cargada'){
                    // Algunas veces Kolla devuelve 0 y eso hace que no se vea 
                    if (event.data.height === 0) {
                        alto_iframe = info.alto_iframe_defecto;
                    } else {
                        alto_iframe = event.data.height;
                    }
                    resizeIframe();
                    
                // Si se respondio la encuesta (botón 'Terminar' de la encuesta)
                } else {
                    
                    // Cuando termina la encuesta y muestra "Comprobante de encuesta respondida"
                    alto_iframe = info.alto_iframe_comprobante;;
                    resizeIframe();
                    
                    kernel.ajax.call(info.url_accion,{
                        type: 'post',
                        data: {t: event.data.t, h: event.data.h, fecha_respuesta: event.data.fecha_terminado},
                        success: function(data){

                            // Scrolleo hasta arriba de todo
                            $('html, body').animate({ scrollTop: 0 }, 'fast');
                            
                            // Si hubo errores al marcar la encuesta como respondida
                            if(data.error_habilitacion){
                                $("#mensaje-error-texto").text(data.mensaje);
                                $("#mensaje-error").fadeIn("slow");
                            // Si se marco bien como respondida la quito del listado de la izquierda
                            } else {
                                // Si la encuesta no es de tipo Beca la quito del listado
                                if ($("#encuesta_seleccionada_id").val() && (!info.tipo_relevamiento_becas)) {
                                    $($("#encuesta_seleccionada_id").val()).hide('slow', function(){
                                        $(this).remove();
                                    });
                                }
                            }
                            
                            
                        }
                    });
                }


            } else {
                console.log("Kolla no está en la url esperada. Se recibió '" + event.origin + "' y se esperaba '" + info.dominio_kolla + "'" );
            }

        }, false);
      
    }

    function cargar_encuesta(params) {
        kernel.ajax.load(params.href, id, {
            success: function() {
                kernel.evts.disparar("seleccionar_link", params);
                $("#encuesta_seleccionada_id").val('#' + params.me);
            }
        });
    }

    function resizeIframe() {
        console.log('Alto del IFRAME de Kolla: ' + alto_iframe + 'px');
        // El alto del iframe es el alto del contenido del mismo
        $('#ifkolla').height(alto_iframe);
    }
    
    function manejo_menu() {
        // Si la encuesta es de tipo Beca desmarco el menú
        if (info.tipo_relevamiento_becas) {
            $("#js-nav .js-menuitem-root.menu-item-seleccionado").removeClass("menu-item-seleccionado");
        }
    }

    return {
		onload: function() {
                        manejo_menu();
			registrar_listeners();
		}
	};
});

