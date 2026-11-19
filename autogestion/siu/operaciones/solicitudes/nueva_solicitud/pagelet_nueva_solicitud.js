kernel.renderer.registrar_pagelet('nueva_solicitud', function(info) {
var id = '#' + info.id;

function prompt(options)
{
var defaults = {
title: '',
msg: '',
msg_ok: 'ok',
msg_cancel: 'Cancelar',
url_cancel: '#',
text_box: false,
icon_question: true,
ok: function() {
if (console) {
console.log('no handler for ok');
}
}
};

options = $.extend(defaults, options);

var text_box = false;
if (options.text_box) {
text_box = $('<textarea class="textarea">');
}

var b_cancel = $('<a href="'+options.url_cancel+'">').html(options.msg_cancel).on("click", function() {
$(document).trigger('close.facebox');
});

var div = $('<div>');
if (options.title != '') {
div.append('<h3>' + options.title + '</h3>');
}

div.append('<p>' + options.msg + '</p>');

if (text_box) {
div.append(text_box);
}

var wrapper =  $('<div class="clearfix">');

var botonera = $('<div class="pull-left">');

var icono = $('<div class="pull-right">').append('<span style="position:relative; top: 6px; right: 13px;" class="icon-question-sign"></span>');

if (options.ok !== false) {
var b_ok = $('<button type="button" class="btn btn-small">').html(options.msg_ok).on("click", function() {
$(document).trigger('close.facebox');
if (text_box) {
options.ok(text_box.val());
} else {
options.ok();
}
});
botonera.append(b_ok);
}

botonera.append(b_cancel);

if (options.icon_question == true){
wrapper.append(botonera).append(icono);
} else {
wrapper.append(botonera);
}

div.append(wrapper);

$.facebox(div, 'prompt');
}

return {
    onload: function() {
        $('#copias').attr('disabled', 'disabled');
        $("#filtro").hide();
        $("#resultado_filtro").hide();
        $("#filtro_resultado").hide();

        $('#constancia').on('change', function() { 
            
            
            $("#filtro").hide();
            $("#resultado_filtro").hide();
            $("#filtro_resultado").hide();

            if ($(this).val() != 0)
            {
            kernel.ajax.call(info.url_cant_copias, {
                async: false,
                data: {
                        constancia: $(this).val()
                },
                success: function(paquete) {
                        var copias = $('#copias');
                        copias.prop("disabled", false);
                        copias.empty();
                        for(var i = 1; i <= paquete.copias; i++)
                                copias.append('<option value="'+i+'">'+i+'</option>');
                }
            });
            var formulario_campo='';
            var formulario = $(this).find( "option:selected" ).data("formulario");
            if(formulario == '34000236'  ||	 formulario == '37000562'  ||	 formulario == '14000020' ||	 formulario == '14000017' ||	  formulario == '14000079'){
            kernel.ajax.call(info.url_formulario_filtro, {
                    type: 'post',
                    data: {
                            form: formulario
                    },
                    success: function(paquete) { 
                        if(paquete.hubo_error){

                            kernel.ui.show_mensaje(paquete.mensaje, {tipo: 'alert-error'});
                            $("#filtro").html("");
                            $("#resultado_filtro").html("");
                        }else{
                        $("#filtro").show();
                        $("#filtro").html(paquete.cont);
                        $("#resultado_filtro").html("");
                        $('#formulario_filtro-fecha_desde').datepicker();
                        $('#formulario_filtro-fecha_hasta').datepicker();
                        /**/
            $('#formulario_filtro-turno_examen').on('change', function() {
            get_actividad_anio();
           
            });               
            $('#formulario_filtro-anio_academico').on('change', function() {

            if( $('#formulario_filtro-anio_academico').val()==''){
                kernel.ui.show_mensaje(info.mensajes.msg_seleccionar_anio_academico, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
            switch($('#constancia').find( "option:selected" ).data("formulario")) {

                case 34000236:
                get_turno_examen();
                get_actividad_anio();
                
                var formulario_campo='llamado_mesa';
                get_campo_seleccion();
                break;
                case 37000562:

                get_actividad_con_comisiones();
                get_periodo_anio();    
                var formulario_campo='evaluacion';
                get_campo_seleccion_evaluacion_parcial();
                break;
                case 14000079:
                get_periodo_anio(); 

                var formulario_campo='clase';
                get_campo_seleccion_clase();
                break;
                case 14000020:
                var formulario_campo='comision';
                get_campo_seleccion_actividad();
                break;
                case 14000017:
                var formulario_campo='comision';
                get_campo_seleccion_actividad();
                break;
                default:
                var formulario_campo='null';
            }
            });
            /**/
            $('#formulario_filtro-periodo_lectivo').on('click', function() {  
               if($('#constancia').find( "option:selected" ).data("formulario")== 14000079  ) {
               if( $('#formulario_filtro-periodo_lectivo').val()==''){
                kernel.ui.show_mensaje('Debe seleccionar periodo lectivo.', {tipo: 'alert-error', until_interaction: true});
                
                $('#formulario_filtro-actividad').html('<option>seleccione periodo lectivo</option>');
                return false;
                }      
                get_inscripciones_comision_alumno();
                
                }
             });
            $('#formulario_filtro-actividad').on('click', function() {
            if($('#constancia').find( "option:selected" ).data("formulario")== 37000562  ) {
            
            if( $('#formulario_filtro-actividad').val()==''){
                kernel.ui.show_mensaje('Debe seleccionar actividad.', {tipo: 'alert-error', until_interaction: true});
                return false;
            }    
                get_comisiones_de_actividad();
            }    
            if($('#constancia').find( "option:selected" ).data("formulario")== 14000079  ) {
             if( $('#formulario_filtro-actividad').val()==''){
                kernel.ui.show_mensaje('Debe seleccionar actividad.', {tipo: 'alert-error', until_interaction: true});
                return false;
            }
            get_comisiones_de_actividad();}
            });
            /**/
            $('#boton_buscar_34000236').on('click', function() {
            /*añoacademico obligatoria*/
            if( $('#formulario_filtro-anio_academico').val()==''){
                kernel.ui.show_mensaje(info.mensajes.msg_seleccionar_anio_academico, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
            event.preventDefault();

            $("#resultado_filtro").html('');
            var formulario_campo='llamado_mesa';
            get_campo_seleccion();


            $("#resultado_filtro").show();
            $("#filtro_resultado").show();
            })  ;
            /**/
            $('#boton_buscar_37000562').on('click', function() {
            /*añoacademico obligatoria*/
            if( $('#formulario_filtro-anio_academico').val()==''){
                kernel.ui.show_mensaje(info.mensajes.msg_seleccionar_anio_academico, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
            event.preventDefault();
            $("#resultado_filtro").html('');
            var formulario_campo='evaluacion';
            get_campo_seleccion_evaluacion_parcial();
            

            $("#resultado_filtro").show();
            $("#filtro_resultado").show();
            })  ;
            $('#boton_buscar_14000079').on('click', function() {
            /*actividad obligatoria*/
            event.preventDefault();
            if( $('#formulario_filtro-actividad').val()==''){
                kernel.ui.show_mensaje(info.mensajes.msg_seleccionar_actividad, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
            $("#resultado_filtro").html('');
            var formulario_campo='clase';
            get_campo_seleccion_actividad_clases();
            

            $("#resultado_filtro").show();
            $("#filtro_resultado").show();
            })  ;
            $('#boton_buscar_14000020').on('click', function() {
            event.preventDefault();

            $("#resultado_filtro").html('');
            var formulario_campo='comision';
            get_campo_seleccion_actividad_aprobada();

            $("#resultado_filtro").show();
            $("#filtro_resultado").show();
            })  ;
            $('#boton_buscar_14000017').on('click', function() {
            event.preventDefault();

            $("#resultado_filtro").html('');
            var formulario_campo='comision';
            get_campo_seleccion_actividad();

            $("#resultado_filtro").show();
            $("#filtro_resultado").show();
            })  ;
                        }
                    }
            });
            }
            else
            {          
            $("#filtro").html("");
            $("#resultado_filtro").html("");
            }}
            else
            {     
            $("#filtro").html("");
            $("#resultado_filtro").html("");                 
            $('#copias').empty();
            $('#copias').attr('disabled', 'disabled');
            }
        });





        $('#form').on('submit', function(event) {
            
            // Prevengo el submit del formulario ya que se va a hacer por AJAX
            event.preventDefault();
            
            if ($('#constancia').val() == 0)
            {
                kernel.ui.show_mensaje(info.mensajes.msg_constancia, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
            if (!$('#presentar').val())
            {
                kernel.ui.show_mensaje(info.mensajes.msg_presentar, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
           

            $('#_msg_placeholder').hide();

            var formulario_campo = '';
            switch($('#constancia').find( "option:selected" ).data("formulario")) {

            case 34000236:
                formulario_campo = 'llamado_mesa';
                
            break;
            case 37000562:
                formulario_campo = 'evaluacion';
                
            break;
            case 14000079:
                formulario_campo = 'clase';
               
            break;
            case 14000020:
            //Constancia de Aprobacion a Curso
                formulario_campo = 'comision';
                
            break;
            case 14000017:       
            //Constancia de Asistencia a Curso
                formulario_campo = 'comision';
                
            break;
            default:
                formulario_campo='';
            }

            if(formulario_campo !== ''){
                if ($('#resultado_filtro').val()== null ){
                        
                kernel.ui.show_mensaje(info.mensajes.msg_buscar, {tipo: 'alert-error', until_interaction: true});
                return false;
            }
                if($('#resultado_filtro').val()=='no_hay_datos'|| $('#resultado_filtro').val()== ""){ 
                    $('#resultado_filtro').focus();
                    kernel.ui.show_mensaje(info.mensajes.msg_seleccionar_opcion, {tipo: 'alert-error', until_interaction: true});
                    return false;
                } 
            }

            kernel.ajax.call($(this).attr('action'), {
                type: 'post',
                async: false,
                data: {
                    constancia: $('#constancia').val(),
                    copias: $('#copias').val(),
                    presentar: $('#presentar').val(),
                    observaciones: $('#observaciones').val(),
                    formulario: $('#constancia').find( "option:selected" ).data("formulario"),
                    formulario_campo: formulario_campo,
                    formulario_valor: $('#resultado_filtro').val()
                },
                success: function(paquete) {
                    // Si hubo error recargo el pagelet y muestro mensaje de error
                    if (!paquete.ok)
                    {
                        $(id).html(paquete.cont);
                        kernel.renderer.pagelet(info.id).onload();
                        kernel.ui.show_mensaje(info.nueva_solicitud_error, {
                            tipo: 'alert-error',
                            until_interaction: false,
                            timeout: 5000,
                            fadeout: 2000
                        });
                    }
                }
            });
        });
    }
};




/*formulario 34000236 y 37000562*/

function get_actividad_anio(){  


    kernel.ajax.call(info.url_actividad_anio, {
    type: 'post',
    data: {
    anio: $('#formulario_filtro-anio_academico').val(),
    turno_examen: $('#formulario_filtro-turno_examen').val()
    },
    success: function(paquete) { 
        $('#formulario_filtro-actividad').empty();
        $('#formulario_filtro-actividad').append($('<option></option>').val('').html('Todos'));
        $.each(paquete.actividades, function (key, value) {
        $('#formulario_filtro-actividad').append($('<option></option>').val(value['elemento']).html(value['actividad_descr']));
        });
    }
    });   

}
/*formulario 34000236 */
function get_turno_examen(){  

    kernel.ajax.call(info.url_turno_examen, {
    type: 'post',
    data: {
    anio: $('#formulario_filtro-anio_academico').val(),
    },
    success: function(paquete) { 
        $('#formulario_filtro-turno_examen').empty();
        if (paquete.turno.length!=0){
        $('#formulario_filtro-turno_examen').append($('<option></option>').val('').html('Todos'));
        $.each(paquete.turno, function (key, value) {
        $('#formulario_filtro-turno_examen').append($('<option></option>').val(key).html(value));
        });
        }else{$('#formulario_filtro-turno_examen').append($('<option></option>').val('').html('Todos'));
        }
    }


    });   

}
function get_campo_seleccion(){  

    kernel.ajax.call(info.url_llamado_examen, {
    type: 'post',
    data: {
    anio: $('#formulario_filtro-anio_academico').val(),
    turno_examen: $('#formulario_filtro-turno_examen').val(),
    actividad: $('#formulario_filtro-actividad').val(),
    fecha_desde: $('#formulario_filtro-fecha_desde').val(),
    fecha_hasta: $('#formulario_filtro-fecha_hasta').val(),
    },
    success: function(paquete) { 
        $('#resultado_filtro').empty();
        if (paquete.llamado_examen.length!=0){
        $('#resultado_filtro').append($('<option></option>').val('--').html('seleccione'));
        $.each(paquete.llamado_examen, function (key, value) {
        $('#resultado_filtro').append($('<option></option>').val(value['llamado_mesa']).html(value['nombre_llamado'] +'-'+ value['fecha']));
        });
        }else{$('#resultado_filtro').append($('<option></option>').val('no_hay_datos').html('No hay datos'));
        }
    }


    });   

}
/*formulario 14000079 */
function get_inscripciones_comision_alumno(){
 kernel.ajax.call(info.url_buscar_inscripciones_comision_alumno, {
    type: 'post',
    data: {
    periodo_lectivo: $('#formulario_filtro-periodo_lectivo').val()
    },
    success: function(paquete) {
        $('#formulario_filtro-actividad').empty();
        $('#formulario_filtro-actividad').append($('<option></option>').val('').html('seleccione'));
        $.each(paquete.actividad, function (key, value) {
        $('#formulario_filtro-actividad').append($('<option></option>').val(value['valor']).html(value['descr']));
        });
    }
    });
};
            


function get_comisiones_de_actividad(){  

    kernel.ajax.call(info.url_comisiones_de_actividad, {
    type: 'post',
    data: {
    actividad: $('#formulario_filtro-actividad').val(),
    anio_academico: $('#formulario_filtro-anio_academico').val()
    },
    success: function(paquete) { 
        $('#formulario_filtro-comision').empty();
        $('#formulario_filtro-comision').append($('<option></option>').val('').html('seleccione'));
        $.each(paquete.resultado, function (key, value) {
        $('#formulario_filtro-comision').append($('<option></option>').val(value['comision']).html(value['nombre']));
        });
    }
    });   

}
function get_periodo_anio(){  


    kernel.ajax.call(info.url_buscar_periodo_lectivo, {
    type: 'post',
    data: {
    anio_academico: $('#formulario_filtro-anio_academico').val()
    },
    success: function(paquete) { 
        $('#formulario_filtro-periodo_lectivo').empty();
        $('#formulario_filtro-periodo_lectivo').append($('<option></option>').val('').html('seleccione'));
        $.each(paquete.periodo_lectivo.periodos, function (key, value) {
        $('#formulario_filtro-periodo_lectivo').append($('<option></option>').val(value['periodo_lectivo']).html(value['periodo_lectivo_nombre']));
        });
    }
    });   

}
/*14000020*/
function get_campo_seleccion_actividad_aprobada(){ 

    kernel.ajax.call(info.url_seleccion_actividad_aprobada, {
    type: 'post',
    data: {
    actividad: $('#formulario_filtro-actividad').val(),
    curso: $('#formulario_filtro-curso').val(),
    docentes: $('#formulario_filtro-docentes').val(),
    fecha_desde: $('#formulario_filtro-fecha_desde').val(),
    fecha_hasta: $('#formulario_filtro-fecha_hasta').val(),
    },
    success: function(paquete) {
        $('#resultado_filtro').empty();
        if(paquete.resultado.length!=0){
            $('#resultado_filtro').append($('<option></option>').val('').html('seleccione'));
            $.each(paquete.resultado, function (key, value) {
            $('#resultado_filtro').append($('<option></option>').val(value['comision']).html(value['nombre_actividad'] +'-'+ value['nombre']+'-'+ value['fecha_inicio_dictado']+' - '+value['fecha_fin_dictado'] +' - Docentes:'+value['docentes']));
            });
        }else{
            $('#resultado_filtro').append($('<option></option>').val('no_hay_datos').html('No hay datos'));
        }
    }


    }); 
 }
/*14000079 */

function get_campo_seleccion_actividad_clases(){ 

    kernel.ajax.call(info.url_seleccion_actividad_clases, {
    type: 'post',
    data: {
    actividad: $('#formulario_filtro-actividad').val(),
    comision: $('#formulario_filtro-comision').val(),
    docentes: $('#formulario_filtro-docentes').val(),
    fecha_desde: $('#formulario_filtro-fecha_desde').val(),
    fecha_hasta: $('#formulario_filtro-fecha_hasta').val(),
    },
    success: function(paquete) { 
        $('#resultado_filtro').empty();
        if (paquete.resultado.length!=0){
        $('#resultado_filtro').append($('<option></option>').val('').html('seleccione'));
        $.each(paquete.resultado, function (key, value) {
        $('#resultado_filtro').append($('<option></option>').val(value['clase']).html(value['fecha'] +'-'+ value['dia_semana_nombre']+'-'+ value['horario']));
        });
        }else{
            $('#resultado_filtro').append($('<option></option>').val('no_hay_datos').html('No hay datos'));           
        }
    }


    }); 
 }
/* 14000017*/
function get_campo_seleccion_actividad(){ 

    kernel.ajax.call(info.url_seleccion_actividad, {
    type: 'post',
    data: {
    actividad: $('#formulario_filtro-actividad').val(),
    curso: $('#formulario_filtro-curso').val(),
    docentes: $('#formulario_filtro-docentes').val(),
    fecha_desde: $('#formulario_filtro-fecha_desde').val(),
    fecha_hasta: $('#formulario_filtro-fecha_hasta').val(),
    },
    success: function(paquete) { 
        $('#resultado_filtro').empty();
        if (paquete.resultado.length!=0){
        $('#resultado_filtro').append($('<option></option>').val('').html('seleccione'));
        $.each(paquete.resultado, function (key, value) {
        $('#resultado_filtro').append($('<option></option>').val(value['comision']).html(value['nombre_actividad'] +'-'+ value['nombre']+'-'+ value['fecha_inicio_dictado']+'-'+value['fecha_fin_dictado'] +'-'+value['docentes']));
        });
        }else{
            $('#resultado_filtro').append($('<option></option>').val('no_hay_datos').html('No hay datos'));           
        }
    }


    }); 
 }
        /* formulario 37000562*/
        function get_campo_seleccion_evaluacion_parcial(){  

            kernel.ajax.call(info.url_evaluacion_parcial, {
            type: 'post',
            data: {
            anio: $('#formulario_filtro-anio_academico').val(),
            periodo_lectivo: $('#formulario_filtro-periodo_lectivo').val(),
            actividad: $('#formulario_filtro-actividad').val(),
            comision: $('#formulario_filtro-comision').val(),
            fecha_desde: $('#formulario_filtro-fecha_desde').val(),
            fecha_hasta: $('#formulario_filtro-fecha_hasta').val(),
            },
            success: function(paquete) { 
                $('#resultado_filtro').empty();
                
                if (paquete.evaluaciones.length!=0){
                    $('#resultado_filtro').append($('<option></option>').val('').html('seleccione'));
                    $.each(paquete.evaluaciones, function (key, value) {
                    $('#resultado_filtro').append($('<option></option>').val(value['evaluacion']).html('Comisión: '+value['nombre_comision'] +' - Evaluación: '+ value['nombre']+' - Evaluación tipo: '+ value['nombre_tipo']+' - Fecha: '+ value['fecha_visual']));
                    });
                }else{
                    $('#resultado_filtro').append($('<option></option>').val('no_hay_datos').html('No hay datos'));           
            }
            }


            });   

        }
        function get_actividad_con_comisiones(){  
            kernel.ajax.call(info.url_buscar_actividad_comisiones, {
            type: 'post',
            data: {
            anio_academico: $('#formulario_filtro-anio_academico').val()
            },
            success: function(paquete) { 
                $('#formulario_filtro-actividad').empty();
                $('#formulario_filtro-actividad').append($('<option></option>').val('').html('Seleccione'));
                $.each(paquete.resultado, function (key, value) {
                $('#formulario_filtro-actividad').append($('<option></option>').val(value.elemento).html(value.actividad_descr));
                });
            kernel.ajax.call(info.url_buscar_periodo_lectivo, {
            type: 'post',
            data: {
            anio_academico: $('#formulario_filtro-anio_academico').val()
            },
            success: function(paquete) { 
                $('#formulario_filtro-periodo_lectivo').empty();
                $('#formulario_filtro-periodo_lectivo').append($('<option></option>').val('').html('Todos'));
                $.each(paquete.periodo_lectivo.periodos, function (key, value) {
                $('#formulario_filtro-periodo_lectivo').append($('<option></option>').val(value['periodo_lectivo']).html(value['periodo_lectivo_nombre']));
                });
            }
            })
            }
            });
        }

        /* formulario 14000079*/
        function get_campo_seleccion_clase(){  

            kernel.ajax.call(info.url_seleccion_clase, {
            type: 'post',
            data: {
            anio: $('#formulario_filtro-anio_academico').val(),
            periodo_lectivo: $('#formulario_filtro-periodo_lectivo').val(),
            actividad: $('#formulario_filtro-actividad').val(),
            comision: $('#formulario_filtro-comision').val(),
            fecha_desde: $('#formulario_filtro-fecha_desde').val(),
            fecha_hasta: $('#formulario_filtro-fecha_hasta').val(),
            },
            success: function(paquete) { 
                $('#resultado_filtro').empty();
                if (paquete.resultado.length!=0){
                $('#resultado_filtro').append($('<option></option>').val('').html('Todos'));
                $.each(paquete.clase, function (key, value) {
                $('#resultado_filtro').append($('<option></option>').val(value['clase']).html(value['fecha'] +' - '+ value['dia_semana_nombre']+' - '+ value['horario']));
                });
                }else{
                    $('#resultado_filtro').append($('<option></option>').val('no_hay_datos').html('No hay datos'));
                }
            }


            });   

        }
});  
