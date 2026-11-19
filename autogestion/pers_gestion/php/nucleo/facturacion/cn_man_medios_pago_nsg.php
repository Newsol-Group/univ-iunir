<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */


class cn_man_medios_pago_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'mediopago';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_medios_pago($filtro);
			
	}
}

?>