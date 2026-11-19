<?php
/**
 * 
 * @package Propuestas Formativas
 */
class pant_seleccion_plan extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_propuesta()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}
}
?>