<?php
/**
 *	Manejador de Espacios
 *
 * @package Unidad de Gestión
 * @subpackage Espacios
 */
class cn_man_espacios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'espacio';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_espacios')->get_listado($filtro);
	}	
}

?>