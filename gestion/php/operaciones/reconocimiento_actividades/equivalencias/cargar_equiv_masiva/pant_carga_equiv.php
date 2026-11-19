<?php
/**
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 * 
 */
class pant_carga_equiv extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('form_autocompletar')->generar_html();
		$this->dep('form_ml_notas')->generar_html();
	}

}

?>