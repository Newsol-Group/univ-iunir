<?php
/**
 *	Manejador de Tipos de Instituciones
 *
 * @package Unidad de Gestión
 * @subpackage Instituciones
 */
class cn_man_tipos_instituciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipoinstitucion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_instituciones')->get_listado_tipos($filtro);
	}	
}

?>