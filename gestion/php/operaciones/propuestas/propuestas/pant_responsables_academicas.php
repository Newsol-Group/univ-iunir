<?php
/**
 * 
 * @package Propuestas Formativas
 */
class pant_responsables_academicas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('ras_asociadas')->generar_html();
		$this->dep('filtro_ras')->generar_html();
		$this->dep('ras_disponibles')->generar_botones();
		$this->dep('ras_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('ras_disponibles')->generar_html();
	}
}
?>