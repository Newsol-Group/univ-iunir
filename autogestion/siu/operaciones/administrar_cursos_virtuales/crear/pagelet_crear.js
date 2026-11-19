kernel.renderer.registrar_pagelet('crear', function (info) {

    var id = '#' + info.id;
    
    var fecha_inicio;
    var fecha_fin;
    
    function crear_datepickers()
    {

        // ver: https://jqueryui.com/datepicker/#date-range

        fecha_inicio = $('#js-fecha-inicio').datepicker({
            changeMonth: true,
            changeYear: true,
            firstDay: 0,
            showOn: "both",
            buttonImage: info.url_calendar_img,
            buttonImageOnly: true,
            buttonText: info.mensajes.calenda_button_text
        }).on("change", function () {
            fecha_fin.datepicker("option", "minDate", $(this).val());
        });

        fecha_fin = $('#js-fecha-fin').datepicker({
            changeMonth: true,
            changeYear: true,
            firstDay: 0,
            showOn: "both",
            buttonImage: info.url_calendar_img,
            buttonImageOnly: true,
            buttonText: info.mensajes.calenda_button_text
        }).on( "change", function() {
            fecha_inicio.datepicker("option", "maxDate", $(this).val());
        });

    }

    function click_importar()
    {
        // Si el checkbox "importar" esta marcado
        if($('#js-importar').is(':checked')) {
            $("#js-cg-copiar-de").slideDown("slow");
            $("#js-importar-input").val(1);
        } else {
            $("#js-cg-copiar-de").slideUp("slow");
            $("#js-importar-input").val(0);
        }
    }
    
    function click_matricular_alumnos()
    {
        // Si el checkbox "matricular alumnos" esta marcado
        if($('#js-matricular-alumnos').is(':checked')) {
            $("#js-matricular-alumnos-input").val(1);
        } else {
            $("#js-matricular-alumnos-input").val(0);
        }
    }

    function click_btn_volver()
    {
        kernel.ui.prompt({
            msg: info.mensajes.confirmacion_salir,
            msg_ok: info.mensajes.aceptar,
            msg_cancel: info.mensajes.cancelar,
            ok: function ()
            {
                window.location.replace(info.url_volver);
            }
        });
    }
    
    return {
        onload: function () 
        {

            crear_datepickers();
            
            $('#js-importar').on("click", click_importar);
            
            $('#js-matricular-alumnos').on("click", click_matricular_alumnos);
            
            $('#js-btn-volver').on("click", click_btn_volver);
            
            $('#js-nombre').trigger("focus");
            
            click_importar();
            click_matricular_alumnos();
        }
    }

});
