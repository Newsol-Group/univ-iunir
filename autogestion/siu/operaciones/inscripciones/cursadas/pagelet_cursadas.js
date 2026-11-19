kernel.renderer.registrar_pagelet('cursadas', function(info) {
    var id = '#' + info.id;

    return {
		onload: function() {
			$(id).on("click", "a.rechazado", function() {
				$('tr#'+$(this).attr("link")).slideToggle("slow");
			});
			
			$(id + ' .js-ver-subcomisiones').on('click', function () {
				var destino = $(this).attr('contenedor');
				var id_link = $(this).data('link');
				if ($(this).hasClass('cargado')){
					$(id + ' #'+destino).toggle();
				} else {
					$(this).addClass('cargado');
					kernel.ajax.call(info.url_ver_subcomisiones, {
						type: 'post',
						data: {link:id_link},
						success: function(paquete) {
							$(id + ' #'+destino).html(paquete.cont);
							$(id + ' #'+destino).show();
							$(this).addClass('cargado');
						}
					});
				}
			})
			
			$(id+' #cursadas_historicas').on("click", function (){
				var valor_check_cur = "A";
				if ($(this).prop('checked')){
					valor_check_cur = "T";
				}
				
				kernel.ajax.load(info.url_cursadas, id, {
					historia: false,
					type: 'GET',
					data: {'cursadas_historicas': valor_check_cur},
					success: function() {
						kernel.renderer.pagelet('cursadas').onload();
					}
				});
			});
		}        
    }
})