<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('form_libro')->generar_html();
		echo '<hr />';
		echo '<center>';
		$this->dep('form_ml_folios')->generar_html();
		echo '</center>';
	}
}
?>