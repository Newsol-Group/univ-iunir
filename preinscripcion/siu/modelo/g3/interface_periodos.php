<?php

namespace siu\modelo\g3;

interface interface_periodos
{
	/**
	 * Determina si existe un período de inscripción vigente de tipos 
	 * "Preinscripción" o "Todos"
	 * 
	 * @return boolean
	 */
	function existe_periodo_abierto();
	
	/**
	 * Obtiene el período de Preinscripción vigente (periodo_insc) para una propuesta dada.
	 * Da prioridad a los períodos de tipo Preinscripción (por encima de los de tipo "Todos").
	 * En caso de haber más de uno, decide por el que comience primero.
	 * 
	 * @param int $propuesta
     * @param int $ubicacion
	 * @param $modalidad
	 * @return integer $periodo_insc
	 */
	function get_periodo_preinscripcion($propuesta, $ubicacion, $modalidad);
	
	/**
	 * Determina si un período de Preinscripción dado se encuentra vigente
	 * 
	 * @param int $periodo_preinscripcion
	 * @return boolean
	 */
	function periodo_vigente($periodo_preinscripcion);

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @return array
	 */
	function test($id);
	
}
?>