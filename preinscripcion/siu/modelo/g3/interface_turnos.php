<?php

namespace siu\modelo\g3;

interface interface_turnos
{
	/**
	 * Determina si existen turnos disponibles (no ocupados, vigentes) para una
	 * Responsable Académica (RA) dada
	 * 
	 * @param int $ra
	 * @return boolean
	 */
	function hay_turnos_disponibles($ra);
	
	/**
	 * Obtiene las fechas en las que exiten turnos disponibles
	 * 
	 * @param int $ra
	 * @return array
	 */
	function get_fechas_turnos_disponibles($ra);
	
	/**
	 * Obtiene la primera fecha en la que hay un turno disponible, o el string 
	 * vacío en caso de no existir turnos disponibles
	 * 
	 * @param int $ra
	 * @return string
	 */
	function get_fecha_minima($ra);
	
	/**
	 * Obtiene la última fecha en la que hay un turno disponible, o el string 
	 * vacío en caso de no existir turnos disponibles
	 * 
	 * @param int $ra
	 * @return string
	 */
	function get_fecha_maxima($ra);
	
	/**
	 * Obtiene las franjas horarias en las que hay turnos disponibles para una 
	 * RA dada en una determinada fecha
	 * 
	 * @param string $fecha
	 * @param int $ra
	 * @return array
	 */
	function get_franjas_horarias_disponibles($fecha, $ra);
	
	/**
	 * Asigna un turno según la solicitud (fecha, rango horario, RA)
	 * 
	 * @param int $id_preinscripcion
	 * @param int $ra
	 * @param array $datos_turno
	 * @throws error_preinscripcion
	 */
	function reservar_turno($id_preinscripcion, $ra, $datos_turno);
	
	/**
	 * Deja nuevamente disponible un turno solicitado por un aspirante, para una
	 * RA dada.
	 * 
	 * @param int $id_preinscripcion
	 * @param int $ra
	 */
	function devolver_turno($id_preinscripcion, $ra);
	
	/**
	 * Determina qué turno de presentación(en caso que haya sido solicitado) de
	 * un aspirante en una RA dada.
	 * 
	 * @param int $id_preinscripcion
	 * @param int $ra
	 * @return array
	 */
	function get_turno_presentacion($id_preinscripcion, $ra);
	
	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id = array('ra' => x, 'fecha' => y)
	 * @return array
	 */
	function test($id);
	
}
?>