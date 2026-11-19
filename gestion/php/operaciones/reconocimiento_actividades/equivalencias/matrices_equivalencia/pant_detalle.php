<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class pant_detalle extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>