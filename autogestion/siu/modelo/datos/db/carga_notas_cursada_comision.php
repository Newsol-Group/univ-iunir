<?php
namespace siu\modelo\datos\db;

use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\alumno_foto;
use siu\modelo\entidades\parametro;

class carga_notas_cursada_comision
{
	const alumnos_pagina = 20;

	/**
	 * parametros: persona
	 * cache: no
	 * filas: n
	 */
	function lista_comisiones_docente($parametros)
	{
		$sql = "SELECT		sga_comisiones.comision as id,
							sga_comisiones.comision,
							sga_comisiones.entidad,
							sga_comisiones.nombre as comision_nombre,
							sga_elementos.nombre as actividad_nombre,
							sga_periodos.anio_academico as anio_academico,
							sga_periodos.nombre as periodo_lectivo_nombre
				FROM		sga_comisiones,
							sga_elementos,
							sga_periodos_lectivos,
							sga_periodos,
							sga_docentes_comision,
							sga_docentes
				WHERE		sga_comisiones.elemento = sga_elementos.elemento AND
							sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
							sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							sga_comisiones.comision = sga_docentes_comision.comision AND
							sga_docentes_comision.docente = sga_docentes.docente AND
							-- Sólo actas de comisiones del docente.
							sga_docentes.persona = {$parametros['persona']}
				ORDER BY	sga_elementos.nombre,
							sga_periodos.anio_academico DESC,
							sga_periodos.fecha_inicio DESC
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: entidad
	 * cache: no
	 * filas: 1
	 */
	function existe_evaluacion_automatica_comision($parametros)
	{
		$where = "	sga_evaluaciones.entidad = {$parametros['entidad']} AND
					sga_evaluaciones_tipos.automatica = '" . \comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a = '" . \acta::evaluacion_tipo_aplica_comision . "'";
		$datos_evaluacion = current(\toba::consulta_php('co_evaluaciones')->get_listado($where));
		return (!empty($datos_evaluacion));
	}

	/**
	 * parametros: acta
	 * cache: no
	 * filas: 1
	 */
	function porcentaje_carga_notas($parametros)
	{
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $parametros['comision']));
		$asentar_notas = parametro::get_valor('cur_asentar_notas_actas_regulares', $propuestas);

