<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('editor')->generar_html();

	}
}
?>