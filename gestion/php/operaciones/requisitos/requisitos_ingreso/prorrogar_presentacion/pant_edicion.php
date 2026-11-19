<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->controlador()->ev_info_fecha_cierre()->generar_html();
		echo '<br />';
		$this->dep('requisitos')->generar_html();
	}

}

?>