<?php
/**
 * 
 * @package Encuestas
 */
class pant_propuestas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('propuestas_asociadas')->generar_html();
		$this->dep('filtro_propuestas')->generar_html();
		$this->dep('propuestas_disponibles')->generar_botones();
		$this->dep('propuestas_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('propuestas_disponibles')->generar_html();
	}

}
?>