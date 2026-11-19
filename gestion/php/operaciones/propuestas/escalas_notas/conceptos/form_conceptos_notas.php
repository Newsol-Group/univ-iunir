<?php
/**
 *
 * @package Propuestas
 * @subpackage Escalas de Notas
 */
class form_conceptos_notas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table><tr><td>';
		$this->generar_html_ef('nombre');
		echo '</td><td>';
		$this->generar_html_ef('activo', '60px');
		echo '</td></tr></table><table><tr><td>';
		$this->generar_html_ef('descripcion');
		echo '</td></tr></table>';
	}
}
?>