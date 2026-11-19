<?php
/**
 * 
 * @package Encuestas
 */
class pant_actividades extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('actividades_asociadas')->generar_html();
		$this->dep('filtro_actividades')->generar_html();
		$this->dep('actividades_disponibles')->generar_botones();
		$this->dep('actividades_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('actividades_disponibles')->generar_html();
	}

}
?>