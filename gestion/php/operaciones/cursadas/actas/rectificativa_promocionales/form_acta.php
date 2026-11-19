<?php
class form_acta extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table width=100%><tr>';
		echo '<td>';
		$this->generar_html_ef('documento');
		echo '</td></tr></table>';
		echo '<table><tr>';
		if ($this->existe_ef('nro_acta')) {
			echo '<td>';
			$this->generar_html_ef('nro_acta');
			echo '</td>';
		}
		echo '</tr>';
		echo '</table>';
		echo '<table width=100%><tr><td>';
		$this->generar_html_ef('observaciones');
		echo '</td></tr>';
		echo '</table>';
	}
}
?>