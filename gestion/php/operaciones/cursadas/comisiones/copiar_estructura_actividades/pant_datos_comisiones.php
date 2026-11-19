<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pant_datos_comisiones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_origen()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}

}
?>