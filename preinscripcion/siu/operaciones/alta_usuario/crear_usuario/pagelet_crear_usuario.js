var url_cascada_nac;
var url_tipo_documentos;
var url_tipo_documentos_validacion;
var url_nro_tramite_dni_validacion;
var url_img_nro_tramite_dni;

var tipo_documento_dni;
var tipo_documento_dnt;
var tipo_documento_cuil_cuit;
var tipo_documento_cedula_diplomatica;
var tipo_documento_pasaporte;

var nro_documento_dni_dnt_min;
var nro_documento_dni_dnt_max;

var msj_nro_documento_dni_leyenda;
var msj_nro_documento_dni_error;
var msj_nro_documento_cuil_leyenda;
var msj_nro_documento_dni_rep;
var msj_ayuda_numero_tramite;
var msj_ayuda_documento;
var info_dni_en_tramite;

var msj_nro_tramite_dni_leyenda;
var msj_nro_tramite_dni_error;
var msj_ws_renaper_error;
var msj_Exito;

var mat_validacion_renaper;

var tipo_documento;
var no_seleccion = 'null';
var errores_formulario = false;

var div_apellido_nombre;
var dt_apellido;
var dt_nombre;

function set_solo_lectura(elemId, solo_lectura)
{
	elemId = '#' + elemId;
	if (solo_lectura) {
		$(elemId).attr('readonly', true);
	} else {
		$(elemId).attr('readonly', false);
	}
}

function set_val(elemId, iniciar, valor)
{
	valor = typeof valor !== 'undefined' ? valor : '';
    
	elemId = '#' + elemId;
	if (iniciar) {
		$(elemId).val('');
	} else {
		$(elemId).val(valor);
                $(elemId).value = valor;
	}
}

function inicializar_nacionalidad()
{
	var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
	var pais_documento = $('#form_crear_usuario-pais_documento').val();

	if (nacionalidad === no_seleccion) {
		set_solo_lectura('form_crear_usuario-pais_documento', true);
		$('#form_crear_usuario-pais_documento').children().remove();
	} else {
		buscar_paises(nacionalidad);
		set_solo_lectura('form_crear_usuario-pais_documento', false);
		if (nacionalidad !== '2') {
			set_solo_lectura('form_crear_usuario-tipo_documento', false);
		} else {
			set_solo_lectura('form_crear_usuario-tipo_documento', true);
		}
	}
	
	$('#form_crear_usuario-pais_documento').val(pais_documento);
}

function cambio_nacionalidad()
{
	var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
        cambio_nro_tramite_dni();
	if (nacionalidad === no_seleccion) {
		set_solo_lectura('form_crear_usuario-pais_documento', true);
		$('#form_crear_usuario-pais_documento').children().remove();
	} else {
		buscar_paises(nacionalidad);
		set_solo_lectura('form_crear_usuario-pais_documento', false);
		if (nacionalidad !== '2') {
			set_solo_lectura('form_crear_usuario-tipo_documento', false);
		} else {
			set_solo_lectura('form_crear_usuario-tipo_documento', true);
		}
	}
	cambio_pais_documento();
}

function buscar_paises(_nacionalidad)
{
	$.ajax({
		url: url_cascada_nac,
		dataType: 'json',
		async: false,
		data: {nacionalidad: _nacionalidad},
		type: 'post',
		success: function (data)
		{
			$('#form_crear_usuario-pais_documento').children().remove();
			$.each(data, function (key, value)
			{
				$('#form_crear_usuario-pais_documento').append(
						$('<option></option>').val(key).html(value)
						);
			});
			ordenar_combo('form_crear_usuario-pais_documento');
		}
	});
}

