kernel.renderer.registrar_pagelet('cursos', function(info) {
    var id = '#' + info.id;

    var cargando_curso = false;

    function registrar_listeners() {
        kernel.evts.escuchar('info_curso_cargado', info_curso_cargado);
        kernel.evts.escuchar('info_curso_inscripto', info_curso_inscripto);
        kernel.evts.escuchar('info_curso_baja', info_curso_baja);
    }

    function info_curso_cargado(params) {
        var anchors = id + ' a';
        $(anchors).parent('li').removeClass('active');
        cargando_curso = false;
        var anchor_seleccionado = anchors + '[href$="' + params.href + '"]';
        $(anchor_seleccionado).parent('li').addClass('active');
    }

    function info_curso_inscripto() {
        $(id + ' li.active').addClass('insc');
    }

    function info_curso_baja(params)
    {
        if (params.cant_inscripciones && params.cant_inscripciones > 0) {
            // no hay que desmarcar la materia porque quedan inscripciones
            return;
        }
        if (params.materia) {
            var selector = id + " a[href$='"+ params.materia +"']";
            $(selector).parent('li').removeClass('insc');
        } else {
            $(id + ' li.active').removeClass('insc');
        }
    }

    function registrar_disparadores() {

        $(id).on('click', 'li', function() {
            $(this).find('a').trigger('click');
        });

        $(id).on('click', 'a', function(e) {
            if (cargando_curso) {
                return false;
            }
            cargando_curso = true;

            setTimeout(function() {
                cargando_curso = false;
            }, 5000);

            if (e.ctrlKey) { // para que ctrl + click abra una nueva pestaña
                return true;
            }

            kernel.evts.disparar('seleccion_curso', {
                href: $(this).attr('href'),
                curso: $(this).text(),
            });

            e.preventDefault();
            return false;
        });
    }

    return {
        onload: function() {

            registrar_listeners();
            registrar_disparadores();
            activar_filtro_lista();

            $( id ).on( "change", "#combo_propuestas_cursos", function(e) {

                var propuesta_curso = $(this).val();

                kernel.ajax.call(info.url_actividades_curso, {
                    type: 'get',
                    data: {propuesta_curso: propuesta_curso},
                    success: function(paquete) {

                        if(paquete.hubo_error){
                            kernel.ui.show_mensaje(paquete.mensaje_error, {tipo: 'alert-error'});
                        }
                        else{
                            $("#actividades_curso_container").html(paquete.cont);
                            activar_filtro_lista();
                            kernel.evts.disparar('seleccion_propuesta_curso', {
                                href: info.url_elegir_propuesta_curso+'/'+propuesta_curso
                            });
                        }

                    }
                });

            });

            function activar_filtro_lista(){

                new util.filtro_lista({
                    selector_pagelet: id,
                    valor_defecto: info.buscar_curso,
                    selector_input: '#filtro_cursos input',
                    selector_li: '#js-listado-cursos li'
                });

            }

        }
    }
});