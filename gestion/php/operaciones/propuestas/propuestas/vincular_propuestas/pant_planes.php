<?php
/**
 * 
 * @package Propuestas Formativas
 */
class pant_planes extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('planes_asociados')->generar_html();
		$this->dep('filtro_planes')->generar_html();
		$this->dep('planes_disponibles')->generar_botones();
		$this->dep('planes_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('planes_disponibles')->generar_html();
	}
}
?>