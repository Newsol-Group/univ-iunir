<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */
class cn_pro_pasar_a_calidad_pasivo extends cn_procesos_bk
{

	function etiquetas($dep=null)
	{
		return guarani_etiquetas::get_tabla('sga_propuestas');
	}

	/**
	 * Obtiene el listado de alumnos que cumplen con las condiciones especificadas.
	 * @todo incorporar restricciones por pérdida de regularidad.
	 *
	 * @param unknown_type $datos
	 * @return unknown
	 */
	function get_listado_alumnos($datos)
	{
		$filtro = "sga_alumnos.ubicacion = {$datos['ubicacion']}";
		$filtro .= ' AND ' . guarani_sql::armar_condicion_in($datos['plan'], 'sga_planes', 'plan');
		$filtro .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."'";

		/**
		 * Los casos en que se seleccionan o no simultaneamente ambas opciones para alumnos regulares
		 * y no regulares no necesitan tener especificado un filtro y semánticamente significan lo
		 * mismo. Solo se diferencian los casos de una única selección en regulares o no regulares.
		 */
		if ($datos['regulares'] xor $datos['no_regulares']) {
			$filtro .= $datos['regulares'] 	  ? " AND sga_alumnos.regular = '".comunes::si."' " : '';
			$filtro .= $datos['no_regulares'] ? " AND sga_alumnos.regular = '".comunes::no."' " : '';
		}

		if ($datos['considerar_perdida_reg']) {
			$desde = quote($datos['desde']);
			$filtro_regularidad = 'sga_alumnos.alumno IN (	SELECT	sga_perdida_regularidad.alumno
														  	FROM	sga_perdida_regularidad
														  	WHERE 	sga_perdida_regularidad.anio_academico ';
			switch ($datos['condicion']) {
				case 'E': 
					$hasta = quote($datos['hasta']);
					$filtro_regularidad .= "BETWEEN $desde AND $hasta)";
					break;
				case 'D':
					$filtro_regularidad .= ">= $desde)";
					break;
				case 'H':
					$filtro_regularidad .= "<= $desde)";
					break;
			}
			$filtro .= "AND $filtro_regularidad ";
		}

		if ($datos['considerar_reinscriptos']) {
			$anio_reinscripcion = quote($datos['anio_reinscripcion']);
			$filtro .= " AND NOT EXISTS (	SELECT	1
											FROM 	sga_reinscripciones
											WHERE 	sga_reinscripciones.alumno = sga_alumnos.alumno AND
													sga_reinscripciones.anio_academico = $anio_reinscripcion)";
		}

		$alumnos = toba::consulta_php('co_alumnos')->get_listado($filtro);

		foreach ($alumnos as $clave => $alumno) {
			if ($datos['considerar_reinscriptos'] &&
				toba::consulta_php('co_alumnos')->es_ingresante($alumno['alumno'], $datos['anio_reinscripcion']) &&
				guarani::parametros()->get_valor('mat_reinscripcion_ingresante', $alumno['propuesta']) == comunes::no) {
				unset($alumnos[$clave]);
			}
		}

		return $alumnos;
	}

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_pasar_a_calidad_pasivo::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
}
?>