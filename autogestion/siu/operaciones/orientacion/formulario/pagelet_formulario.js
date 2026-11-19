kernel.renderer.registrar_pagelet('formulario', function(info) {
	var id = '#' + info.id;

    return {
        onload: function() {
			$('#btn_cambiar_o').on("click", function() {
				$('#o_disponibles').toggle();
				$('#resultado_cambio').hide();
			});

			$('td a.btn-selec').on("click", function() {

				if (! $(this).attr('disabled')) {

					$('#id_orientacion').val($(this).attr('data-link'));
                    kernel.ui.prompt({
                        title: info.confirmacion,
                        msg: info.mensaje_cambio_orientacion,
                        msg_ok: info.aceptar,
                        msg_cancel: info.cerrar,
                        ok: function () {
                            $('#form_orientacion').submit();
                        }
                    });
                }
				return false;
			});
        }
    }
})