kernel.renderer.registrar_pagelet('info_curso', function(info) {
    var id = '#' + info.id;

    //http://stackoverflow.com/questions/4068373/center-a-popup-window-on-screen
    function PopupCenter(url, title, w, h) {
        // Fixes dual-screen position                         Most browsers      Firefox
        var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
        var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

        var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

        var left = ((width / 2) - (w / 2)) + dualScreenLeft;
        var top = ((height / 2) - (h / 2)) + dualScreenTop;
        var newWindow = window.open(url, title, 'toolbar=no, scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

        // Puts focus on the newWindow
        if (window.focus) {
            newWindow.focus();
        }
    }

    function registrar_listeners() {
        kernel.evts.escuchar('seleccion_propuesta_curso', cargar_pantalla_inicial);
        kernel.evts.escuchar('seleccion_curso', cargar_curso);
    }

    function registrar_eventos_comisiones() {

        //***************************************************************
        //****************** Baja Cursos ********************************
        //***************************************************************

        $( id ).on( "click" , ".js-baja-inicial" , function(e) {

            var hash_curso  =   $(this).data('hashCurso');
            var curso       =   $(this).data('curso');
            var inscripcion =   $(this).data('inscripcion');
            var comision    =   $(this).data('comision');
            var csrf        =   $(id).find('.js-csrf').data('csrf');

            baja_curso(csrf, curso, hash_curso, comision, inscripcion, info.si);

            e.preventDefault();
            return false;
        });

        $( id ).on( "submit" , ".form_baja" , function(e) {

            var $form       =   $(this);
            var hash_curso  =   null;
            var curso       =   $form.find("input[name='curso']" ).val();
            var inscripcion =   $form.find("input[name='inscripcion']" ).val();
            var comision    =   $form.find("input[name='comision']" ).val();
            var csrf        =   $form.find("input[name='__csrf']" ).val();

            baja_curso(csrf, curso, hash_curso, comision, inscripcion, info.no);

            e.preventDefault();
            return false;
        });

        function baja_curso(csrf, curso, hash_curso, comision, inscripcion, baja_inicial){

            var modo_devolucion                         =   null;
            var mostrar_seleccion_modo_devolucion_pago  =   false;

            var inscripcion_existe                      =   existe_inscripcion(curso, inscripcion);
            var comision_cobrable                       =   es_comision_cobrable(curso, inscripcion);

            // Si no existe la inscripción
            if (!inscripcion_existe) {

                kernel.ui.show_mensaje(info.mensajes.error_no_existe_inscripcion, {
                    tipo: 'alert-error',
                    timeout: 10000
                });

                return false;
            }

            //Si la llamada a la función 'es_comision_cobrable' da error salgo
            if (comision_cobrable === null) return false;

            //Si la comisión es cobrable
            if (comision_cobrable) {
                var forma_pago  =   obtener_forma_pago_consumo_externo(curso, inscripcion);
                
                //Si la llamada a la función 'obtener_forma_pago_consumo_externo' da error salgo
                if (forma_pago === null) return false;
                
                // Si el estado de la inscripción es 'cobrada' (ya se cobro)
                if (forma_pago['estado'] == info.estado_consumo_pagado) {
                    
                    // Si hay mas de una opción de devolución
                    if (forma_pago['opciones_devolucion'].length > 1) {
                        mostrar_seleccion_modo_devolucion_pago  =   true;
                    } else {// Si hay solo una opción de devolución tomo esa
                        modo_devolucion = forma_pago['opciones_devolucion'][0]['valor'];
                    }
                    
                } else {
                    modo_devolucion = info.modo_devolucion_no_corresponde;
                }
                
            }// Si la comisión NO es cobrable
            else {
                modo_devolucion =   null;
            }

            //Si se debe mostrar selección de modo de devolución del pago
            if (mostrar_seleccion_modo_devolucion_pago) {

                //Seteo valores para el modal
                $(id + " #mdp_csrf").val(csrf);
                $(id + " #mdp_curso").val(curso);
                $(id + " #mdp_hash_curso").val(hash_curso);
                $(id + " #mdp_comision").val(comision);
                $(id + " #mdp_inscripcion").val(inscripcion);
                $(id + " #mdp_baja_inicial").val(baja_inicial);
                $(id + ' select#mdp_modo_devolucion_pago').html(forma_pago['select_opciones_devolucion']);

                //Limpio y muestro el modal de modo de devolución de pago
                $(id + ' #control_group_mdp_modo_devolucion_pago').removeClass('error');
                $(id + ' #control_group_mdp_modo_devolucion_pago span.help-inline').text('');
                $(id + ' #modal_modo_devolucion_pago').modal('show');

            }//Si NO se debe mostrar selección de modo de devolución del pago
            else {

                kernel.ui.prompt({
                    msg: info.msg_baja,
                    msg_ok: info.msg_confirmar_baja,
                    msg_cancel: info.cancelar,

                    ok: function() {

                        baja_curso_ajax(csrf, curso, hash_curso, comision, inscripcion, baja_inicial, modo_devolucion);

                    }
                });

            }

        }

        function baja_curso_ajax(csrf, curso, hash_curso, comision, inscripcion, baja_inicial, modo_devolucion){

            kernel.ajax.load(info.url_baja, id, {
                titulo: info.mensajes.titulo,
                historia: false,
                type: 'POST',
                data: {
                    '__csrf': csrf,
                    'curso': curso,
                    'comision': comision,
                    'inscripcion': inscripcion,
                    'baja_inicial': baja_inicial,
                    'modo_devolucion': modo_devolucion
                },
                success: function(response) {

                    if (baja_inicial == info.si) {
                        callback_baja_inicial_success(curso, hash_curso, response);
                    }
                    else if (baja_inicial == info.no) {
                        callback_baja_success(response)
                    }

                },
                error: function(paquete) {

                    if(baja_inicial == info.si){
                        callback_baja_inicial_error(paquete);
                    }
                    else if (baja_inicial == info.no) {
                        callback_baja_error(paquete)
                    }

                }
            });

        }

        function callback_baja_inicial_success(curso, hash_curso, response){

            var inscripciones = $(id).find('.js-boton-baja button').filter(function() {
                return $(this).data("curso") == curso
            }).length;
            // cuando se ejecuta el onload se registra el listener de nuevo
            kernel.evts.disparar('info_curso_baja', {
                cant_inscripciones: inscripciones,
                materia: hash_curso
            });
            kernel.ui.show_mensaje(response.mensaje_cert_baja, {
                until_interaction: true
            });
            reload_pagelet();

        }

        function callback_baja_success(response){

            kernel.evts.disparar('info_curso_baja', {
                cant_inscripciones: $(id).find('.comision.inscripto').length
            });
            kernel.ui.show_mensaje(response.mensaje_cert_baja, {
                until_interaction: true
            });
            reload_pagelet();

        }

        function callback_baja_inicial_error(paquete){

            $(id).find('.js-csrf').data('csrf', paquete.csrf_token);

        }

        function callback_baja_error(paquete){

            $(id).find('[name=__csrf]').val(paquete.csrf_token);

        }

        function existe_inscripcion(curso, inscripcion){

            var resultado = null;

            kernel.ajax.call(info.url_existe_inscripcion, {
                type: 'get',
                async: false,
                data: {
                    curso: curso,
                    inscripcion: inscripcion
                },
                success: function(paquete) {
                    if(paquete.hubo_error){

                        kernel.ui.show_mensaje(paquete.msj_error, {
                            tipo: 'alert-error',
                            until_interaction: true
                        });

                    }
                    else{
                        resultado = paquete.existe_inscripcion;
                    }
                }
            });

            return resultado;
        }

        function es_comision_cobrable(curso, inscripcion){

            var resultado = null;

            kernel.ajax.call(info.url_es_comision_cobrable, {
                type: 'get',
                async: false,
                data: {
                    curso: curso,
                    inscripcion: inscripcion
                },
                success: function(paquete) {
                    if(paquete.hubo_error){

                        kernel.ui.show_mensaje(paquete.msj_error, {
                            tipo: 'alert-error',
                            until_interaction: true
                        });

                    }
                    else{
                        resultado = paquete.comision_cobrable;
                    }
                }
            });

            return resultado;
        }

        function obtener_forma_pago_consumo_externo(curso, inscripcion){

            var resultado = null;

            kernel.ajax.call(info.url_obtener_forma_pago_consumo_externo, {
                type: 'get',
                async: false,
                data: {
                    curso: curso,
                    inscripcion: inscripcion
                },
                success: function(paquete) {
                    if(paquete.hubo_error){

                        kernel.ui.show_mensaje(paquete.msj_error, {
                            tipo: 'alert-error',
                            until_interaction: true
                        });

                    }
                    else{
                        resultado = [];
                        resultado['estado'] = paquete.estado;
                        resultado['opciones_devolucion'] = paquete.opciones_devolucion;
                        resultado['select_opciones_devolucion'] = paquete.select_opciones_devolucion;
                    }
                }
            });

            return resultado;
        }

        //***************************************************************
        //****************** Fin Baja Cursos ****************************
        //***************************************************************

        //***************************************************************
        //****************** Modal modo devolución pago *****************
        //***************************************************************


        $( id ).on( "click" , "#mdp_dar_baja" , function(e) {

            //Obtengo valores del modal para la baja
            var csrf            =   $(id + " #mdp_csrf").val();
            var curso           =   $(id + " #mdp_curso").val();
            var hash_curso      =   $(id + " #mdp_hash_curso").val();
            var comision        =   $(id + " #mdp_comision").val();
            var inscripcion     =   $(id + " #mdp_inscripcion").val();
            var baja_inicial    =   $(id + " #mdp_baja_inicial").val();
            var modo_devolucion =   $(id + " #mdp_modo_devolucion_pago").val();

            $(id + ' #control_group_mdp_modo_devolucion_pago').removeClass('error');
            $(id + ' #control_group_mdp_modo_devolucion_pago span.help-inline').text('');

            var modos_devolucion_pago = info.modos_devolucion_validos;
            //Se valida el SELECT de modos de devolución de pago
            if (modos_devolucion_pago.indexOf(modo_devolucion) == -1) {
                $(id + ' #control_group_mdp_modo_devolucion_pago').addClass('error');
                $(id + ' #control_group_mdp_modo_devolucion_pago span.help-inline').text(info.modo_devolucion_pago_invalido);
                return false;
            }

            //Escondo el modal de modo de devolución de pago
            $(id + ' #modal_modo_devolucion_pago').modal('hide');

            baja_curso_ajax(csrf, curso, hash_curso, comision, inscripcion, baja_inicial, modo_devolucion);

            e.preventDefault();
            return false;
        });

        //***************************************************************
        //****************** Fin Modal modo devolución pago *************
        //***************************************************************

        $( id ).on( "submit", ".form_inscribir", function(e) {
            
            var $form = $(this);
                     
            kernel.ui.prompt({
                    msg: info.msg_inscripcion_exitosa.replace("%nombre_sede%", $form.data("sedeNombre")).replace("%comision_nombre%", $form.data("comisionNombre")).replace("%actividad_nombre%", $form.data("actividadNombre")),
                    msg_ok: info.msg_confirmar_inscripcion,
                    msg_cancel: info.cancelar,

                    ok: function() {
                        
                        kernel.ajax.load($form.attr('action'), id, {
                        titulo: info.mensajes.titulo,
                        historia: false,
                        type: 'POST',
                        data: $form.serializeArray(),
                        success: function(paquete) {
                            kernel.evts.disparar('info_curso_inscripto', []);
                            kernel.evts.disparar('info_curso_notificaciones_inscripto', []);
                            reload_pagelet();

                            kernel.ui.show_mensaje(paquete.mensaje_inscripcion, {
                                until_interaction: true
                            });

                            //Si hay que navegar a SQ pagos y no hubo error al generar una solicitud de consumo externo
                            if(paquete.abrir_SQ_pagos && (!paquete.hubo_error_SQ)){
                                //Se abre una nueva ventana para pagar (SQ pagos)
                                //PopupCenter(paquete.url_sq_pagos, '_blank', '1100', '600');
                                window.location.replace(paquete.url_sq_pagos);
                            }

                        },
                        error: function(paquete) {
                            $(id).find('[name=__csrf]').val(paquete.csrf_token);
                        }
                    });
                        
                    }
                });
            e.preventDefault();
            return false;
        });

        $( id ).on( "click", ".js-pagar", function(e) {

            var token_sq    =   $(this).data("tokenSq");
            var curso       =   $(this).data("curso");
            var inscripcion =   $(this).data("inscripcion");

            var inscripcion_existe  =   existe_inscripcion(curso, inscripcion);

            // Si no existe la inscripción
            if (!inscripcion_existe) {

                kernel.ui.show_mensaje(info.mensajes.error_no_existe_inscripcion, {
                    tipo: 'alert-error',
                    timeout: 10000
                });

                return false;
            }

            kernel.ajax.call(info.url_verificar_solicitud_consumo_externo, {
                type: 'post',
                data: {
                    token: token_sq
                },
                success: function(paquete) {
                    if(paquete.error_pagar){

                        kernel.ui.show_mensaje(paquete.msj_error_pagar, {
                            tipo: 'alert-error',
                            until_interaction: true
                        });

                    }
                    else{
                        window.location.replace(paquete.url_consumo_SQ_pagos);
                    }
                }
            });

            e.preventDefault();
            return false;
        });

        $( id ).on( "keyup focusout", ".codigo_inscripcion_cerrada", function(e) {

            var codigo_inscripcion = $(this);
            var btn_inscribirse = codigo_inscripcion.parents(".acciones").find("input[type='submit']");

            //Si el codigo de inscripcion es vacio deshabilito la inscripcion
            if(codigo_inscripcion.val().trim() == ""){
                btn_inscribirse.attr("disabled", true).prop("disabled", true);
            } else {
		btn_inscribirse.attr("disabled", false).prop("disabled", false);
	    }

            e.preventDefault();
            return false;
        });
	
	$(id).on("click", ".js-cic-icon", function (e) {

	    var id_input = $(this).data('idInput');

	    // Si se quiere mostrar el "Código inscripción"
	    if ($(this).hasClass("icon-eye-close")) {
		$(this).removeClass("icon-eye-close");
		$(this).addClass("icon-eye-open");
		$("#" + id_input).attr("type", "text");
	    } else {// Si se quiere ocultar el "Código inscripción"
		$(this).removeClass("icon-eye-open");
		$(this).addClass("icon-eye-close");
		$("#" + id_input).attr("type", "password");
	    }
	});

    }

    function registrar_eventos_comprobantes() {

        $(id).on('click', '.comprobante-cursada', function(e) {
            var placeholder_comprobante = $(this);
            var url_img = placeholder_comprobante.data('img');
            if (! placeholder_comprobante.data('loaded')) {
                kernel.ui.show_loading(placeholder_comprobante, 'small', {
                    my: 'center',
                    at: 'right center',
                    offset: '10 0',
                    of: placeholder_comprobante
                });
                $('<img />').attr('src', url_img)
                    .on("load", function() {
                        $(this).appendTo(placeholder_comprobante.find('.img-placeholder'));
                        kernel.ui.hide_loading();
                        toggle_msg_comprobante(placeholder_comprobante);
                        placeholder_comprobante.find('.img-placeholder').toggle();
                        placeholder_comprobante.data('loaded', true);
                    })
                    .on("error", function(event) {
                        kernel.ui.hide_loading();
                        kernel.ui.show_mensaje(info.mensajes.error_no_existe_comprobante_inscripcion, {
                            tipo: 'alert-error',
                            timeout: 10000
                        });
                        placeholder_comprobante.data('error', true);
                        placeholder_comprobante.data('loaded', true);
                    });
            } else {
                // Si dio error (se borrï¿½ inscripciï¿½n desde Gestiï¿½n)
                if(placeholder_comprobante.data('error')){
                    kernel.ui.show_mensaje(info.mensajes.error_no_existe_comprobante_inscripcion, {
                        tipo: 'alert-error',
                        timeout: 10000
                    });
                } // sino despliego el comprobante
                else{
                    toggle_msg_comprobante(placeholder_comprobante);
                    placeholder_comprobante.find('.img-placeholder').toggle();
                }
            }

            e.preventDefault();
            return false;
        });

        //Ver: http://www.todoexpertos.com/categorias/tecnologia-e-internet/desarrollo-de-sitios-web/respuestas/2214856/boton-imprimir
        $(id).on('click', '.js-print-comprobante', function(e) {
            var ventana_impresion = window.open($(this).data('img'), '_blank', 'menubar=0,scrollbars=0,location=0');
            ventana_impresion.document.title = 'Imprimir comprobante de curso';
            ventana_impresion.print();
            e.preventDefault();
            return false;
        });

        $(id).on('click', '.js-mail-comprobante', function(e) {
            var href = $(this).attr('href');
            var fn_ok = function() {
                kernel.ajax.call(href, {
                    type: 'get',
                    success: function() {
                        kernel.ui.show_mensaje(info.envio_mail_exitoso);
                    }
                });
            };

            kernel.ui.prompt({
                title: info.msg_titulo_enviar,
                msg: (info.falta_mail) ? info.msg_falta_mail : info.msg_enviar,
                msg_ok: info.msg_enviar_boton,
                ok: (info.falta_mail) ? false : fn_ok
            });

            e.preventDefault();
            return false;
        });
    }

    function toggle_msg_comprobante(placeholder)
    {
        var original_msg = placeholder.find('a').html();
        var nuevo_msg = placeholder.data('msg-toggle');
        placeholder.find('a:first').html(nuevo_msg);
        placeholder.data('msg-toggle', original_msg);
    }

    function cargar_pantalla_inicial(params) {
        kernel.ajax.load(params.href, id, {
            titulo: info.mensajes.titulo,
            success: function(paquete) {
                reload_pagelet();
            }
        });
    }

    function cargar_curso(params) {
        kernel.ajax.load(params.href, id, {
            titulo: info.mensajes.titulo,
            success: function(paquete) {
                kernel.evts.disparar('info_curso_cargado', params);
                reload_pagelet();
            }
        });
    }

    function reload_pagelet()
    {
        kernel.evts.dejar_de_escuchar('seleccion_curso', cargar_curso);
        $(id).off();
        kernel.renderer.pagelet('info_curso').onload();
    }

    function mostrar_mensaje(){

        if(info.msj_contenido){

            kernel.ui.show_mensaje(info.msj_contenido, {
                tipo: info.msj_tipo,
                timeout: 20000
            });

            delete info.msj_contenido;
            delete info.msj_tipo;
        }
    }

    function mostrar_comprobante(){

        if(info.mostrar_comprobante){
            // Muestro el comprobante en cuestion
            $( ".comprobante-cursada[data-transaccion='"+info.nro_transaccion+"']" ).trigger( "click" );
            // Borro las variables involucradas
            delete info.mostrar_comprobante;
            delete info.nro_transaccion;
        }
    }

    return {
        onload: function() {

            registrar_listeners();
            registrar_eventos_comisiones();
            registrar_eventos_comprobantes();
            mostrar_mensaje();
            mostrar_comprobante();
            registrar_eventos_filtros();

            $(id + ' .js-pagar').tooltip({placement: 'bottom'});

            $(id + ' .icono i').tooltip({placement: 'top'});
	    
        }

    }
    
    function registrar_eventos_filtros()
	{
            $( id ).on( "vclick", ".js-dia", function(e) {
                filtrar();
            });            
            
            $( id ).on( "change", "#js-ubicaciones", function(e) {
                filtrar();
            });
	}
    
    function filtrar()
        {
            var dias = [];
            var ubicacion = "";	    
	    var ubicacion_seleccionada = $( "#js-ubicaciones" ).val();
	    
            if (ubicacion_seleccionada != 0) {
                ubicacion = 'js-ubicacion-'+ubicacion_seleccionada;
            }
            
            $( ".js-dia.active" ).each(function( index ) {
                dias.push(".js-dia-"+$( this ).data('dia'));
            });
            
            
            $( "li.comision" ).each(function( index ) {
                
                var cumple_dias = dias.length == 0 || $( this ).is(dias.join(','));
                var cumple_ubicacion = ubicacion == "" || $( this ).hasClass(ubicacion);
                
                if (cumple_dias && cumple_ubicacion)
                {
                    $( this ).show().addClass('visible');
                } else {
                    $( this ).hide().removeClass('visible');
                }
                
            });
	    
	    $( "div.accordion" ).each(function( index ) {
                
                if($(this).find(".accordion-inner li.comision.visible").length > 0){
		    $( this ).show();
                } else {
                    $( this ).hide();
                }             
            });
            
        }
})