function buscar_tipo_documento_paises(_pais_documento)
{
    
	$.ajax({
		url:url_tipo_documentos,
		dataType: 'json',
		async: false,
		data: {pais: _pais_documento},
		type: 'post',
		success: function (data)
		{
			$('#form_crear_usuario-tipo_documento').children().remove();
			$.each(data, function (key, value)
			{
				$('#form_crear_usuario-tipo_documento').append(
						$('<option></option>').val(key).html(value)
						);
			});
			ordenar_combo('form_crear_usuario-tipo_documento');
                        $("#form_crear_usuario-tipo_documento").val(tipo_documento);
		}
	});
}
function cambio_pais_documento()
{
	var valor = $('#form_crear_usuario-pais_documento').val();
       if(mat_validacion_renaper === 'S'){
         cambio_nro_tramite_dni();
        pos_validar_nro_tramite_dni();}
	if ((valor === no_seleccion) || (valor === null)) {
		set_solo_lectura('form_crear_usuario-tipo_documento', true);
		$('#form_crear_usuario-tipo_documento').val(no_seleccion);
	} else {
		set_solo_lectura('form_crear_usuario-tipo_documento', false);
                buscar_tipo_documento_paises(valor);
	}
	cambio_tipo_documento();
}

function cambio_tipo_documento()
{
	var valor = $('#form_crear_usuario-tipo_documento').val();
	var pais = $('#form_crear_usuario-pais_documento').val();
        //paises miembros del mercosur
        var paises_mercosur =['54','55','595','591','593','57','598','592','51','597'];
        if(mat_validacion_renaper === 'S'){
        cambio_nro_tramite_dni();
        pos_validar_nro_tramite_dni();}
	// Activación / desactivación de campo Nro. documento
	if (valor === null) {
		set_solo_lectura('form_crear_usuario-nro_documento', true);
		set_solo_lectura('form_crear_usuario-nro_documento_rep', true);
	} else {
		set_solo_lectura('form_crear_usuario-nro_documento', false);
		set_solo_lectura('form_crear_usuario-nro_documento_rep', false); 
	}
	
	switch (valor) {
	    // Si el tipo de documento es DNI
	    case tipo_documento_dni:
		$("#js_nro_documento").html(msj_nro_documento_dni_leyenda).slideDown("slow");
		var tooltip_info_dni_tramite = "<p id='info_dni_en_tramite'><i class='icon-info-sign' class='control-group' data-toggle='tooltip' data-placement='top' data-original-title='"+info_dni_en_tramite+"'></i></p>";
		var input = $('#form_crear_usuario-nro_tramite_dni');
		input = input.after(tooltip_info_dni_tramite);
		$('.icon-info-sign').tooltip('show');
		
		var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
		if(nacionalidad !== null && nacionalidad === tipo_documento_dnt){
			$("#js_renaper").slideDown("slow");
		}else{
			$("#js_renaper").slideUp("slow");
			if (paises_mercosur.includes(pais)===false){
				$('#js_nro_documento').html(msj_ayuda_documento).slideDown('slow');
			}
		}
                
		break;
	    // Si el tipo de documento es CUIL/CUIT
	    case tipo_documento_cuil_cuit:
		$("#js_nro_documento").html(msj_nro_documento_cuil_leyenda).slideDown("slow");
                $("#js_renaper").slideUp("slow");
		break;
	    default:
		$("#js_nro_documento").slideUp("slow");
                $("#js_renaper").slideUp("slow");
                break;
	}
}

function cambio_nro_tramite_dni()
{
    if(mat_validacion_renaper === 'S'){
        
        var tipo_doc = $('#form_crear_usuario-tipo_documento').val();
        var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
        if(nacionalidad === tipo_documento_dnt){
            if (tipo_doc === null) {
                    set_solo_lectura('form_crear_usuario-nro_tramite_dni', true);
            } else {            
                if(tipo_doc === tipo_documento_dni){
                    set_solo_lectura('form_crear_usuario-nro_tramite_dni', false);
                }else{
                    set_solo_lectura('form_crear_usuario-nro_tramite_dni', true);                
                    $("#js_nro_tramite_dni").slideUp("slow");

                }
            }
        }else{
            $("#js_renaper").slideUp("slow");
        }
        
    }
}

