<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */


class cn_man_ubicaciones_tipos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'ubicaciontipo';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_ubicaciones')->get_listado_tipos($filtro);
			
	}	
}

?>