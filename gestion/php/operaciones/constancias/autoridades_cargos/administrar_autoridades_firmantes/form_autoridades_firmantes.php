<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Autoridades Firmantes
 * 
 */
class form_autoridades_firmantes extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tr>';
				echo '<td colspan=2>';
				$this->generar_html_ef('persona');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('titulo_persona');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('cargo');
				echo '</td>';
			echo '</tr></table>';
			echo '<fieldset><legend>Firma digital</legend><table width=100%><tr><td>';
				$this->generar_html_ef('firma_digital', '80px');
				echo '</td><td>';
				$this->generar_html_ef('firma_digital_analitico', '80px');
				echo '</td><td>';
				$this->generar_html_ef('firma_digital_diploma', '80px');
				echo '</td><td>';
				$this->generar_html_ef('firma_egreso', '80px');
			echo '</td></tr></table></fieldset>';
			echo '<table><tr><td>';
				$this->generar_html_ef('activo');
			echo '</td></tr>';
		echo '</table>';
	}
}