function validar_documento(_pais_documento, _tipo_documento, nro_documento)
{
   
	$.ajax({
		url: url_tipo_documentos_validacion,
		dataType: 'json',
		async: false,
		data: {tipo_documento: _tipo_documento, pais: _pais_documento, nro_documento: nro_documento},
		type: 'post',
		success: function (data)
		{
			errores_formulario = false;
			var exp_reg = new RegExp(data.exp_regular_validacion);
                        var control_group = $('#form_crear_usuario-nro_documento').parents('.control-group');
			$(control_group).removeClass('error');
			var span = $(control_group).find('span.help-inline');
			$(span).remove();
			if (!exp_reg.test(nro_documento)) {
				$(control_group).addClass('error');
				$(control_group).append('<span class="help-inline">' + data.exp_regular_mensaje + '</span>');
				errores_formulario = true;
			} else {
			    
			    // Validaciones extras
			    switch (_tipo_documento) {
				// Si el tipo de documento es DNI
				case tipo_documento_dni:
				    // Valida que el DNI sea mayor a 1 millón y menor a 100 millones.
				    if ((parseInt(nro_documento) < parseInt(nro_documento_dni_dnt_min)) || (parseInt(nro_documento) > parseInt(nro_documento_dni_dnt_max))) {
					$(control_group).addClass('error');
					$(control_group).append('<span class="help-inline">' + msj_nro_documento_dni_error + '</span>');
					errores_formulario = true;
				    }
                                    var nro_documento_rep = $('#form_crear_usuario-nro_documento_rep').val();
                                    if (nro_documento_rep && nro_documento_rep !== nro_documento) {
					$(control_group).addClass('error');
					$(control_group).append('<span class="help-inline">' + msj_nro_documento_dni_rep + '</span>');
					errores_formulario = true;
				    }

				break;
				// Si el tipo de documento es CUIL/CUIT
				case tipo_documento_cuil_cuit:
				    // Valida el CUIL/CUIT.
				    if (data.mensaje_validacion_cuil) {
					$(control_group).addClass('error');
					$(control_group).append('<span class="help-inline">' + data.mensaje_validacion_cuil + '</span>');
					errores_formulario = true;
				    }

				break;
			    }
			}
	    }
	});
    
}

function validar_nro_tramite_dni()
{
    var _pais_documento = $('#form_crear_usuario-pais_documento').val(); 
    var _tipo_documento = $('#form_crear_usuario-tipo_documento').val(); 
    var nro_documento   = $('#form_crear_usuario-nro_documento').val(); 
    var nro_tramite_dni = $('#form_crear_usuario-nro_tramite_dni').val();
    var genero          = $('#form_crear_usuario-sexo').val();
    if(_pais_documento && _tipo_documento && nro_documento && nro_tramite_dni && genero){
        $.ajax({
		url: url_nro_tramite_dni_validacion,
		dataType: 'json',
		async: false,
		data: {nro_documento: nro_documento, nro_tramite_dni : nro_tramite_dni ,genero: genero},
		type: 'post',
		success: function (data)
		{
                    var objeto = '';
                    errores_formulario = false;
                    if(data !== null && data !== ''){
                        objeto = JSON.parse(data);
                        if(objeto['message'] === msj_Exito && objeto['person'] !== ""){
                            //Seteo los valores devueltos por renaper
                            errores_formulario = false;
                            pos_validar_nro_tramite_dni(objeto['person']);                        
                        }else{
                            //Si la persona no se encuentra en RENAPER muestra mensaje de error
                            var control_group = $('#form_crear_usuario-nro_tramite_dni').parents('.control-group');
                            $(control_group).removeClass('error');
                            var span = $(control_group).find('span.help-inline');
                            $(span).remove();
                            $(control_group).addClass('error');
                            $(control_group).append('<span class="help-inline">' + msj_ws_renaper_error + '</span>');
                        }
                    }else{
                        //Si la persona no se encuentra en RENAPER muestra mensaje de error
                        var control_group = $('#form_crear_usuario-nro_tramite_dni').parents('.control-group');
                        $(control_group).removeClass('error');
                        var span = $(control_group).find('span.help-inline');
                        $(span).remove();
                        $(control_group).addClass('error');
                        $(control_group).append('<span class="help-inline">' + msj_ws_renaper_error + '</span>');
                        
                        //Los vuelvo al valor inicial, todos vacios
                        pos_validar_nro_tramite_dni(objeto, false);                       
                        errores_formulario = true;
                    }
                    
                }
	});
    }
}
/*
 * Una vez completado el campo del nro de tramite asigna con los datos del renaper los siguientes inputs
 * Si no encuentra la persona los vuelve a iniciarlizar, dejando en blanco los campos siguientes
 */
