<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */


class cn_man_bonif_x_convenio_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'bonifxconvenio';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_listado_bonif_x_convenio($filtro);
	}	
}

?>