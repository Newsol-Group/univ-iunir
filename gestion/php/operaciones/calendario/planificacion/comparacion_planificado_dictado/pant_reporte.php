<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class pant_reporte extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->controlador()->ev_info_clase()->generar_html();
		echo '<br />';
		parent::generar_layout();
	}
}
?>