function pos_validar_nro_tramite_dni(persona, setear_valor)
{   
    
    persona = typeof persona !== 'undefined' ? persona : '';
    setear_valor = typeof setear_valor !== 'undefined' ? setear_valor : true;
    
    if (setear_valor && persona !== ''){
        //Apellidos
        persona = JSON.parse(persona);
                
        $("#apellido_nombre").children().remove();
        
        $("#apellido_nombre").append(
            $('<div/>')
              .attr("id", "div_apellido")
              .addClass("control-group span6")
              .append(dt_apellido)
                .append("<p>"+persona['lastNames']+"</p>")
          );
  
          $("#apellido_nombre").append(
            $('<div/>')
              .attr("id", "div_nombres")
              .addClass("control-group span6")
              .append(dt_nombre)
                .append("<p>"+persona['names']+"</p>")
          );
        //No permito que se cambie el numero de tramite
        set_solo_lectura('form_crear_usuario-nro_tramite_dni', true);
        set_val('form_crear_usuario-validado_con_renaper', false, mat_validacion_renaper);
        set_solo_lectura('form_crear_usuario-apellido', true);
        set_val('form_crear_usuario-apellido',false,persona['lastNames']);
        //Nombres
        set_solo_lectura('form_crear_usuario-nombres', true);
        set_val('form_crear_usuario-nombres',false,persona['names']);

        //los inputs ocultos asigno datos de renaper
        set_val('form_crear_usuario-fecha_nacimiento',false,persona['birthdate']);
        set_val('form_crear_usuario-calle_proc',false,persona['streetAddress']);
        set_val('form_crear_usuario-numero_proc',false,persona['numberStreet']);
        set_val('form_crear_usuario-piso_proc',false,persona['floor']);
        set_val('form_crear_usuario-dpto_proc',false,persona['department']);
        set_val('form_crear_usuario-cp_proc',false,persona['zipCode']);
        set_val('form_crear_usuario-barrio_proc',false,persona['city']);
        set_val('form_crear_usuario-numero_cuil',false,persona['cuil']);
        $('#validar').prop('disabled',true);
        
    } else {
        $("#apellido_nombre").children().remove();
        $("#apellido_nombre").append(div_apellido_nombre);
        //No permito que se cambie el numero de tramite
        set_solo_lectura('form_crear_usuario-nro_tramite_dni', false);
        
        //serializo y vuelvo a los valores iniciales del formulario, para ser cargados
        set_solo_lectura('form_crear_usuario-apellido', false);
        set_val('form_crear_usuario-apellido',true);
        //Nombres
        set_solo_lectura('form_crear_usuario-nombres', false);
        set_val('form_crear_usuario-nombres',true);

        //los inputs ocultos
        set_val('form_crear_usuario-fecha_nacimiento',true);
        set_val('form_crear_usuario-calle_proc',true);
        set_val('form_crear_usuario-numero_proc',true);
        set_val('form_crear_usuario-piso_proc',true);
        set_val('form_crear_usuario-dpto_proc',true);
        set_val('form_crear_usuario-cp_proc',true);
        set_val('form_crear_usuario-barrio_proc',true);
        set_val('form_crear_usuario-validado_con_renaper', false, 'N');
        $('#validar').removeAttr("disabled");
        
    }
    
    
}

