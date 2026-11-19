<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class pant_informacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo 'El cambio de Plan/Plan-Versión ha concluido satisfactoriamente.';
		echo '<br> <br>';
		$this->controlador()->ev_info_resumen()->generar_html();
		echo '<br> <br>';
	}
}
?>