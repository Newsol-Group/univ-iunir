<?php
/**
 * 
 * 
 * @package		Cursadas
 * @subpackage	Cursos
 * 
 */
class cn_completar_pagos_pendientes extends cn_manejador_entidad
{
	function get_listado($where = '')
	{
		if ($where) {
			$where .= ' AND';
		}
		$where .= ' sga_insc_cursada.sq_token is not null';
		$where .= ' AND sga_insc_cursada.interfaz = ' . comunes::interfaz_gestion;
		$where .= ' AND sga_insc_cursada.estado = ' . quote(inscripcion_cursada::estado_pendiente);
		$datos = toba::consulta_php('co_inscripciones_cursadas')->get_listado($where);

		// Controlar que cada token tenga el estado nuevo
		foreach (array_keys($datos) as $key) {
			$query = array(
				"token" => $datos[$key]['sq_token'],
			);
			$solicitud_consumo_externo = cobro::get_solicitudes_consumos_externos($query);
			$solicitud_consumo_externo = current($solicitud_consumo_externo);
			// Si el consumo externo es no NUEVO => ya fue procesado y no se necesita que se pague
			if (!$solicitud_consumo_externo['codigo_procesamiento'] == cobro::CODIGO_PROCESAMIENTO_CONSUMO_EXTERNO_NUEVO) {
				unset($datos[$key]);
			}
		}
		return $datos;
	}

}