kernel.renderer.registrar_pagelet('crear_usuario', function (info)
{
	var id = '#' + info.id;
	url_cascada_nac = info.url_paises;
	url_tipo_documentos = info.url_tipo_documentos;
	url_tipo_documentos_validacion = info.url_tipo_documentos_validacion;
        url_nro_tramite_dni_validacion = info.url_nro_tramite_dni_validacion;
        url_img_nro_tramite_dni = info.url_img_nro_tramite_dni;
        mat_validacion_renaper  = info.mat_validacion_renaper;
        tipo_documento = info.tipo_documento;
        msj_Exito = info.msj_Exito;
	
	// Tipos de documentos
	tipo_documento_dni = info.tipo_documento_dni;
	tipo_documento_dnt = info.tipo_documento_dnt;
	tipo_documento_cuil_cuit = info.tipo_documento_cuil_cuit;
	tipo_documento_cedula_diplomatica = info.tipo_documento_cedula_diplomatica;
	tipo_documento_pasaporte = info.tipo_documento_pasaporte;
	
	// Máximos y mínimos de números de documento
	nro_documento_dni_dnt_min = info.nro_documento_dni_dnt_min;
	nro_documento_dni_dnt_max = info.nro_documento_dni_dnt_max;
	
	// Mensajes
        msj_nro_documento_dni_leyenda = info.mensajes.msj_nro_documento_dni_leyenda;
        msj_nro_documento_dni_error = info.mensajes.msj_nro_documento_dni_error;
        msj_nro_documento_cuil_leyenda = info.mensajes.msj_nro_documento_cuil_leyenda;
        msj_nro_documento_dni_rep = info.mensajes.msj_nro_documento_dni_rep;
        
        msj_nro_tramite_dni_leyenda = info.mensajes.msj_nro_tramite_dni_leyenda;
        msj_nro_tramite_dni_error = info.mensajes.msj_nro_tramite_dni_error;
        msj_ws_renaper_error = info.mensajes.msj_ws_renaper_error;
        msj_ayuda_numero_tramite = info.mensajes.msj_ayuda_numero_tramite;
        msj_ayuda_documento = info.mensajes.msj_ayuda_documento;
        info_dni_en_tramite = info.mensajes.info_dni_en_tramite;
        dt_apellido = $("#form_crear_usuario-apellido-label").clone();
        dt_nombre = $("#form_crear_usuario-nombres-label").clone();
        
        div_apellido = $("#div_apellido").clone();
        div_nombre = $("#div_nombres").clone();
        //---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults( $.datepicker.regional["es"] );
			$(id).find('input.date').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay: 1,
				yearRange: "-150:+0",
                                maxDate: "-15y"
			});
	div_apellido_nombre = $('#apellido_nombre').clone();//Lo uso para crear nuevamente el div cuando se elimina
	
