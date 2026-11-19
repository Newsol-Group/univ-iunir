<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class co_licencias
{
	/**
	 * Obtiene un listado de licencias según filtro especificado.
	 *
	 */
	function get_listado($where='') 
	{
		if ($where) {
			$where = ' AND '.$where;
		}		
		$sql = "SELECT 		
					sga_licencias.licencia,
					sga_licencias_motivos.nombre as motivo_nombre,
					sga_licencias.estado,
					f_propuestas_licencia(sga_licencias.licencia) AS propuestas,
					CASE WHEN sga_licencias.es_licencia_financiera = '".comunes::si."'
						THEN '".comunes::si_desc."'
						ELSE '".comunes::no_desc."'
					END as es_licencia_financiera_desc,
					es_licencia_financiera,
					CASE WHEN sga_licencias.puede_rendir_examen = '".comunes::si."'
						THEN '".comunes::si_desc."'
						ELSE '".comunes::no_desc."'
					END as puede_rendir_examen_desc,
					puede_rendir_examen,
					sga_documentos.documento_numero as resolucion,
					CASE WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN '".licencia::descr_rango_fechas."' 
						 WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN '".licencia::descr_periodos_lectivos."'
					END as tipo_licencia,
					CASE
						WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN to_char(sga_licencias.fecha_desde, '".comunes::formato_fecha_visual."')
						WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN periodo_desde.nombre
					END as desde,
					CASE
						WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN to_char(sga_licencias.fecha_hasta, '".comunes::formato_fecha_visual."')
						WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN (	SELECT	periodos.nombre
																									FROM 	(	SELECT		sga_periodos.nombre,
																															sga_periodos.fecha_fin
																												FROM		sga_periodos_lectivos,
																															sga_periodos,
																															sga_periodos_genericos
																												WHERE		sga_periodos_lectivos.periodo = sga_periodos.periodo AND
																															sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico AND
																															sga_periodos.anio_academico >= sga_licencias.anio_academico AND
																															sga_periodos.fecha_inicio >= periodo_desde.fecha_inicio AND
																															sga_periodos_genericos.periodo_lectivo_tipo = sga_licencias.periodo_lectivo_tipo
																												ORDER BY	sga_periodos.anio_academico,
																															sga_periodos_genericos.orden_en_su_tipo,
																															sga_periodos.fecha_fin
																												LIMIT		sga_licencias.cant_periodos_lectivos) AS periodos
																									ORDER BY	periodos.fecha_fin desc
																									LIMIT		1)		
					END as hasta,
					sga_licencias.observaciones,
					sga_licencias_doc_digital.licencia_doc,
					sga_licencias_doc_digital.nombre_archivo
				FROM 		sga_licencias					
				LEFT JOIN
						sga_documentos ON
						sga_documentos.documento = sga_licencias.documento
				LEFT JOIN 
						sga_periodos_lectivos ON
						sga_periodos_lectivos.periodo_lectivo = sga_licencias.periodo_lectivo_desde
				LEFT JOIN 
						sga_periodos as periodo_desde ON
						sga_periodos_lectivos.periodo = periodo_desde.periodo
				LEFT JOIN
						sga_licencias_doc_digital
				ON
						sga_licencias_doc_digital.licencia = sga_licencias.licencia,
						sga_licencias_motivos
				WHERE 		sga_licencias_motivos.motivo_licencia = sga_licencias.motivo_licencia
							$where
				ORDER BY	sga_licencias.licencia
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene la lista de licencias de una persona dada
	 *
	 * @param 	int 	$persona
	 * @return	array
	 */
	function get_licencias_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$where = "sga_licencias.persona = $persona";
		return $this->get_listado($where);
	}

        /**
	 * Combo para Licencia financiera
	 *
	 */
	function get_es_licencia_financiera()
	{
            return [
                ['valor' => comunes::si, 'descr' => comunes::si_desc],
                ['valor' => comunes::no, 'descr' => comunes::no_desc]
            ];
	}

	/**
	 * Listado de alcances de licencia
	 *
	 */
	function get_alcances_licencia()
	{
		return array(array('valor' => licencia::alcance_rango_fechas, 'descr' => licencia::descr_rango_fechas),
					 array('valor' => licencia::alcance_periodos_lectivos, 'descr' => licencia::descr_periodos_lectivos)
		            );
	}
	
	/**
	 * Listado de estados de licencia
	 *
	 */
	function get_estados_licencia()
	{
		return array(array('valor' => licencia::estado_aceptado, 'descr' => licencia::estado_aceptado),
					 array('valor' => licencia::estado_pendiente, 'descr' => licencia::estado_pendiente),
					 array('valor' => licencia::estado_rechazado, 'descr' => licencia::estado_rechazado)
					 
		            );
	}
	
	/**
	 * Obiente los datos de un documento de licencia dado
	 *
	 */
	function get_licencia_doc($licencia_doc)
	{
		$licencia_doc = toba::db()->quote($licencia_doc);
		
		$sql = "SELECT 	sga_licencias_doc_digital.licencia_doc,
						sga_licencias_doc_digital.licencia,
						sga_licencias_doc_digital.nombre_archivo,
						sga_licencias_doc_digital.archivo
				FROM 	sga_licencias_doc_digital
				WHERE	sga_licencias_doc_digital.licencia_doc = $licencia_doc";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene las fechas de los períodos que comprenden las licencias de una persona.
	 *
	 * @param integer $persona
	 * @param string $where
	 * @return array
	 */
	function get_fechas_licencia_persona($persona, $where='')
	{
		$persona = toba::db()->quote($persona);
		if ($where) {
			$where = " AND $where";
		}
		
		$sql = "SELECT 	sga_licencias.licencia,
						CASE
							WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN
								sga_licencias.fecha_desde
							WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN
								sga_periodos.fecha_inicio
						END as fecha_desde,
						CASE
							WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN
								sga_licencias.fecha_hasta
							WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN
								sga_periodos.fecha_fin
						END as fecha_hasta,
						sga_licencias.alcance,
						sga_licencias.anio_academico,
						sga_licencias.periodo_lectivo_desde,
						sga_licencias.periodo_lectivo_tipo,
						sga_licencias.cant_periodos_lectivos
				FROM	sga_licencias
    					LEFT JOIN sga_periodos_lectivos ON
							sga_periodos_lectivos.periodo_lectivo = sga_licencias.periodo_lectivo_desde
						LEFT JOIN sga_periodos ON
							sga_periodos_lectivos.periodo = sga_periodos.periodo
				WHERE	sga_licencias.persona = $persona
						$where
				";
		
		$res = guarani_db::consultar($sql);
		if (!empty($res)) {
			foreach ($res as $key => $licencia) {
				if ($licencia['alcance'] == licencia::alcance_periodos_lectivos) {
					$res[$key]['fecha_hasta'] = $this->get_fecha_hasta_lic_periodo($licencia['anio_academico'], $licencia['fecha_desde'], $licencia['periodo_lectivo_tipo'], $licencia['cant_periodos_lectivos']);
				}
			}
		}
		
		return $res;
	}

	/**
	 * Retorna la fecha de finalización de una licencia de tipo "Período Lectivo" en base a sus datos.
	 *
	 * @param integer $anio_academico año académico.
	 * @param date $fecha_inicio fecha de inicio.
	 * @param integer $periodo_lectivo_tipo Tipo de período lectivo.
	 * @param string $cant_periodos_lectivos cantidad de períodos lectivos.
	 * @return date
	 */
	function get_fecha_hasta_lic_periodo($anio_academico, $fecha_inicio, $periodo_lectivo_tipo, $cant_periodos_lectivos)
	{
		$periodo_lectivo_tipo = toba::db()->quote($periodo_lectivo_tipo);
		$fecha_inicio = toba::db()->quote($fecha_inicio);
		$where_gatillo = 'true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_periodos', 'sga_periodos');
		}
		$sql = "SELECT 	MAX(periodos.fecha_fin) as fecha_fin
				FROM	(	SELECT		sga_periodos.fecha_fin
							FROM		sga_periodos_lectivos,
										sga_periodos,
										sga_periodos_genericos
							WHERE		sga_periodos_lectivos.periodo = sga_periodos.periodo AND
										sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico AND
										sga_periodos.anio_academico >= $anio_academico AND
										sga_periodos.fecha_inicio >= $fecha_inicio AND
										sga_periodos_genericos.periodo_lectivo_tipo = $periodo_lectivo_tipo AND
										$where_gatillo
							ORDER BY	sga_periodos.anio_academico,
										sga_periodos_genericos.orden_en_su_tipo,
										sga_periodos.fecha_fin
							LIMIT		$cant_periodos_lectivos
						) as periodos
		";

		$res = guarani_db::consultar_fila($sql, null, false);
		return $res['fecha_fin'];
	}
	
	function get_listado_reporte_licencias($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$tipos = $this->get_alcances_licencia();
		$sql = "SELECT		    COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as apellido_y_nombre,
							f_propuestas_licencia(sga_licencias.licencia) AS propuestas,
							sga_licencias_motivos.nombre AS motivo_licencia,
							" . guarani_sql::get_case('sga_licencias', 'alcance', 'tipo_licencia', $tipos) . ",
							sga_licencias.estado,
							CASE
								WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN to_char(sga_licencias.fecha_desde, '".comunes::formato_fecha_visual."')
								WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN periodo_desde.nombre
							END as desde,
							CASE
								WHEN sga_licencias.alcance = ".licencia::alcance_rango_fechas." THEN to_char(sga_licencias.fecha_hasta, '".comunes::formato_fecha_visual."')
				                WHEN sga_licencias.alcance = ".licencia::alcance_periodos_lectivos." THEN (	SELECT	periodos.nombre
																											FROM 	(	SELECT		sga_periodos.nombre,
																																	sga_periodos.fecha_fin
																														FROM		sga_periodos_lectivos,
																																	sga_periodos,
																																	sga_periodos_genericos
																														WHERE		sga_periodos_lectivos.periodo = sga_periodos.periodo AND
																																	sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico AND
																																	sga_periodos.anio_academico >= sga_licencias.anio_academico AND
																																	sga_periodos.fecha_inicio >= periodo_desde.fecha_inicio AND
																																	sga_periodos_genericos.periodo_lectivo_tipo = sga_licencias.periodo_lectivo_tipo
																														ORDER BY	sga_periodos.anio_academico,
																																	sga_periodos_genericos.orden_en_su_tipo,
																																	sga_periodos.fecha_fin
																														LIMIT		sga_licencias.cant_periodos_lectivos) AS periodos
																											ORDER BY	periodos.fecha_fin desc
																											LIMIT		1)		
							END as hasta
				FROM		sga_licencias
							LEFT JOIN sga_periodos_lectivos as periodo_lectivo_desde ON sga_licencias.periodo_lectivo_desde = periodo_lectivo_desde.periodo_lectivo
							LEFT JOIN sga_periodos as periodo_desde ON periodo_lectivo_desde.periodo = periodo_desde.periodo,
							mdp_personas,
							sga_licencias_motivos
				WHERE		mdp_personas.persona = sga_licencias.persona
				AND			sga_licencias_motivos.motivo_licencia = sga_licencias.motivo_licencia
							$where
				ORDER BY	 COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres),
							propuestas
		";
		return guarani_db::consultar($sql);
	}

        /**
         * Devuelve una lista de Licencias Estudiantiles para el ENDPOINT GET /licencias-estudiantiles.
         *
         * @param string $persona Opcional, por defecto vacío.
         * @param string $plan Opcional, por defecto vacío.
         * @param string $fecha_desde Opcional, por defecto vacío.
         * @param string $fecha_hasta Opcional, por defecto vacío.
         * @param string $estado Opcional, por defecto vacío.
         * @param string $plan_cobrable Opcional, por defecto vacío.
         * @param string $licencia_financiera Opcional, por defecto vacío.
         * @param string $permite_rendir_examen Opcional, por defecto vacío.
         *
         * @return array Lista de Licencias Estudiantiles para el ENDPOINT GET /licencias-estudiantiles.
         */
        function get_listado_rest(
            $persona = '',
            $plan = '',
            $fecha_desde = '',
            $fecha_hasta = '',
            $estado = '',
            $plan_cobrable = '',
            $licencia_financiera = '',
            $permite_rendir_examen = ''
        )
        {
            
            $where_persona = "";
            // Si filtra por persona
            if (!empty($persona)) {
                $where_persona = "AND sga_licencias.persona = $persona";
            }

            $where_estado = "";
            // Si filtra por estado
            if (!empty($estado)) {
                $where_estado = "AND sga_licencias.estado = '$estado'";
            }

            $where_licencia_financiera = "";
            // Si filtra por licencia_financiera
            if (!empty($licencia_financiera)) {
                $where_licencia_financiera = "AND sga_licencias.es_licencia_financiera = '$licencia_financiera'";
            }

            $where_permite_rendir_examen = "";
            // Si filtra por permite_rendir_examen
            if (!empty($permite_rendir_examen)) {
                $where_permite_rendir_examen = "AND sga_licencias.puede_rendir_examen = '$permite_rendir_examen'";
            }

            $where_alcance = "";
            $where_fecha_desde = "";
            // Si filtra por fecha_desde
            if (!empty($fecha_desde)) {
                $fecha_desde_to_date = "TO_DATE('$fecha_desde', '".comunes::formato_fecha_bd."')";
                $where_fecha_desde = "AND sga_licencias.fecha_hasta >= $fecha_desde_to_date";
                $where_alcance = "AND sga_licencias.alcance = ".licencia::alcance_rango_fechas;
            }

            $where_fecha_hasta = "";
            // Si filtra por fecha_hasta
            if (!empty($fecha_hasta)) {
                $fecha_hasta_to_date = "TO_DATE('$fecha_hasta', '".comunes::formato_fecha_bd."')";
                $where_fecha_hasta = "AND sga_licencias.fecha_desde <= $fecha_hasta_to_date";
                $where_alcance = "AND sga_licencias.alcance = ".licencia::alcance_rango_fechas;
            }

            $where_plan = "";
            // Si filtra por plan
            if (!empty($plan)) {
                $where_plan = "AND sga_planes.plan = {$plan}";
            }

            $where_plan_cobrable = "";
            // Si filtra por plan_cobrable
            if (!empty($plan_cobrable)) {
                $where_plan_cobrable = "AND sga_planes.cobrable = '$plan_cobrable'";
            }

            $sql = "
                SELECT * FROM (
                    SELECT
                    sga_licencias.licencia,
                    sga_licencias.persona,
                    sga_licencias.es_licencia_financiera AS licencia_financiera,
                    sga_licencias.puede_rendir_examen AS permite_rendir_examen,
                    sga_licencias.alcance,
                    CASE sga_licencias.alcance
                       WHEN ".licencia::alcance_rango_fechas." THEN '".licencia::descr_rango_fechas."'
                       WHEN ".licencia::alcance_periodos_lectivos." THEN '".licencia::descr_periodos_lectivos."'
                    END alcance_descr,
                    sga_licencias.fecha_desde,
                    sga_licencias.fecha_hasta,
                    sga_licencias.estado,
                    sga_licencias_motivos.motivo_licencia AS motivo,
                    sga_licencias_motivos.nombre AS motivo_descr,
                    sga_licencias.observaciones,
                    -- https://www.pgcasts.com/episodes/generating-json-from-sql
                    (SELECT json_agg(row_to_json(t)) AS propuestas FROM (

                            SELECT
                            sga_propuestas.propuesta,
                            sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
                            sga_planes.plan,
                            sga_planes.cobrable AS plan_cobrable,
                            sga_propuestas_aspira.nro_transaccion
                            FROM sga_licencias_propuestas
                            JOIN sga_alumnos ON (sga_alumnos.propuesta = sga_licencias_propuestas.propuesta)
                            JOIN sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
                            JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
                            JOIN sga_planes_versiones ON (sga_planes_versiones.plan_version = sga_alumnos.plan_version)
                            JOIN sga_planes ON (sga_planes.plan = sga_planes_versiones.plan)
                            WHERE sga_licencias_propuestas.licencia = sga_licencias.licencia
                            AND sga_alumnos.persona = sga_licencias.persona
                            $where_plan
                            $where_plan_cobrable
                            
                    ) t)
                    FROM sga_licencias
                    JOIN sga_licencias_motivos ON (sga_licencias.motivo_licencia = sga_licencias_motivos.motivo_licencia)
                    WHERE true
                    $where_persona
                    $where_estado
                    $where_licencia_financiera
                    $where_permite_rendir_examen
                    $where_alcance
                    $where_fecha_desde
                    $where_fecha_hasta
                ) AS licencias
                -- Si no hay propuestas no listo la licencia
                WHERE propuestas IS NOT NULL
                ORDER BY licencia
                ";

            $licencias = guarani_db::consultar($sql);
            
            foreach ($licencias as $key => $licencia) {

                // Reemplazo null por "" para respetar el tipo string
                $licencias[$key]['fecha_desde'] = $licencia['fecha_desde'] ?? "";
                $licencias[$key]['fecha_hasta'] = $licencia['fecha_hasta'] ?? "";
                $licencias[$key]['observaciones'] = $licencia['observaciones'] ?? "";

                // Si no hay propuestas
                if (is_null($licencia['propuestas'])) {
                    $licencias[$key]['propuestas'] = [];
                // Si hay autorizantes
                } else {
                    // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                    $propuestas = iconv("ISO-8859-1", "UTF-8", $licencia['propuestas']);
                    $propuestas = json_decode($propuestas, true);
                    $licencias[$key]['propuestas'] = $propuestas;
                    
                }
            }

            return $licencias;
        }
}