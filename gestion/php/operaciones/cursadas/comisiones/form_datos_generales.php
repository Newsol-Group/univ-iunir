<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class form_datos_generales extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('nombre', '150px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('ubicacion');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('division');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('inscripcion_habilitada');
					echo '</td>';
					echo "<td colspan=2>";
					$this->generar_html_ef('acepta_planes_personalizados');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '</table><fieldset><legend>Cupo</legend><table width=100%><tr><td>';
					$this->generar_html_ef('cupo_tipo', '145px');
					echo '<td>';
					$this->generar_html_ef('cupo_minimo', '50px');
					echo '</td>';
					echo "<td colspan=2>";
					$this->generar_html_ef('cupo', '50px');
				echo '</td></tr></table></fieldset>';
				echo '<table><tr>';
					echo '<td>';
					$this->generar_html_ef('letra_desde');
					echo '</td>';
					echo "<td colspan=2>";
					$this->generar_html_ef('letra_hasta');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('turno');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('catedra');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cobrable');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('estado');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('inscripcion_cerrada');
				echo '</td>';
				echo '<td colspan=2>';
				$this->generar_html_ef('inscripcion_cerrada_codigo');
				echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('observaciones');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
        $pendiente = comunes::estado_pendiente;
        $activo = comunes::estado_activo;
		$cupo_tipo_propuesta = comision::cupo_tipo_propuesta;
		$msj_rango_letras = guarani_mensajes::get('800_dato_dep_invalido', array('Letra Hasta', 'Letra Desde'));
		$msj_completar_rango = guarani_mensajes::get('800CUR_completar_letras', array('Comisión'));
		$si = comunes::si;
		$no = comunes::no;
		$codigo_validacion_cant_digitos = comision::CODIGO_INSCRIPCION_CANT_DIGITOS;
		$codigo_validacion_regexp = comision::CODIGO_INSCRIPCION_REGEXP;
		$msj_codigo_validacion_solo_letras_numeros = guarani_mensajes::get('800_codigo_validacion_solo_letras_numeros');
		$msj_codigo_validacion_x_caracteres = guarani_mensajes::get('800_codigo_validacion_x_caracteres', array($codigo_validacion_cant_digitos));

		/**
		 * En caso de que se cambie la cátedra elegida y existan asignaciones de docentes activas (sin fecha de 
		 * finalización), se deberá mostrar el mensaje 800CUR_docentes_catedra, ya que en caso de que el usuario confirme,
		 * se eliminarán todos los docentes de la cátedra asignados a la comisión y sus correspondientes dedicaciones 
		 * horarias.
		 */
		echo "
			
		var catedra_original;
		var seteo_catedra_original = false;
		var mensaje_cambio = '';
	
		//---- Validación general de datos -----------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			
			// Las letras desde y hasta deben completarse ambas o ninguna.
			if ((this.ef('letra_desde').tiene_estado() && !this.ef('letra_hasta').tiene_estado()) ||
				!this.ef('letra_desde').tiene_estado() && this.ef('letra_hasta').tiene_estado()) {
				notificacion.agregar('$msj_completar_rango');
				ok = false;
			} 
			//Chequeo que el campo letra desde sea menor en orden alfabético al campo letra hasta.
			if (this.ef('letra_desde').tiene_estado() && this.ef('letra_hasta').tiene_estado()) {
				var desde = this.ef('letra_desde').get_estado();
				var hasta = this.ef('letra_hasta').get_estado();
				letra_desde = desde.toLowerCase();
				letra_hasta = hasta.toLowerCase();
				if (letra_desde.charAt(0) > letra_hasta.charAt(0)) {
					notificacion.agregar('$msj_rango_letras');
					ok = false;		
				} 
			}
			
			var regexp = new RegExp('$codigo_validacion_regexp');
			
			//Chequeo el Código de Validación
			if (this.ef('inscripcion_cerrada').tiene_estado() && this.ef('inscripcion_cerrada').get_estado() == '$si') {
				
				if (this.ef('inscripcion_cerrada_codigo').tiene_estado()) {
				
					if (this.ef('inscripcion_cerrada_codigo').get_estado().length == $codigo_validacion_cant_digitos) {
						
						if (!regexp.test(this.ef('inscripcion_cerrada_codigo').get_estado())) {
							notificacion.agregar('$msj_codigo_validacion_solo_letras_numeros');
							ok = false;	
						}
						
					} else {
						notificacion.agregar('$msj_codigo_validacion_x_caracteres');
						ok = false;	
					}
				}
			}
			
			return ok;
		}
		
		//---- Procesamiento de EFs -----------------------------------

		{$this->objeto_js}.evt__cupo_tipo__procesar = function(es_inicial)
		{
			if (this.ef('cupo_tipo').tiene_estado() && this.ef('cupo_tipo').get_estado() == '$cupo_tipo_propuesta') {
				this.ef('cupo').resetear_estado();
				this.ef('cupo').desactivar();
			} else {
				this.ef('cupo').activar();
			}
		}

		{$this->objeto_js}.evt__cupo_minimo__procesar = function(es_inicial)
		{
			if (!es_inicial && this.ef('cupo_minimo').tiene_estado() && this.ef('cupo_minimo').get_estado() != 0) {
				this.ef('estado').set_estado('$pendiente');
			}
		}

		{$this->objeto_js}.evt__catedra__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (!seteo_catedra_original) {
					//Si la variable seteo_catedra_original es true entonces se está en el caso en que se está cambiando
					//manualmente el estado del ef a su valor original y no debe dispararse la validación ajax.
					var catedra = this.ef('catedra').get_estado();
					//Si el usuario cambió la cátedra se realiza una validación ajax para saber si existen asignaciones docentes activas.
					this.controlador.ajax('validar_cambio_catedra', [], this, this.respuesta_validacion_cambio_catedra);
				} 
				//Se resetea la variable ya que si se seteó el estado original de la catedra por cancelación del usuario, 
				//luego debo volverse a pedir confirmación ante cualquier cambio.
				seteo_catedra_original = false;
	      	} else if (this.ef('catedra').tiene_estado()) {
	      		catedra_original = this.ef('catedra').get_estado();
	      	}
		}        

        {$this->objeto_js}.evt__inscripcion_cerrada__procesar = function(es_inicial)
		{
            if (this.ef('inscripcion_cerrada').tiene_estado() && this.ef('inscripcion_cerrada').get_estado() == '$si') {
            	this.ef('inscripcion_cerrada_codigo').mostrar();
            	this.ef('inscripcion_cerrada_codigo').set_obligatorio(true);
            } else if (this.ef('inscripcion_cerrada').tiene_estado() && this.ef('inscripcion_cerrada').get_estado() == '$no') {
            	this.ef('inscripcion_cerrada_codigo').resetear_estado();
            	this.ef('inscripcion_cerrada_codigo').ocultar();
            	this.ef('inscripcion_cerrada_codigo').set_obligatorio(false);
            }
        }
        
        {$this->objeto_js}.evt__inscripcion_cerrada_codigo__procesar = function(es_inicial)
		{
            if (es_inicial) {
            	$(this.ef('inscripcion_cerrada_codigo').input()).attr('maxlength', $codigo_validacion_cant_digitos).prop('maxlength', $codigo_validacion_cant_digitos);
            	$(this.ef('inscripcion_cerrada_codigo').input()).attr('size', $codigo_validacion_cant_digitos).prop('size', $codigo_validacion_cant_digitos);
            }
        }
		
		{$this->objeto_js}.respuesta_validacion_cambio_catedra = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				mensaje_cambio = respuesta['mensaje'];
				//Si existen asignaciones docentes activas se pregunta al usuario si confirma el cambio de cátedra.
				this.confirmacion_cambio_catedra();
			} 
		}
		
		{$this->objeto_js}.confirmacion_cambio_catedra = function()
		{
			if (confirmar_edicion()) {
				//El usuario confirma que desea cambiar el valor del combo de catedra, se setea un evento del ci
				//que realiza las eliminaciones correspondientes y el submit del nuevo valor.
				this.set_evento(new evento_ei('cambio_catedra_docentes'), true); 
      		} else {
      			//El usuario no confirma el cambio de cátedra, por lo que no se realiza el submit, y el valor del ef
      			//catedra vuelve a ser el original previo al procesar del combo.
      			if (catedra_original) {
      				seteo_catedra_original = true;
      				this.ef('catedra').set_estado(catedra_original);
      			}
      			return false;
      		}
		}
		
		function confirmar_edicion(proyecto, operacion, url, es_popup) 
		{
			if (! es_popup || typeof(es_popup) == 'undefined') {
				resp = confirm(mensaje_cambio);
				if (resp) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}
		
		toba.set_callback_menu(confirmar_edicion);
		
		";
	}
}

?>