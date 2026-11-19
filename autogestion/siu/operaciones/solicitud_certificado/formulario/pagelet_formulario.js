kernel.renderer.registrar_pagelet('formulario', function(info) {
    var id = '#' + info.id;

	function registrar_eventos() {
		$(id+' #js-btn-solicitar').on("click", function (){
			var data = $('#js-form-certificado').serializeArray();

			kernel.ajax.call($('#js-form-certificado').attr('action'), {
				type: 'post',
				data: data,
				success: function(data) {
					$('#mensaje').removeClass('alert-error');
					$('#mensaje').addClass('alert-success');
					$('#mensaje').show();
					$('#mensaje').html(data.cont);
					
					kernel.evts.disparar("seleccionar_certificado", {
						nrcert: $('#nro_certificado').val()
					});
				},
				error: function(data) {
					$('#mensaje').removeClass('alert-success');
					$('#mensaje').addClass('alert-error');
					$('#mensaje').show();
					$('#mensaje').html(data.cont);
				}
			});
		})
    }
    
    function registrar_listeners() {
        kernel.evts.escuchar("cargar_formulario", cargar_formulario);
    }
	
	function cargar_formulario(params) {
		kernel.ajax.load(params.href, '#formulario', {
			success: function() {
				registrar_eventos();
				kernel.evts.disparar("seleccionar_link", params);
			}
		});
    }
	
    return {
		onload: function() {
			registrar_listeners();
			registrar_eventos();
		}
	}
})
