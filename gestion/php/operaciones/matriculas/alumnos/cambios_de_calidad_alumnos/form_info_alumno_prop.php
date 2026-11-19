<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Personas/Alumnos/Cambios de Calidad de Alumnos
 */


class form_info_alumno_prop extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('apellido_nombres');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('documento', '70px');
					echo '</td>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('propuesta_nombre');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('propuesta_tipo_desc', '70px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('plan_codigo');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('version', '70px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('modalidad_nombre');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('calidad_nombre', '55px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('regular_descr', '55px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('ubicacion_nombre');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
	}
}

?>