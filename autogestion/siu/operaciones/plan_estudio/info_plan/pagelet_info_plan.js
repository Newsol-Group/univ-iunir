kernel.renderer.registrar_pagelet('info_plan', function (info) {
    var id = '#' + info.id;

    return {
        onload: function () {

            function delegate_correlativas($link) {
		
		var $tr_correlatividades = $link.parents('tr.materia').next('tr.correlatividades');

                // Si la info de correlatividades esta oculta
                if ($tr_correlatividades.is(":hidden")) {

                    // Si la info aún no esta cargada la cargo
                    if (!$link.data('completo')) {
                        kernel.ajax.call(info.url_correlativas, {
                            type: 'post',
                            data: {
                                elemento: $link.data("elemento"),
                                elemento_padre: $link.data("elementoPadre")
                            },
                            success: function (paquete) {
                                $link.data('completo', true);
				$link.attr("data-completo", true);
                                $tr_correlatividades.html(paquete.cont);
                            },
                            error: function (paquete) {
                                $tr_correlatividades.html(paquete.cont);
                            },
                            complete: function () {
                                $tr_correlatividades.toggle('slow', cambiar_texto($link));
                            }
                        })
                    } else {// Si la info ya esta cargada simplemente la muestro
                        $tr_correlatividades.toggle('slow', cambiar_texto($link));
                    }

                } else if ($tr_correlatividades.is(":visible")) {// Si la info de correlatividades esta visible
                    $tr_correlatividades.toggle('slow', cambiar_texto($link));
                }

            }

            $(id).on('vclick', 'a.ver_correlatividades', function (event) {
                event.preventDefault();
                var $link = $(this);

                delegate_correlativas($link);
            });

            $(id).on('keypress', 'a.ver_correlatividades', function (event) {
                event.preventDefault();
                if (event.keyCode == 13) {
                    $(this).trigger('click');
                }
            });

            function cambiar_texto($link) {
		
		var $tr_correlatividades = $link.parents('tr.materia').next('tr.correlatividades');
		
                if ($tr_correlatividades.is(":hidden")) {
                    $link.text(info.ocultar);
                } else {
                    $link.text(info.ver);
                }
            }

            $(id).on('vclick', '#pdf_btn', function (event) {
                event.preventDefault();
                window.open(info.url_pdf);
            });

            $(id).on('keypress', '#pdf_btn', function (event) {
                event.preventDefault();
                if (event.keyCode == 13) {
                    $(this).trigger('click');
                }
            });


            function exp_xls() {
                window.open(info.url_xls);
            }

            $(id).on('vclick', '#xls_btn', function (event) {
                event.preventDefault();
                exp_xls()
            });

            $(id).on('keypress', '#xls_btn', function (event) {
                event.preventDefault();
                if (event.which == 13)
                    exp_xls()
            });

            $(id).on('vclick', '#expandir', function (event) {
                event.preventDefault();
		// Expande todo menos las materias genéricas
                $(".js-accordion:not(.materia_generica)").collapse('show');
            });

            $(id).on('vclick', '#contraer', function (event) {
                event.preventDefault();
                $(".js-accordion").collapse('hide');
            });

            $(id).on('vclick', '.verificar', function (event) {
                event.preventDefault();

                var $btn_verificar = $(this);
                var padre = $btn_verificar.data('padre');
                var materia = $btn_verificar.data('materia');
                var completo = $btn_verificar.data('completo');
                var tipo_correlativa;

                if ($btn_verificar.hasClass('correlativas_cursar')) {
                    tipo_correlativa = "cursar";
                } else if ($btn_verificar.hasClass('correlativas_aprobar')) {
                    tipo_correlativa = "aprobar";
                }

                var $alert = $("#alert_correlativas_" + tipo_correlativa + "_padre" + padre + "_materia" + materia + "_js");

                if (!completo) {

                    kernel.ajax.call(info.url_verificar_correlativas, {
                        type: 'post',
                        data: {
                            materia: materia,
                            tipo_correlativa: tipo_correlativa
                        },
                        success: function (paquete) {

                            if (paquete.hubo_error) {
                                kernel.ui.show_mensaje(paquete.mensaje_error, {tipo: 'alert-error'});
                                return false;
                            }

                            var class_alert = "";
                            if (paquete.cumple_regla) {
                                class_alert = "alert-success";
                            } else {
                                class_alert = "alert-error";
                            }

                            $alert.addClass(class_alert);
                            $alert.find('h4.alert-heading').text(paquete.mensaje_usuario);
                            $btn_verificar.data('completo', true);

                        }
                    });

                }

                if ($alert.is(":hidden")) {
                    $alert.slideDown();
                } else {
                    $alert.slideUp();

                }
            });

            $(id).on('vclick', '.alert_verificar_correlativas .btn.ocultar', function (event) {
                event.preventDefault();
                $(this).parents('.alert_verificar_correlativas').slideUp();
            });

            $(id).on('vclick', '.alert_verificar_cumplimiento .btn.ocultar', function (event) {
                event.preventDefault();
                $(this).parents('.alert_verificar_cumplimiento').slideUp();
            });

            // Para que me de tiempo de hacer click en el link dentro del tooltip
            $('i.asignacion_optativa').tooltip({delay: {show: 0, hide: 1000}});

            /*cumplimiento de las genericas*/
            $(id).on('vclick', '.btn_verificar_cumplimiento_js', function (event) {
                event.preventDefault();


                var btn_verificar_cumplimiento_regla = $(this);
                var materia = btn_verificar_cumplimiento_regla.data('materia');
                var completo = btn_verificar_cumplimiento_regla.data('completo');
                var regla = btn_verificar_cumplimiento_regla.data('regla');
                var parametros = btn_verificar_cumplimiento_regla.data('parametros');
                var elemento_comp = btn_verificar_cumplimiento_regla.data('elementoComp');
                var alert = $("#alert_cumplimiento_regla_" + regla + "_materia_" + elemento_comp + "_js");

                if (!completo) {
                    kernel.ajax.call(info.url_verificar_cumplimientos, {
                        type: 'post',
                        data: {
                            materia: materia,
                            regla: regla,
                            parametros: parametros,
                        },
                        success: function (paquete) {

                            if (paquete.hubo_error) {
                                kernel.ui.show_mensaje(paquete.mensaje_error, {tipo: 'alert-error'});
                                return false;
                            }

                            var class_alert = "";
                            if (paquete.cumple_regla) {
                                class_alert = "alert-success";
                            } else {
                                class_alert = "alert-error";
                            }

                            alert.addClass(class_alert);
                            alert.find('h4.alert-heading').text(paquete.mensaje_usuario);
                            btn_verificar_cumplimiento_regla.data('completo', true);


                        }
                    });

                }
                if (alert.is(":hidden")) {
                    alert.slideDown();
                } else {
                    alert.slideUp();

                }
            });

	    /* Cuando se quiere desplegar un acordion para materias genéricas */
	    $(id).on('vclick', '.accordion.materia_generica', function (event) {
		
		event.preventDefault();
		var $accordion = $(this);
		
		// Si aún no se cargaron las optativas
		if (!$accordion.data("cargado")) {
		    
		    kernel.ajax.call(info.url_optativas, {
			type: 'post',
			data: {
			    elemento: $accordion.data("elemento")
			},
			success: function (paquete) {
			    
			    if (paquete.hubo_error) {
				kernel.ui.show_mensaje(paquete.mensaje, {tipo: 'alert-error'});
			    } else {
				$accordion.find(".js-optativas").html(paquete.cont);
				$accordion.data("cargado", true);
				$accordion.attr("data-cargado", true);
				$accordion.find(".accordion-toggle").data("toggle", "collapse");
				$accordion.find(".accordion-toggle").attr("data-toggle", "collapse");
				$accordion.find(".js-accordion").collapse('show');
			    }
			    
			}
		    });
		}
		
	    });
	    
        }
    };
});