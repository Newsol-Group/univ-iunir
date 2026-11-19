kernel.renderer.registrar_pagelet('pantalla_inicial', function (info) {

    var id = '#' + info.id;
    
    return {
        onload: function () 
        {

            // Inicializo los tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Evento click del botón "Consultar"
            $(id).on('click', '.js-btn-consultar-estado', function (event) {
                
                event.preventDefault();

                var btn_consultar_estado = $(this);
                var hash_habilitacion = $(btn_consultar_estado).data('hashHabilitacion');

                kernel.ajax.call(info.url_consultar_estado, {
                    type: 'GET',
                    data: {
                        hash_habilitacion: hash_habilitacion
                    },
                    success: function (response) {
                        
                        // Oculto el botón "Consultar"
                        $(btn_consultar_estado).hide();
                        // Muestro el estado de la convocatoria 
                        $(btn_consultar_estado)
                                .next('.js-estado')
                                .addClass('alert '+response.estado.mensaje_tipo)
                                .html(response.estado.mensaje)
                                .show();
                        
                    }

                });

            });
        }
    };

});
