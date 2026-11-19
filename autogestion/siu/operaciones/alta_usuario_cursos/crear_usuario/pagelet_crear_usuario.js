var url_cascada_nac;
var url_tipo_documentos;
var no_seleccion = 'null';
var errores_formulario = false;
var url_tipo_documentos_validacion;
var identidad_genero_especifique;

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
var msj_tipo_documento_ayuda;
var paises_mercosur;

var url_nro_tramite_dni_validacion;
var url_sugerencia_user;
var url_validar_user;

var mat_validacion_renaper;

var div_apellido_nombre;
var dt_apellido;
var dt_nombre;

var msj_Exito;
var msj_no_coincide;

function set_solo_lectura(elemId, solo_lectura)
{
    if (solo_lectura) {
        $('#' + elemId).attr('readonly', 'readonly');
    } else {
        $('#' + elemId).removeAttr('readonly');
    }
}

function set_valor(elem, iniciar, id_string)
{
	elem = '#' + elem;
	if (iniciar) 
		$(elem).val('');
	else {
		$(elem).val(id_string);
        $(elem).value = id_string;
	}
}

function inicializar_nacionalidad()
{
    var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
    var pais_documento = $('#form_crear_usuario-pais_documento').val();
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
    
    $('#form_crear_usuario-pais_documento').val(pais_documento);
}

function cambio_nacionalidad()
{
    var nacionalidad = $('#form_crear_usuario-nacionalidad').val();

    $('#form_crear_usuario-tipo_documento').children().remove();
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
        success: function (data) {
            $('#form_crear_usuario-pais_documento').children().remove();
            $.each(data, function (key, value) {
                $('#form_crear_usuario-pais_documento').append(
                        $('<option></option>').val(key).html(value)
                        );
            });
            ordenar_combo('form_crear_usuario-pais_documento');
        }
    });
}
function buscar_tipo_documento_pais(_pais)
{
    $.ajax({
        url: url_tipo_documentos,
        dataType: 'json',
        async: false,
        data: {pais: _pais},
        type: 'post',
        success: function (data) {
            $('#form_crear_usuario-tipo_documento').children().remove();
            $.each(data, function (key, value) {
                $('#form_crear_usuario-tipo_documento').append(
                        $('<option></option>').val(key).html(value)
                        );
            });
            ordenar_combo('form_crear_usuario-tipo_documento');
        }
    });
}
function cambio_pais_documento()
{
    var valor = $('#form_crear_usuario-pais_documento').val();
    cambio_nro_tramite_dni();
    if ((valor === no_seleccion) || (valor === null)) {
        set_solo_lectura('form_crear_usuario-tipo_documento', true);
        $('#form_crear_usuario-tipo_documento').val(no_seleccion);
    } else {
        set_solo_lectura('form_crear_usuario-tipo_documento', false);
    }
    cambio_tipo_documento();
}

function cambio_tipo_documento()
{
    var pais =  $('#form_crear_usuario-pais_documento').val();
    var valor = $('#form_crear_usuario-tipo_documento').val();
    cambio_nro_tramite_dni();
    pos_validar_nro_tramite_dni();
    if (pais !== no_seleccion) {
	buscar_tipo_documento_pais(pais);
	set_solo_lectura('form_crear_usuario-tipo_documento', false);
    }
    if  ((valor === no_seleccion) || (valor === null)) {
        set_solo_lectura('form_crear_usuario-nro_documento', true);
        
        $('#form_crear_usuario-tipo_documento').val(no_seleccion);
        $('#form_crear_usuario-nro_documento').val('');
      
    } else {
        $('#form_crear_usuario-tipo_documento').val(valor);
        set_solo_lectura('form_crear_usuario-nro_documento', false);
    }
    
    switch (valor) {
	// Si el tipo de documento es DNI
	case tipo_documento_dni:
            $('#js_nro_documento').html(msj_nro_documento_dni_leyenda).slideDown('slow');
            var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
            if(nacionalidad !== null && nacionalidad === tipo_documento_dnt){
                $('#js_renaper').slideDown('slow');
            }else{
                $('#js_renaper').slideUp('slow');
            if (paises_mercosur.includes(pais)===false){
                $('#js_nro_documento').html(msj_tipo_documento_ayuda).slideDown('slow');
            }
            }
	    break;
	// Si el tipo de documento es CUIL/CUIT
	case tipo_documento_cuil_cuit:
	    $('#js_nro_documento').html(msj_nro_documento_cuil_leyenda).slideDown('slow');
            $('#js_renaper').slideUp('slow');
	    break;
	default:
	    $('#js_nro_documento').slideUp('slow');
            $('#js_renaper').slideUp('slow');
    }
}

