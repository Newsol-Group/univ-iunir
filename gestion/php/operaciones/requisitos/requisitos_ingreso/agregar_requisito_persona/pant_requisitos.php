<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class pant_requisitos extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('asignados')->generar_html();
		echo '<hr />';
		$this->dep('filtro_requisitos')->generar_html();
		echo '<hr />';
		$this->dep('disponibles')->generar_botones();		
		$this->dep('disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('disponibles')->generar_html();
	}
	
}
?>