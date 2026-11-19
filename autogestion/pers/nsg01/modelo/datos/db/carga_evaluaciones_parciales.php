<?php

namespace nsg01\modelo\datos\db;

use siu\modelo\datos\catalogo;
use siu\modelo\datos\util;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\entidades\alumno_foto;

class carga_evaluaciones_parciales extends \siu\modelo\datos\db\carga_evaluaciones_parciales

{
	private function sql_comision_evaluacion($evaluacion)
	{
		return "(SELECT sga_comisiones.comision
				FROM sga_evaluaciones
				JOIN sga_comisiones ON (sga_comisiones.entidad = sga_evaluaciones.entidad)
				WHERE sga_evaluaciones.evaluacion = $evaluacion)";
	}

	/**
	 * La presencia del alumno en int_sq_morosos es la unica fuente de verdad
	 * para determinar su morosidad dentro de la operacion Evaluaciones.
	 */
	private function sql_alumno_moroso($alumno)
	{
		return "EXISTS (
					SELECT 1
					FROM int_sq_morosos AS moroso
					WHERE moroso.alumno = $alumno
				)";
	}

	private function sql_alumno_no_moroso($alumno)
	{
		return "NOT " . $this->sql_alumno_moroso($alumno);
	}

	private function sql_alumno_libre_en_comision($alumno, $comision)
	{
		return "EXISTS (
					SELECT 1
					FROM sga_clases_asistencia_acum
					WHERE sga_clases_asistencia_acum.alumno = $alumno
					AND sga_clases_asistencia_acum.comision = $comision
					AND sga_clases_asistencia_acum.libre = 1
				)";
	}

	private function sql_alumno_no_libre_en_comision($alumno, $comision)
	{
		return "NOT " . $this->sql_alumno_libre_en_comision($alumno, $comision);
	}

	private function sql_origen_alumnos_agregar_evaluacion($parametros)
	{
		if ($parametros['param_alumnos'] == "'ALUMNO'") {
			return "SELECT {$parametros['alumno']}::integer AS alumno";
		}

		if ($parametros['param_alumnos'] == "'COMISION'") {
			return "SELECT	sga_eval_detalle_cursadas.alumno
					FROM	sga_evaluaciones,
							sga_evaluaciones as eval_comision,
							sga_evaluaciones_tipos,
							sga_eval_detalle_cursadas
					WHERE	sga_evaluaciones.evaluacion = {$parametros['evaluacion']} AND
							eval_comision.entidad = sga_evaluaciones.entidad AND
							sga_evaluaciones_tipos.evaluacion_tipo = eval_comision.evaluacion_tipo AND
							sga_evaluaciones_tipos.automatica = 'S' AND
							sga_eval_detalle_cursadas.evaluacion = eval_comision.evaluacion";
		}

		if ($parametros['param_alumnos'] == "'SUBCOMISION'") {
			return "SELECT	sga_insc_cursada.alumno
					FROM	sga_insc_subcomision,
							sga_insc_cursada
					WHERE	sga_insc_subcomision.subcomision = {$parametros['subcomision']} AND
							sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion AND
							sga_insc_cursada.estado = 'A'";
		}

		if ($parametros['param_alumnos'] == "'EVALUACION'") {
			$sql_resultado = '';
			if ($parametros['resultado'] == "'X'") {
				$sql_resultado = " AND sga_eval_detalle.resultado IN ('R','U') ";
			}
			if ($parametros['resultado'] == "'A'" || $parametros['resultado'] == "'R'" || $parametros['resultado'] == "'U'" ) {
				$sql_resultado = " AND sga_eval_detalle.resultado = ".$parametros['resultado'];
			}

			return "SELECT	sga_eval_detalle.alumno
					FROM	sga_eval_detalle
					WHERE	sga_eval_detalle.evaluacion = {$parametros['evaluacion_origen']}
							$sql_resultado";
		}

		return "SELECT NULL::integer AS alumno WHERE false";
	}

	/**
	 * parametros: evaluacion, escala_nota, param_alumnos, alumno, subcomision, resultado, evaluacion_origen
	 * param_null: alumno, subcomision, resultado, evaluacion_origen
	 * cache: no
	 * filas: n
	 */
	function alumnos_excluidos_agregar_evaluacion($parametros)
	{
		$sql_origen = $this->sql_origen_alumnos_agregar_evaluacion($parametros);
		$sql_not_exists = " (SELECT alumno FROM sga_eval_detalle WHERE evaluacion = {$parametros['evaluacion']})";
		$sql_comision = $this->sql_comision_evaluacion($parametros['evaluacion']);
		$sql_alumno_moroso = $this->sql_alumno_moroso('origen.alumno');
		$sql_alumno_libre = $this->sql_alumno_libre_en_comision('origen.alumno', $sql_comision);

		$sql = "SELECT DISTINCT
					origen.alumno,
					vw_personas.apellido_nombres_elegido as nombre,
					vw_personas.tipo_nro_documento as documento,
					concat_ws(' / ',
						CASE WHEN $sql_alumno_moroso THEN 'Moroso' END,
						CASE WHEN sga_alumnos.calidad = 'P' THEN 'Pasivo' END,
						CASE WHEN $sql_alumno_libre THEN 'Libre' END
					) as motivos
				FROM ($sql_origen) as origen
				JOIN sga_alumnos ON (sga_alumnos.alumno = origen.alumno)
				JOIN vw_personas ON (vw_personas.persona = sga_alumnos.persona)
				WHERE origen.alumno NOT IN $sql_not_exists
				AND (
					$sql_alumno_moroso
					OR sga_alumnos.calidad = 'P'
					OR $sql_alumno_libre
				)
				ORDER BY nombre";

		return kernel::db()->consultar($sql);
	}

	// Newsol - Joaquin (16/04/26) - Se agrega filtro para no mostrar alumnos morosos
	// Newsol - Joaquin (17/04/26) - Se agrega filtro para no mostrar alumnos libres/pasivos
	/**
	 * parametros: evaluacion, escala_nota, param_alumnos, alumno, subcomision, resultado, evaluacion_origen
	 * param_null: alumno, subcomision, resultado, evaluacion_origen
	 * cache: no
	 * filas: n
	 */
	function agregar_alumnos_a_evaluacion($parametros)
	{
		/*
		  La evaluacion, param_alumnos y escala_nota debe pasarse siempre.
		  param_buscar_alumnos:
		  - ALUMNO      = Inserta el alumno pasado por par�metro (alumno)
		  - COMISION    = Todos los Alumnos de la comision (comision)
		  - SUBCOMISION = Todos los Alumnos de una subcomision. Viene el dato en subcomision
		  - EVALUACION  = Alumnos de una evaluacion. Vienen los datos en evaluacion_origen y resultado
		 */
		$sql_not_exists = " (SELECT alumno FROM sga_eval_detalle WHERE evaluacion = {$parametros['evaluacion']})";
		$sql_comision = $this->sql_comision_evaluacion($parametros['evaluacion']);
		if ($parametros['param_alumnos'] == "'ALUMNO'") {
			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_alumnos.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_alumnos
					WHERE	sga_alumnos.alumno = {$parametros['alumno']}
							AND {$this->sql_alumno_no_moroso('sga_alumnos.alumno')}
							AND sga_alumnos.calidad <> 'P'
							AND {$this->sql_alumno_no_libre_en_comision('sga_alumnos.alumno', $sql_comision)}
							AND sga_alumnos.alumno NOT IN $sql_not_exists";
		} elseif ($parametros['param_alumnos'] == "'COMISION'") {
			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_eval_detalle_cursadas.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_evaluaciones,
							sga_evaluaciones as eval_comision,
							sga_evaluaciones_tipos,
							sga_eval_detalle_cursadas
					WHERE	sga_evaluaciones.evaluacion = {$parametros['evaluacion']} AND
							eval_comision.entidad = sga_evaluaciones.entidad AND
							sga_evaluaciones_tipos.evaluacion_tipo = eval_comision.evaluacion_tipo AND
							sga_evaluaciones_tipos.automatica = 'S' AND
							sga_eval_detalle_cursadas.evaluacion = eval_comision.evaluacion
							AND {$this->sql_alumno_no_moroso('sga_eval_detalle_cursadas.alumno')}
							AND sga_eval_detalle_cursadas.alumno NOT IN (SELECT alumno FROM sga_alumnos WHERE calidad = 'P')
							AND {$this->sql_alumno_no_libre_en_comision('sga_eval_detalle_cursadas.alumno', $sql_comision)}
							AND sga_eval_detalle_cursadas.alumno NOT IN $sql_not_exists
			";
		} elseif ($parametros['param_alumnos'] == "'SUBCOMISION'") {
			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_insc_cursada.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_insc_subcomision,
							sga_insc_cursada
					WHERE	sga_insc_subcomision.subcomision = {$parametros['subcomision']} AND
							sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion AND
							sga_insc_cursada.estado = 'A'
							AND {$this->sql_alumno_no_moroso('sga_insc_cursada.alumno')}
							AND sga_insc_cursada.alumno NOT IN (SELECT alumno FROM sga_alumnos WHERE calidad = 'P')
							AND {$this->sql_alumno_no_libre_en_comision('sga_insc_cursada.alumno', $sql_comision)}
							AND sga_insc_cursada.alumno NOT IN $sql_not_exists
			";
		} elseif ($parametros['param_alumnos'] == "'EVALUACION'") {
			$sql_resultado = '';
			if ($parametros['resultado'] == "'X'") {
				// Ausentes y desaprobados
				$sql_resultado = " AND sga_eval_detalle.resultado IN ('R','U') ";
			}
			if ($parametros['resultado'] == "'A'" || $parametros['resultado'] == "'R'" || $parametros['resultado'] == "'U'" ) {	
				// Aprobados, Desaprobados o Ausentes.
				$sql_resultado = " AND sga_eval_detalle.resultado = ".$parametros['resultado'];
			}

			$sql = "INSERT INTO	sga_eval_detalle (alumno, evaluacion, escala_nota)
					SELECT	sga_eval_detalle.alumno,
							{$parametros['evaluacion']},
							{$parametros['escala_nota']}
					FROM	sga_eval_detalle
					WHERE	sga_eval_detalle.evaluacion = {$parametros['evaluacion_origen']}
							$sql_resultado
							AND {$this->sql_alumno_no_moroso('sga_eval_detalle.alumno')}
							AND sga_eval_detalle.alumno NOT IN (SELECT alumno FROM sga_alumnos WHERE calidad = 'P')
							AND {$this->sql_alumno_no_libre_en_comision('sga_eval_detalle.alumno', $sql_comision)}
							AND sga_eval_detalle.alumno NOT IN $sql_not_exists
			";
		}
		return kernel::db()->ejecutar($sql);
	}

	// Newsol - Joaquin (16/04/26) - Se agrega filtro para no mostrar alumnos morosos
	// Newsol - Joaquin (17/04/26) - Se agrega filtro para no mostrar alumnos libres/pasivos
	/**
	 * parametros: comision, subcomision
     * param_null: subcomision
	 * no_quote: subcomision
	 * cache: no
	 * filas: n
	 */
	function buscar_alumnos_comision($parametros)
	{
		$sql = "SELECT sga_evaluaciones.evaluacion
				FROM sga_comisiones, sga_evaluaciones, sga_evaluaciones_tipos
				WHERE sga_comisiones.comision = {$parametros['comision']}
				AND sga_evaluaciones.entidad = sga_comisiones.entidad
				AND sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				AND sga_evaluaciones_tipos.automatica = '".\comunes::si."'";
		$datos = kernel::db()->consultar_fila($sql);
		$sql_alumno_no_libre = $this->sql_alumno_no_libre_en_comision('sga_alumnos.alumno', $parametros['comision']);

                $filtro_subcomision = "";
                // Si es una subcomisi�n
                if (!empty($parametros['subcomision'])) {
                    $filtro_subcomision = carga_notas_cursada_comision::filtro_subcomision('sga_alumnos.alumno', $parametros['subcomision']);
                }

		$sql2 = "SELECT
					sga_alumnos.alumno as alumno,
					vw_personas.apellido_nombres_elegido as alumno_nombre,
					sga_alumnos.legajo as legajo
				FROM sga_eval_detalle_cursadas
				JOIN sga_alumnos ON (sga_alumnos.alumno = sga_eval_detalle_cursadas.alumno)
				JOIN vw_personas ON (vw_personas.persona = sga_alumnos.persona)
				WHERE sga_eval_detalle_cursadas.evaluacion = {$datos['evaluacion']}
				AND {$this->sql_alumno_no_moroso('sga_alumnos.alumno')}
				AND sga_alumnos.calidad <> 'P'
				AND $sql_alumno_no_libre
				AND (f_limpiar_acentos(vw_personas.apellido_nombres_elegido)
					ILIKE '%' || {$parametros['term']} || '%'
				OR
					sga_alumnos.legajo ILIKE '%' || {$parametros['term']} || '%')
				$filtro_subcomision";
                                
		return kernel::db()->consultar($sql2);
	}




}
