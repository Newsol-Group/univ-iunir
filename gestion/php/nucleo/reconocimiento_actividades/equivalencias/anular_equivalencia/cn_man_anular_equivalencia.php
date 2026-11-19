<?php
/**
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class cn_man_anular_equivalencia extends cn_manejador_entidad
{
	// Se redefine la función que obtiene las etiquetas en el cn manejador para que no se invoque al cn entidad.
	// Se recupera directamente la etiqueta de la tabla ya que no hay ninguna dependencia asociada.
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_equiv_tramite');
		} else {
			parent::etiquetas($dep);
		}
	}

	function get_listado($filtro)
	{
		$where = "sga_equiv_tramite.estado = '" . equivalencia::estado_abierto . "'";
		if (isset($filtro) && trim($filtro) != '') {
			$where .= ' AND ' . $filtro; 
		}
		return toba::consulta_php('co_equivalencias')->get_listado_tramites($where);			
	}

	function anular_tramite_equivalencia($id_tramite, $observ)
	{
		guarani::act('act_equivalencias')->anular_tramite_equivalencia($id_tramite, $observ);
	}
}

