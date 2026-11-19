<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class pant_acciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('cuadro_acciones_asociadas')->generar_html();
		echo '<hr />';
		$this->dep('filtro_acciones_disp')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_acciones_disponibles')->generar_botones();		
		$this->dep('cuadro_acciones_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_acciones_disponibles')->generar_html();
	}
}

?>