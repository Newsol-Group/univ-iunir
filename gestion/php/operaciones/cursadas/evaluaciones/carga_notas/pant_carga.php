<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class pant_carga extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_evaluacion()->generar_html();
		echo '<hr />';
		$this->dep('form_autocompletar')->generar_html();
		echo '<hr />';
		$this->dep('form_ml_notas')->generar_html();
	}
}
?>