<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class pant_seleccion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('cuadro')->generar_html();
	}

}
?>