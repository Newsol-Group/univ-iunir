<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */


class cn_man_formas_pago_x_propuesta_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'formapagoxpropuesta';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_formas_pago_x_propuesta($filtro);
	}	
}

?>