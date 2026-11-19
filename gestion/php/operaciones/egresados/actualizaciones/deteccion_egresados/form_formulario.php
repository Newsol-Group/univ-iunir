<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class form_formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width="100%">';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('propuesta');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('plan');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('plan_version');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('anio_desde');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('anio_hasta', '65px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('certificado_tipo');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('certificado');
			echo '</td>';
		echo '</tr>';
		echo '</table>';
	}
}
?>