function validar_documento(_tipo_documento, nro_documento, _pais)
{
    $.ajax({
        url: url_tipo_documentos_validacion,
        dataType: 'json',
        async: false,
        data: {tipo_documento: _tipo_documento, pais: _pais, nro_documento: nro_documento},
        type: 'post',
        success: function (data) {
            errores_formulario = false;
            var exp_reg = new RegExp(data.exp_regular);
            var control_group = $('#form_crear_usuario-nro_documento').parents('.control-group');
            $(control_group).removeClass('error');
            var span = $(control_group).find('span.help-inline');
            $(span).remove();
            if (!exp_reg.test(nro_documento)) {
                $(control_group).addClass('error');
                $(control_group).append('<span class="help-inline">' + data.mensaje + '</span>')
                errores_formulario = true;
            } else {
			    
				// Validaciones extras
				switch (_tipo_documento) {
						// Si el tipo de documento es DNI
						case tipo_documento_dni:
							// Valida que el DNI sea mayor a 1 millón y menor a 100 millones.
							if ((parseInt(nro_documento) < parseInt(nro_documento_dni_dnt_min)) || (parseInt(nro_documento) > parseInt(nro_documento_dni_dnt_max))) {
									$(control_group).addClass('error');
									$(control_group).append('<span class="help-inline">' + msj_nro_documento_dni_error + '</span>')
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

function cambio_usuario(_usuario){

    $.ajax({
        url: url_validar_user,
        dataType: 'json',
        async: false,
        data: {usuario: _usuario},
        type: 'post',
        success: function (data) {
            errores_formulario = false;
            var exp_reg = new RegExp(data.exp_regular);
            var control_group = $('#form_crear_usuario-usuario').parents('.control-group');
            $(control_group).removeClass('error');
            var span = $(control_group).find('span.help-inline');
            $(span).remove();
            if (exp_reg.test(_usuario)) {
                $(control_group).addClass('error');
                $(control_group).append('<span class="help-inline">' + data.mensaje + '</span>')
                errores_formulario = true;
            } else {
			    errores_formulario = false;
	        }
        }
    });
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
                    $('#js_nro_tramite_dni').slideUp('slow');

                }
            }
        }else{
            $('#js_renaper').slideUp('slow');
        }

    }
}
/*
 * Una vez completado el campo del nro de tramite, asignamos con los datos del renaper los siguientes inputs
 * Si no encuentra la persona los vuelve a inicializar, dejando en blanco los campos siguientes
 */
function pos_validar_nro_tramite_dni(persona)
{  
    //Se hace de esta manera por yuicompressor detecta como error si le paso parametros por default
    if (typeof persona != "undefined"){

        //Apellidos
        persona = JSON.parse(persona);

        $('#apellido_nombre').children().remove();

        $('#apellido_nombre').append(
            $('<div/>')
              .attr('id', 'div_apellido')
              .addClass('control-group span6')
              .append(dt_apellido)
                .append('<p>'+persona['lastNames']+'</p>')
          );

          $('#apellido_nombre').append(
            $('<div/>')
              .attr('id', 'div_nombres')
              .addClass('control-group span6')
              .append(dt_nombre)
                .append('<p>'+persona['names']+'</p>')
          );
        //No permito que se cambie el numero de tramite
        set_solo_lectura('form_crear_usuario-nro_tramite_dni', true);
        set_valor('form_crear_usuario-validado_con_renaper', false, mat_validacion_renaper);
        set_solo_lectura('form_crear_usuario-apellido', true);
        set_valor('form_crear_usuario-apellido',false,persona['lastNames']);
        //Nombres
        set_solo_lectura('form_crear_usuario-nombres', true);
        set_valor('form_crear_usuario-nombres',false,persona['names']);

        //los inputs ocultos asigno datos de renaper
        set_valor('form_crear_usuario-fecha_nacimiento',false,persona['birthdate']);
        set_solo_lectura('form_crear_usuario-fecha_nacimiento', true);
        set_valor('form_crear_usuario-periodo_lectivo_calle',false,persona['streetAddress']);
        set_valor('form_crear_usuario-periodo_lectivo_numero',false,persona['numberStreet']);
        set_valor('form_crear_usuario-periodo_lectivo_piso',false,persona['floor']);
        set_valor('form_crear_usuario-periodo_lectivo_departamento',false,persona['department']);
        set_valor('form_crear_usuario-periodo_lectivo_codigo_postal',false,persona['zipCode']);
        set_valor('form_crear_usuario-periodo_lectivo_barrio',false,persona['city']);
        set_valor('form_crear_usuario-numero_cuil',false,persona['cuil']);
        $('#validar').prop('disabled',true);

    } else {
        //para verificar si la persona fue validada por renaper. 
        //para solo borrar los datos los casos que sean datos del renaper
        if ((typeof $('#form_crear_usuario-validado_con_renaper').val() !== "undefined") && ($('#form_crear_usuario-validado_con_renaper').val()=='S' )){
        //No permito que se cambie el numero de tramite
        set_solo_lectura('form_crear_usuario-nro_tramite_dni', false);

        //serializo y vuelvo a los valores iniciales del formulario, para ser cargados
        set_solo_lectura('form_crear_usuario-apellido', false);
        set_valor('form_crear_usuario-apellido',true,'');
        //Nombres
        set_solo_lectura('form_crear_usuario-nombres', false);
        set_valor('form_crear_usuario-nombres',true,'');
        
        $('#apellido_nombre').children().remove();
        $('#apellido_nombre').append(div_apellido_nombre);

        //los inputs ocultos 

        set_valor('form_crear_usuario-fecha_nacimiento',true,'');
        set_solo_lectura('form_crear_usuario-fecha_nacimiento', false);
        set_valor('form_crear_usuario-periodo_lectivo_calle',true,'');
        set_valor('form_crear_usuario-periodo_lectivo_numero',true,'');
        set_valor('form_crear_usuario-periodo_lectivo_piso',true,'');
        set_valor('form_crear_usuario-periodo_lectivo_departamento',true,'');
        set_valor('form_crear_usuario-periodo_lectivo_codigo_postal',true,'');
        set_valor('form_crear_usuario-periodo_lectivo_barrio',true,'');
        set_valor('form_crear_usuario-numero_cuil',true,'');
        set_valor('form_crear_usuario-validado_con_renaper', false, 'N');
        $('#validar').removeAttr('disabled');
        }
    }


}

function validar_nro_tramite_dni()
{
    var pais_documento = $('#form_crear_usuario-pais_documento').val(); 
    var tipo_documento = $('#form_crear_usuario-tipo_documento').val(); 
    var nro_documento   = $('#form_crear_usuario-nro_documento').val(); 
    var nro_tramite_dni = $('#form_crear_usuario-nro_tramite_dni').val();
    var genero          = $('#form_crear_usuario-sexo').val();
    if(pais_documento && tipo_documento && nro_documento && nro_tramite_dni && genero){
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
                        if(objeto['message'] === msj_Exito && objeto['person'] !== ''){
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
                        pos_validar_nro_tramite_dni();                       
                        errores_formulario = true;
                    }

                }
        });
    }
}

