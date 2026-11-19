<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Inscripciones
 */
class cn_man_baja_insc_propuesta extends cn_baja_insc_propuesta
{
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_propuestas_aspira');
		} else {
			parent::etiquetas($dep);
		}
	}

	/**
	 * Retorna los datos de inscripciones a propuesta en estado ACEPTADA o PENDIENTE.
	 *
	 * @param str $filtro
	 * @return array
	 */
	function get_listado($filtro)
	{
		if (! empty($filtro)) {
			$filtro .= " AND ";
		}
		$filtro .= "sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."', '".propuesta::inscripcion_pendiente."')";
		return guarani::co('co_inscripciones')->get_alumnos_inscriptos($filtro);
	}
}
?>