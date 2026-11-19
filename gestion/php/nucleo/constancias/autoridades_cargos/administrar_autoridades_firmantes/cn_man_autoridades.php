<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Autoridades Firmantes
 * 
 */
class cn_man_autoridades extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'autoridad';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_autoridades_cargos')->get_listado_autoridades($filtro);			
	}
}
?>