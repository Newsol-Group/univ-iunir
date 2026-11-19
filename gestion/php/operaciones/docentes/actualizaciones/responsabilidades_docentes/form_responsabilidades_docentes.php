<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Autoridades Firmantes
 * 
 */
class form_responsabilidades_docentes extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('descripcion');
					echo '</td>';
				echo '</tr>';
				echo '</table><table>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('firma');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('orden_listado', '110px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('orden_acta', '110px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
}
?>