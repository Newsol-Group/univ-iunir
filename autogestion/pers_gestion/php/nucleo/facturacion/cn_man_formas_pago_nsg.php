<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */


class cn_man_formas_pago_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'formapago';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_formas_pago($filtro);
			
	}
}

?>