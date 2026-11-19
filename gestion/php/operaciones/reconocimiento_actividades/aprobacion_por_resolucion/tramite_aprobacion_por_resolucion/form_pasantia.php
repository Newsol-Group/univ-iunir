<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class form_pasantia extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('responsable');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('email');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('institucion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('sector');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('descripcion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('modo_evaluacion');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('nota', '60px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
}
?>