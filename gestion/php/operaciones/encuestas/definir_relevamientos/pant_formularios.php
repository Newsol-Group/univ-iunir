<?php
/**
 * 
 * @package Encuestas
 */
class pant_formularios extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('formularios_asociados')->generar_html();
		$this->dep('filtro_formularios')->generar_html();
		$this->dep('formularios_disponibles')->generar_botones();
		$this->dep('formularios_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('formularios_disponibles')->generar_html();
	}

}
?>