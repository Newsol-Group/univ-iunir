<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo $this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>