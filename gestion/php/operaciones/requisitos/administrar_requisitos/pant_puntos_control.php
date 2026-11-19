<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class pant_puntos_control extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('ptos_control_asoc')->generar_html();
		echo '<hr />';
		$this->dep('filtro_ptos_control')->generar_html();
		echo '<hr />';
		$this->dep('ptos_control_disp')->generar_botones();		
		$this->dep('ptos_control_disp')->evento('agregar')->set_en_botonera(false);
		$this->dep('ptos_control_disp')->generar_html();
	}
}

?>