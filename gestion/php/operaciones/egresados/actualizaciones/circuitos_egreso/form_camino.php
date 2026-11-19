<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class form_camino extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table><tr><td>";
		$this->generar_html_ef('estado_inicial');
		echo "</td></tr><tr><td>";
		$this->generar_html_ef('accion');
		echo "</td></tr><tr><td>";
		$this->generar_html_ef('estado_final');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('recalcular_histacad', '200px');
		echo "</td><td>";
		$this->generar_html_ef('controlar_pago', '110px');
		echo "</td><td>";
		$this->generar_html_ef('enviar_aviso_alumno', '160px');
		echo "</td></tr></table><br>";
	}
}
?>