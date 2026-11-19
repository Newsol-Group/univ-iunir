<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Cargos Institucionales
 * 
 */
class cn_man_cargos_institucionales extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'cargo_institucional';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_autoridades_cargos')->get_listado_cargos($filtro);			
	}

}
?>