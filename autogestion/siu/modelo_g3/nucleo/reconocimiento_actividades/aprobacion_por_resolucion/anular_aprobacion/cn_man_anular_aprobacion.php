<?php
/**
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class cn_man_anular_aprobacion extends cn_manejador_entidad
{
	// Se redefine la función que obtiene las etiquetas en el cn manejador para que no se invoque al cn entidad.
	// Se recupera directamente la etiqueta de la tabla ya que no hay ninguna dependencia asociada.
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_reconocimiento');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$where = "sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_abierto."'";
		if (isset($filtro) && trim($filtro) != '') {
			$where .= ' AND '.$filtro; 
		}
		return toba::consulta_php('co_aprobacion_por_resolucion')->get_listado($where);
	}

	function anular_tramite_aprobacion($nro_tramite, $observaciones)
	{
		guarani::act('act_aprobacion_por_resolucion')->anular_tramite_aprobacion($nro_tramite, $observaciones);
	}
	
}
?>