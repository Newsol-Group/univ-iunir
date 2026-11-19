<?php
/**
 *
 * @package Cobro
 * @subpackage Cobro
 */
class co_cobros_nsg extends co_cobros
{

	/**
	 * Retorna el listado de nombres de Alumnos que figuran como morosos de alguna Comisión.
	 * 
	 * @param string $where		filtro sobre los alumnos a recuperar.
	 * @return 
	 */
	function get_datos_alumnos_morosos($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT	int_sq_morosos.id_moroso,
						int_sq_morosos.alumno,
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS alumno_nombre,
						int_sq_morosos.nro_transaccion,
						int_sq_morosos.fecha
				FROM	int_sq_morosos
				JOIN	sga_alumnos ON sga_alumnos.alumno = int_sq_morosos.alumno
				JOIN	mdp_personas ON mdp_personas.persona = sga_alumnos.persona
				$where
		;";
		
		return guarani::db()->consultar($sql);
	}

	/**
	 * Condicion financiera sobre sga_alumnos. La excepcion requiere una
	 * incorporacion persistida en la misma evaluacion, incluso sin nota.
	 * Sin evaluacion se conserva el control habitual para otros consumidores.
	 */
	function get_condicion_habilitados_evaluacion($evaluacion = null)
	{
		$condicion = 'NOT EXISTS (
			SELECT 1 FROM int_sq_morosos
			WHERE int_sq_morosos.alumno = sga_alumnos.alumno
		)';
		if ($evaluacion !== null && $evaluacion !== '') {
			$evaluacion = toba::db()->quote($evaluacion);
			$condicion .= " OR EXISTS (
				SELECT 1 FROM sga_eval_detalle
				WHERE sga_eval_detalle.evaluacion = $evaluacion
				AND sga_eval_detalle.alumno = sga_alumnos.alumno
			)";
		}
		return '(' . $condicion . ')';
	}

	/**
	 * Consulta por lote los morosos que todavia no pertenecen a la evaluacion.
	 * No toma como evidencia las filas en memoria ni la marca agregado.
	 */
	function get_morosos_para_incorporar_evaluacion($alumnos, $evaluacion = null)
	{
		$ids_alumnos = array();
		foreach ((array) $alumnos as $alumno) {
			if (is_array($alumno)) {
				if (!isset($alumno['alumno'])) {
					continue;
				}
				$alumno = $alumno['alumno'];
			}
			if ($alumno === null || $alumno === '') {
				continue;
			}
			$ids_alumnos[(string) $alumno] = toba::db()->quote($alumno);
		}
		if (empty($ids_alumnos)) {
			return array();
		}

		$filtro = 'sga_alumnos.alumno IN (' . implode(', ', $ids_alumnos) . ')'
			. ' AND NOT ' . $this->get_condicion_habilitados_evaluacion($evaluacion);
		return $this->get_datos_alumnos_morosos($filtro);
	}
}