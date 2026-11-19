<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class pant_seleccion_optativas extends guarani_pers_ei_pantalla 
{
	function generar_layout()
	{
		$this->controlador()->ev_info_generica()->generar_html();
		echo '<hr />';
		$this->dep('filtro_optativas_asociadas')->generar_html();
		$this->dep('cuadro_optativas_asociadas')->generar_html();
		echo '<hr />';
		$this->dep('filtro_optativas_a_agregar')->generar_html();
		$this->dep('cuadro_optativas_a_agregar')->generar_botones();
		$this->dep('cuadro_optativas_a_agregar')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_optativas_a_agregar')->generar_html();
	}
}
?>