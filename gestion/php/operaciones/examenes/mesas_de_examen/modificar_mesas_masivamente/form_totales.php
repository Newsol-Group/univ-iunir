<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Modificar Mesas Masivamente
 */
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>'; 
				echo '<tr>';
					echo '<td>'; 
					$this->generar_html_ef('cant_mesas_modificadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_mesas_modificadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>'; 
                        $this->generar_html_ef('cant_mesas_no_modificadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_mesas_no_modificadas', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';		
	}
	
	function extender_objeto_js () {

		echo "

		{$this->objeto_js}.evt__rep_mesas_modificadas = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas_modificadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas_modificadas);
			return false;
		}

		{$this->objeto_js}.evt__rep_mesas_no_modificadas = function()
		{
			var params = [];
			params['evento'] = 'rep_mesas_no_modificadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte_mesas_no_modificadas);
			return false;
		}
		
		{$this->objeto_js}.respuesta_reporte_mesas_modificadas = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'950','height':'950'});
		}

		{$this->objeto_js}.respuesta_reporte_mesas_no_modificadas = function(respuesta)
		{
			abrir_popup('reporte',respuesta, {'width':'950','height':'950'});
		}
		
		";
	}
}
?>