		$sql = "SELECT	CAST(SUM(CASE
								WHEN (	sga_eval_detalle_cursadas.cond_regularidad IS NULL OR
										sga_eval_detalle_cursadas.fecha_regular IS NULL OR
										('$asentar_notas' = '" . \acta::ingreso_notas_obligatorio . "' AND sga_eval_detalle_cursadas.resultado_cursada IN ('" . \escalas_notas::resultado_aprobado . "', '" . \escalas_notas::resultado_reprobado . "') AND sga_eval_detalle_cursadas.nota_cursada IS NULL) OR
										sga_eval_detalle_cursadas.resultado_cursada IS NULL) THEN 0
                				ELSE 1
							END) * 100.00 / COUNT(*) AS decimal(5,2)) AS porcentaje
				FROM	sga_eval_detalle_cursadas
				WHERE	sga_eval_detalle_cursadas.id_acta_cursada = {$parametros['acta']}
		";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['porcentaje'];
	}

	/**
	 * parametros:
	 * cache: no
	 */
	function condiciones_activas()
	{
		return \toba::consulta_php('co_condiciones_regularidad')->get_condiciones_activas();
	}

	/**
	 * parametros:
	 * cache: no
	 */
	function resultados_escala_nota()
	{
		return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados();
	}

	/**
	 * parametros: tipo
	 * cache: no
	 */
	function lista_escala_notas($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_listado("tipo={$parametros['tipo']}");
	}

	/**
	 * parametros: escala
	 * no_quote: escala
	 * cache: no
	 */
	function rango_escala_notas($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_rangos_resultados($parametros['escala']);
	}

	/**
	 * parametros: escala
	 * no_quote: escala
	 * cache: no
	 */
	function detalle_escala_notas($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_detalles($parametros['escala']);
	}

	/**
	 * parametros: comision, id_acta
	 * param_null: id_acta
	 * no_quote: comision, id_acta
	 * cache: no
	 * filas: 1
	 */
	function comision_cabecera($parametros)
	{
	$sql_id_acta = "";
		
		if (!empty($parametros['id_acta'])) {
			$sql_id_acta = " AND ".$parametros['id_acta']."in (sga_eval_detalle_cursadas.id_acta_cursada,
							sga_eval_detalle_cursadas.id_acta_promocion)";
		}
		$sql = "SELECT	sga_comisiones.comision as id,
						sga_comisiones.comision,
						sga_comisiones.nombre as comision_nombre,
						sga_elementos.codigo as actividad_codigo,
						sga_elementos.nombre as actividad_nombre,
						sga_periodos.anio_academico as anio_academico_periodo,
						sga_periodos.nombre as periodo_lectivo_nombre,
						sga_periodos.fecha_inicio as fecha_inicio_periodo,
						to_char(sga_periodos.fecha_inicio, '" . comunes::formato_fecha_visual . "') as fecha_inicio_periodo_visual,
						comisiones_instancias_regularidad.instancia as instancia_regularidad,
						comisiones_instancias_regularidad.escala_nota as escala_nota_regularidad,
						escalas_notas_regularidad.tipo as escala_nota_tipo_regularidad,
						comisiones_instancias_promocion.instancia as instancia_promocion,
						comisiones_instancias_promocion.escala_nota as escala_nota_promocion,
						escalas_notas_promocion.tipo as escala_nota_tipo_promocion,
						(	SELECT	COUNT(1)
							FROM	sga_evaluaciones
									JOIN sga_evaluaciones_tipos ON (sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND sga_evaluaciones_tipos.aplica_a = '" . \acta::evaluacion_tipo_aplica_comision . "')
									JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
       								LEFT JOIN sga_insc_cursada ON (sga_comisiones.comision = sga_insc_cursada.comision AND sga_eval_detalle_cursadas.alumno = sga_insc_cursada.alumno)
							WHERE	sga_comisiones.entidad = sga_evaluaciones.entidad AND
									(sga_insc_cursada.estado IS NULL OR sga_insc_cursada.estado = '" . \inscripcion_cursada::estado_aceptada . "')
									$sql_id_acta						
									) as cant_alumnos
				FROM	sga_comisiones
						  LEFT JOIN sga_comisiones_instancias as comisiones_instancias_regularidad ON 
						           (comisiones_instancias_regularidad.comision = sga_comisiones.comision AND
						            comisiones_instancias_regularidad.instancia = " . \comision::inst_cursar . ")
						  LEFT JOIN sga_comisiones_instancias as comisiones_instancias_promocion ON             
						           (sga_comisiones.comision = comisiones_instancias_promocion.comision AND
						            comisiones_instancias_promocion.instancia = " . \comision::inst_promocionar . ")
						  LEFT JOIN sga_escalas_notas as escalas_notas_regularidad ON escalas_notas_regularidad.escala_nota = comisiones_instancias_regularidad.escala_nota
						  LEFT JOIN sga_escalas_notas as escalas_notas_promocion ON escalas_notas_promocion.escala_nota = comisiones_instancias_promocion.escala_nota, 
						sga_elementos,
						sga_periodos_lectivos,
						sga_periodos
				WHERE	sga_comisiones.elemento = sga_elementos.elemento AND
						sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
						sga_periodos_lectivos.periodo = sga_periodos.periodo AND
						sga_comisiones.comision = {$parametros['comision']}
		";
		$datos = kernel::db()->consultar_fila($sql);
		$datos['paginas'] = ceil($datos['cant_alumnos'] / carga_notas_cursada_comision::alumnos_pagina);
		return $datos;
	}

	/**
	 * parametros: comision, subcomision, id_acta, pagina
	 * param_null: subcomision, pagina, id_acta
	 * no_quote: comision, subcomision, id_acta, pagina
	 * cache: no
	 * filas: n
	 */
	function alumnos_comision($parametros)
	{
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $parametros['comision']));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$param_ordenamiento = parametro::get_valor('cur_orden_detalle_actas', $ras);
		$campo_ordenamiento = \toba::consulta_php('co_actas')->get_campo_ordenamiento($param_ordenamiento);

		$sql_limit = "";
		$sql_id_acta = "";
		if ($parametros['pagina']) {
			$alumnos_pagina = carga_notas_cursada_comision::alumnos_pagina;
			$sql_limit = " LIMIT $alumnos_pagina OFFSET $alumnos_pagina * ({$parametros['pagina']} - 1)";
		}
		if (!empty($parametros['id_acta'])) {
			$sql_id_acta = "AND ".$parametros['id_acta']."in (sga_eval_detalle_cursadas.id_acta_cursada,
							sga_eval_detalle_cursadas.id_acta_promocion)";
		}

                $filtro_subcomision = "";
                // Si es una subcomisión
                if (!empty($parametros['subcomision'])) {
                    $filtro_subcomision = static::filtro_subcomision('sga_alumnos.alumno', $parametros['subcomision']);
                }

		$sql = "SELECT		sga_comisiones.comision,
							sga_evaluaciones.evaluacion,
							sga_eval_detalle_cursadas.instancia_cursada,
							sga_eval_detalle_cursadas.instancia_promocion,
							sga_eval_detalle_cursadas.cond_regularidad,
							to_char(sga_eval_detalle_cursadas.fecha_regular, '" . comunes::formato_fecha_visual . "') as fecha_regular,
							to_char(sga_eval_detalle_cursadas.fecha_promocion, '" . comunes::formato_fecha_visual . "') as fecha_promocion,
							sga_eval_detalle_cursadas.escala_nota_cursada,
							sga_eval_detalle_cursadas.escala_nota_promocion,
							sga_eval_detalle_cursadas.nota_cursada,
							sga_eval_detalle_cursadas.nota_promocion,
							sga_eval_detalle_cursadas.resultado_cursada,
							sga_eval_detalle_cursadas.resultado_promocion,
							sga_eval_detalle_cursadas.id_acta_cursada,
							sga_eval_detalle_cursadas.id_acta_promocion,
							sga_eval_detalle_cursadas.acta_cursada_cerrada,
							sga_eval_detalle_cursadas.acta_promocion_cerrada,
							sga_eval_detalle_cursadas.observacion_cursada,
							sga_eval_detalle_cursadas.observacion_promocion,
							acta_regularidad.nro_acta as nro_acta_regularidad,
							acta_promocion.nro_acta as nro_acta_promocion,
							acta_regularidad.version as version_regularidad,
							acta_promocion.version as version_promocion,
							acta_regularidad.version_impresa as version_impresa_regularidad,
							acta_promocion.version_impresa as version_impresa_promocion,
							sga_alumnos.alumno,
							sga_alumnos.alumno as renglon,
							vw_personas.apellido,
							vw_personas.apellido_elegido,
							vw_personas.nombres,
							vw_personas.nombres_elegido,
							vw_personas.apellido_nombres_elegido,
							vw_personas.tipo_nro_documento as documento_principal,
							vw_personas.id_imagen,
							sga_eval_detalle_cursadas.ultimo_cambio,
							sga_clases_asistencia_acum.porc_asistencia

				FROM		sga_comisiones
							JOIN sga_evaluaciones ON sga_comisiones.entidad = sga_evaluaciones.entidad
							JOIN sga_evaluaciones_tipos ON (sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND sga_evaluaciones_tipos.aplica_a = '" . \acta::evaluacion_tipo_aplica_comision . "')
							JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
							JOIN sga_alumnos ON (sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno)
							JOIN vw_personas ON (sga_alumnos.persona = vw_personas.persona)
							LEFT JOIN sga_actas as acta_regularidad ON sga_eval_detalle_cursadas.id_acta_cursada = acta_regularidad.id_acta
							LEFT JOIN sga_actas as acta_promocion ON sga_eval_detalle_cursadas.id_acta_promocion = acta_promocion.id_acta
							LEFT JOIN sga_insc_cursada ON (sga_comisiones.comision = sga_insc_cursada.comision AND sga_eval_detalle_cursadas.alumno = sga_insc_cursada.alumno)
							LEFT JOIN sga_clases_asistencia_acum ON (sga_comisiones.comision = sga_clases_asistencia_acum.comision AND sga_eval_detalle_cursadas.alumno = sga_clases_asistencia_acum.alumno)

				WHERE		sga_comisiones.comision = {$parametros['comision']}
                                                AND (sga_insc_cursada.estado IS NULL OR sga_insc_cursada.estado = '" . \inscripcion_cursada::estado_aceptada . "')
						$sql_id_acta
                                                $filtro_subcomision
                                -- https://stackoverflow.com/questions/13580826/postgresql-repeating-rows-from-limit-offset
				ORDER BY $campo_ordenamiento, sga_alumnos.alumno
				$sql_limit";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $dato) {
			$dato['url_imagen'] = empty($dato['id_imagen']) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm.png') : alumno_foto::url_imagen($dato['id_imagen']);
			$nuevo[$dato['renglon']] = $dato;
		}
		return $nuevo;
	}

	/**
	 * parametros: evaluacion, alumno, fecha_regular, fecha_promocion, cond_regularidad, nota_cursada, nota_promocion, resultado_cursada, resultado_promocion, observacion_cursada, observacion_promocion, ultimo_cambio
	 * param_null: fecha_regular, fecha_promocion, cond_regularidad, nota_cursada, nota_promocion, resultado_cursada, resultado_promocion, observacion_cursada, observacion_promocion
	 * param_tipo: int, int, str/10, str/10, int, str/10, str/10, str/1, str/1, str/100, str/100, str/100
	 * cache: no
	 * filas: 1
	 */
	function guardar_renglon($parametros)
	{
		if (!is_null($parametros['fecha_regular'])) {
			$fecha = date_parse_from_format('d/m/Y', $parametros['fecha_regular']);
			$fecha = "'{$fecha['year']}-{$fecha['month']}-{$fecha['day']}'";
		} else {
			$fecha = 'null';
		}
		$set = "fecha_regular = $fecha";

		if (!is_null($parametros['fecha_promocion'])) {
			$fecha = date_parse_from_format('d/m/Y', $parametros['fecha_promocion']);
			$fecha = "'{$fecha['year']}-{$fecha['month']}-{$fecha['day']}'";
		} else {
			$fecha = 'null';
		}
		$set .= ", fecha_promocion = $fecha";

		$cond_regularidad = is_null($parametros['cond_regularidad']) ? 'null' : $parametros['cond_regularidad'];
		$set .= ", cond_regularidad = $cond_regularidad";

		$nota_cursada = is_null($parametros['nota_cursada']) ? 'null' : $parametros['nota_cursada'];
		$set .= ", nota_cursada = $nota_cursada";

		$nota_promocion = is_null($parametros['nota_promocion']) ? 'null' : $parametros['nota_promocion'];
		$set .= ", nota_promocion = $nota_promocion";

		$resultado_cursada = is_null($parametros['resultado_cursada']) ? 'null' : $parametros['resultado_cursada'];
		$set .= ", resultado_cursada = $resultado_cursada";

		$resultado_promocion = is_null($parametros['resultado_promocion']) ? 'null' : $parametros['resultado_promocion'];
		$set .= ", resultado_promocion = $resultado_promocion";
		
		$observacion_cursada = is_null($parametros['observacion_cursada']) ? 'null' : $parametros['observacion_cursada'];
		$set .= ", observacion_cursada = $observacion_cursada";

		$observacion_promocion = is_null($parametros['observacion_promocion']) ? 'null' : $parametros['observacion_promocion'];
		$set .= ", observacion_promocion = $observacion_promocion";

		$sql = "UPDATE	sga_eval_detalle_cursadas
				SET		$set
				WHERE	evaluacion = {$parametros['evaluacion']} AND
						alumno = {$parametros['alumno']} AND
						ultimo_cambio = {$parametros['ultimo_cambio']}
		";
		$filas_afectadas = kernel::db()->ejecutar($sql);
		return $filas_afectadas;
	}
	
	/**
	 * parametros: evaluacion, alumnos, columnas_completar, para, subcomision, id_acta, origen_acta
	 * no_quote: evaluacion, alumnos, columnas_completar, para, subcomision, id_acta, origen_acta
         * param_null: subcomision, id_acta, origen_acta
	 * cache: no
	 * filas: 1
	 */
	function autocompletar_notas_cursada($parametros)
	{	
		
		$filas_afectadas = 0;
		
		try{
			
			kernel::db()->abrir_transaccion();

			$pagina_actual = false;
			$solo_vacios = false;
			switch ($parametros['para']) {
				case \comision::autocompletar_para_todos_pagina:
					$pagina_actual = true;
					$solo_vacios = false;
					break;
				case \comision::autocompletar_para_sin_datos_pagina:
					$pagina_actual = true;
					$solo_vacios = true;
					break;
				case \comision::autocompletar_para_todos:
					$pagina_actual = false;
					$solo_vacios = false;
					break;
				case \comision::autocompletar_para_sin_datos:
					$pagina_actual = false;
					$solo_vacios = true;
					break;
			}

			$filtro_pagina = "";
			if ($pagina_actual) {
				$filtro_pagina = "AND sga_eval_detalle_cursadas.alumno IN (".implode(", ", $parametros['alumnos']).")";
			}

                        $filtro_subcomision = "";
                        // Si es una subcomisión
                        if (!empty($parametros['subcomision'])) {
                            $filtro_subcomision = static::filtro_subcomision('sga_eval_detalle_cursadas.alumno', $parametros['subcomision']);
                        }

                        $filtro_acta = "";
                        // Si es un acta
                        if (!empty($parametros['id_acta'])) {
                            $filtro_acta = static::filtro_acta($parametros['id_acta'], $parametros['origen_acta']);
                        }
                        
			foreach ($parametros['columnas_completar']['principales'] as $columna => $valor) {
				
				$campos_extra = "";
				// Si hay campos extras los actualizo
				if (isset($parametros['columnas_completar']['extras'])) {
					foreach ($parametros['columnas_completar']['extras'] as $columna_extra => $valor_extra) {
						// Si el valor es null devuelvo 'NULL', sino lo quoteo
						$valor_extra = is_null($valor_extra)? 'NULL' : kernel::db()->quote($valor_extra);
						$campos_extra .= ", {$columna_extra} = {$valor_extra}";
					}
				}
				
				// Filtro para no modificar datos de actas cerradas
				$filtro_acta_abierta = "";
				$columnas_regularidad = [
					\comision::autocompletar_campo_nota_cursada,
					\comision::autocompletar_campo_resultado_cursada,
					\comision::autocompletar_campo_fecha_regular,
					\comision::autocompletar_campo_observacion_cursada
				];
				// Si es un campo de regularidad
				if (in_array($columna, $columnas_regularidad)) {
					$filtro_acta_abierta = "AND sga_eval_detalle_cursadas.acta_cursada_cerrada = 0";
				}
				
				$columnas_promocion = [
					\comision::autocompletar_campo_nota_promocion,
					\comision::autocompletar_campo_resultado_promocion,
					\comision::autocompletar_campo_fecha_promocion,
					\comision::autocompletar_campo_observacion_promocion
				];
				// Si es un campo de promoción
				if (in_array($columna, $columnas_promocion)) {
					$filtro_acta_abierta = "AND sga_eval_detalle_cursadas.acta_promocion_cerrada = 0";
				}
				
				$valor = (is_null($valor))? 'NULL' : kernel::db()->quote($valor);
				
				$filtro_vacios = ($solo_vacios)? "AND $columna IS NULL" : "";
				$sql = "UPDATE	sga_eval_detalle_cursadas
						SET		$columna = $valor
						$campos_extra
						WHERE	sga_eval_detalle_cursadas.evaluacion = {$parametros['evaluacion']}
						$filtro_vacios
						$filtro_pagina
						$filtro_acta_abierta
						$filtro_subcomision
						$filtro_acta
				";

				$filas_afectadas += kernel::db()->ejecutar($sql);
			}
			
			kernel::db()->cerrar_transaccion();
		} catch (\Exception $e) {
			$filas_afectadas = -1;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
		}
		
		return $filas_afectadas;
	}
	
	/**
	 * parametros: evaluacion, instancia, alumnos_completar
	 * no_quote: evaluacion, instancia, alumnos_completar
	 * cache: no
	 * filas: 1
	 */
	function calcular_notas_cursada($parametros)
	{	
		$hubo_error = false;
		
		try{
			
			kernel::db()->abrir_transaccion();
			
			// Filtro para no modificar datos de actas cerradas
			// Si la instancia es "Regularidad"
			if ($parametros['instancia'] == \comision::inst_cursar) {
				$filtro_acta_abierta = "AND sga_eval_detalle_cursadas.acta_cursada_cerrada = 0";
			} else {// Si la instancia es "Promoción"
				$filtro_acta_abierta = "AND sga_eval_detalle_cursadas.acta_promocion_cerrada = 0";
			}

			$sql = "";
			foreach ($parametros['alumnos_completar'] as $alumno => $campos) {
				
				$columnas = [];
				foreach ($campos as $campo => $valor) {
					$valor = kernel::db()->quote($valor);
					$columnas[] = "$campo = {$valor}";
				}
				// Uno todos las columnas con una coma (ej: nota_cursada = '1', resultado_cursada = 'R', cond_regularidad = '3')
				$set = implode(', ', $columnas);
				
				$sql .= "UPDATE	sga_eval_detalle_cursadas
						SET		$set
						WHERE	sga_eval_detalle_cursadas.evaluacion = {$parametros['evaluacion']}
						AND		sga_eval_detalle_cursadas.alumno = {$alumno}
						$filtro_acta_abierta;
				";
			
			}
			
			// Hago un UPDATE en bulk
			kernel::db()->ejecutar($sql);
			
			kernel::db()->cerrar_transaccion();
		} catch (\Exception $e) {
			$hubo_error = true;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
		}
		
		return $hubo_error;
	}

	/**
	 * parametros: acta, version, version_impresa
	 * cache: no
	 * filas: 1
	 */
	function actualizar_acta($parametros)
	{
		if ($parametros['version'] == $parametros['version_impresa']) {
			$sql = "UPDATE	sga_actas
					SET		version = ({$parametros['version']} + 1)
					WHERE	id_acta = {$parametros['acta']}";
			$datos = kernel::db()->ejecutar($sql);
			return $datos;
		}
	}

	/**
	 * parametros: instancia, escala_notas
	 * no_quote: instancia, escala_notas
	 * cache: memoria
	 * filas: n
	 */
	function resultados($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_resultados_posibles_instancia_escala_nota($parametros['instancia'], $parametros['escala_notas']);
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function condiciones_regularidad($parametros)
	{
		return \toba::consulta_php('co_condiciones_regularidad')->get_condiciones_activas();
	}

	/**
	 * parametros: comision, subcomision,  term
         * param_null: subcomision
	 * no_quote: comision, subcomision
	 * cache: no
	 * filas: n
	 */
	function busqueda_alumno($parametros)
	{
		$alumnos_pagina = carga_notas_cursada_comision::alumnos_pagina;

		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $parametros['comision']));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$param_ordenamiento = parametro::get_valor('cur_orden_detalle_actas', $ras);

                $filtro_subcomision = "";
                // Si es una subcomisión
                if (!empty($parametros['subcomision'])) {
                    $filtro_subcomision = static::filtro_subcomision('alumno', $parametros['subcomision']);
                }

		$sql = "SELECT	alumno,
						alumno as renglon,
						pagina,
						apellido || ', ' || nombres as apellido_nombres
				FROM	f_buscar_alumnos_comision({$parametros['comision']}, $alumnos_pagina, $param_ordenamiento, {$parametros['term']})
				WHERE true
                                $filtro_subcomision
		";
		return kernel::db()->consultar($sql);
	}

        /**
         * Filtra alumnos de la subcomisión.
         *
         * @param string $columna_alumno     Columna de alumnos.
         * @param int    $subcomision        ID de subcomisión
         *
         * @return string Filtra alumnos de la subcomisión.
         */
        public static function filtro_subcomision($columna_alumno, $subcomision)
        {

            $filtro_subcomision = "
                -- Filtra alumnos de la subcomisión
                AND {$columna_alumno} IN (
                    SELECT alumno
                    FROM sga_insc_cursada
                    JOIN sga_insc_subcomision ON (sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion)
                    AND sga_insc_subcomision.subcomision = {$subcomision}
                )";

            return $filtro_subcomision;
        }

        /**
         * Filtra alumnos del acta.
         *
         * @param int       $id_acta        Id del acta.
         * @param string    $origen_acta    Origen del acta.
         *
         * @return string Filtra alumnos del acta.
         */
        public static function filtro_acta($id_acta, $origen_acta)
        {
            $columna_id_acta = ($origen_acta == \acta::origen_regulares)? 'id_acta_cursada' : 'id_acta_promocion';
            $origen_acta_descr = ($origen_acta == \acta::origen_regulares)? \acta::origen_regulares_descr : \acta::origen_promociones_descr;
            $filtro_acta = "
                -- Filtra alumnos de {$origen_acta_descr}
                AND {$columna_id_acta} = {$id_acta}
            ";

            return $filtro_acta;
        }

	/**
	 * parametros: alumno, comision
	 * no_quote: alumno, comision
	 * cache: no
	 * filas: n
	 */
	function get_datos_parciales($parametros)
	{
		return \toba::consulta_php('co_evaluaciones')->get_resultados_evaluaciones_alumno_comision($parametros['alumno'], $parametros['comision']);
	}

	/**
	 * parametros: comision
	 * cache: memoria
	 * filas: 1
	 */
	function get_nombre_propuesta($parametros)
	{
		$sql = "SELECT f_nombre_propuesta_comision({$parametros['comision']}, 1, true) as nombre";
		return kernel::db()->consultar_fila($sql);
	}
	
	/**
	 * parametros:
	 * cache: memoria
	 * filas: n
	 */
	function get_listado_instancias_comisiones($parametros)
	{
		return \toba::consulta_php('co_comisiones')->get_listado_instancias_comisiones();
	}

	/**
	 * parametros:
	 * cache: memoria
	 * filas: n
	 */
	function get_opciones_combo_para($parametros)
	{
		return \toba::consulta_php('co_comisiones')->get_opciones_combo_para();
	}
	
	/**
	 * parametros: es_acta
	 * no_quote: es_acta
	 * cache: memoria
	 * filas: n
	 */
	function get_todas_opciones_combo_para($parametros)
	{
		return \toba::consulta_php('co_comisiones')->get_todas_opciones_combo_para($parametros['es_acta']);
	}
	
	/**
	 * parametros:
	 * cache: memoria
	 * filas: n
	 */
	function get_escalas_notas_resultados_solo_ausente($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados_solo_ausente();
	}
	
	/**
	 * parametros:
	 * cache: memoria
	 * filas: n
	 */
	function get_escalas_notas_resultados($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados();
	}
}