<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class pant_seleccion_comision extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_alumno()->generar_html();
		echo '<hr />';
		parent::generar_layout();
	}

}
?>