<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Departamentos
 */
class cn_man_departamento extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'departamento';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_departamentos')->get_listado($filtro);
	}	
}
?>