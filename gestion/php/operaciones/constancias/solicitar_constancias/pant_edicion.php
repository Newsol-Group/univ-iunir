<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}
}
?>