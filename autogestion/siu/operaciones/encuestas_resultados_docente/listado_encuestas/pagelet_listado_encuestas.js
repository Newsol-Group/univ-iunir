kernel.renderer.registrar_pagelet('listado_encuestas', function (info) {
    var id = '#' + info.id;

    return {
        onload: function () {

            $(id).on("click", "#btn_ver", function (event) {

                var formulario = $(this).data('formulario');
                var habilitacion = $(this).data('habilitacion');
                var encuesta = $(this).data('encuesta');
                var unidad_gestion = $(this).data('unidad_gestion');
                var comision = $(this).data('comision');

                window.location.href = info.url_ver_bloques
                                        +"?encuesta="+encuesta+
                                        "&formulario="+formulario+
                                        "&habilitacion="+habilitacion+
                                        "&unidad_gestion="+unidad_gestion+
                                        "&comision="+comision;


                });

        }
    };

});