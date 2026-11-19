kernel.renderer.registrar_pagelet('constancias', function(info) {
    var id = '#' + info.id;

    return {
        onload: function() {

            if(info.hubo_error){
                kernel.ui.show_mensaje(info.msj_error, {tipo: 'alert-error'});
            }

        }
    };
});