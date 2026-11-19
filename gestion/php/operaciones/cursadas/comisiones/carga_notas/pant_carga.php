<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Comision
 * 
 */
class pant_carga extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('autocompletar_calcular_notas')->generar_html();
		$this->dep('form_ml_notas')->generar_html();
	}
}
?>