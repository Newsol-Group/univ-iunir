kernel.renderer.registrar_pagelet('mesas', function (info) {
    var id = '#' + info.id;
    var errorMsgTimeout;

    function isDateValid(date){
        var isValid = false;

        //Si la fecha cumple con el formato dd/mm/yyyy
        if(/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/\d{4}$/.test(date))
        {
            //si la fecha es valida, ej: 31/02/2013 no es valida
            //ver doc en https://code.google.com/p/datejs/
            if(Date.parse(date))
            {
                isValid = true;
            }

        }

        return isValid;
    }

    function changeFiltros(select_filtro_value){

        switch(select_filtro_value) {
            case 'f':
                $('#fecha').parents('.control-group').show();
                $('#desde').parents('.control-group').hide();
                $('#hasta').parents('.control-group').hide();
                break;
            case 'r':
                $('#fecha').parents('.control-group').hide();
                $('#desde').parents('.control-group').show();
                $('#hasta').parents('.control-group').show();
                break;
            default:
        }

    }

    return {
        onload: function () {

            changeFiltros($('#mesas #filtrar_por').val());

            //si hubo error del lado del servidor
            if(info.hubo_error_validacion)
            {
                kernel.ui.show_mensaje(info.mensajes.msg_error_fechas, {tipo: 'alert-error', until_interaction: true});
            }

            var $filtro = $('form').formulario();

            $filtro.find('input.date').datepicker( "destroy" );
            $filtro.find('input.date').datepicker({
                changeMonth: true,
                changeYear: true,
                firstDay: 0
            });

            $('#mesas #filtrar_por').change(function(){

                changeFiltros($(this).val());

            });

            $('form').on('submit', function () {

                var filtrar_por = $filtro.find('#filtrar_por').val();
                var fecha = $filtro.find('#fecha').val();
                var fecha_desde = $filtro.find('#desde').val();
                var fecha_hasta = $filtro.find('#hasta').val();
                var fecha_desde_error = false;
                var fecha_hasta_error = false;
                var error_filtros = false;
                var msg_error;

                if(filtrar_por == 'f')
                {

                    if((fecha != "") && (!isDateValid(fecha))){

                        error_filtros = true;
                        msg_error = info.mensajes.fecha_invalida;

                    }

                }
                else
                {

                    if(fecha_desde != ""){
                        if(isDateValid(fecha_desde))
                        {
                            fecha_desde = Date.parse(fecha_desde);
                        }
                        else
                        {
                            fecha_desde_error = true;
                            error_filtros = true;
                        }
                    }

                    if(fecha_hasta != ""){
                        if(isDateValid(fecha_hasta))
                        {
                            fecha_hasta = Date.parse(fecha_hasta);
                        }
                        else
                        {
                            fecha_hasta_error = true;
                            error_filtros = true;
                        }
                    }

                    if(fecha_desde_error && fecha_hasta_error)
                    {
                        msg_error = info.mensajes.msg_error_fechas;
                    }
                    else if(fecha_desde_error)
                    {
                        msg_error = info.mensajes.fecha_desde_invalida;
                    }
                    else if(fecha_hasta_error)
                    {
                        msg_error = info.mensajes.fecha_hasta_invalida;
                    }
                    else if((fecha_desde != "") && (fecha_hasta != ""))
                    {
                        if(fecha_desde.compareTo(fecha_hasta) > 0)
                        {
                            msg_error = info.mensajes.fecha_hasta_menor_fecha_desde;
                            error_filtros = true;
                        }

                    }

                }

                //si hay error muestro mensaje
                if(error_filtros)
                {
                    kernel.ui.show_mensaje(msg_error, {tipo: 'alert-error', until_interaction: true});
                    return false;
                }
                
            });


        }
    };
});