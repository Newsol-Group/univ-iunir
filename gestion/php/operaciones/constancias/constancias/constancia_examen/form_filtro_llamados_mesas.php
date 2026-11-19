<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class form_filtro_llamados_mesas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('anio_academico');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('turno_examen');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('actividad');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="60%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha_desde');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_hasta', '75px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}	
}
?>