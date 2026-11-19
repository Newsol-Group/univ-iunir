<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		// ev_info
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';				
		$this->dependencia('filtro_req_a_configurar')->generar_html();
		echo '<hr />';				
		// Formulario que permite autocompletar la configuración de los Requisitos asignados para la operación.
		$this->dependencia('form_autocompletar')->generar_html();
		// Se muestran los Requisitos asignados	para la Operación.
		$this->dep('form_ml_conf_requisitos')->generar_html();		
	}
}
?>