return {
		onload: function ()
		{ 

                    var control_group = $('#form_crear_usuario-respuesta_secreta').parents('.control-group');
                    $(control_group).append('<i data-id-input="form_crear_usuario-respuesta_secreta" class="icon-eye-close js-cic-icon"></i>');

                    var control_group = $('#form_crear_usuario-password').parents('.control-group');
                    $(control_group).append('<i data-id-input="form_crear_usuario-password" class="icon-eye-close js-cic-icon"></i>');

                    var control_group = $('#form_crear_usuario-rpassword').parents('.control-group');
                    $(control_group).append('<i data-id-input="form_crear_usuario-rpassword" class="icon-eye-close js-cic-icon"></i>');
                    $(id).on("click", ".js-cic-icon", function (e) {

                         var id_input = $(this).attr("data-id-input")

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
			$('#form_crear_usuario').submit(function ()
			{
				if (errores_formulario) {
					kernel.ui.show_mensaje('Error en el formulario', {tipo: 'alert-error'});
					return false;
				}
			});
                        
			inicializar_nacionalidad();
			cambio_pais_documento();
                        
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_crear_usuario-nacionalidad').on('change', function ()
			{
				cambio_nacionalidad();
			});
			$('#form_crear_usuario-pais_documento').on('change', function ()
			{
				cambio_pais_documento();
			});
			$('#form_crear_usuario-tipo_documento').on('change', function ()
			{
				cambio_tipo_documento();
			});
			$('#form_crear_usuario-nro_documento').on('change', function ()
			{
				validar_documento($('#form_crear_usuario-pais_documento').val(), $('#form_crear_usuario-tipo_documento').val(), $('#form_crear_usuario-nro_documento').val());
			}
			);
                
                        $('#form_crear_usuario-nro_tramite_dni').on('change', function ()
			{
                            var length = $('#form_crear_usuario-nro_tramite_dni').val().length;
                            var valor = $('#form_crear_usuario-nro_tramite_dni').val();
                            var control_group = $('#form_crear_usuario-nro_tramite_dni').parents('.control-group');
                            var regex = new RegExp(info.regexp_nro_tramite_dni,'i'); 
                            
                            $(control_group).removeClass('error');
                            var span = $(control_group).find('span.help-inline');
                            $(span).remove();
                            if(length !== 11 || regex.test(valor) === false){
                                $(control_group).addClass('error');
                                $(control_group).append('<span class="help-inline">' + msj_nro_tramite_dni_error + '</span>');
                                pos_validar_nro_tramite_dni();
                            }
			}
			);
                
                        $('#form_crear_usuario-nro_documento_rep').on('change', function ()
			{
                            var nro_documento = $('#form_crear_usuario-nro_documento').val();
                            var nro_documento_rep = $('#form_crear_usuario-nro_documento_rep').val();
                            if (nro_documento_rep && nro_documento && nro_documento_rep !== nro_documento) {
                                var control_group = $('#form_crear_usuario-nro_documento').parents('.control-group');
                                $(control_group).removeClass('error');
                                var span = $(control_group).find('span.help-inline');
                                $(control_group).addClass('error');
                                $(control_group).append('<span class="help-inline">' + msj_nro_documento_dni_rep + '</span>');
                                errores_formulario = true;
                            }
			}
			);
                
			//------------------------------------------------------------------

			$('#frameRC').contents().on('keypress', function (e)
			{
				if (e.which === 13) {
					$('#form_crear_usuario').submit();
				}
			});
                        $('#form_crear_usuario-telefono').attr('autocomplete', 'off');
                        $('#form_crear_usuario-respuesta_secreta').attr('autocomplete', 'off');
                        $('#form_crear_usuario-usuario').attr('autocomplete', 'off');
                        $('#form_crear_usuario-usuario_rep').attr('autocomplete', 'off');
                        $('#form_crear_usuario-password').attr('autocomplete', 'off');
                        $('#form_crear_usuario-rpassword').attr('autocomplete', 'off');
			$('#form_crear_usuario-usuario_rep').on('paste', function ()
			{
				return false;
			});
                        
                        //--------docuemntorepetir--------------------------------
                        $('#form_crear_usuario-nro_documento_rep').on('paste', function ()
			{
				return false;
			});
			$('#form_crear_usuario-nro_documento_rep').attr('autocomplete', 'off');
                        $('#ayuda_numero_tramite').popover({
                            trigger: 'click',
                            html: true,
                            content: function(){ 
                               return '<img src="' + $(this).data('img') + '"/>'; 
                            }
                         });
		}
	};
});
