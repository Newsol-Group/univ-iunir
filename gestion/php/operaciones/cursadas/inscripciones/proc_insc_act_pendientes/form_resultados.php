<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class form_resultados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('aceptadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_aceptadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('con_observaciones');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_con_observaciones', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('a_rechazar');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rechazar', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js() 
	{
		echo "
		{$this->objeto_js}.evt__rep_aceptadas = function()
		{
			var params = [];
			params['evento'] = 'rep_aceptadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_con_observaciones = function()
		{
			var params = [];
			params['evento'] = 'rep_con_observaciones';
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