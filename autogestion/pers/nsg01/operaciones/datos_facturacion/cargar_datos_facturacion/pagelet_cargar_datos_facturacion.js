kernel.renderer.registrar_pagelet('cargar_datos_facturacion', function(info) {
    const id = `#${info.id}`;

    function setupMedioPagoHandlers(prefix) {
        const $medio_pago = $(`#js-medio_pago_alumno_${prefix}`);

		const $div_tarjeta = $(`#js-div_tarjeta_${prefix}`);
        const $label_tarjeta = $(`#js-label_tarjeta_${prefix}`);
        const $tarjeta = $(`#js-nro_tarjeta_alumno_${prefix}`);
        var tarjeta_org_val = $tarjeta.val();

        const $div_cbu = $(`#js-label_cbu_${prefix}`);
        const $label_cbu = $(`#js-div_dbu_${prefix}`);
        const $cbu = $(`#js-cbu_alumno_${prefix}`);
        var cbu_org_val = $cbu.val();

        function show_hide_elements() {
            const val = $medio_pago.val();

            if (val == 1) {
                // Tarjeta Crédito
                //$tarjeta.val(tarjeta_org_val).show();
                $tarjeta.show();
                $div_tarjeta.show();
                $label_tarjeta.show();
                $cbu.hide();
                // $cbu.val('').hide();
                $div_cbu.hide();
                $label_cbu.hide();
            } else if (val == 2) {
                // Débito Directo
                $cbu.show();
                // $cbu.val(cbu_org_val).show();
                $div_cbu.show();
                $label_cbu.show();
                // $tarjeta.val('').hide();
                $tarjeta.hide();
                $div_tarjeta.hide();
                $label_tarjeta.hide();
            } else if (val == 3 || val == 4) {
                // Mercado Pago / Macroclick
                $tarjeta.hide();
                // $tarjeta.val('').hide();
                $div_tarjeta.hide();
                $label_tarjeta.hide();
                // $cbu.val('').hide();
                $cbu.hide();
                $div_cbu.hide();
                $label_cbu.hide();
            }
        }

        $medio_pago.on('change', show_hide_elements);
        show_hide_elements();
    }

    return {
        onload: function() {
            const $pago_empresa = $('#pago_a_cargo_empresa');
            $('#datos_facturacion_empresa').hide();
            $pago_empresa.val('N');

            $pago_empresa.change(function() {
                if ($(this).is(':checked')) {
                    $(this).val('S');
                    $('#datos_facturacion_empresa').show();
                } else {
                    $(this).val('N');
                    $('#datos_facturacion_empresa').hide();
                }
            });

            setupMedioPagoHandlers('mat');
            setupMedioPagoHandlers('cuota');

            if (info.error) {
                kernel.ui.show_mensaje(info.mensajes.error_msj, {
                    tipo: 'alert-error',
                    timeout: 10000
                });
            }

            $(id).on('click', '#js-btn-confirmar', function() {
                $('#js-form-cargar-datos-facturacion').submit();
            });
        }
    };
});
