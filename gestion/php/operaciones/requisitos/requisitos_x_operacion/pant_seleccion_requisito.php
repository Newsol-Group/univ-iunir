<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class pant_seleccion_requisito extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('cuadro')->generar_html();
	}
}
?>