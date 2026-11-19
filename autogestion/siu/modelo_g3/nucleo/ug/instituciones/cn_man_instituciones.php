<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */


class cn_man_instituciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'institucion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_instituciones')->get_listado($filtro);
	}	
}

?>