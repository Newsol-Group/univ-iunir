<?php
/**
 * 
 * @package Egresados
 */
class pant_propuestas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('cuadro_propuestas_asociadas')->generar_html();
		$this->dep('filtro_propuestas')->generar_html();
		$this->dep('cuadro_propuestas_disponibles')->generar_botones();
		$this->dep('cuadro_propuestas_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_propuestas_disponibles')->generar_html();
	}

}
?>