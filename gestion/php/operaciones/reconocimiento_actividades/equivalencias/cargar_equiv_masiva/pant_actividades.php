<?php
/**
 *
 *
 * @package     Reconocimiento de Actividades
 * @subpackage  Equivalencias
 */
class pant_actividades extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<br>';
		$this->dep('form_ml_actividades')->generar_html();
	}

}
?>