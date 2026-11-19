function setupHandlers(prefix) {
    const $medio_pago = $(`select[id*=\"medio_pago_${prefix}\"]`);
    const $tarjeta = $(`input[id*=\"nro_tarjeta_${prefix}\"]`);
    const tarjeta_org_val = $tarjeta.val();
    const $cbu = $(`input[id*=\"cbu_${prefix}\"]`);
    const cbu_org_val = $cbu.val();

    function enableFields() {
        const val = $medio_pago.val();
        if (val == '1') {
            $tarjeta.prop('disabled', false);
            if (tarjeta_org_val !== null && tarjeta_org_val !== '') {
                $tarjeta.val(tarjeta_org_val);
            }
            $cbu.prop('disabled', true);
            $cbu.val('');
        } else if (val == '2') {
            $tarjeta.prop('disabled', true);
            $tarjeta.val('');
            $cbu.prop('disabled', false);
            if (cbu_org_val !== null && cbu_org_val !== '') {
                $cbu.val(cbu_org_val);
            }
        } else {
            $tarjeta.prop('disabled', true);
            $tarjeta.val('');
            $cbu.prop('disabled', true);
            $cbu.val('');
        }
    }

    $medio_pago.on('change', enableFields);
    enableFields();
}

setupHandlers('alumno_mat');
setupHandlers('alumno_cuota');
setupHandlers('empresa_mat');
setupHandlers('empresa_cuota');

function setupPagoEmpresaHandler() {
    const $pago_emp = $('input[id*=\"pago_a_cargo_empresa\"]');

    function toggleEmpresaFields() {
        if ($pago_emp.is(':checked')) {
            $('#datos_empresa').show();
        } else {
            $('#datos_empresa').hide();
        }
    }

    $pago_emp.on('change', toggleEmpresaFields);
    toggleEmpresaFields();
}

setupPagoEmpresaHandler();

//     const pf_alu = $('input[id*=\"porcentaje_facturacion_alumno\"]');
//     const pf_emp = $('input[id*=\"porcentaje_facturacion_empresa\"]');