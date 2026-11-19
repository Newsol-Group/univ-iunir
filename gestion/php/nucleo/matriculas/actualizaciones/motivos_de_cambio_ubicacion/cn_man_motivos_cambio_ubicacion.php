<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_man_motivos_cambio_ubicacion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_de_cambio_ubicacion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_cambio_ubicacion')->get_listado($filtro);
	}
}
?>