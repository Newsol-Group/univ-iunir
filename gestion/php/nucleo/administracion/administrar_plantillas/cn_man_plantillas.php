<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Administrar Plantillas
 * 
 */
class cn_man_plantillas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'plantilla';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_constancias')->get_datos_plantillas($filtro);			
	}

}
?>