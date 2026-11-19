kernel.renderer.registrar_pagelet('derechos', function(info) {
    var id = '#' + info.id;
	
	function registrar_eventos() 
	{
        $('#derechos th.col-derecho').tooltip({container:'body'});

//		guarani.set_mensaje_antes_de_navegar("Se perderán los cambios ¿Desea salir sin guardar?");
//		guarani.set_mensaje_antes_de_navegar_activo(false);

        $(id).find('#js-colapsar-cuadro-derechos').on("click", function() {
            var detalle = $('.js-cuadro-derechos');
            if (detalle.is(':visible')) {
                detalle.slideUp();
            }else{
                detalle.slideDown();
            }
        });

        $('#derechos form.form-derechos').on('submit', function() {
            var $form = $(this);
            var data  = $form.serializeArray();
              // te parsea los datos
            // chequear validaciones si es que hay
            kernel.ajax.call($form.attr('action'), {
                type: 'post',
                data: data,
                historia: false,
                success: function(response) {
	                //guarani.set_mensaje_antes_de_navegar_activo(false);
                    $('#derechos').html(response.cont);
                    kernel.renderer.pagelet('derechos').onload();
                    kernel.ui.show_mensaje(info.mensajes.mensaje_exito);
                }
            })
            return false;
        }).on('change', function(){
		       // guarani.set_mensaje_antes_de_navegar_activo(true);
	        });

		$('#derechos .check-todo-nada').on('click', function(){
			var doc = $(this).data('docente');
			var selector = 'input:not(:disabled).doc-'+doc;//[name=doc\\[\\]]';
			$(selector).prop("checked",this.checked);
			console.log(this.checked);
		});


    }
	
    return {
        onload: function() {
			registrar_eventos();
        }
    }
})