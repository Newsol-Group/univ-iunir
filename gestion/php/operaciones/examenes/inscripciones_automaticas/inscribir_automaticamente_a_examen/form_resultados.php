<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Inscripciones automáticas
 * 
 */
class form_resultados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cant_com_procesadas');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('cant_insc_procesadas');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cant_insc_aceptadas');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cant_insc_pendientes');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('cant_insc_rechazadas');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
}
?>