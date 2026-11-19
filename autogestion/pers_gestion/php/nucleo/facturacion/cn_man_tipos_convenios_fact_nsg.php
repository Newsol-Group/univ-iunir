<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */


class cn_man_tipos_convenios_fact_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipoconveniofact';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_tipos_convenios_fact($filtro);
			
	}
}

?>