kernel.renderer.registrar_pagelet('form_reinscripcion', function(info) {
    var id = '#' + info.id;

	return {
        onload: function() {
			$('.popover-notificacion').popover('toggle');
            
            //Ver: http://www.todoexpertos.com/categorias/tecnologia-e-internet/desarrollo-de-sitios-web/respuestas/2214856/boton-imprimir
            $(id).on('click', '.js-print-comprobante', function() {
                var ventana_impresion = window.open($(this).data('img'), '_blank', 'menubar=0,scrollbars=0,location=0');
                ventana_impresion.document.title = 'Imprimir comprobante de reinscripción';
                ventana_impresion.print();
                return false;
            });
        }
    }
})