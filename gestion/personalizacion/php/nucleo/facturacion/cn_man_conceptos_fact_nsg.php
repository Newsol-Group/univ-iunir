<?php
/**
 *
 *
 * @package Facturación
 * @subpackage Conceptos de Facturación
 */


class cn_man_conceptos_fact_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'conceptosfact';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_conceptos_fact($filtro);
			
	}
}

?>