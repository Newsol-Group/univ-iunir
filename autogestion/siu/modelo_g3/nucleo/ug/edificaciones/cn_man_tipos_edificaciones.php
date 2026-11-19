<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */


class cn_man_tipos_edificaciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipoedificacion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_edificaciones')->get_listado_tipos($filtro);
	}	
}

?>