kernel.renderer.registrar_pagelet('info_plan', function(info) {
    var id = '#' + info.id;
    var mensajes = info.mensajes;
	
    return {
	onload: function() {
	    $(id).on('click', 'a.ver_correlatividades', function () {
			var $link = $(this);
			var $id_destino = $link.attr('linea');
			
			if (! $link.attr('completo')){
				kernel.ajax.call(info.url_correlativas, {
					type: 'post',
					data: {id: $id_destino},
					success: function(paquete) {
						$link.attr('completo', true);
						$('#'+$id_destino).html(paquete.cont);
					},
					error: function() {
						$('#'+$id_destino).html(paquete.cont);
					}
				})
			}
			$('#'+$id_destino).toggle('slow', cambiar_texto($link));
		});
		
		
		$(id).on('click', 'a.ver_materias', function () {
			$link = $(this);
			$('.'+$link.attr('linea')).toggle('slow');
	    });

	    function cambiar_texto($id) {
			if ($('#' + $id.attr('linea')).css('display') == 'none') {
				$id.text('Ocultar');
			} else {
				$id.text('Ver');
			}
	    }
	    
	    $().popover('show');
	    
	    $('#pdf_btn').on("click", function () {
		$('#pdf_form').submit();
	    });
	}
    }
})