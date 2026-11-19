kernel.renderer.registrar_pagelet('certificados', function(info) {
    var id = '#' + info.id;

	function registrar_listeners() {
		var id_evento = 'seleccionar_link';
        kernel.evts.escuchar(id_evento, seleccionar_link);
		
		id_evento = 'seleccionar_certificado';
        kernel.evts.escuchar(id_evento, seleccionar_certificado);
    }
	
	function registrar_eventos() {		
		
		$(id).on('click', 'a', function() {
			
			$(id+' a').removeClass('active');
			kernel.evts.disparar('cargar_formulario', {
                href: $(this).attr('href')
            });
			return false;
		});
    }
	
	function seleccionar_link(params){
		$(id+' a').removeClass('active');
		$(id+' a[href="'+params.href+'"]').addClass('active');
	}
	
	function seleccionar_certificado(params){
		var certificado = '<i class="icon-ok"></i>' + $(id+' #nrocert'+params.nrcert).text();
		$(id+' #nrocert'+params.nrcert).html(certificado);
	}
	
    return {
        onload: function() {
			registrar_eventos();
			registrar_listeners();
        }
    }
})
