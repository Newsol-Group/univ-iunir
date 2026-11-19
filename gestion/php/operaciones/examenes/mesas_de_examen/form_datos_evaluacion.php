<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class form_datos_evaluacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('hora_inicio', '65px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('hora_fin', '50px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=1>';
					$this->generar_html_ef('evaluacion_tipo');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('escala_nota', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('descripcion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('instancias', '120px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
}
?>