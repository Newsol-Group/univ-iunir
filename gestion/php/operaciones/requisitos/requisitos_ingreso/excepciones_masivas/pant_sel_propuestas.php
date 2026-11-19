<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class pant_sel_propuestas extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}

}

?>