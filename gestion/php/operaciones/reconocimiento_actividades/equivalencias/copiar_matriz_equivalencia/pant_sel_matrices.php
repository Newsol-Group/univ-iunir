<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class pant_sel_matrices extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_matrices_hacia()->generar_html();
		$this->dep('cuadro_matrices_hacia')->generar_html();
		echo '<br>';
		echo '<hr />';
		$this->controlador()->ev_info_matrices_desde()->generar_html();
		$this->dep('cuadro_matrices_desde')->generar_html();
	}
}
?>