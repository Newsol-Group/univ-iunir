<?php
/**
 *
 *
 * @package Matriculas
 * @subpackage Personas
 */
class form_personas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$url_check = toba::proyecto()->get_www("img/verificado_icon.png")['url'];
		$tooltip_renaper = '
		<div id="tooltip_renaper" style="display:none;margin-bottom: 20px"
		   aria-label="Optativa compartida y sin asignar a una genérica."
		   data-toggle="tooltip" 
		   data-placement="top" 
		   data-html="true"
		   data-container="body"
		   title="Datos validado por el Renaper." 
		   data-original-title="Optativa compartida y sin asignar a una genérica.">
		   
		   <h3 style="margin-left:10px" >Validado por renaper <img src='.$url_check.' style="width:20px;" /></h3>
		</div>
		';
		echo $tooltip_renaper;
		echo '
		<table border="0" cellpadding="1" cellspacing="1" width="100%">
			<tbody>
				<tr>
					<td>';
						$this->generar_html_ef('apellido');
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('nombres'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('apellido_elegido'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('nombres_elegido'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('nro_cuil'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('sexo');
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('identidad_genero', '200px'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('identidad_genero_otro'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('usuario'); 
		echo '		</td>
				</tr>
			</tbody>
		</table>
		<table border="0" cellpadding="1" cellspacing="1" width="100%">
			<tbody>
				<tr>
					<td>';
						$this->generar_html_ef('fecha_nacimiento'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('localidad_nacimiento'); 
		echo "		</td>
				</tr>
				<tr>
					<td colspan='2'>";
						$this->generar_html_ef('nacionalidad'); 
		echo '		</td>
				</tr>
			</tbody>
		</table>
		<table border="0" cellpadding="1" cellspacing="1" width="100%">
			<tbody>
				<tr>
					<td colspan="2">';
						$this->generar_html_ef('pertenece_pueblo_originario', '240px');
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('pueblo_originario'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('pueblo_originario_otro');
		echo "		</td>
				</tr>
			</tbody>
		</table>";
		echo '
		<table border="0" cellpadding="1" cellspacing="1" width="100%">
			<tbody>
				<tr>
					<td>';
						$this->generar_html_ef('pais_origen'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('fecha_ingreso_pais'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('residencia_cupo'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('tipo_visa'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('otorgamiento_visa'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('vencimiento_visa'); 
		echo "		</td>
				</tr>
				<tr>
					<td>";
						$this->generar_html_ef('tipo_residencia'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('otorgamiento_residencia'); 
		echo "		</td>
					<td>";
						$this->generar_html_ef('vencimiento_residencia'); 
		echo "		</td>
				</tr>
			</tbody>
		</table>";
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$extranjero = persona::nac_extranjero;
		$date = guarani_fecha::get_hoy_js();
		$sexo_fem = persona::sexo_femenino;
		$sexo_masc = persona::sexo_masculino;
		$sexo_no_binario = persona::sexo_no_binario;
		$identidad_genero_ninguna = persona::identidad_genero_ninguna;
		$sin_residencia = persona::sin_residencia;
		$precaria = persona::residencia_precaria;
		$temporal = persona::residencia_temporal;
		$definitiva = persona::residencia_definitiva;
		$DNI = persona::doc_dni;
		$DNI_temporal = persona::doc_dni_temporal;
		$cedula = persona::doc_cedula;
		$pasaporte = persona::doc_pasaporte;
		$LE = persona::doc_LE;
		$LC = persona::doc_LC;
		$visa_permanente = persona::visa_permanente;
		$visa_temporal = persona::visa_temporal;
		$id_argentina = persona::pais_argentina;
		$mensaje_tipo_doc_sexo = guarani_mensajes::get('800_dato_dep_invalido', array('tipo de documento', 'sexo de la persona'));
		$mensaje_fecha_res = guarani_mensajes::get('800_dato_dep_obligatorio', array('fecha de otorgamiento', 'tipo de residencia'));
		$mensaje_fechas_res = guarani_mensajes::get('800_dato_dep_obligatorio', array('fecha de otorgamiento y fecha de vencimiento de la residencia', 'tipo de residencia'));
		$mensaje_fecha_visa = guarani_mensajes::get('800_dato_dep_obligatorio', array('fecha de otorgamiento', 'tipo de visa'));
		$mensaje_fechas_visa = guarani_mensajes::get('800_dato_dep_obligatorio', array('fecha de otorgamiento y fecha de vencimiento de la visa', 'tipo de visa'));
		$mensaje_fecha_nac = guarani_mensajes::get('800_dato_invalido', array('fecha de nacimiento', 'la persona debe ser mayor a 15 años'));
		$mensaje_fecha_nac_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('fecha de nacimiento', 'tipo de fecha'));
		$mensaje_pueblo_originario = guarani_mensajes::get('800_dato_dep_obligatorio', ['campo ¿Cuál?', '¿Pertenece a algún pueblo originario?']);
		$mensaje_pueblo_originario_otro = guarani_mensajes::get('800_dato_dep_obligatorio', ['campo Especificar', '¿Cuál?']);
		$mensaje_otorg = guarani_mensajes::get('800_control_fechas', array(' de otorgamiento', 'mayor o igual', ' actual'));
		$mensaje_venc = guarani_mensajes::get('800_control_fechas', array(' de vencimiento', 'menor', ' de otorgamiento ni a la fecha actual'));
		$si = comunes::si;
		$pueblo_originario_otros = comunes::pueblo_originario_otros;
		$ef_combo_seleccione = comunes::ef_combo_seleccione;
		$validado_con_renaper 	 = $this->controlador()->usa_renaper();
		$url_check 				 = toba::proyecto()->get_www("img/verificado_icon.png")['url'];
		$documento_validado 	 = toba::db()->quote($this->controlador()->get_datos_documentos());
		echo "		
		var localidad_ok = true;
		var msj = '';
		var apellido_inicial = '';
		var nombres_inicial = '';
		var sexo_inicial = '';
		var nacionalidad_inicial = '';
		var fecha_nacimiento_inicial = '';
		encabezado_renaper();
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			var hoy = new $date;
		
			//Chequeo de los campos que son obligatorios segun el tipo de residencia.
					
			if (!this.ef('tipo_residencia').es_oculto() && this.ef('tipo_residencia').tiene_estado()) {
				var tipo_residencia = this.ef('tipo_residencia').get_estado();
				if ((tipo_residencia == $precaria || tipo_residencia == $temporal) && 
					(! this.ef('otorgamiento_residencia').tiene_estado() 
					|| ! this.ef('vencimiento_residencia').tiene_estado())) {
					notificacion.agregar('$mensaje_fechas_res');
					ok = false;		
				} else if (tipo_residencia == $definitiva && !this.ef('otorgamiento_residencia').tiene_estado()) {
					notificacion.agregar('$mensaje_fecha_res');
					ok = false;		
				}
			}			
				
			//Chequeo de los campos que son obligatorios segun el tipo de visa.
			
			if (!this.ef('tipo_visa').es_oculto() && this.ef('tipo_visa').tiene_estado()) {
				var tipo_visa = this.ef('tipo_visa').get_estado();
				if ((tipo_visa == $visa_temporal) && (!(this.ef('otorgamiento_visa').tiene_estado()) 
					|| !(this.ef('vencimiento_visa').tiene_estado()))) {
					notificacion.agregar('$mensaje_fechas_visa');
					ok = false;
				} else if (tipo_visa == $visa_permanente && !this.ef('otorgamiento_visa').tiene_estado()) {
					notificacion.agregar('$mensaje_fecha_visa');
					ok = false;		
				}
			}
		
			if (!this.ef('vencimiento_visa').es_oculto() && this.ef('vencimiento_visa').tiene_estado()) {
				var venc_visa = this.ef('vencimiento_visa').fecha();
				var otorg_visa = this.ef('otorgamiento_visa').fecha();
				if ((venc_visa <= hoy) || (venc_visa <= otorg_visa)) {
					notificacion.agregar('$mensaje_venc');
					ok = false;	
				}
			}
			
			var edad = this.ef('fecha_nacimiento').calcular_edad(); //calcula la diferencia entre la fecha actual y la del campo.  
			if (this.ef('fecha_nacimiento').tiene_estado() && edad < 15) {
				if(!confirm('La edad de la persona que esta ingresando es menor de 15 años, desea continuar?')) {                
					ok = false;
				}                
			}
		
			return ok;
		}
		
		{$this->objeto_js}.respuesta_validacion_pais_nacimiento = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				msj = respuesta['mensaje'];
				localidad_ok = false;
			} else {
				localidad_ok = true;
			}
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_nacimiento__validar = function()
		{
			var ok = true;
			if (this.ef('fecha_nacimiento').tiene_estado()) {
				var edad = this.ef('fecha_nacimiento').calcular_edad(); //calcula la diferencia entre la fecha actual y la del campo.  
				if (edad < 15) {
					//this.ef('fecha_nacimiento').set_error('$mensaje_fecha_nac');
					//ok = false;                    
				}
			}else{
				this.ef('fecha_nacimiento').set_error('$mensaje_fecha_nac_oblig');
				ok = false;  
			}
			return ok;
		} 
		
		{$this->objeto_js}.evt__otorgamiento_visa__validar = function()
		{
			var ok = true;
			var hoy = new $date;
			
			if(this.ef('otorgamiento_visa').tiene_estado() && !this.ef('otorgamiento_visa').es_oculto()) {
				if (this.ef('otorgamiento_visa').fecha() > hoy) {
					this.ef('otorgamiento_visa').set_error('$mensaje_otorg');
					ok = false;		
				}
			}
			
			return ok;
		}
		
		{$this->objeto_js}.evt__vencimiento_residencia__validar = function() {
			var ok = true;
			var hoy = new $date;
			
			if(this.ef('vencimiento_residencia').tiene_estado() && !this.ef('vencimiento_residencia').es_oculto()) {
				var venc_res = this.ef('vencimiento_residencia').fecha();
				var otorg_res = this.ef('otorgamiento_residencia').fecha();
				if ((venc_res <= hoy) || (venc_res <= otorg_res)) {
					this.ef('vencimiento_residencia').set_error('$mensaje_venc');
					ok = false;		
				}
			}
			
			return ok;
		}
		
		{$this->objeto_js}.evt__otorgamiento_residencia__procesar = function(es_inicial)
		{
			var tipo_residencia = this.ef('tipo_residencia').get_estado();
			if (! es_inicial &&
				(tipo_residencia == '$precaria' || tipo_residencia == '$temporal') &&
				this.ef('otorgamiento_residencia').tiene_estado() &&
				! this.ef('vencimiento_residencia').tiene_estado()) {
		
				var fecha = this.ef('otorgamiento_residencia').fecha();
				var nueva = new Date();
				nueva.setTime(fecha.getTime() + (1000 * 60 * 60 * 24 * 730)); //suma 730 dias a la fecha de otorgamiento.
				this.ef('vencimiento_residencia').set_fecha(nueva);
			}
		}
		
		{$this->objeto_js}.evt__tipo_visa__procesar = function(es_inicial)
		{
			var tipo = this.ef('tipo_visa').get_estado();
			var mostrar_otorgamiento = false;
			var mostrar_vencimiento = false;
		
			if (tipo == $visa_temporal) {
				mostrar_otorgamiento = true;
				mostrar_vencimiento = true;
			} else if (tipo == $visa_permanente) {
				mostrar_otorgamiento = true;
			}
		
			// Los campos que no se muestran, es decir se ocultan, se resetean.
			this.ef('otorgamiento_visa').mostrar(mostrar_otorgamiento,!mostrar_otorgamiento);
			this.ef('vencimiento_visa').mostrar(mostrar_vencimiento,!mostrar_vencimiento);
		}
		
		{$this->objeto_js}.evt__tipo_residencia__procesar = function(es_inicial)
		{
			var tipo = this.ef('tipo_residencia').get_estado();
			var mostrar_otorgamiento = false;
			var mostrar_vencimiento = false;
			
			if (tipo == $temporal || tipo == $precaria) {
				mostrar_otorgamiento = true;
				mostrar_vencimiento = true;
			} else if (tipo == $definitiva) {
				mostrar_otorgamiento = true;
			} //si la residencia tiene el valor sin residencia, no se muestra ninguno de los campos.
			 		
			// Los campos que no se muestran, es decir se ocultan, se resetean.
			this.ef('otorgamiento_residencia').mostrar(mostrar_otorgamiento,!mostrar_otorgamiento);
			this.ef('vencimiento_residencia').mostrar(mostrar_vencimiento,!mostrar_vencimiento);
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__identidad_genero__procesar = function(es_inicial)
		{
			var identidad_genero = this.ef('identidad_genero').get_estado();
			if (identidad_genero == '$identidad_genero_ninguna') {
				this.ef('identidad_genero_otro').mostrar();
			} else {
				this.ef('identidad_genero_otro').ocultar(true);
			}
		}
		
		{$this->objeto_js}.evt__localidad_nacimiento__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var localidad = this.ef('localidad_nacimiento').get_estado();
				var params = [localidad];
				this.controlador.ajax('get_pais_localidad', params, this, this.setear_nacionalidad_y_pais);
			}
		} 
		
		{$this->objeto_js}.setear_nacionalidad_y_pais = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				pais = respuesta['pais'];
				
				if (pais != $id_argentina && pais != null) {
					this.ef('nacionalidad').set_estado($extranjero);
					this.ef('pais_origen').set_estado(pais);
				}
			}
		}		
			
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__pertenece_pueblo_originario__procesar = function(es_inicial)
		{
			var pertenece = this.ef('pertenece_pueblo_originario').get_estado();
			if (pertenece == '$si') {
				this.ef('pueblo_originario').mostrar();
			} else {
				this.ef('pueblo_originario').ocultar(true);
				this.ef('pueblo_originario_otro').ocultar(true);
			}
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__pueblo_originario__procesar = function(es_inicial)
		{
			var pueblo = this.ef('pueblo_originario').get_estado();
			if (pueblo == '$pueblo_originario_otros') {
				this.ef('pueblo_originario_otro').mostrar();
			} else {
				this.ef('pueblo_originario_otro').ocultar(true);
			}
		}

		//---- Validacion de EFs -----------------------------------

		{$this->objeto_js}.evt__pueblo_originario__validar = function()
		{
                        var ok = true;
                        var pertenece = this.ef('pertenece_pueblo_originario').get_estado();
                        var pueblo = this.ef('pueblo_originario').get_estado();
			if(pertenece == '$si' && pueblo == '$ef_combo_seleccione') {
                            this.ef('pueblo_originario').set_error('$mensaje_pueblo_originario');
                            ok = false;
			}

			return ok;
		}

		{$this->objeto_js}.evt__pueblo_originario_otro__validar = function()
		{
                        var ok = true;
                        var pueblo = this.ef('pueblo_originario').get_estado();
                        var pueblo_otro = this.ef('pueblo_originario_otro').get_estado();
			if(pueblo == '$pueblo_originario_otros' && pueblo_otro.trim().length === 0) {
                            this.ef('pueblo_originario_otro').set_error('$mensaje_pueblo_originario_otro');
                            ok = false;
			}

			return ok;
		}
		
		{$this->objeto_js}.evt__apellido__procesar = function(es_inicial)
		{
			//Agrego icono de validación si se valida con el parametro validado_con_renaper
			var id_elemento = this.ef('apellido')._id_form;
			if(es_inicial){	
				//sacar esto de aca y ver como coloco el icono de renaper al lado del titulo				
				apellido_inicial = $('#' + id_elemento).val();
				toggle_icono_renaper(id_elemento);
			}else{
				if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
					var apell = $('#' + id_elemento).val();
					if(apellido_inicial !== apell){
						remove_toggle_icono_renaper(id_elemento);
						this.hay_cambios_renaper();
					}else{
						toggle_icono_renaper(id_elemento);
					}
				}
			}
		}
		
		{$this->objeto_js}.evt__nombres__procesar = function(es_inicial)
		{
			//Agrego icono de validación si se valida con el parametro validado_con_renaper
			var id_elemento = this.ef('nombres')._id_form;
			
			if(es_inicial){
				nombres_inicial = $('#' + id_elemento).val();
				toggle_icono_renaper(id_elemento);
			}else{
				if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
					var nomb = $('#' + id_elemento).val();
					if(nombres_inicial !== nomb){
						remove_toggle_icono_renaper(id_elemento);
						this.hay_cambios_renaper();
					}else{
						toggle_icono_renaper(id_elemento);
					}
				}
			}	
		}
		
		{$this->objeto_js}.evt__sexo__procesar = function(es_inicial)
		{
			//Agrego icono de validación si se valida con el parametro validado_con_renaper
			var id_elemento = this.ef('sexo')._id_form;
			var len = this.ef('sexo')._cant_columnas - 1;
			if(es_inicial){				
				if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
					sexo_inicial = this.ef('sexo').get_estado();
					var img  = document.createElement('img');
					img.id	 = 'img_'+id_elemento;
					img.src  = '$url_check';
					img.style.cssText = 'width:20px;margin-left:10px;float:right';
					$('#' + id_elemento + len).last().after(img);
				}
			}else{
				if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
					sex = this.ef('sexo').get_estado();					
					if(sexo_inicial !== sex){
						remove_toggle_icono_renaper(id_elemento);
						this.hay_cambios_renaper();
					}else{
						remove_toggle_icono_renaper(id_elemento);
						var img  = document.createElement('img');
						img.id	 = 'img_'+id_elemento;
						img.src  = '$url_check';
						img.style.cssText = 'width:20px;margin-left:10px;float:right';
						$('#' + id_elemento + len).last().after(img);
					}
				}
			}		
		}
		
		{$this->objeto_js}.evt__fecha_nacimiento__procesar = function(es_inicial)
		{
			//Agrego icono de validación si se valida con el parametro validado_con_renaper
			var id_elemento = this.ef('fecha_nacimiento')._id_form;
			if(es_inicial){
				fecha_nacimiento_inicial = $('#' + id_elemento).val();
				toggle_icono_renaper(id_elemento);
			}else{
				if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
					var f_nac = $('#' + id_elemento).val();
					if(fecha_nacimiento_inicial !== f_nac){
						remove_toggle_icono_renaper(id_elemento);
						this.hay_cambios_renaper();
					}else{
						toggle_icono_renaper(id_elemento);
					}
				}
			}		
		}
		
		{$this->objeto_js}.evt__nacionalidad__procesar = function(es_inicial)
		{
			//Si la persona no es extranjera se oculta el campo de ingreso al pais y además varios campos del form de documentos
			
			var nacionalidad = this.ef('nacionalidad').get_estado();
			var mostrar_campos_extranjeros = false;
			
			if (nacionalidad == $extranjero) {
				mostrar_campos_extranjeros = true;
			}
			
			//Los siguientes campos se muestran solo si la persona es de nacionalidad extranjera.
			this.ef('fecha_ingreso_pais').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('pais_origen').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('residencia_cupo').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('fecha_ingreso_pais').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('tipo_visa').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('otorgamiento_visa').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('vencimiento_visa').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('tipo_residencia').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('otorgamiento_residencia').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('vencimiento_residencia').mostrar(mostrar_campos_extranjeros,!mostrar_campos_extranjeros);
			this.ef('pais_origen').set_obligatorio(mostrar_campos_extranjeros);
		
			//Agrego icono de validación si se valida con el parametro validado_con_renaper
			var id_elemento = this.ef('nacionalidad')._id_form;
			if(es_inicial){
				nacionalidad_inicial = nacionalidad;
				toggle_icono_renaper(id_elemento);
			}else{
				if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
					var nac = this.ef('nacionalidad').get_estado();
					if(nacionalidad_inicial !== nac){
						remove_toggle_icono_renaper(id_elemento);
						this.hay_cambios_renaper();
					}else{
						toggle_icono_renaper(id_elemento);
					}
				}
			}		
		}
		
		function toggle_icono_renaper(id_elemento)
		{			
			if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {				
				if($('#' + 'img_'+id_elemento).length == 0){
					var img  = document.createElement('img');
					img.id	 = 'img_'+id_elemento;
					img.src  = '$url_check';
					img.style.cssText = 'width:20px;margin-left:10px';
					$('#' + id_elemento).after(img);
				}				
			}
		}
		
		function remove_toggle_icono_renaper(id_elemento)
		{	
			$('#' + 'img_'+id_elemento).remove();
		}
		
		function encabezado_renaper()
		{	
			if ('$validado_con_renaper' == '$si' && $documento_validado == '$si') {
				$('#tooltip_renaper').show();				
			}
		}
		
		{$this->objeto_js}.hay_cambios_renaper = function()
		{
			var apellido = this.ef('apellido').get_estado();
			var nombres = this.ef('nombres').get_estado();
			var sexo = this.ef('sexo').get_estado();
			var fecha_nacimiento = this.ef('fecha_nacimiento').get_estado();
			var nacionalidad = this.ef('nacionalidad').get_estado();
			var cambio_renaper = false;
			if(apellido_inicial !== apellido || nombres_inicial !== nombres || sexo_inicial !== sexo || fecha_nacimiento_inicial !== fecha_nacimiento || nacionalidad_inicial !== nacionalidad) {
				cambio_renaper = true; 
				var parametros = {cambio_renaper};
				this.controlador.ajax('cambios_campos_renaper', parametros, this, this.atender_respuesta);
				return false;
			}else{
				var parametros = cambio_renaper;
				this.controlador.ajax('cambios_campos_renaper', parametros, this, this.atender_respuesta);
				return false;
			}
		}
		{$this->objeto_js}.atender_respuesta = function(datos)
		{
			// notificacion.limpiar();
			// notificacion.mostrar_ventana_modal('Hubo cambios en los datos validado por renaper',
			// 					 resp.responseText, '400px', 'overlay(true)');
			// notificacion.mostrar();
		}
		";
	}



}