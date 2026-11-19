<?php
/**
 *
 *
 * @package		Unidades de Gestión
 * @subpackage  Espacios
 * 
 */
class pant_asignaciones_espacios_masiva extends guarani_pers_ei_pantalla 
{
    function generar_layout() 
	{
		if($this->existe_dependencia('formulario_asignadas')) {
			$this->dep('formulario_asignadas')->generar_html();
		} else {
			$this->dep('formulario_pendientes')->generar_html();
		}
	}
}
?>