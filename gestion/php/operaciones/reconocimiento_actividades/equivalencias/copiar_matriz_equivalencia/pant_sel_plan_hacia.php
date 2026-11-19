<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class pant_sel_plan_hacia extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_plan_desde()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>