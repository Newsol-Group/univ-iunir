<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class form_justificacion extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('motivo_ausencia');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('estado_justificacion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cant_inasistencias');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cant_justificadas');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
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
		if ($this->controlador()->controlador()->indica_cantidad_inasistencias()) {
			$valor_minimo = $this->controlador()->controlador()->get_minimo_justificadas();
			$msj_valor_maximo = guarani_mensajes::get('800_valor_mayor', array('Cantidad Justificadas', 'Cantidad Inasistencias'));
			$msj_valor_minimo = guarani_mensajes::get('800_valor_menor', array('Cantidad Justificadas', $valor_minimo));
			
			/**
			 * En caso de que se cambie la cátedra elegida y existan asignaciones de docentes activas (sin fecha de 
			 * finalización), se deberá mostrar el mensaje 800CUR_docentes_catedra, ya que en caso de que el usuario confirme,
			 * se eliminarán todos los docentes de la cátedra asignados a la comisión y sus correspondientes dedicaciones 
			 * horarias.
			 */
	
			echo "
				
			{$this->objeto_js}.evt__estado_justificacion__procesar = function(es_inicial)
				{
					if (this.ef('estado_justificacion').tiene_estado()) {
						if (this.ef('estado_justificacion').get_estado() == '" . asistencia::estado_ausencia_rechazado . "')  {
							this.ef('cant_justificadas').set_estado(0);
							this.ef('cant_justificadas').set_solo_lectura(true);
						} else {
							this.ef('cant_justificadas').set_solo_lectura(false);
						}
					}
				}
		
			//---- Validación general de datos -----------------------------
			
			{$this->objeto_js}.evt__validar_datos = function()
			{
				var ok = true;
				
				// La cantidad de justificadas no puede ser mayor a la cantidad de inasistencias
				if ((this.ef('cant_justificadas').tiene_estado() && this.ef('cant_inasistencias').tiene_estado())) {
					var cant_justif = this.ef('cant_justificadas').get_estado();
					var cant_inasis = this.ef('cant_inasistencias').get_estado();
					if (cant_justif > cant_inasis) {
						notificacion.agregar('$msj_valor_maximo');
						ok = false;
					}
				} 
				
				return ok;
			}
			
			//---- Validacion de EFs -----------------------------------
			
			{$this->objeto_js}.evt__cant_justificadas__validar = function()
			{
				var ok = true;
				
				if (this.ef('cant_justificadas').tiene_estado() && (this.ef('estado_justificacion').get_estado() != '" . asistencia::estado_ausencia_rechazado . "')) {
					var cant = this.ef('cant_justificadas').get_estado();
					if (cant < '$valor_minimo') {
						this.ef('cant_justificadas').set_error('$msj_valor_minimo');
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