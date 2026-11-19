<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class pant_invalidacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('formulario')->generar_html();
		echo '<hr />';
		echo '<div style="font-size:14px; font-weight:bold; text-decoration:underline; ">Historia Académica</div>';
		$this->dep('cuadro_aprobadas')->generar_html();
		$this->dep('cuadro_regularizadas')->generar_html();
	}
}
?>