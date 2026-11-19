<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class form_acta extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width=100%><tr>';
		if ($this->existe_ef('documento')) {
			echo '<td colspan=2>';
			$this->generar_html_ef('documento');
			echo '</td>';
		}
		if ($this->existe_ef('nro_acta')) {
			echo '<td>';
			$this->generar_html_ef('nro_acta', '110px');
			echo '</td>';
		}
		echo '<table width=100%><tr>';
		echo '<td>';
		$this->generar_html_ef('observaciones');
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

}
?>