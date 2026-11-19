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

var url_tipo_documentos_validacion;
var validado_con_renaper;

kernel.renderer.registrar_pagelet('datos_principales', function(info) {
	var id = '#' + info.id;	
	var r = window.matchMedia("(max-width: 700px)");
	
	url_tipo_documentos_validacion = info.url_tipo_documentos_validacion;
	
	// Tipos de documentos
	tipo_documento_dni = info.tipo_documento_dni;
	tipo_documento_dnt = info.tipo_documento_dnt;
	tipo_documento_cuil_cuit = info.tipo_documento_cuil_cuit;
	tipo_documento_cedula_diplomatica = info.tipo_documento_cedula_diplomatica;
	tipo_documento_pasaporte = info.tipo_documento_pasaporte;
	
	// M�ximos y m�nimos de n�meros de documento
	nro_documento_dni_dnt_min = info.nro_documento_dni_dnt_min;
	nro_documento_dni_dnt_max = info.nro_documento_dni_dnt_max;
	
	// Mensajes
        msj_nro_documento_dni_leyenda = info.mensajes.msj_nro_documento_dni_leyenda;
        msj_nro_documento_dni_error = info.mensajes.msj_nro_documento_dni_error;
        msj_nro_documento_cuil_leyenda = info.mensajes.msj_nro_documento_cuil_leyenda;
        
        validado_con_renaper = info.validado_con_renaper;
        
	
    return {
		
        onload: function() {
                        //----arreglo jquery para mostrar ayuda 
                        $("#form_datos_principales-identidad_genero-label").html($("#form_datos_principales-identidad_genero-label").text()+$("#ayuda_genero").html());$("#ayuda_genero").hide();
			//---- Inicializaci�n de estado de visibilidad de secciones --------
                        valida_con_renaper();
			cambio_tipo_visa();
			cambio_residencia();
			cambio__pertenece_pueblo_originario();
			cambio__pueblo_originario();
			cambio__identidad_genero();
			cambio__tipo_documento();
			r.addListener(mediaQuerymatch(r));
			
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_datos_principales-tipo_visa').on('change', function() { cambio_tipo_visa(); });
			$('#form_datos_principales-residencia').on('change', function() { cambio_residencia(); });
			$('#form_datos_principales-pertenece_pueblo_originario').on('change', function() { cambio__pertenece_pueblo_originario(); });
			$('#form_datos_principales-pueblo_originario').on('change', function() { cambio__pueblo_originario(); });
			$('#form_datos_principales-identidad_genero').on('change', function() { cambio__identidad_genero(); });
			$('#form_datos_principales-pais_documento').on('change', function() { buscar_tipo_documento_paises($('#form_datos_principales-pais_documento').val()); });
			$('#form_datos_principales-identidad_genero').tooltip('show');
			$('#form_datos_principales-tipo_documento').on('change', function() { cambio__tipo_documento(); });
			
			$('#form_datos_principales-nro_documento').on('change', function ()
			{
				validar__nro_documento($('#form_datos_principales-pais_documento').val(), $('#form_datos_principales-tipo_documento').val(), $('#form_datos_principales-nro_documento').val());
			});
			
			//---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults( $.datepicker.regional["es"] );
			$(id).find('input.date').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
				yearRange: "-150:+0",
			});

			//---- Date Picker  Particular para otorgamiento de residencia -------------------------------------------------
			$(id).find('input.date-residencia-otorg').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
				yearRange: "-150:+0",
				maxDate:0,
			});
			
			//---- Date Picker  Particular para vencimiento de residencia -------------------------------------------------
			$(id).find('input.date-residencia-venc').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
				yearRange: "-150:+3",
			});
			
			
			//---- Minusculizaci�n de e-mail -----------------------------------
			if ($('#form_datos_principales-e_mail').val() !== 'undefined') {
				$('#form_datos_principales-e_mail').val($('#form_datos_principales-e_mail').val().toString().toLowerCase());
			}
			
			//---- Combo de pa�ses para el selector de localidades -------------
			handle_combo_pais_localidad('form_datos_principales-pais_nacimiento', 'form_datos_principales-loc_nacimiento');
			$('#form_datos_principales-loc_nacimiento').on('change', function() { 
				handle_combo_pais_localidad('form_datos_principales-pais_nacimiento', 'form_datos_principales-loc_nacimiento');
			});
			
			//---- Lista de localidades (autocompletado) -----------------------
			if ($("#form_datos_principales-loc_nac_descr").val() !== '') {
				set_solo_lectura('form_datos_principales-loc_nac_descr', true);
				$("#loc_nac_descr_help").hide();
			} else {
				$("#loc_nac_descr_help").show();
			}
			
			$("#form_datos_principales-loc_nac_descr").attr('campo_id', 'form_datos_principales-loc_nacimiento');
			$("#form_datos_principales-loc_nac_descr").attr('ayuda', 'loc_nac_descr_help');
			$("#form_datos_principales-loc_nac_descr").attr('campo_pais', 'form_datos_principales-pais_nacimiento');
			$("#form_datos_principales-loc_nac_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_localidades,
					  dataType: "json",
					  data: {
						term : request.term,
						pais : $('#form_datos_principales-pais_nacimiento').val()
					  },
					  success: function(data) {
						response(data);
					  }, 
                                          // A value of 0 means there will be no timeout
                                          timeout: 0 
					});
				},
				minLength: 3,	
				select: localidad_seleccionada,
				focus: localidad_foco
				
			});
			
			//---- Selector de localidad ---------------------------------------
			$('#btnSelectorLocNacimiento').on('click', function() {
				$.facebox(info.selector_localidad);
				var _info = new cloneObject(info);
				_info['retorno_valor'] = 'form_datos_principales-loc_nacimiento';
				_info['retorno_descr'] = 'form_datos_principales-loc_nac_descr';
				_info['ayuda'] = 'loc_nac_descr_help';
				_info['campo_pais'] = 'form_datos_principales-pais_nacimiento';
				inicializar_popup_localidades(_info);
			});
		   
			$('#btnLimpiarLocNacimiento').on('click', function() {
				$('#form_datos_principales-loc_nacimiento').val('null');
				$('#form_datos_principales-loc_nac_descr').val('');
				$("#loc_nac_descr_help").show();
				set_solo_lectura('form_datos_principales-loc_nac_descr', false);
				handle_combo_pais_localidad('form_datos_principales-pais_nacimiento', 'form_datos_principales-loc_nacimiento');
			});
			
			//------------------------------------------------------------------
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			//------------------------------------------------------------------
			// Se muestra mensaje cuando se guarada con exito
			if (info.msg_guardado_datos_exitoso) {
			    kernel.ui.show_mensaje(info.msg_guardado_datos_exitoso, {
				tipo: 'alert-success',
				until_interaction: false,
				timeout: 5000,
				fadeout: 2000
			    });
                            info.msg_guardado_datos_exitoso = false;
                        }

			
			//---- Submit ------------------------------------------------------
			$('#form_datos_principales').submit(function() {
				var hubo_cambios = formulario_hubo_cambios();
				if (hubo_cambios) {
					var $form = $(this);
					kernel.ajax.load($form.attr('action'), id, {
						historia: false,
						type: 'POST',
						data: $form.serializeArray(),
						success: function(response) {
							if (response.info_estado.exitoso) {
								document.location.reload(true);
							} else {
								kernel.ui.show_mensaje(response.info_estado.mensaje, {
									tipo: 'alert-error',
									until_interaction: false,
									timeout: 5000,
									fadeout: 2000
								});	
							}
							
							// Si corresponde, se desactiva el autosave
							if (info.autosave) {
								$(id).autosave('disable');
							}
							kernel.renderer.pagelet('datos_principales').onload();
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
				return false;
			});
        }
    }
	
	/**
	 * Cambio del campo "Tipo de visa"
	 */
	function cambio_tipo_visa()
	{
		if ($('#form_datos_principales-tipo_visa').val() === 'PERMANENTE') {
			ocultar('div_vencimiento_visa', true);
		} else {
			mostrar('div_vencimiento_visa');
		}
	}

	/**
	 * Cambio del campo "Tipo de residencia"
	 */
	function cambio_residencia()
	{
		var residencia = $('#form_datos_principales-residencia').val();

		if ((residencia === '1') || (residencia === no_seleccion)) {
			// Sin residencia: No se muestran fechas de otorg. y venc. de residencia
			ocultar('div_fecha_respre_otorg', true);
			ocultar('div_fecha_respre_venc', true);
		} else if (residencia === '4') {
			// Residencia definitiva: no se muestra fecha de vencimiento
			mostrar('div_tipo_residencia');
			ocultar('div_fecha_respre_venc', true);
		} else {
			// Residencia temporal o precaria: se muestran fechas de otorg. y venc.
			mostrar('div_fecha_respre_otorg');
			mostrar('div_fecha_respre_venc');
		}
	}

	/**
	 * Cambio del campo "�Pertenec�s a alg�n pueblo originario?"
	 */
	function cambio__pertenece_pueblo_originario()
	{
		if ($('#form_datos_principales-pertenece_pueblo_originario').val() !== 'S') {
			ocultar('div_pueblo_originario', true);
		} else {
			mostrar('div_pueblo_originario');
			cambio__pueblo_originario();
		}
	}

	/**
	 * Cambio del campo "�A cu�l?"
	 */
	function cambio__pueblo_originario()
	{
		if ($('#form_datos_principales-pueblo_originario').val() !== info.pueblo_originario_OTRO) {
			ocultar('div_otro_pueblo_originario', true);
		} else {
			mostrar('div_otro_pueblo_originario');
		}
	}
	
	/**
	 * Cambio del campo "Tipo de Documento"
	 */
	function cambio__tipo_documento()
	{
	    var valor = $('#form_datos_principales-tipo_documento').val();

	    switch (valor) {
		// Si el tipo de documento es DNI
		case tipo_documento_dni:
		    $("#js_nro_documento").html(msj_nro_documento_dni_leyenda).slideDown("slow");
		    break;
		    // Si el tipo de documento es CUIL/CUIT
		case tipo_documento_cuil_cuit:
		    $("#js_nro_documento").html(msj_nro_documento_cuil_leyenda).slideDown("slow");
		    break;
		default:
		    $("#js_nro_documento").slideUp("slow");
	    }
	}

	/**
	 * Validaci�n del campo "N�mero de documento"
	 */
	function validar__nro_documento(_pais_documento, _tipo_documento, nro_documento)
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
		    var control_group = $('#form_datos_principales-nro_documento').parents('.control-group');
		    $(control_group).removeClass('error');
		    var span = $(control_group).find('span.help-inline');
		    $(span).remove();
		    if (!exp_reg.test(nro_documento)) {
			$(control_group).addClass('error');
			$(control_group).append('<span class="help-inline">' + data.exp_regular_mensaje + '</span>')
			errores_formulario = true;
		    } else {

			// Validaciones extras
			switch (_tipo_documento) {
			    // Si el tipo de documento es DNI
			    case tipo_documento_dni:
				// Valida que el DNI sea mayor a 1 mill�n y menor a 100 millones.
				if ((parseInt(nro_documento) < parseInt(nro_documento_dni_dnt_min)) || (parseInt(nro_documento) > parseInt(nro_documento_dni_dnt_max))) {
				    $(control_group).addClass('error');
				    $(control_group).append('<span class="help-inline">' + msj_nro_documento_dni_error + '</span>');
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
	
	//--------------------------------------------------------------------------
	//---- Funci�n autocompletar (localidades) ---------------------------------
	//--------------------------------------------------------------------------
			
	function localidad_seleccionada(event, ui)
	{
		var campo_id = event.target.attributes.campo_id.nodeValue;
		var campo_descr = event.target.attributes.id.nodeValue;
		var ayuda = event.target.attributes.ayuda.nodeValue;
		var campo_pais = event.target.attributes.campo_pais.nodeValue;

		var localidad = ui.item.value.localidad;
		var localidad_descr = ui.item.value.localidad_descr;

		$("#"+campo_id).val(localidad);
		$("#"+campo_descr).val(localidad_descr);
		$("#"+ayuda).hide();
		$("#"+campo_pais).hide();
		event.preventDefault();
		
		set_solo_lectura(campo_descr, true);
	}
	
	function localidad_foco(event)
	{
		event.preventDefault();
	}
	
	function mediaQuerymatch(r)
	{
		//media query matches
		if (r.matches) { 
			$('#form_datos_principales-loc_nac_descr').removeClass('input-localidad');

		} else {
			$('#form_datos_principales-loc_nac_descr').addClass('input-localidad');
		}
	}

        function cambio__identidad_genero(){
               if ( $("#form_datos_principales-identidad_genero").val() != info.identidad_genero_especifique){
                        ocultar('form_datos_principales-identidad_genero_otro', true);
                        ocultar('form_datos_principales-identidad_genero_otro-label', true);
                } else {
                        mostrar('form_datos_principales-identidad_genero_otro');
                        mostrar('form_datos_principales-identidad_genero_otro-label');
                            $( "#form_datos_principales-identidad_genero_otro" ).focus();

                }
	}
        
    function buscar_paises(_nacionalidad)
    {
	$.ajax({
	    url: info.url_cascada_nac,
	    dataType: 'json',
	    async: false,
	    data: {nacionalidad: _nacionalidad},
	    type: 'post',
	    success: function (data)
	    {
		$('#form_datos_principales-pais_documento').children().remove();
		$.each(data, function (key, value)
		{
		    $('#form_datos_principales-pais_documento').append(
			    $('<option></option>').val(key).html(value)
			    );
		});
		ordenar_combo('form_datos_principales-pais_documento');
	    }
	});
    }
	
    function buscar_tipo_documento_paises(_pais_documento)
    {
	var tipo_documento = $('#form_datos_principales-tipo_documento').val();
	$.ajax({
	    url: info.url_tipo_documentos,
	    dataType: 'json',
	    async: false,
	    data: {pais: _pais_documento},
	    type: 'post',
	    success: function (data)
	    {
		$('#form_datos_principales-tipo_documento').children().remove();
		$.each(data, function (key, value)
		{
		    $('#form_datos_principales-tipo_documento').append(
			    $('<option></option>').val(key).html(value)
			    );
		});
		ordenar_combo('form_datos_principales-tipo_documento');
		$("#form_datos_principales-tipo_documento").val(tipo_documento);
	    }
	});

    }
    
    function valida_con_renaper()
    {
        if(validado_con_renaper === 'S'){
            //Nombre y Apellido, Fecha de Nacimiento, Tipo y N�mero de Documento, N�mero de Tr�mite y CUIL deben aparecer precargados y grisados
            set_solo_lectura('form_datos_principales-apellido',true);
            set_solo_lectura('form_datos_principales-nombres',true);
            set_solo_lectura('form_datos_principales-tipo_documento',true);
            set_solo_lectura('form_datos_principales-nro_documento',true);
            set_solo_lectura('form_datos_principales-pais_documento',true);
            set_solo_lectura('form_datos_principales-numero_cuil',true);
            set_solo_lectura('form_datos_principales-fecha_nacimiento',true);
            
        }
    }
    
    function set_solo_lectura(elemId, solo_lectura)
    {
        //Coloca en readonly si el campo no esta vacio
            elemId = '#' + elemId;
            if (solo_lectura) {
                if($(elemId).val()!== ''){
                    $(elemId).attr('readonly', true);
                }                    
            } else {
                    $(elemId).attr('readonly', false);
            }
    }
});