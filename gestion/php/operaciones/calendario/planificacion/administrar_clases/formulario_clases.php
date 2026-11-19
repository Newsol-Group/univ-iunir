<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Planificacion
 */
class formulario_clases extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<fieldset title="Banda Horaria"><legend>Banda Horaria</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('utilizar_bh_definida', '180px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('banda_horaria', '180px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('hora_inicio');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('hora_finalizacion');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cantidad_horas_dictadas');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('tipo_clase');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('id_tramo');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('valido');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('motivo_invalidacion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('modalidad');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('asistencia', '230px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('tema_planificado');
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
		$si = comunes::si;
		$msj_valor_minimo = guarani_mensajes::get('800_valor_menor', array('Hora Fin', 'Hora Inicio'));
		$es_edicion = $this->controlador()->controlador()->hay_clase_seleccionada();
			
		echo "
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__valido__procesar = function(es_inicial)
		{
			var valido = this.ef('valido').get_estado();
			if (valido == 0) {
				this.ef('motivo_invalidacion').activar();
				this.ef('motivo_invalidacion').set_obligatorio(true);
			} else {
				this.ef('motivo_invalidacion').resetear_estado();
				this.ef('motivo_invalidacion').desactivar();
				this.ef('motivo_invalidacion').set_obligatorio(false);
			}
		}
		
		{$this->objeto_js}.evt__utilizar_bh_definida__procesar = function(es_inicial)
		{
			if (! '$es_edicion') {
				if (this.ef('utilizar_bh_definida').chequeado()) {
					this.ef('banda_horaria').set_obligatorio(true);
					this.ef('banda_horaria').set_solo_lectura(false);
				} else {
					this.ef('banda_horaria').resetear_estado();
					this.ef('banda_horaria').set_obligatorio(false);
					this.ef('banda_horaria').set_solo_lectura(true);
				}
			}
		}
		
		/**
		 * Cuando se selecciona una Banda Horaria existente, se setean por defecto los valores de los campos 
		 * relacionados, que no podrán ser editados por el usuario.
		 */
		{$this->objeto_js}.evt__banda_horaria__procesar = function(es_inicial)
		{
			if (! '$es_edicion') {
				if (this.ef('banda_horaria').tiene_estado()) {
					var params = [];
					params['bh'] = this.ef('banda_horaria').get_estado();
					this.controlador.ajax('datos_banda_horaria_existente', params, this, this.respuesta_set_datos_bh);
					this.ef('hora_inicio').desactivar();
					this.ef('hora_finalizacion').desactivar();
					this.ef('cantidad_horas_dictadas').desactivar();
					this.ef('tipo_clase').desactivar();
				} else {
					this.ef('hora_inicio').resetear_estado();
					this.ef('hora_inicio').activar();
					this.ef('hora_finalizacion').resetear_estado();
					this.ef('hora_finalizacion').activar();
					this.ef('cantidad_horas_dictadas').resetear_estado();
					this.ef('cantidad_horas_dictadas').activar();
					this.ef('tipo_clase').resetear_estado();
					this.ef('tipo_clase').activar();
				}
			} 
		}
			
		{$this->objeto_js}.respuesta_set_datos_bh = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				this.ef('hora_inicio').set_estado(respuesta['hora_inicio']);
				this.ef('hora_finalizacion').set_estado(respuesta['hora_finalizacion']);
				this.ef('cantidad_horas_dictadas').set_estado(respuesta['cantidad_horas']);
				this.ef('tipo_clase').set_estado(respuesta['tipo_clase']);
				this.ef('modalidad').set_estado(respuesta['modalidad']);
				this.ef('asistencia').set_estado(respuesta['asistencia']);
			}
		}
		
		";
		
		/**
		 * Las validaciones se realizan sólo para el caso del Alta, ya que en la modificación los campos sobre los 
		 * cuales se realizan las mismas no son editables.
		 */
		if (!$this->controlador()->controlador()->hay_clase_seleccionada()) {
			
			echo "
				
			//---- Validación general de datos -----------------------------
			
			{$this->objeto_js}.evt__validar_datos = function()
			{
				var ok = true;
				
				// La hora de finalizacion debe ser mayor a la hora de inicio de la Clase.
				if ((this.ef('hora_inicio').tiene_estado() && this.ef('hora_finalizacion').tiene_estado())) {
					var hr_inicio = this.ef('hora_inicio').hora();
					var hr_fin = this.ef('hora_finalizacion').hora();
					if (hr_fin < hr_inicio) {
						notificacion.agregar('$msj_valor_minimo');
						ok = false;
					}
				} 
				
				return ok;
			}
			
			";
		}
	}
}
?>