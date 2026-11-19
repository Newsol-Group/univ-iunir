<?php
/**
 *
 * @package Cobro
 * @subpackage Cobro
 */
class co_cobros
{
	/**
	 * Listado de alumnos morosos, que adeudan pagos de inscipcion a Comisiones.
	 * @param string $where filtro sobre los alumnos a recuperar.
	 */
	function get_morosos($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT	int_sq_morosos.id_moroso,
						int_sq_morosos.alumno,
						int_sq_morosos.nro_transaccion,
						int_sq_morosos.fecha
				FROM	int_sq_morosos
				$where
		;";
		return guarani::db()->consultar($sql);
	}

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
	 * Retorna el listado de nombres de Alumnos que figuran como morosos de una Propuesta.
	 * @param string $where filtro sobre los alumnos a recuperar.
	 */
	function get_datos_alumnos_morosos_propuesta( $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT	int_sq_morosos.id_moroso,
						int_sq_morosos.alumno,
						int_sq_morosos.fecha,
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS alumno_nombre,
						sga_propuestas.nombre AS propuesta_nombre,
						int_sq_morosos.nro_transaccion,
						int_sq_morosos.fecha
				FROM	int_sq_morosos
				JOIN	sga_alumnos ON int_sq_morosos.alumno = sga_alumnos.alumno
				JOIN	mdp_personas ON sga_alumnos.persona = mdp_personas.persona
				JOIN	sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN	sga_propuestas_aspira ON (sga_propuestas_aspira.propuesta = sga_alumnos.propuesta AND sga_propuestas_aspira.persona = sga_alumnos.persona)
				JOIN	sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				WHERE   sga_propuestas_aspira.nro_transaccion = int_sq_morosos.nro_transaccion -- Moroso relacionado con la propuesta.
				  AND	sga_situacion_aspirante.resultado_asp IN ('A','P')
				$where
		;";
		return guarani::db()->consultar($sql);
	}	
	/**
	 * Retorna el listado de nombres de Alumnos que figuran como morosos de una Comision.
	 * @param integer $comision id de la comision para la que se desea averiguar los morosos.
	 * @param string $where filtro sobre los alumnos a recuperar.
	 */
	function get_datos_alumnos_morosos_comision($comision, $where='')
	{
		$comision = toba::db()->quote($comision);
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT	int_sq_morosos.id_moroso,
						int_sq_morosos.alumno,
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS alumno_nombre,
						sga_insc_cursada.comision,
						sga_comisiones.nombre AS comision_nombre,
						int_sq_morosos.nro_transaccion,
						int_sq_morosos.fecha
				FROM	sga_comisiones
				JOIN	sga_insc_cursada ON sga_insc_cursada.comision = sga_comisiones.comision
				JOIN	int_sq_morosos ON int_sq_morosos.nro_transaccion = sga_insc_cursada.nro_transaccion
				JOIN	sga_alumnos ON sga_alumnos.alumno = int_sq_morosos.alumno
				JOIN	mdp_personas ON mdp_personas.persona = sga_alumnos.persona
				
				WHERE	sga_comisiones.comision = $comision
				$where
		;";
		return guarani::db()->consultar($sql);
	}

	/**
	 * Retorna true or false, dependiendo de si el Alumno figura como moroso en la comision dada.
	 * @param integer $alumno Id del alumno para el que se desea averiguar si es moroso.
	 * @param integer $comision Id de la comision para la que se verifica si el alumno adeuda pagos.
	 */
	function alumno_es_moroso_en_comision($alumno, $comision)
	{
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);
		
