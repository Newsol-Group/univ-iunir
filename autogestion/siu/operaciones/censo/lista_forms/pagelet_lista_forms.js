kernel.renderer.registrar_pagelet('lista_forms', function(info) {
    var id = '#' + info.id;
    var mensajes = info.mensajes;

		
    function registrar_disparadores() {
		$(id).on('click', 'a', function () {
			kernel.evts.disparar('seleccion_formulario', {
				href: $(this).attr('href'),
				form: $(this).attr('form')
			});
			return false;
		});
    }
    
    function registrar_listeners() {
        kernel.evts.escuchar('info_formulario_cargado', info_formulario_cargado);		
	}

	function info_formulario_cargado(params) {
	    var anchors = id + ' a';
        
	    $(anchors).removeClass('active');
	    var anchor_seleccionado = anchors + '[href$="' + params.href + '"]';
		
	    $(anchor_seleccionado).addClass('active');
        if (info.modo_reinscripcion){
            $(anchor_seleccionado).addClass('visitado');
        }
                
        mensaje_reinscripcion ($(anchors).length == $(anchors+'.visitado').length);
	}
	
    return {
        onload: function() {
            registrar_listeners();
            registrar_disparadores();
            
            var anchors = id + ' a';            
            mensaje_reinscripcion ($(anchors).length == $(anchors+'.visitado').length);
        }
    }
    
    function mensaje_reinscripcion (activo){
        if (activo){
            $('#mensaje_reinscripcion').html(info.reinscripcion_verificacion_completa);
            $('#btn-reinscripcion').on("click", function() {
                reinscripcion();
            });
        } else {
            $('#mensaje_reinscripcion').html(info.reinscripcion_mensaje);
        }
    }
    
    function reinscripcion(){
        $.ajax({
            url: info.url_reinscripcion,
            dataType: 'json',
            type: 'post',
            success: function() {
                $('#mensaje_reinscripcion').html(info.reinscripcion_completa);
            }
        });
        return true;
    }
})