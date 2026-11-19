<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */


class cn_man_convenios_tipo extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'conveniotipo';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_convenios')->get_listado_tipos($filtro);
			
	}
}

?>