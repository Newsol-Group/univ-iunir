kernel.renderer.registrar_pagelet('discapacidad', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {
            
            //---- Manejo de visibilidad de secciones por eventos --------------
            
            evt_condicion_discapacidad();
            toggle_titulos_subrayado_gris();
            
            $('.js_condicion_discapacidad').on('click', evt_condicion_discapacidad);
            
            $('#form_discapacidad-disc_auditiva').on('click', evt_discapacidades_auditivas);
            
            $('#form_discapacidad-aud_req_otros_apoyos').on('click', evt_aud_otros_apoyos);
            
            $('#form_discapacidad-disc_visual').on('click', evt_discapacidades_visuales);
            
            $('#form_discapacidad-vis_req_otros_apoyos').on('click', evt_vis_otros_apoyos);
            
            $('#form_discapacidad-disc_motora').on('click', evt_discapacidades_motoras);
            
            $('#form_discapacidad-mot_req_apoyo_aula').on('click', evt_mot_apoyo_aula);
            
            $('#form_discapacidad-mot_req_otros_apoyos').on('click', evt_mot_otros_apoyos);
            
            $('#form_discapacidad-disc_cond_psicosocial').on('click', evt_discapacidades_psicosocial);
            
            $('#form_discapacidad-psi_req_otros_apoyos').on('click', evt_psi_otros_apoyos);
            
            $('#form_discapacidad-disc_otra').on('click', evt_discapacidades_otras);
            
            $('#form_discapacidad-otra_req_apoyo_vida_diaria').on('click', evt_otra_apoyo_vida_diaria);
            
            function toggle_titulos_subrayado_gris()
            {
                $( "#js_auditiva_forma_habitual_comunicacion" ).toggle( $(".js_auditiva_forma_habitual_comunicacion").size() > 0 );
                $( "#js_auditiva_apoyos_requeridos" ).toggle( $(".js_auditiva_apoyos_requeridos").size() > 0 );
                $( "#js_visual_apoyos_requeridos" ).toggle( $(".js_visual_apoyos_requeridos").size() > 0 );
                $( "#js_motora_apoyos_requeridos" ).toggle( $(".js_motora_apoyos_requeridos").size() > 0 );
                $( "#js_psicosocial_apoyos_requeridos" ).toggle( $(".js_psicosocial_apoyos_requeridos").size() > 0 );
            }
            
            function evt_condicion_discapacidad()
            {
                
                if ($('input.js_condicion_discapacidad:checked').val() == info.si) {
                    $("#div_tiene_cud").show();
                    
                    mostrar_tipos_discapacidades();
                    
                } else {
                    $("#div_tiene_cud").hide();
                    
                    ocultar_tipos_discapacidades();
                }
                
            }
            
            function mostrar_tipos_discapacidades()
            {
                $("#div_listado_discapacidad_tipos").show();
                $("#div_importante").show();
                evt_discapacidades_auditivas();
                evt_discapacidades_visuales();
                evt_discapacidades_motoras();
                evt_discapacidades_psicosocial();
                evt_discapacidades_otras();
            }
            
            function ocultar_tipos_discapacidades()
            {
                $("#div_listado_discapacidad_tipos").hide();
                $("#div_importante").hide();
                ocultar_discapacidades_auditivas();
                ocultar_discapacidades_visuales();
                ocultar_discapacidades_motoras();
                ocultar_discapacidades_psicosocial();
                ocultar_discapacidades_otras();
            }
            
            function evt_discapacidades_auditivas()
            {
                if ($('#form_discapacidad-disc_auditiva').is(':checked')) {
                    mostrar_discapacidades_auditivas();
                } else {
                    ocultar_discapacidades_auditivas();
                }
            }
            
            function mostrar_discapacidades_auditivas()
            {
                $("#div_auditiva").show();
                evt_aud_otros_apoyos();
            }
            
            function ocultar_discapacidades_auditivas()
            {
                $("#div_auditiva").hide();
            }
            
            function evt_aud_otros_apoyos()
            {
                if ($('#form_discapacidad-aud_req_otros_apoyos').is(':checked')) {
                    mostrar_aud_otros_apoyos();
                } else {
                    ocultar_aud_otros_apoyos();
                }
            }
            
            function mostrar_aud_otros_apoyos()
            {
                $("#form_discapacidad-aud_otros_apoyos").parent(".control-group").show();
            }
            
            function ocultar_aud_otros_apoyos()
            {
                $("#form_discapacidad-aud_otros_apoyos").parent(".control-group").hide();
            }
            
            function evt_discapacidades_visuales()
            {
                if ($('#form_discapacidad-disc_visual').is(':checked')) {
                    mostrar_discapacidades_visuales();
                } else {
                    ocultar_discapacidades_visuales();
                }
            }
            
            function mostrar_discapacidades_visuales()
            {
                $("#div_visual").show();
                evt_vis_otros_apoyos();
            }
            
            function ocultar_discapacidades_visuales()
            {
                $("#div_visual").hide();
            }
            
            function evt_vis_otros_apoyos()
            {
                if ($('#form_discapacidad-vis_req_otros_apoyos').is(':checked')) {
                    mostrar_vis_otros_apoyos();
                } else {
                    ocultar_vis_otros_apoyos();
                }
            }
            
            function mostrar_vis_otros_apoyos()
            {
                $("#form_discapacidad-vis_otros_apoyos").parent(".control-group").show();
            }
            
            function ocultar_vis_otros_apoyos()
            {
                $("#form_discapacidad-vis_otros_apoyos").parent(".control-group").hide();
            }
            
            function evt_discapacidades_motoras()
            {
                if ($('#form_discapacidad-disc_motora').is(':checked')) {
                    mostrar_discapacidades_motoras();
                } else {
                    ocultar_discapacidades_motoras();
                }
            }
            
            function mostrar_discapacidades_motoras()
            {
                $("#div_motora").show();
                evt_mot_apoyo_aula();
                evt_mot_otros_apoyos();
            }
            
            function ocultar_discapacidades_motoras()
            {
                $("#div_motora").hide();
            }
            
            function evt_mot_apoyo_aula()
            {
                if ($('#form_discapacidad-mot_req_apoyo_aula').is(':checked')) {
                    mostrar_mot_apoyo_aula();
                } else {
                    ocultar_mot_apoyo_aula();
                }
            }
            
            function mostrar_mot_apoyo_aula()
            {
                $("#form_discapacidad-mot_apoyo_aula").parent(".control-group").show();
            }
            
            function ocultar_mot_apoyo_aula()
            {
                $("#form_discapacidad-mot_apoyo_aula").parent(".control-group").hide();
            }
            
            function evt_mot_otros_apoyos()
            {
                if ($('#form_discapacidad-mot_req_otros_apoyos').is(':checked')) {
                    mostrar_mot_otros_apoyos();
                } else {
                    ocultar_mot_otros_apoyos();
                }
            }
            
            function mostrar_mot_otros_apoyos()
            {
                $("#form_discapacidad-mot_otros_apoyos").parent(".control-group").show();
            }
            
            function ocultar_mot_otros_apoyos()
            {
                $("#form_discapacidad-mot_otros_apoyos").parent(".control-group").hide();
            }
            
            function evt_discapacidades_psicosocial()
            {
                if ($('#form_discapacidad-disc_cond_psicosocial').is(':checked')) {
                    mostrar_discapacidades_psicosocial();
                } else {
                    ocultar_discapacidades_psicosocial();
                }
            }
            
            function mostrar_discapacidades_psicosocial()
            {
                $("#div_psicosocial").show();
                evt_psi_otros_apoyos();
            }
            
            function ocultar_discapacidades_psicosocial()
            {
                $("#div_psicosocial").hide();
            }
            
            function evt_psi_otros_apoyos()
            {
                if ($('#form_discapacidad-psi_req_otros_apoyos').is(':checked')) {
                    mostrar_psi_otros_apoyos();
                } else {
                    ocultar_psi_otros_apoyos();
                }
            }
            
            function mostrar_psi_otros_apoyos()
            {
                $("#form_discapacidad-psi_otros_apoyos").parent(".control-group").show();
            }
            
            function ocultar_psi_otros_apoyos()
            {
                $("#form_discapacidad-psi_otros_apoyos").parent(".control-group").hide();
            }
            
            function evt_discapacidades_otras()
            {
                if ($('#form_discapacidad-disc_otra').is(':checked')) {
                    mostrar_discapacidades_otras();
                } else {
                    ocultar_discapacidades_otras();
                }
            }
            
            function mostrar_discapacidades_otras()
            {
                $("#div_otras").show();
                evt_otra_apoyo_vida_diaria();
            }
            
            function ocultar_discapacidades_otras()
            {
                $("#div_otras").hide();
            }
            
            function evt_otra_apoyo_vida_diaria()
            {
                if ($('#form_discapacidad-otra_req_apoyo_vida_diaria').is(':checked')) {
                    mostrar_otra_apoyo_vida_diaria();
                } else {
                    ocultar_otra_apoyo_vida_diaria();
                }
            }
            
            function mostrar_otra_apoyo_vida_diaria()
            {
                $("#form_discapacidad-otra_apoyo_vida_diaria").parent(".control-group").show();
            }
            
            function ocultar_otra_apoyo_vida_diaria()
            {
                $("#form_discapacidad-otra_apoyo_vida_diaria").parent(".control-group").hide();
            }

            function formulario_validar()
            {
                if($('#form_discapacidad-condicion_discapacidad-S').prop('checked') == true){
                    if($('#form_discapacidad-disc_auditiva').prop('checked')  == false && 
                    $('#form_discapacidad-disc_visual').prop('checked') == false && 
                    $('#form_discapacidad-disc_motora').prop('checked') == false &&
                    $('#form_discapacidad-disc_cond_psicosocial').prop('checked') == false &&
                    $('#form_discapacidad-disc_otra').prop('checked') == false 
                     ){  
                         $('#div_listado_discapacidad_tipos').addClass('control-group  error') 
                    return false;
                    }
                        $('#div_listado_discapacidad_tipos').removeClass('control-group  error') 
                    
                }
                    return true;
            }
            
            if (info.autosave) {
                $(id).autosave();
            }
            formulario_validar()
            $('#form_discapacidad').submit(function () {
                var valido = formulario_validar();
                var hubo_cambios = formulario_hubo_cambios();
                if (valido) {   
                if (hubo_cambios) {
                    var $form = $(this);
                    kernel.ajax.load($form.attr('action'), id, {
                        historia: false,
                        type: 'POST',
                        data: $form.serializeArray(),
                        success: function (response) {
                            if (response.info_estado.exitoso) {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-success',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                });
                            } else {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-error',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                })
                            }
                            // Si corresponde, se desactiva el autosave
                            if (info.autosave) {
                                $(id).autosave('disable');
                            }
                            kernel.renderer.pagelet('discapacidad').onload();
                        }
                    });
                } else {
                    kernel.ui.show_mensaje(info.msj_sin_cambios, {
                        tipo: 'alert-info',
                        until_interaction: false,
                        timeout: 5000,
                        fadeout: 2000
                    });
                }
                }else {
                    kernel.ui.show_mensaje(info.msj_faltan_datos_discapacidad, {
                        tipo: 'alert-error',
                        until_interaction: false,
                        timeout: 15000,
                        fadeout: 2000
                    });
                }
                return false;
            });
        }
    }
});