<?php
class form_totales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table>';
		echo'<tbody>';
		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('cant_actas');
		echo '</td>';
		echo '<td>';
		$this->generar_boton('rep_cantidad_actas', false);
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}
}