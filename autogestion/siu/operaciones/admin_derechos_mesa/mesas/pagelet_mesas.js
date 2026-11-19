kernel.renderer.registrar_pagelet('mesas', function(info) {
	var id = '#' + info.id;


	function registrar_eventos() 
	{
		$(id).on( "click", ".js-info-docentes", function( event ) {
			event.preventDefault();
			event.stopPropagation();
			var url = $(this).data('url');
			$.ajax({
				url: url,
				success: function(res) {
					var tmpl = $('#template_docente .ficha-docente').first();
					var cuadro = $('<div></div>');
					if (!(typeof res == "undefined")){
						$(JSON.parse(res)).each(function() {
							var $fila = tmpl.clone();
							var datos = $(this)[0];

							$fila.find('img').attr({'src': datos.url_imagen_chica,'title': datos.apellido});
							$fila.find('.nombre').html(datos.apellido + " " + datos.nombre);
							$fila.find('.identificacion').html(datos.legajo);
							$fila.find('.responsabilidad').html(datos.responsabilidad);
							cuadro.append($fila);
						});
					} else {
						cuadro.append("<div class='alert alert-notice'>No hay docentes asignados</div>");
					}
					$.facebox(cuadro);
				}
			});
		});
	}

	return {
		onload: function() {
			registrar_eventos();
		}
	}
})
