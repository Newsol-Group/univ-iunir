<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */


class cn_man_tipos_beneficios_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipobeneficio';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_tipos_beneficio($filtro);
			
	}
}

?>