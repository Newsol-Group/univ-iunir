kernel.renderer.registrar_pagelet('lista_carreras', function(info) {
    var id = '#' + info.id;
    
    return {
        onload: function() {
            $(id).on('click', '.js-btn-qr-per_lec', function (){
                $("#"+ $(this).data('target')).toggle();
            });
            
            // Si hubo error al controlar período de inscripción vigente para Inscripción a Materias muestro el modal/popup
            if (info.error_controlar_periodo_inscripcion_vigente) {
                $("#lista_carreras #js-modal-no-hay-periodo-inscripcion-vigente").modal('show');
            }
            
        }
    };
    
});