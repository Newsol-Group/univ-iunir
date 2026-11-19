<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 */
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td colspan="2">'; 
					$this->generar_html_ef('procesados');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('perdieron_regularidad');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_perdieron_regularidad', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('recuperaron_regularidad');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_recuperaron_regularidad', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js () {

		echo "

		{$this->objeto_js}.evt__rep_perdieron_regularidad = function()
		{
			var params = [];
			params['evento'] = 'rep_perdieron_regularidad';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_recuperaron_regularidad = function()
		{
			var params = [];
			params['evento'] = 'rep_recuperaron_regularidad';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}
		
		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte', respuesta, {'width':'950','height':'600'});
		}
		
		{$this->objeto_js}.hay_cambios = function(ef_actual) {
			return false;
		}
		
		";
	}
}
?>