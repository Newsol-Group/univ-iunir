<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */
class form_turno_destino extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="60%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('anio_academico_destino');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('turno_destino', '50px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('ubicacion_nombre', '60px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('criterio');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('mesas_destino', '175px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="3">';
					$this->generar_html_ef('criterio_dia_no_habil','200px');
					echo '</td>';
			echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}	
}
?>