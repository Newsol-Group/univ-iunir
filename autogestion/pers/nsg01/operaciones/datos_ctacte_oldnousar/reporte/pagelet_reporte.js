kernel.renderer.registrar_pagelet('reporte', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {
         
            $('.btnPagarMCP').on('click', function () {
                
                var snd_monto = accion_pagar_mcp($(this).data('saldo'));
                var index = $(this).data('index');
                const $btn = $(this);

                if (snd_monto && snd_monto !== -1) {
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: info.url_pagar_mcp,
                        type: 'post',
                        dataType: 'json',
                        data:
                        {
                            monto: snd_monto,
                            index: index
                        },
                        async: true,
                        success: function (data) {
                            $btn.prop('disabled', false);

                            if (data.error) {
                                alert(data.error);
                                return;
                            }

                            $form = $('#payForm_' + index);
                            if (!$form) return;
                            $form.find('input[name="TransaccionComercioId"]').val(data.trans_id);
                            $form.find('input[name="Monto"]').val(data.monto);
                            $form.find('input[name="Hash"]').val(data.hash);
                            $form.submit();
                        }
                    });
                }
            });

            $('.btnPagarMP').on('click', function() 
            {
                var ok = accion_pagar_mp($(this).data('codfor') + ' - ' + $(this).data('nrofor'));
                var link = $(this).data('link');

                if (ok) {
                    $.ajax({
                        url: info.url_pagar_mp,
                        type: 'post',
                        dataType: 'json',
                        data: {id: $(this).data('id')},
                        async: true,
                        success: function(data) {
                            if (data.link_err) {
                                alert('Pago no disponible');
                                return;
                            }
                            window.open(link, '_blank', 'noopener,noreferrer');
                        }
                    });
                }
            });
        }
    }

    // function accion_pagar_mcp(propuesta_nombre) {
    //     var mensaje = info.msj_confirmar_pagar_mcp + propuesta_nombre;
    //     return confirm(mensaje);
    // }

    function accion_pagar_mp(propuesta_nombre) {
        var mensaje = info.msj_confirmar_pagar_mp + propuesta_nombre;
        return confirm(mensaje);
    }

    function accion_pagar_mcp(monto) {

        let montoInput = prompt("Ingrese el monto a abonar:", monto);
        if (montoInput === null) return;

        if (montoInput.trim() === "" || isNaN(montoInput)) {
            alert("Por favor, ingrese un numero entero valido.");
            return;
        }
        if (Number(montoInput) <= 0) {
            alert("El monto no puede ser menor o igual a 0.");
            return;
        }
        if (Number(montoInput) > Number(monto)) {
            alert("El monto no puede ser mayor a " + monto);
            return;
        }
        
        const monto2 = formatPaymentAmount(montoInput);
        if (confirm("Se generara un link de pago por: " + formatCurrency(montoInput) + "¿Desea continuar?")) {
            return monto2;
        }
        return -1;
    }
    function formatPaymentAmount(input) {
        const num = parseFloat(input).toFixed(2);
        const [integerPart, decimalPart] = num.split('.');
        const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return `${formattedInteger},${decimalPart}`;
    }
    function formatCurrency(amount) {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2
        }).format(amount);
    }
});