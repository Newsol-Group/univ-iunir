<?php
/**
 *
 *
 * @package 	Calendario
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_tipos_clases extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipo_clase';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_clases')->get_tipos_clase($filtro);			
	}
	
}
?>