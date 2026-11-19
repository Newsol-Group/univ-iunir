<?php
/**
 * 
 * @package Encuestas
 */
class pant_requisitos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('requisitos_asociados')->generar_html();
		$this->dep('filtro_requisitos')->generar_html();
		$this->dep('requisitos_disponibles')->generar_botones();
		$imagen = toba_recurso::imagen_toba('warning.gif', true);
		echo "<table class='ei-barra-sup-desc-warning'><tr><td class='ei-barra-sup-desc-img'>$imagen</td><td>Debe presionar el botón <b>Agregar Requisitos</b> para asociar los seleccionados al relevamiento.</td></tr></table>\n";  
		$this->dep('requisitos_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('requisitos_disponibles')->generar_html();
	}
}