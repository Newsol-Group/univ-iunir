<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class pant_imprimir_rechazar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		parent::generar_layout();
	}
}
?>