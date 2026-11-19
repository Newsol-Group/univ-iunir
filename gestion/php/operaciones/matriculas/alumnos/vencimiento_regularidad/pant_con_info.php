<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class pant_con_info extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_alumno()->generar_html();
		$this->controlador()->ev_info_actividad()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}
}
?>