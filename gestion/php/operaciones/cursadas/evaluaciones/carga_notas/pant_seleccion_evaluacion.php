<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class pant_seleccion_evaluacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_comision()->generar_html();
		echo '<hr />';
		$this->dep('cuadro_evaluacion')->generar_html();
	}
}
?>