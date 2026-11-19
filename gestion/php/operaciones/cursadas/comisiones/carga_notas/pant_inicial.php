<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Comision
 * 
 */
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('alumnos')->generar_html();
	}
}
?>