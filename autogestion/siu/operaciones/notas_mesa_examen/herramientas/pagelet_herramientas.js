kernel.renderer.registrar_pagelet('herramientas', function(info) {
    
    var id = '#' + info.id;
    
    var campos_undo = {
	campo: null
    };
    var fecha_inicio, fecha_fin;
    
    function set_undo(campo) {
	campos_undo.campo = campo;
	$('#preset_undo').prop("disabled", false);
    }

    function get_escala() {
	var escala = $('#preset_campo').val();
	var partes = escala.split(',');
	return guarani.escalas().recuperar(partes[1]);
    }

    function create_date(date_string) {
	var fecha_split = date_string.split('/');
	return new Date(fecha_split[2], fecha_split[1]-1, fecha_split[0]);
    }
	
    function controlar_btn_completar(){
        
        var campo = $('#preset_campo').val();
            
        if (campo === 'resultado') {
            $('#btnCompletar').prop("disabled", false);
        } else {
            if (($('#preset-otros').val() === '') && ($('#preset-fecha').val() === '')) {
                    $('#btnCompletar').prop('disabled', true);
            } else {
                    $('#btnCompletar').prop("disabled", false);
            }
        }
    }

    return {
        onload: function() {
            
            $('#preset_form .fecha').datepicker();
            
            $('#preset_form').submit(function (e) {
                
                    e.preventDefault();
                    
                    var campo = $('#preset_campo').val();
                    var val = $.trim($(id).find('.preset_valor:visible').val());
                    
                    if (val === '') {
                        return false;
                    }
                    
                    $(id).find('.preset_valor:visible:not(.resultado)').val('');

                    // chequeo si el valor es una fecha válida
                    if (campo === 'fecha' && !fecha_valida(val)) {
                        return false;
                    }
                    
                    set_undo(campo);

                    var solo_vacio = $('#preset_solo_vacio').is(':checked');

                    var filas_undo = $('#renglones .tabla-renglones tbody tr').clone();

                    // Recorro los renglones actuales
                    $('#renglones .tabla-renglones tbody tr').each(function (i)
                    {
                        
                        // Se guardan los valores anteriores al autocompletar en atributos data.
                        $(this).data("fechaAnt", $(filas_undo[i]).find(".fecha").val());
                        $(this).data("notaAnt", $(filas_undo[i]).find(".nota").val());
                        $(this).data("resultadoAnt", $(filas_undo[i]).find(".resultado").val());

                        // Si se trata de la fecha
                        if (campo === 'fecha') {

                            var fecha_obj = $(this).find(".fecha");
                            // Si el campo fecha esta deshabilitado salteo dicha fila
                            if ($(fecha_obj).is(':disabled')) {
                                return;
                            }
                            // Si "Completar sólo vacíos" esta tildado y la fecha ya tiene un valor, entonces salteo dicha fila
                            if (solo_vacio && $(fecha_obj).val() != '') {
                                return;
                            }

                            $(fecha_obj).val(val);
                            $(fecha_obj).trigger( "change" );
                            $(fecha_obj).trigger( "keyup" );

                        } else {// Si se trata de la nota o resultado

                            // chequeo si el valor es una nota válida
                            if (campo !== 'resultado') {
                                
                                var partes = campo.split(',');
                                var nota_instancia_class = 'js-nota_instancia_' + partes[0];
                                // Si la instancia de la nota a autocompletar es otra, entonces salteo dicha fila
                                if (!$(this).find(".nota").hasClass(nota_instancia_class)) {
                                    return;
                                }
                            
                                var escala = get_escala();
                                // Si la nota es invalida salgo del "each"
                                if (!escala.validar(val)) {
                                    return false;
                                } else {
                                    if (escala.get_tipo() === 'continua') {
                                        val = escala.formatear(val);
                                    }
                                }
                            }
                            
                            var nota_obj = $(this).find(".nota");
                            var nota_val = $(nota_obj).val();
                            var resultado_obj = $(this).find(".resultado");
                            var resultado_val = $(resultado_obj).val();

                            // Si "Completar sólo vacíos" esta tildado y la nota o resultado tiene un valor, entonces salteo dicha fila
                            if (solo_vacio && (nota_val != '' || resultado_val != '')) {
                                return;
                            }

                            if (campo === 'resultado') {
                                $(resultado_obj).val(val);
                                $(resultado_obj).trigger( "change" );
                                $(resultado_obj).trigger( "keyup" );
                            } else {
                                $(nota_obj).val(val);
                                $(nota_obj).trigger( "change" );
                                $(nota_obj).trigger( "keyup" );
                            }
                        }
                    });

                    if (campo != 'resultado') {
                        $('#btnCompletar').prop('disabled', true);
                    }

                    // Habilito el botón "Deshacer"
                    $('#preset_undo').prop('disabled', false);
      
            });
                
                fecha_inicio = create_date(info.fecha_inicio);
                fecha_fin = create_date(info.fecha_fin);

                function fecha_valida(val) {
                var fecha = create_date(val);
                        return (fecha.getTime() >= fecha_inicio.getTime() && fecha.getTime() <= fecha_fin.getTime());
                }

                $('#preset_campo').change(function() {
                    
                    // Vacío todos campos dentro de "Autocompletar" menos el resultado
                    $('#preset_form').find('.preset_valor:not(.resultado)').val('');
                    
                    switch ($(this).val()) {
                    case 'fecha':
                            $('#preset_form').find('.otros').hide();
                            $('#preset_form').find('.resultado').hide();
                            $('#preset_form').find('.fecha').show();                            
                            $('#preset_solo_vacio').prop('disabled', false);
                            break;
                    case 'resultado':
                            $('#preset_form').find('.otros').hide();
                            $('#preset_form').find('.fecha').hide();
                            $('#preset_form').find('.resultado').show();
                            $('#preset_solo_vacio').prop('disabled', false);
                            break;
                    default:
                            $('#preset_form').find('.otros').show();
                            $('#preset_form').find('.fecha').hide();
                            $('#preset_form').find('.resultado').hide();
                            $('#preset_solo_vacio').prop('disabled', false);
                            break;
                    }
                });
                $('#preset_campo').change(function() {
                    controlar_btn_completar();
                });
                $('.preset_valor').keyup(function() {
                    controlar_btn_completar();
                });
                
                $('#preset_undo').on("click", function() { 
                    
                    // Recorro los renglones actuales para ponerle los valores anteriores
                    $('#renglones .tabla-renglones tbody tr').each(function ()
                    {
                        $(this).find(".fecha").val($(this).data("fechaAnt")).trigger( "change" ).trigger( "keyup" );
                        $(this).find(".nota").val($(this).data("notaAnt")).trigger( "change" ).trigger( "keyup" );
                        $(this).find(".resultado").val($(this).data("resultadoAnt")).trigger( "change" ).trigger( "keyup" );
                    });

                    $('#preset_undo').prop('disabled', true);
                    if (campos_undo.campo != 'resultado') {
                        $('#btnCompletar').prop('disabled', true);
                    }
                });
                
                $('#preset_form .preset_valor').change(function() {
                    controlar_btn_completar();
                    var campo = $('#preset_campo').val();
                    var val;
                    if (campo !== 'fecha') {
                        val = $('#preset_form .preset_valor').val();
                        if (! get_escala().validar(val)) {
                            $(this).find('.preset_valor:visible').val('');
                            kernel.ui.show_mensaje(info.mensajes.nota_invalida, {tipo: 'alert-error'});
                        }
                    }

                    if (campo === 'fecha') {
                        val = $('#preset_form .fecha').val();
                        if (! fecha_valida(val)) {
                            $(this).find('.preset_valor:visible').val('');
                            kernel.ui.show_mensaje(info.mensajes.fecha_invalida, {tipo: 'alert-error'});
                        }
                    }
                });
            }
        }
})