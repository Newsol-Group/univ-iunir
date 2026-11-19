<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Planificacion
 */
class pant_seleccion_subcomision extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_comision()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>