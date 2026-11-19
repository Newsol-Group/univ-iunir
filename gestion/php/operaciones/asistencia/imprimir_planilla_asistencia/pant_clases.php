<?php
/**
 *
 *
 * @package 	Asistencia
 * @subpackage 	Imprimir planilla de asistencia
 * 
 */
class pant_clases extends guarani_pers_ei_pantalla 
{
	function generar_layout()
	{
		$this->controlador()->ev_info_comision()->generar_html();
		echo '<hr />';
		$this->dep('cuadro_selec')->generar_html();
		echo '<hr />';
		$this->dep('form_fecha_clases')->generar_html();
	}
}
?>