<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */
class form_turno_origen extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="60%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('anio_academico_origen');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('turno_origen', '50px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('ubicacion');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}	
}

?>