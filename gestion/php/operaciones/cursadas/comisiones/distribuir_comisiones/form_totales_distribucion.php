<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class form_totales_distribucion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('alumnos_distribuidos');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_alumnos_distribuidos', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('alumnos_no_distribuidos');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_alumnos_no_distribuidos', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js ()
	{
		echo "
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}

		{$this->objeto_js}.evt__rep_alumnos_distribuidos = function()
		{
			var params = [];
			params['evento'] = 'rep_alumnos_distribuidos';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_alumnos_no_distribuidos = function()
		{
			var params = [];
			params['evento'] = 'rep_alumnos_no_distribuidos';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta);
		}
		";
	}
}
?>