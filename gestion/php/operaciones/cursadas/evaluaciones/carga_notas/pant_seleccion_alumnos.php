<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class pant_seleccion_alumnos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_evaluacion()->generar_html();
		$this->dep('alumnos')->generar_html();
	}
}
?>