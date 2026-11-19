<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class form_inasistencia extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cant_inasistencias');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cant_justificadas');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('motivo_ausencia');
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
		if ($this->controlador()->controlador()->indica_cantidad_inasistencias()) {
			$valor_minimo = $this->controlador()->controlador()->get_minimo_inasistencias();
			$valor_maximo = $this->controlador()->controlador()->get_maximo_inasistencias();
			$msj_valor_maximo = guarani_mensajes::get('800_valor_mayor', array('Cantidad Inasistencias', $valor_maximo));
			$msj_valor_minimo = guarani_mensajes::get('800_valor_menor', array('Cantidad Inasistencias', $valor_minimo));
			$msj_justificadas = guarani_mensajes::get('800_valor_mayor', array('Cantidad Justificadas', 'Cantidad Inasistencias'));

			echo "
				
			//---- Validación general de datos -----------------------------
			
			{$this->objeto_js}.evt__validar_datos = function()
			{
				var ok = true;
				
				// La cantidad de justificadas no puede ser mayor a la cantidad de inasistencias
				if ((this.ef('cant_justificadas').tiene_estado() && this.ef('cant_inasistencias').tiene_estado())) {
					var cant_justif = this.ef('cant_justificadas').get_estado();
					var cant_inasis = this.ef('cant_inasistencias').get_estado();
					if (cant_justif > cant_inasis) {
						notificacion.agregar('$msj_justificadas');
						ok = false;
					}
				} 
				
				return ok;
			}
			
			//---- Validacion de EFs -----------------------------------
			
			{$this->objeto_js}.evt__cant_inasistencias__validar = function()
			{
				var ok = true;
				
				if (this.ef('cant_inasistencias').activo() && this.ef('cant_inasistencias').tiene_estado()) {
					var cant = this.ef('cant_inasistencias').get_estado();
					if (cant < $valor_minimo) {
						this.ef('cant_inasistencias').set_error('$msj_valor_minimo');
						ok = false;		
					}
					if (cant > $valor_maximo) {
						this.ef('cant_inasistencias').set_error('$msj_valor_maximo');
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