/**
     * Cambio del campo "especifique"
     */
 function cambio__identidad_genero() {
    if ($('#form_crear_usuario-identidad_genero').val() != identidad_genero_especifique) {
        ocultar('form_crear_usuario-identidad_genero_otro', true);
        ocultar('form_crear_usuario-identidad_genero_otro-label', true);
    } else {
        mostrar('form_crear_usuario-identidad_genero_otro');
        mostrar('form_crear_usuario-identidad_genero_otro-label');
        $('#form_crear_usuario-identidad_genero_otro').focus();

    }
}

function validar_repeticion(control_group, campo, campo_rep)
{                      
    if (campo_rep && campo_rep !== campo) {
	$(control_group).addClass('error');
        $(control_group).find('#error').remove();
        $(control_group).append('<span id="error" class="help-inline">' + msj_no_coincide + '</span>');
	errores_formulario = true;
    }else{ 

        $(control_group).find('#error').remove();
        $(control_group).removeClass('error');
        errores_formulario = false;}
}
kernel.renderer.registrar_pagelet('crear_usuario', function (info) {
    var id = '#' + info.id;
    url_cascada_nac = info.url_paises;
    url_tipo_documentos = info.url_tipo_documentos;
    url_tipo_documentos_validacion = info.url_tipo_documentos_validacion;
    url_nro_tramite_dni_validacion = info.url_nro_tramite_dni_validacion;
    url_sugerencia_user            = info.url_sugerencia_user;
    url_validar_user               = info.url_validar_user;
    
    // Tipos de documentos
    tipo_documento_dni = info.tipo_documento_dni;
    tipo_documento_dnt = info.tipo_documento_dnt;
    tipo_documento_cuil_cuit = info.tipo_documento_cuil_cuit;
    tipo_documento_cedula_diplomatica = info.tipo_documento_cedula_diplomatica;
    tipo_documento_pasaporte = info.tipo_documento_pasaporte;
    
    // Máximos y mínimos de números de documento
    nro_documento_dni_dnt_min = info.nro_documento_dni_dnt_min;
    nro_documento_dni_dnt_max = info.nro_documento_dni_dnt_max;
    //paises miembros del mercosur
    paises_mercosur =['54','55','595','591','593','57','598','592','51','597'];
    
    // Mensajes
    msj_nro_documento_dni_leyenda = info.mensajes.msj_nro_documento_dni_leyenda;
    msj_nro_documento_dni_error = info.mensajes.msj_nro_documento_dni_error;
    msj_nro_documento_cuil_leyenda = info.mensajes.msj_nro_documento_cuil_leyenda;
    msj_tipo_documento_ayuda = info.mensajes.msj_tipo_documento_ayuda;
    
    msj_nro_tramite_dni_leyenda = info.mensajes.msj_nro_tramite_dni_leyenda;
    msj_nro_tramite_dni_error = info.mensajes.msj_nro_tramite_dni_error;
    msj_ws_renaper_error = info.mensajes.msj_ws_renaper_error;
    msj_ayuda_numero_tramite = info.mensajes.msj_ayuda_numero_tramite;
    
    mat_validacion_renaper  = info.mat_validacion_renaper;
     
    msj_no_coincide  = info.mensajes.msj_no_coincide;

    identidad_genero_especifique = info.identidad_genero_especifique;
    
    dt_apellido = $('#form_crear_usuario-apellido-label').clone();
    dt_nombre = $('#form_crear_usuario-nombres-label').clone();

    div_apellido = $('#div_apellido').clone();
    div_nombre = $('#div_nombres').clone();

    div_apellido_nombre = $('#apellido_nombre').clone();//Lo uso para crear nuevamente el div cuando se elimina
    
    msj_Exito = info.msj_Exito;
    
    return {

        onload: function () {

            //---- Date Picker -------------------------------------------------
            $.datepicker.setDefaults($.datepicker.regional['es']);
            $(id).find('input.date').datepicker({
                changeMonth: true,
                changeYear: true,
                firstDay: 0,
                yearRange: '-90:+0'
            });

            //----arreglo jquery para mostrar ayuda 
            $('#form_crear_usuario-identidad_genero-label').html($('#form_crear_usuario-identidad_genero-label').text() + $('#ayuda_genero').html());
            $('#ayuda_genero').hide();
            //----arreglo jquery para mostrar leyenda ley genero
            $('#form_crear_usuario-nombres_elegido-label').after($("#leyenda").text());
            $("#leyenda").hide();
            //---- Inicializaciones dependencias -------------------------------
            inicializar_nacionalidad();
            cambio_pais_documento();
            cambio__identidad_genero();

            //---- Manejo de visibilidad de secciones por eventos --------------
            $('#form_crear_usuario-nacionalidad').on('change', function () {
                cambio_nacionalidad();
            });
            $('#form_crear_usuario-pais_documento').on('change', function () {
                cambio_pais_documento();
            });
            $('#form_crear_usuario-tipo_documento').on('change', function () {
                cambio_tipo_documento();
            });
            $('#form_crear_usuario-rpassword').on('change', function () {
                var control_group = $('#form_crear_usuario-rpassword').parents('.control-group');
      
                validar_repeticion(control_group,$('#form_crear_usuario-password').val(),$('#form_crear_usuario-rpassword').val())
           
            });
            $('#form_crear_usuario-password').on('change', function () {
                var control_group = $('#form_crear_usuario-rpassword').parents('.control-group');
      
                validar_repeticion(control_group,$('#form_crear_usuario-password').val(),$('#form_crear_usuario-rpassword').val())
           
            });

            $('#form_crear_usuario-email_rep').on('change', function () {
                var control_group = $('#form_crear_usuario-email_rep').parents('.control-group');
                validar_repeticion(control_group,$('#form_crear_usuario-email').val(),$('#form_crear_usuario-email_rep').val())
           
            });

            $('#form_crear_usuario-email').on('change', function () {
                var control_group = $('#form_crear_usuario-email_rep').parents('.control-group');
                validar_repeticion(control_group,$('#form_crear_usuario-email').val(),$('#form_crear_usuario-email_rep').val())
           
            });
            //---- Sugerencia de nombre de usuario -----------------------------
            $('#form_crear_usuario-nro_documento').on('change', function () {
                sugerir_usuario($('#form_crear_usuario-pais_documento').val(), $('#form_crear_usuario-tipo_documento').val(), $('#form_crear_usuario-nro_documento').val());
                validar_documento($('#form_crear_usuario-tipo_documento').val(), $('#form_crear_usuario-nro_documento').val(), $('#form_crear_usuario-pais_documento').val());
            });

            //------------------------------------------------------------------

            $('#form_crear_usuario').submit(function () {
                if (errores_formulario) { 
                    kernel.ui.show_mensaje('Error en el formulario', {tipo: 'alert-error'});
                    return false;
                }
            });

            $('#form_crear_usuario-email_rep').on('paste', function () {
                return false;
            });
            $('#form_crear_usuario-email_rep').attr('autocomplete', 'off');

            //------------------------------------------------------------------
            //---- Selector de localidad ---------------------------------------
            //------------------------------------------------------------------

            //---- Combo de países para el selector de localidades -------------
            handle_combo_pais_localidad('form_crear_usuario-pais', 'form_crear_usuario-localidad');

            //---- Lista de localidades (autocompletado) -----------------------
            if ($('#form_crear_usuario-localidad_descr').val() !== '') {
                set_solo_lectura('form_crear_usuario-localidad_descr', true);
                $('#localidad_help').hide();
            } else {
                $('#localidad_help').show();
            }

            $('#form_crear_usuario-localidad_descr').attr('campo_id', 'form_crear_usuario-localidad');
            $('#form_crear_usuario-localidad_descr').attr('ayuda', 'localidad_help');
            $('#form_crear_usuario-localidad_descr').attr('campo_pais', 'form_crear_usuario-pais');
            $('#form_crear_usuario-localidad_descr').attr('campo_descr', 'form_crear_usuario-localidad_descr');
            $('#form_crear_usuario-localidad_descr').autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: info.url_lista_localidades,
                        dataType: 'json',
                        data: {
                            term: request.term,
                            pais: $('#form_crear_usuario-pais').val()
                        },
                        success: function (data) {
                            response(data);
                        },
                        // A value of 0 means there will be no timeout
                        timeout: 0 
                    });
                },
                minLength: $("#js-ui-autocomplete-min-length").val(),
                select: localidad_seleccionada,
                focus: localidad_foco

            });

            $('#btnSelectorLoc').on('click', function () {
                $.facebox(info.selector_localidad);
                var _info = new cloneObject(info);
                _info['retorno_valor'] = 'form_crear_usuario-localidad';
                _info['retorno_descr'] = 'form_crear_usuario-localidad_descr';
                _info['ayuda'] = 'localidad_help';
                _info['campo_pais'] = 'form_crear_usuario-pais';
                inicializar_popup_localidades(_info);
            });

            $('#btnLimpiarLoc').on('click', function () {
                $('#form_crear_usuario-localidad').val('null');
                $('#form_crear_usuario-localidad_descr').val('');
                set_solo_lectura('form_crear_usuario-localidad_descr', false);
                handle_combo_pais_localidad('form_crear_usuario-pais', 'form_crear_usuario-localidad');
                $('#localidad_help').show();
            });
            
            $('#ayuda_numero_tramite').popover({
                trigger: 'click',
                html: true,
                content: function(){ 
                   return '<img src="' + $(this).data('img') + '"/>'; 
                }
             });
             
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

            //---- Sugerencia de nombre de usuario -----------------------------
            $('#form_crear_usuario-usuario').on('change', function () {
                cambio_usuario($('#form_crear_usuario-usuario').val());
            });

            //------------------- Cambio de genero -----------------------------
            $('#form_crear_usuario-identidad_genero').on('change', function () {
                cambio__identidad_genero();
            });
        }
        
    }

    function sugerir_usuario(_pais_doc, _tipo_doc, _nro_doc)
    {
        $.ajax({
            url: url_sugerencia_user,
            dataType: 'json',
            data: {
                pais_doc: _pais_doc,
                tipo_doc: _tipo_doc,
                nro_doc: _nro_doc
            },
            type: 'post',
            success: function (data) {
                //Quito caracteres en blanco, en backend tambien se valida
                var usr = data.usuario;
                var usuario_sin_blancos = usr.replace(/ /g, "");
				var usr = $('#form_crear_usuario-usuario').val();
				if(usr === ''){
					$('#form_crear_usuario-usuario').val(usuario_sin_blancos);
				}                
            }
        });
    }

    //--------------------------------------------------------------------------
    //---- Función autocompletar (localidades) ---------------------------------
    //--------------------------------------------------------------------------

    function localidad_seleccionada(event, ui)
    {
        var campo_id = event.target.attributes.campo_id.nodeValue;
        var campo_descr = event.target.attributes.id.nodeValue;
        var ayuda = event.target.attributes.ayuda.nodeValue;
        var campo_pais = event.target.attributes.campo_pais.nodeValue;

        var localidad = ui.item.value.localidad;
        var localidad_descr = ui.item.value.localidad_descr;

        $("#" + campo_id).val(localidad);
        $("#" + campo_descr).val(localidad_descr);
        $("#" + ayuda).hide();
        $("#" + campo_pais).hide();
        event.preventDefault();

        set_solo_lectura(campo_descr, true);
    }

    function localidad_foco(event, ui)
    {
        event.preventDefault();
    }
});
