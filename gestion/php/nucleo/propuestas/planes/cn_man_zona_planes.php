<?php
/**
 * Manejador de Zona Planes
 *
 *
 * @package		Propuestas
 * @subpackage	Plan
 * 
 * Se extiende de un cn de Baja de Insc para la Eliminación en el caso de Planes Personalizados.
 */
class cn_man_zona_planes extends cn_baja_insc_propuesta
{
	/**
	 * Retorna un listado de los planes existentes
	 * @return recordset
	 */
	function get_listado($filtro)
	{
		return toba::consulta_php('co_planes')->get_lista_planes($filtro);
	}

}
?>