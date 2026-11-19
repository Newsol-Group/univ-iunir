<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Aulas
 */
class cn_man_aula extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'aula';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_aulas')->get_listado($filtro);
	}	
}

?>