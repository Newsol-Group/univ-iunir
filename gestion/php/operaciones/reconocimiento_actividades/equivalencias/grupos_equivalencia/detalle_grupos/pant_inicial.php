<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Detalle Grupos Equivalencias
 */
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_datos	()->generar_html();
		echo '<hr />';
		$this->dep('cuadro_actividades_grupos')->generar_html();
	}

}

?>