				$sql = "SELECT 1
						FROM	int_sq_morosos
						JOIN	sga_insc_cursada ON sga_insc_cursada.nro_transaccion = int_sq_morosos.nro_transaccion
						JOIN	sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
						WHERE	int_sq_morosos.alumno = $alumno
						  AND sga_insc_cursada.comision = $comision;";
		$datos = guarani::db()->consultar_fila($sql);
		return (!empty($datos));
	}
	
	
	function get_listado_propuestas_sincronizar($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$propuesta_estado_activo	= toba::db()->quote(propuesta::estado_activo);
		$plan_estado_activo_vigente = toba::db()->quote(planes::estado_activo_vigente);
		$plan_estado_activo_no_vigente = toba::db()->quote(planes::estado_activo_no_vigente);
		$plan_no_sincronizado_sq	= toba::db()->quote(planes::no_sincronizado_sq);
		
		$sql = "SELECT 	sga_propuestas.propuesta			AS propuesta_id,
						sga_propuestas.codigo				AS propuesta_codigo,
						sga_propuestas.nombre				AS propuesta_nombre,
						sga_propuestas.nombre_abreviado		AS propuesta_nombre_abreviado,
						sga_propuestas_tipos.propuesta_tipo	AS propuesta_tipo,
						sga_propuestas_tipos.descripcion	AS propuesta_tipo_descripcion,
						sga_planes.plan						AS plan_id,
						sga_planes.codigo					AS plan_codigo,
						sga_planes.nombre					AS plan_nombre,
						sga_planes.cobrable					AS plan_cobrable
				FROM sga_planes
				JOIN sga_propuestas			ON (sga_planes.propuesta = sga_propuestas.propuesta)
				JOIN sga_propuestas_tipos	ON (sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo)
				WHERE sga_propuestas.estado = $propuesta_estado_activo
				AND sga_planes.estado IN ($plan_estado_activo_vigente, $plan_estado_activo_no_vigente)
				AND sga_planes.sq_sincronizado = $plan_no_sincronizado_sq				
				$where
				ORDER BY propuesta_nombre, plan_nombre, plan_cobrable
		";
		
		return guarani_db::consultar($sql);
	}
	
	function get_propuesta_sincronizar($novedad){
		
		$novedad	= toba::db()->quote($novedad);
		$where		= "sga_planes.plan = $novedad";
		
		$result = $this->get_listado_propuestas_sincronizar($where);
		return (empty($result))? NULL : $result[0];
		
	}
	
	function get_listado_inscripciones_sincronizar($where = '')
	{
		if (!empty($where)) {
			$where = "WHERE $where";
		}
		
		$propuesta_estado_activo					= toba::db()->quote(propuesta::estado_activo);
		$plan_estado_activo_vigente					= toba::db()->quote(planes::estado_activo_vigente);
		$plan_estado_activo_no_vigente				= toba::db()->quote(planes::estado_activo_no_vigente);
		$plan_sincronizado_sq						= toba::db()->quote(planes::sincronizado_sq);
		$si											= toba::db()->quote(comunes::si);
		$estado_baja								= toba::db()->quote(comunes::estado_baja);
		
		$SQ_INSCRIPCION_NO_SINCRONIZADA				= toba::db()->quote(cobro::SQ_INSCRIPCION_NO_SINCRONIZADA);
		$SQ_INSCRIPCION_FALLO_ALTA					= toba::db()->quote(cobro::SQ_INSCRIPCION_FALLO_ALTA);
		$SQ_INSCRIPCION_FALLO_BAJA					= toba::db()->quote(cobro::SQ_INSCRIPCION_FALLO_BAJA);
		$SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_ACTIVO	= toba::db()->quote(cobro::SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_ACTIVO);
		$SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_PASIVO	= toba::db()->quote(cobro::SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_PASIVO);
		$SQ_INSCRIPCION_FALLO_RECHAZO				= toba::db()->quote(cobro::SQ_INSCRIPCION_FALLO_RECHAZO);
		$SQ_INSCRIPCION_SINCRONIZADA				= toba::db()->quote(cobro::SQ_INSCRIPCION_SINCRONIZADA);
		$SQ_INSCRIPCION_FALLO_REVERTIR_RECHAZO		= toba::db()->quote(cobro::SQ_INSCRIPCION_FALLO_REVERTIR_RECHAZO);
		$SQ_INSCRIPCION_ENCOLADA                        = toba::db()->quote(cobro::SQ_INSCRIPCION_ENCOLADA);
		
		$sq_sincronizar_encolada			= toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_encolada'));
		$sq_sincronizar_alta				= toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_alta'));
		$sq_sincronizar_baja				= toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_baja'));
		$sq_sincronizar_cambio_calidad_activo           = toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_cambio_calidad_activo'));
		$sq_sincronizar_cambio_calidad_pasivo           = toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_cambio_calidad_pasivo'));
		$sq_sincronizar_rechazo                         = toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_rechazo'));
		$sq_sincronizar_revertir_rechazo                = toba::db()->quote(guarani_mensajes::get('800_sq_sincronizar_revertir_rechazo'));
                
		$sq_estado_en_cola                              = toba::db()->quote(guarani_mensajes::get('800_sq_estado_en_cola'));
		$sq_sq_estado_falla                             = toba::db()->quote(guarani_mensajes::get('800_sq_estado_falla'));
		
		$sql = "SELECT * 
                        FROM    (
                                        -- Inscripciones a propuestas (aceptadas, pendientes, rechazadas)
					SELECT	inscripciones_propuestas.propuesta_aspira,
							inscripciones_propuestas.fecha_inscripcion AS fecha,
							inscripciones_propuestas.nro_transaccion,
							sga_propuestas.propuesta,
							sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas.codigo AS propuesta_codigo,
							vw_personas.persona,
							vw_personas.apellido_nombres AS persona_apellido_nombres,
							sga_planes.plan,
							sga_planes.nombre AS plan_nombre,
							sga_planes.codigo AS plan_codigo,
							inscripciones_propuestas.sq_sincronizado AS corte,
							CASE inscripciones_propuestas.sq_sincronizado
								WHEN $SQ_INSCRIPCION_FALLO_ALTA				THEN $sq_sincronizar_alta
								WHEN $SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_ACTIVO	THEN $sq_sincronizar_cambio_calidad_activo
								WHEN $SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_PASIVO	THEN $sq_sincronizar_cambio_calidad_pasivo
								WHEN $SQ_INSCRIPCION_FALLO_RECHAZO			THEN $sq_sincronizar_rechazo
								WHEN $SQ_INSCRIPCION_FALLO_REVERTIR_RECHAZO		THEN $sq_sincronizar_revertir_rechazo
                                                                WHEN $SQ_INSCRIPCION_ENCOLADA                           THEN $sq_sincronizar_encolada
							END AS corte_nombre,
                                                        CASE inscripciones_propuestas.sq_sincronizado
								WHEN $SQ_INSCRIPCION_ENCOLADA   THEN $sq_estado_en_cola
								ELSE                                 $sq_sq_estado_falla
							END AS estado_sincronizacion

					FROM sga_propuestas_aspira	AS inscripciones_propuestas
					JOIN sga_propuestas 		ON (inscripciones_propuestas.propuesta = sga_propuestas.propuesta)
					JOIN vw_personas			ON (inscripciones_propuestas.persona = vw_personas.persona)
					JOIN sga_planes_versiones 	ON (inscripciones_propuestas.plan_version = sga_planes_versiones.plan_version)
					JOIN sga_planes				ON (sga_planes.plan = sga_planes_versiones.plan)
					
					WHERE sga_propuestas.estado		= $propuesta_estado_activo
					AND sga_planes.estado			IN ($plan_estado_activo_vigente, $plan_estado_activo_no_vigente)
					AND sga_planes.sq_sincronizado	= $plan_sincronizado_sq
					AND sga_planes.cobrable			= $si
					AND inscripciones_propuestas.sq_sincronizado NOT IN ($SQ_INSCRIPCION_NO_SINCRONIZADA, $SQ_INSCRIPCION_SINCRONIZADA)
						
					UNION
                                        
					-- Inscripciones a propuestas dadas de baja.
					SELECT 	inscripciones_propuestas.propuesta_aspira,
							inscripciones_propuestas.fecha_inscripcion AS fecha,
							inscripciones_propuestas.nro_transaccion,
							sga_propuestas.propuesta,
							sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas.codigo AS propuesta_codigo,
							vw_personas.persona,
							vw_personas.apellido_nombres AS persona_apellido_nombres,
							sga_planes.plan,
							sga_planes.nombre AS plan_nombre,
							sga_planes.codigo AS plan_codigo,
							inscripciones_propuestas.sq_sincronizado AS corte,
							$sq_sincronizar_baja AS corte_nombre,
                                                        $sq_sq_estado_falla  AS estado_sincronizacion

					FROM sga_propuestas_aspira_log	AS inscripciones_propuestas
					JOIN sga_propuestas				ON (inscripciones_propuestas.propuesta = sga_propuestas.propuesta)
					JOIN vw_personas				ON (inscripciones_propuestas.persona = vw_personas.persona)
					JOIN sga_planes_versiones		ON (inscripciones_propuestas.plan_version = sga_planes_versiones.plan_version)
					JOIN sga_planes					ON (sga_planes.plan = sga_planes_versiones.plan)
					
					WHERE sga_propuestas.estado						= $propuesta_estado_activo
					AND sga_planes.estado			IN ($plan_estado_activo_vigente, $plan_estado_activo_no_vigente)
					AND sga_planes.sq_sincronizado					= $plan_sincronizado_sq
					AND sga_planes.cobrable							= $si
					AND inscripciones_propuestas.sq_sincronizado	= $SQ_INSCRIPCION_FALLO_BAJA
					AND inscripciones_propuestas.operacion			= $estado_baja
                                ) AS resultado_union
                                $where
                                ORDER BY corte_nombre, fecha, persona_apellido_nombres, propuesta_nombre, plan_nombre
		";
		
		return guarani_db::consultar($sql);
	}

        /**
	 * Para llenar el combo "Estado sincronización"
         * de la operación "Sincronizar Novedades con SQ" solapa "Inscripciones a propuestas"
	 *
	 * @return array
	 */
        function get_estados_sincronizacion()
        {
            $sq_estado_en_cola  = guarani_mensajes::get('800_sq_estado_en_cola');
            $sq_sq_estado_falla = guarani_mensajes::get('800_sq_estado_falla');

            return [
                ['valor' => $sq_estado_en_cola, 'descr' => $sq_estado_en_cola],
                ['valor' => $sq_sq_estado_falla, 'descr' => $sq_sq_estado_falla],
            ];
        }

        /**
	 * Si el alumni es moroso retorna la fecha desde. Sino, retorna la cadena '---'.
	 * 
	 * @param int $alumno
	 * @return str
	 */
	function es_alumno_moroso($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	to_char(int_sq_morosos.fecha,'".comunes::formato_fecha_visual."') as fecha_formato_visual
				FROM	int_sq_morosos
				WHERE	int_sq_morosos.alumno = $alumno
		";
		$rs = guarani::db()->consultar_fila($sql);
		return empty($rs) ? '---' : $rs['fecha_formato_visual'];
	}

        /**
	 * Recupera estado de la tabla int_sq_pagos
	 * @param int $nro_transaccion (relacionado a sga_insc_cursada.nro_transaccion o sga_propuestas_aspira.nro_transaccion)
	 * @param string $concepto Posibles valores 'C' (Comisión) o 'P' (Propuesta)
	 * @return string|null $estado Posibles valores 'P' (Pago) o 'V' (Vencido)
	 */
        function get_estado_pago($nro_transaccion, $concepto)
        {
            $concepto = toba::db()->quote($concepto);
            
            $sql = "SELECT estado
                    FROM int_sq_pagos
                    WHERE nro_transaccion = $nro_transaccion
                    AND concepto = $concepto;
            ";
            $rs = guarani::db()->consultar_fila($sql);
            return ($rs['estado'] ?? null);
        }
}