<?php
/**
 * 
 * @package Encuestas
 */
class co_encuestas
{
	function get_listado($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = 'SELECT		gde_encuestas.encuesta,
							gde_encuestas.nombre,
							gde_encuestas.descripcion,
							gde_encuestas.kolla_id_encuesta,
							gde_encuestas.subtipo,
							gde_encuestas.estado,
							gde_subtipos.nombre as subtipo_nombre,
							'.guarani_sql::get_case('gde_encuestas', 'estado', 'estado_nombre', toba::consulta_php('co_comunes')->get_estado_activo_baja())."
				FROM		gde_encuestas
							JOIN gde_subtipos ON gde_encuestas.subtipo = gde_subtipos.subtipo
				$where
				ORDER BY	gde_encuestas.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_listado_grupos($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		gde_grupos.habilitacion,
							gde_habilitaciones.kolla_id_habilitacion,
							gde_habilitaciones.kolla_sincronizado,
							gde_habilitaciones.titulo,
							gde_habilitaciones.descripcion,
							gde_habilitaciones.fecha_desde,
							gde_habilitaciones.fecha_hasta,
							to_char(gde_habilitaciones.fecha_desde, '".comunes::formato_fecha_visual."') || ' al ' || to_char(gde_habilitaciones.fecha_hasta, '".comunes::formato_fecha_visual."') as validez,
							gde_tipos.nombre as habilitacion_tipo,
							".guarani_sql::get_case('gde_habilitaciones', 'tipo_relevamiento', 'tipo_relevamiento_descr', $this->get_tipos_relevamiento()).",
							".guarani_sql::get_case('gde_habilitaciones', 'encuestado', 'encuestado_nombre', $this->get_encuestados()).",
							get_encuestas_habilitacion(gde_grupos.habilitacion) as encuestas,
							(SELECT	COUNT(DISTINCT gde_encuestas_pendientes.respuesta)
								FROM gde_formularios
								JOIN gde_encuestas_pendientes ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
								WHERE gde_formularios.habilitacion = gde_grupos.habilitacion
								  AND gde_formularios.estado = 'A'
							) as cantidad_encuestados,
							(SELECT	COUNT(DISTINCT gde_encuestas_pendientes.respuesta)
								FROM gde_formularios
								JOIN gde_encuestas_pendientes ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
								WHERE gde_formularios.habilitacion = gde_grupos.habilitacion
								  AND gde_formularios.estado = 'A'
								  AND gde_encuestas_pendientes.fecha_respuesta IS NOT NULL
							) as cantidad_contestadas
				FROM		gde_grupos
							JOIN gde_habilitaciones ON gde_grupos.habilitacion = gde_habilitaciones.habilitacion
							JOIN gde_tipos ON gde_habilitaciones.tipo = gde_tipos.tipo
				$where
				GROUP BY	gde_grupos.habilitacion,
							gde_habilitaciones.kolla_id_habilitacion,
							gde_habilitaciones.kolla_sincronizado,
							gde_habilitaciones.titulo,
							gde_habilitaciones.descripcion,
							gde_habilitaciones.tipo_relevamiento,
							gde_habilitaciones.encuestado,
							gde_habilitaciones.fecha_desde,
							gde_habilitaciones.fecha_hasta,
							gde_tipos.nombre
				ORDER BY	gde_habilitaciones.titulo
		";

		return guarani_db::consultar($sql);
	}
	
	function get_datos_habilitacion($seleccion)
	{
		$sql = "SELECT	gde_habilitaciones.habilitacion,
						gde_habilitaciones.kolla_id_habilitacion,
						gde_habilitaciones.kolla_sincronizado,
						gde_habilitaciones.kolla_unidad_gestion,
						gde_habilitaciones.titulo,
						gde_habilitaciones.descripcion,
						gde_habilitaciones.fecha_desde,
						gde_habilitaciones.tipo_relevamiento
				FROM	gde_habilitaciones
				WHERE	gde_habilitaciones.habilitacion = " . quote($seleccion['habilitacion']);
		
		return guarani_db::consultar_fila($sql);
	}

	function get_tipo_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT	gde_habilitaciones.tipo
				FROM	gde_habilitaciones
				WHERE	gde_habilitaciones.habilitacion = $habilitacion
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['tipo'];
	}

	function get_habilitacion_tipos()
	{
		$sql = "SELECT		gde_tipos.tipo as valor,
							gde_tipos.nombre as descr
				FROM		gde_tipos
				ORDER BY	gde_tipos.nombre";
		return guarani_db::consultar($sql);
	}

	function get_habilitacion_alcances_x_tipo($tipo)
	{
		$where = 'true';
		if ($tipo == encuesta::tipo_generica || $tipo == encuesta::tipo_egresados) {
			$where .= ' AND gde_alcances.alcance <> '.encuesta::alcance_actividades;
		}
		$alcances = array(encuesta::alcance_toda_institucion, encuesta::alcance_responsables_academicas, encuesta::alcance_tipos_propuesta, encuesta::alcance_propuestas, encuesta::alcance_actividades);
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			$perfil_datos_restricciones = current(toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos));
			$dominio_unidad_gestion = toba::consulta_php('co_unidades_gestion')->get_dominio_unidad_gestion($perfil_datos_restricciones['clave']);
			switch ($dominio_unidad_gestion) {
				case unidades_gestion::dominio_institucion:
					break;
				case unidades_gestion::dominio_tipo_prop:
					$where .= ' AND gde_alcances.alcance IN ('.encuesta::alcance_tipos_propuesta.', '.encuesta::alcance_propuestas.', '.encuesta::alcance_actividades.')';
					break;
				case unidades_gestion::dominio_ra:
					$where .= ' AND gde_alcances.alcance IN ('.encuesta::alcance_responsables_academicas.', '.encuesta::alcance_propuestas.', '.encuesta::alcance_actividades.')';
					break;
				case unidades_gestion::dominio_prop:
					$where .= ' AND gde_alcances.alcance IN ('.encuesta::alcance_propuestas.', '.encuesta::alcance_actividades.')';
			}
		}
		$sql = "SELECT		gde_alcances.alcance as valor,
							gde_alcances.nombre as descr
				FROM		gde_alcances
				WHERE		$where
				ORDER BY	gde_alcances.nombre";
		return guarani_db::consultar($sql);
	}

	function get_habilitacion_alumnos()
	{
		return array(	array('valor' => encuesta::alumnos_todos, 'descr' => 'Todos los alumnos'),
						array('valor' => encuesta::alumnos_algunos, 'descr' => 'Sólo algunos alumnos'),
						array('valor' => encuesta::alumnos_estado_tesis, 'descr' => 'Alumnos según estado de tesis'));
	}

	function get_habilitacion_alumnos_beca()
	{
		return array(	array('valor' => encuesta::alumnos_todos, 'descr' => 'Todos los alumnos'),
						array('valor' => encuesta::alumnos_algunos, 'descr' => 'Sólo algunos alumnos'));
	}

	function get_habilitacion_inscriptos_comision()
	{
		return array(	array('valor' => encuesta::inscriptos_comision_todos, 'descr' => 'Todos los inscriptos a cursada'),
						array('valor' => encuesta::inscriptos_comision_algunos, 'descr' => 'Según resultado de cursada'));
	}

	function get_habilitacion_docentes()
	{
		return array(	array('valor' => encuesta::docentes_todos, 'descr' => 'Todos los docentes'),
						array('valor' => encuesta::docentes_por_roles, 'descr' => 'Docentes por roles'));
	}

	function get_habilitacion_egresados()
	{
		return array(	array('valor' => encuesta::egresados_todos, 'descr' => 'Todas las certificaciones'),
						array('valor' => encuesta::egresados_algunos, 'descr' => 'Algunas certificaciones'));
	}

	function get_habilitacion_pares()
	{
		return array(	array('valor' => encuesta::pares_responsable_docentes, 'descr' => 'El responsable de la comisión responde sobre el resto de los docentes'),
						array('valor' => encuesta::pares_docentes_responsable, 'descr' => 'Los docentes de la comisión responden sobre el responsable'),
						array('valor' => encuesta::pares_todos, 'descr' => 'Todos los docentes de la comisión responden sobre el resto de los docentes'));
	}

	function get_encuesta_subtipos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT		gde_subtipos.subtipo as valor,
							gde_subtipos.nombre as descr
				FROM		gde_subtipos
							$where
				ORDER BY	gde_subtipos.nombre";
		return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna los datos correspondientes a una encuesta en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
      *	@param integer/array código de encuesta o arreglo de códigos de encuesta.
    */
	function get_datos_encuesta($encuesta)
	{
		if (is_array($encuesta)) {
			$filtro = guarani_sql::armar_condicion_in($encuesta, 'gde_encuestas', 'encuesta');
		} else {
			$encuesta = toba::db()->quote($encuesta);
			$filtro = "gde_encuestas.encuesta = $encuesta";
		}
		return $this->get_listado($filtro);
	}

	/**
	 * Retorna verdadero si el grupo de encuestas dado tiene al menos una respuesta.
	 *
	 * @param integer $habilitacion
	 * @return boolean
	 */
	function existe_respuesta($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT	COUNT(gde_encuestas_pendientes.respuesta) as cant
				FROM	gde_encuestas_pendientes
						JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
				WHERE	gde_encuestas_pendientes.fecha_respuesta IS NOT NULL AND
						gde_formularios.habilitacion = $habilitacion
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	/**
	 * Retorna verdadero si la encuesta dada está asociada a algún grupo de encuestas.
	 *
	 * @param integer $encuesta
	 * @return boolean
	 */
	function encuesta_asociada_a_grupo($encuesta)
	{
		$encuesta = toba::db()->quote($encuesta);
		$sql = "SELECT	COUNT(gde_grupos.habilitacion) as cant
				FROM	gde_grupos
				WHERE	gde_grupos.encuesta = $encuesta
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	/**
	 * Retorna la cantidad de encuestas de una comision que no estan contestadas por el alumno.
	 *
	 * @param integer $alumno
	 * @param integer $comision
	 * @return integer
	 */
	function get_cant_encuestas_no_contestadas_x_comision($alumno, $comision)
	{
		$alumno   = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);
		$encuestado_alumno = toba::db()->quote(encuesta::encuestado_alumno);
		$encuestado_todos = toba::db()->quote(encuesta::encuestado_todos);
		$si = toba::db()->quote(comunes::si);
		$tipo_relevamiento_encuesta = toba::db()->quote(encuesta::tipo_relevamiento_encuestas);

		
		// Recupera encuestas obligatorias7activas/vigentes relacionadas con la comision que el alumno tenga pendiente de contestar.
		$sql = "SELECT	COUNT(*) as cant
				FROM	sga_alumnos
						JOIN gde_encuestas_pendientes ON sga_alumnos.persona = gde_encuestas_pendientes.persona
						JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
						JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
				WHERE	sga_alumnos.alumno = $alumno AND
						gde_formularios.comision = $comision AND
						gde_habilitaciones.obligatoria = $si AND
						gde_habilitaciones.activo      = $si AND
						gde_habilitaciones.kolla_id_habilitacion IS NOT NULL AND 
						gde_formularios.generado_en_kolla = $si AND
						gde_habilitaciones.tipo_relevamiento = $tipo_relevamiento_encuesta AND
						gde_habilitaciones.encuestado  IN ($encuestado_alumno, $encuestado_todos) AND 
						gde_encuestas_pendientes.fecha_respuesta IS NULL AND
						CURRENT_DATE BETWEEN gde_habilitaciones.fecha_desde AND gde_habilitaciones.fecha_hasta
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant']);
	}

	/**
	 * Retorna la cantidad de encuestas obligatorias y activas que no estan contestadas por la persona.
	 *
	 * @param integer $persona
	 * @return integer
	 */
	function get_cant_encuestas_no_contestadas($persona)
	{
		$persona = toba::db()->quote($persona);
		$si = toba::db()->quote(comunes::si);
		$encuestado_alumno = toba::db()->quote(encuesta::encuestado_alumno);
		$encuestado_todos = toba::db()->quote(encuesta::encuestado_todos);
		$tipo_relevamiento_encuesta = toba::db()->quote(encuesta::tipo_relevamiento_encuestas);
		
		// Recupera encuestas obligatorias/activas/vigentes que el alumno tenga pendiente de contestar.
		$sql = "SELECT	COUNT(*) as cant
				FROM	gde_encuestas_pendientes
						JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
						JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
				WHERE	gde_encuestas_pendientes.persona = $persona AND
						gde_habilitaciones.obligatoria = $si AND
						gde_habilitaciones.activo = $si AND
						gde_habilitaciones.kolla_id_habilitacion IS NOT NULL AND 
						gde_formularios.generado_en_kolla = $si AND
						gde_habilitaciones.tipo_relevamiento = $tipo_relevamiento_encuesta AND
						gde_habilitaciones.encuestado  IN ($encuestado_alumno, $encuestado_todos) AND 
						gde_encuestas_pendientes.fecha_respuesta IS NULL AND
						CURRENT_DATE BETWEEN gde_habilitaciones.fecha_desde AND gde_habilitaciones.fecha_hasta
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant']);
	}

	/**
	 * Retorna la cantidad de encuestas obligatorias, activas y vigentes de la propuesta que no estan contestadas
	 *
	 * @param integer $alumno	Identificacion del Alumno
	 * @return integer			Cantidad de encuestas obligatorias pendientes de contestar.
	 */
	function get_cant_encuestas_no_contestadas_propuesta($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$si = toba::db()->quote(comunes::si);
		$no = toba::db()->quote(comunes::no);
		$encuestado_alumno = toba::db()->quote(encuesta::encuestado_alumno);
		$encuestado_todos = toba::db()->quote(encuesta::encuestado_todos);
		$tipo_docente = toba::db()->quote(encuesta::tipo_docente);
		$tipo_academica = toba::db()->quote(encuesta::tipo_academica);
		$tipo_docente_academica = toba::db()->quote(encuesta::tipo_docente_academica);
		$tipo_generica = toba::db()->quote(encuesta::tipo_generica);
		$tipo_egresados = toba::db()->quote(encuesta::tipo_egresados);
		$tipo_relevamiento_encuesta = toba::db()->quote(encuesta::tipo_relevamiento_encuestas);
		
		// Recupera encuestas obligatorias/activas/vigentes de la propuesta del alumno que tenga pendiente de contestar.
		// Tambien encuestas genericas de la responsable academica de la propuesta.
		$sql = "SELECT	COUNT(DISTINCT gde_encuestas_pendientes.respuesta) as cant
				FROM sga_alumnos 
				JOIN gde_encuestas_pendientes ON gde_encuestas_pendientes.persona = sga_alumnos.persona
				JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
				JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
				JOIN gde_formulario_items ON gde_formulario_items.formulario = gde_formularios.formulario
				JOIN gde_items ON gde_items.item = gde_formulario_items.item
				
				WHERE sga_alumnos.alumno = $alumno
				  AND gde_habilitaciones.obligatoria = $si
				  AND gde_habilitaciones.activo      = $si
				  AND gde_habilitaciones.kolla_id_habilitacion IS NOT NULL
				  AND gde_formularios.generado_en_kolla = $si
				  AND gde_habilitaciones.tipo_relevamiento = $tipo_relevamiento_encuesta
				  AND gde_habilitaciones.encuestado IN ($encuestado_alumno, $encuestado_todos)  
				  AND gde_encuestas_pendientes.fecha_respuesta IS NULL
				  AND CURRENT_DATE BETWEEN gde_habilitaciones.fecha_desde AND gde_habilitaciones.fecha_hasta
				  AND ((-- 1. Evaluacion Academica y/o Docente. Comisiones de actividades de la propuesta del alumno.
					    gde_habilitaciones.tipo IN ($tipo_docente, $tipo_academica, $tipo_docente_academica) AND   
					    EXISTS (SELECT 1 
				                  FROM sga_comisiones
				                  JOIN vw_actividades_plan ON vw_actividades_plan.elemento = sga_comisiones.elemento
								  JOIN vw_planes ON vw_planes.plan_version = vw_actividades_plan.plan_version
								 WHERE sga_comisiones.comision = gde_items.comision
								   AND vw_planes.propuesta = sga_alumnos.propuesta
				                )
					   ) OR 
				       (-- 2. Evaluacion Generica
				        gde_habilitaciones.tipo = $tipo_generica AND
						((gde_habilitaciones.encuesta_por_propuesta = $no AND 
						  EXISTS (SELECT 1 FROM gde_propuestas WHERE gde_propuestas.habilitacion = gde_habilitaciones.habilitacion AND gde_propuestas.propuesta = sga_alumnos.propuesta)) OR 
						 (gde_habilitaciones.encuesta_por_propuesta = $si AND gde_items.propuesta = sga_alumnos.propuesta) 
						)
					   ) OR 
				       (-- 3. Egresados. Certificados relacionados con planes de la propuesta del alumno. 
					    gde_habilitaciones.tipo = $tipo_egresados AND 
						gde_items.certificado IN (SELECT sga_planes_certificados.certificado 
						                            FROM vw_planes 
													JOIN sga_planes_certificados ON sga_planes_certificados.plan_version = vw_planes.plan_version
						                           WHERE vw_planes.propuesta = (SELECT propuesta FROM sga_alumnos WHERE alumno = $alumno)
												  ) 
					   )
					  )			
				  
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant']);
	}

	/**
	 * Retorna la cantidad de encuestas obligatorias, activas y vigentes de la responsable academica de la propuesta del alumno que no estan contestadas
	 *
	 * @param integer $alumno	Identificacion del Alumno
	 * @return integer			Cantidad de encuestas obligatorias pendientes de contestar.
	 */
	function get_cant_encuestas_no_contestadas_resp_academica($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$si = toba::db()->quote(comunes::si);
		$no = toba::db()->quote(comunes::no);
		$encuestado_alumno = toba::db()->quote(encuesta::encuestado_alumno);
		$encuestado_todos = toba::db()->quote(encuesta::encuestado_todos);
		$tipo_docente = toba::db()->quote(encuesta::tipo_docente);
		$tipo_academica = toba::db()->quote(encuesta::tipo_academica);
		$tipo_docente_academica = toba::db()->quote(encuesta::tipo_docente_academica);
		$tipo_generica = toba::db()->quote(encuesta::tipo_generica);
		$tipo_egresados = toba::db()->quote(encuesta::tipo_egresados);
		$tipo_relevamiento_encuesta = toba::db()->quote(encuesta::tipo_relevamiento_encuestas);
		
		// Recupera encuestas obligatorias/activas/vigentes de la responsable academica de la propuesta del alumno que tenga pendiente de contestar.
		// Tambien encuestas genericas de la responsable academica de la propuesta.
		$sql = "SELECT	COUNT(DISTINCT gde_encuestas_pendientes.respuesta) as cant
				FROM sga_alumnos 
				JOIN gde_encuestas_pendientes ON gde_encuestas_pendientes.persona = sga_alumnos.persona
				JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
				JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
				JOIN gde_formulario_items ON gde_formulario_items.formulario = gde_formularios.formulario
				JOIN gde_items ON gde_items.item = gde_formulario_items.item
				
				WHERE sga_alumnos.alumno = $alumno
				  AND gde_habilitaciones.obligatoria = $si
				  AND gde_habilitaciones.activo      = $si
				  AND gde_habilitaciones.kolla_id_habilitacion IS NOT NULL
				  AND gde_formularios.generado_en_kolla = $si
				  AND gde_habilitaciones.tipo_relevamiento = $tipo_relevamiento_encuesta
				  AND gde_habilitaciones.encuestado IN ($encuestado_alumno, $encuestado_todos)  
				  AND gde_encuestas_pendientes.fecha_respuesta IS NULL
				  AND CURRENT_DATE BETWEEN gde_habilitaciones.fecha_desde AND gde_habilitaciones.fecha_hasta
				  AND ((-- 1. Evaluacion Academica y/o Docente. Comisiones de actividades de propuestas del alumno de la misma responsable academica
					    gde_habilitaciones.tipo IN ($tipo_docente, $tipo_academica, $tipo_docente_academica) AND   
						-- Que la comision sea de de una actividad de la misma propuesta u otra propuesta del alumno que sean de la misma responsable academica.
					    EXISTS (SELECT 1  
				                  FROM sga_comisiones
								  JOIN sga_alumnos as alu2 ON alu2.persona = sga_alumnos.persona
								  JOIN sga_propuestas_ra as pra1 ON pra1.propuesta = sga_alumnos.propuesta
								  JOIN sga_propuestas_ra as pra2 ON (pra2.responsable_academica = pra1.responsable_academica AND pra2.propuesta = alu2.propuesta)
				                  JOIN vw_actividades_plan ON (vw_actividades_plan.elemento = sga_comisiones.elemento AND vw_actividades_plan.propuesta = alu2.propuesta)
								 WHERE sga_comisiones.comision = gde_items.comision
				                )
					   ) OR 
				       (-- 2. Evaluacion Generica
				        gde_habilitaciones.tipo = $tipo_generica AND
						((gde_habilitaciones.encuesta_por_propuesta = $no AND 
                          (gde_habilitaciones.alcance = 1 OR
                           (gde_habilitaciones.alcance = 2 AND -- RA
                            EXISTS (SELECT 1 FROM gde_responsables_academicas as gra
                                     WHERE gra.habilitacion = gde_habilitaciones.habilitacion 
								     AND gra.responsable_academica IN (SELECT pra2.responsable_academica
						                                FROM sga_alumnos as alu1
							                            JOIN sga_alumnos as alu2 ON alu2.persona = alu1.persona
														JOIN sga_propuestas_ra as pra1 ON pra1.propuesta = alu1.propuesta
														JOIN sga_propuestas_ra as pra2 ON (pra2.responsable_academica = pra1.responsable_academica AND pra2.propuesta = alu2.propuesta)
						                               WHERE alu1.alumno = $alumno)
                                      )       
                             ) OR 
                           (gde_habilitaciones.alcance = 3 AND -- tipo de Prop.
                            EXISTS (SELECT 1 FROM gde_propuestas_tipos as gpt
                                     WHERE gpt.habilitacion = gde_habilitaciones.habilitacion 
								       AND gpt.propuesta_tipo IN (SELECT p2.propuesta_tipo
						                                FROM sga_alumnos as alu1
							                            JOIN sga_alumnos as alu2 ON alu2.persona = alu1.persona
                                                        JOIN sga_propuestas as p2 ON p2.propuesta = alu2.propuesta        
														JOIN sga_propuestas_ra as pra1 ON pra1.propuesta = alu1.propuesta
														JOIN sga_propuestas_ra as pra2 ON (pra2.responsable_academica = pra1.responsable_academica AND pra2.propuesta = alu2.propuesta)
						                               WHERE alu1.alumno = $alumno)
                                      )       
                             ) OR 
                           (gde_habilitaciones.alcance = 4 AND -- Prop.
						    EXISTS (SELECT 1 FROM gde_propuestas 
						           WHERE gde_propuestas.habilitacion = gde_habilitaciones.habilitacion 
								     AND gde_propuestas.propuesta IN (SELECT alu2.propuesta 
						                                FROM sga_alumnos as alu1
							                            JOIN sga_alumnos as alu2 ON alu2.persona = alu1.persona
														JOIN sga_propuestas_ra as pra1 ON pra1.propuesta = alu1.propuesta
														JOIN sga_propuestas_ra as pra2 ON (pra2.responsable_academica = pra1.responsable_academica AND pra2.propuesta = alu2.propuesta)
						                               WHERE alu1.alumno = $alumno)
								    )
						   )
                          )
						 ) OR 
						 (gde_habilitaciones.encuesta_por_propuesta = $si
						  AND gde_items.propuesta IN (SELECT alu2.propuesta 
						                                FROM sga_alumnos as alu1
							                            JOIN sga_alumnos as alu2 ON alu2.persona = alu1.persona
														JOIN sga_propuestas_ra as pra1 ON pra1.propuesta = alu1.propuesta
														JOIN sga_propuestas_ra as pra2 ON (pra2.responsable_academica = pra1.responsable_academica AND pra2.propuesta = alu2.propuesta)
						                               WHERE alu1.alumno = $alumno
													)
					 	 ) 
					    )
					   ) OR		
				       (-- 3. Egresados. Certificados relacionados con planes de la propuesta del alumno. 
					    gde_habilitaciones.tipo = $tipo_egresados AND 
						gde_items.certificado IN (SELECT sga_planes_certificados.certificado 
						                            FROM sga_alumnos as alu1
													JOIN sga_alumnos as alu2 ON alu2.persona = alu1.persona
													JOIN sga_propuestas_ra as pra1 ON pra1.propuesta = alu1.propuesta
													JOIN sga_propuestas_ra as pra2 ON (pra2.responsable_academica = pra1.responsable_academica AND pra2.propuesta = alu2.propuesta)
													JOIN vw_planes ON vw_planes.propuesta = alu2.propuesta
													JOIN sga_planes_certificados ON sga_planes_certificados.plan_version = vw_planes.plan_version
						                           WHERE alu1.alumno = $alumno
												  ) 
					   )
					  )			
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant']);
	}

	/**
	 * Retorna la cantidad de encuestas activas asociadas a una comisión.
	 *
	 * @param integer $comision
	 * @return integer
	 */
	function get_cant_encuestas_x_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT	COUNT(gde_formularios.comision) as cant
				FROM	gde_formularios
						JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
				WHERE	gde_habilitaciones.tipo IN (".encuesta::tipo_docente.", ".encuesta::tipo_docente_academica.") AND
						gde_formularios.estado = '".comunes::estado_activo."' AND
						gde_habilitaciones.activo = '".comunes::si."' AND
						gde_formularios.comision = $comision
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant']);
	}

	/**
	 * Retorna el listado de encuestas activas asociadas a una comisión.
	 *
	 * @param integer $comision
	 * @return array
	 */
	function get_encuestas_x_comision($comision)
	{
		$comision = toba::db()->quote($comision);
		$sql = "SELECT	gde_habilitaciones.titulo,
						gde_habilitaciones.fecha_desde,
						gde_habilitaciones.fecha_hasta
				FROM	gde_formularios,
						gde_habilitaciones
				WHERE	gde_formularios.habilitacion = gde_habilitaciones.habilitacion AND
						gde_habilitaciones.tipo IN (".encuesta::tipo_docente.", ".encuesta::tipo_docente_academica.") AND
						gde_formularios.estado = '".comunes::estado_activo."' AND
						gde_habilitaciones.activo = '".comunes::si."' AND
						gde_formularios.comision = $comision
		";
		return guarani_db::consultar($sql);
	}

	////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////
	/////CONSULTAS REFERIDAS A LA SINCRONIZACION CON KOLLA
	////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////

    /**
     * Retorna los datos de una habilitación junto a sus ítems y formularios asociados.
     * Se utiliza para la comunicación con el web service de SIU-Kolla.
     *
     * @param integer $habilitacion
     * @return array
     */
    function get_datos_habilitacion_rest($habilitacion)
    {
        $datos = array();

        $habilitacion = toba::db()->quote($habilitacion);

        $datos['habilitacion'] = $this->get_habilitacion($habilitacion);

        $datos['elementos'] = $this->get_elementos($habilitacion);

        $datos['conceptos'] = $this->get_conceptos($habilitacion);

        $datos['formularios'] = $this->get_formularios($habilitacion);

        return $datos;
    }
	
    protected function get_habilitacion($habilitacion)
	{
        // Datos básicos de la habilitación.
        $sql = "SELECT	gde_habilitaciones.fecha_desde,
						gde_habilitaciones.fecha_hasta,
						gde_habilitaciones.anonima,
						gde_habilitaciones.titulo as descripcion,
						gde_habilitaciones.kolla_id_habilitacion as habilitacion,
						gde_habilitaciones.kolla_estilo_visual as estilo,
						conf.valor as url_imagenes_base
				FROM	gde_habilitaciones,
			  		    par_configuraciones as conf

				WHERE	gde_habilitaciones.habilitacion = $habilitacion
						AND conf.nombre = 'url_fotos'

		";
        return guarani_db::consultar_fila($sql);
    }

    protected function get_elementos($habilitacion)
	{
        // Ítems para mandar solos.
        $sql = "SELECT
                        i.item as elemento,
						i.titulo as descripcion,
						CASE
						  WHEN i.docente IS NOT NULL AND mdp_personas_foto.sincronizada = 1 THEN mdp_personas.id_imagen
						  WHEN i.docente IS NOT NULL THEN 'DFLT'
						  ELSE cast(NULL as varchar(255))
						END as foto_persona
				FROM

						gde_items as i
							LEFT JOIN sga_docentes ON sga_docentes.docente = i.docente
						    LEFT JOIN mdp_personas ON mdp_personas.persona = sga_docentes.persona
							LEFT JOIN mdp_personas_foto ON mdp_personas_foto.persona = mdp_personas.persona
				WHERE	i.habilitacion = $habilitacion
				AND		i.estado = 'A'
				AND     i.generado_en_kolla = 'N'
		";
        $elementos = guarani_db::consultar($sql);
        foreach($elementos as &$elemento){
            $foto = $elemento['foto_persona'];
            unset($elemento['foto_persona']);

            if($foto == 'DFLT'){
                $elemento['url_img'] = encuesta_nucleo::imagen_docente_default; //default
            }else if($foto == null){
                //$elemento['url'] = 'NULL'; //default
                //no va nada
//				$elemento['url_img'] = ''; //default
            }else {
                $elemento['url_img'] = $this->get_url_foto($foto); // $url_base . $foto
            }
        }

        return $elementos;
    }

    protected function get_conceptos($habilitacion)
	{
        // Conceptos: hago uno igual a cada formulario - como en guarani 3.9
        $sql = "SELECT	gde_formularios.formulario as concepto,
						gde_formularios.titulo as descripcion
				FROM	gde_formularios
				WHERE	gde_formularios.habilitacion = $habilitacion
				        AND gde_formularios.estado = 'A'
				        AND gde_formularios.generado_en_kolla = 'N'
				ORDER BY gde_formularios.formulario asc
		";
        return guarani_db::consultar($sql);
    }


    /**
     * @param $habilitacion
     * @return array
     */
    protected function get_formularios($habilitacion)
    {
		
		$estado_activo = toba::db()->quote(comunes::estado_activo);
		$no = toba::db()->quote(comunes::no);
		
        $sql = "SELECT		gde_formularios.formulario,
							gde_formulario_items.item as elemento,
							gde_encuestas.kolla_id_encuesta as encuesta
				FROM		gde_formularios
							JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
							JOIN gde_formulario_items ON gde_formularios.formulario = gde_formulario_items.formulario
							JOIN gde_items ON gde_formulario_items.item = gde_items.item
							JOIN gde_grupos ON (gde_items.habilitacion = gde_grupos.habilitacion AND gde_items.encuesta = gde_grupos.encuesta)
							JOIN gde_encuestas ON gde_grupos.encuesta = gde_encuestas.encuesta
							JOIN gde_subtipos_x_tipo ON (gde_habilitaciones.tipo = gde_subtipos_x_tipo.tipo AND gde_encuestas.subtipo = gde_subtipos_x_tipo.subtipo)
				 WHERE		gde_habilitaciones.habilitacion = $habilitacion AND
							gde_items.estado = $estado_activo AND
							gde_formularios.generado_en_kolla = $no AND
							gde_formularios.estado = $estado_activo
				 ORDER BY	gde_formularios.formulario asc,
							gde_subtipos_x_tipo.orden, -- orden de la encuesta
							gde_items.titulo
         ";

        $encuestas_form = guarani_db::consultar($sql);

        // Formularios.
        $sql = "SELECT	gde_formularios.formulario as formulario,
						gde_formularios.titulo as nombre,
						gde_formularios.estado,
						gde_formularios.formulario as concepto --el concepto tiene el mismo id que el formulario
				FROM	gde_formularios
				WHERE	gde_formularios.habilitacion = $habilitacion AND
						gde_formularios.generado_en_kolla = $no AND
						gde_formularios.estado = $estado_activo
						
				ORDER BY gde_formularios.formulario asc
		";
        $formularios = guarani_db::consultar($sql);

        $cant_encuestas = guarani_arreglos::count($encuestas_form);
        $cant_forms = guarani_arreglos::count($formularios);
        $it = 0;

        $form_actual = null;
        for ($f = 0; $f < $cant_forms; $f++) {
            $form_actual = & $formularios[$f];
            $form_actual['detalle'] = array();
            while ($it < $cant_encuestas) { //obs: los datos estan ordenados
                $item_actual = $encuestas_form[$it];
                if ($item_actual['formulario'] == $form_actual['formulario']) {
                    $form_actual['detalle'][] = array(
                        'encuesta' => $item_actual['encuesta'],
                        'elemento' => $item_actual['elemento']
                    );
                    $it++; //avanzo de item
                } else break;
            }
        }
        toba::logger()->info("Formularios procesados: $f /$cant_forms - Items procesados $it/$cant_encuestas");
        return $formularios;
    }
	
	function get_tipos_estado_sync()
	{
		return array(	array('valor' => encuesta::sinc_error, 'descr' => 'Error'),
						array('valor' => encuesta::sinc_pendiente, 'descr' => 'Pendiente'),
						array('valor' => encuesta::sinc_ok, 'descr' => 'Sincronizada'));
	}
	
	function get_listado_respuestas($filtro)
	{
		if(!empty($filtro)){
			$filtro = "AND $filtro";
		}
		$sql =
				"SELECT 
				  gde_formularios.titulo,
				  gde_encuestas_pendientes.fecha_respuesta, 
				  gde_encuestas_pendientes.sync_status as estado_id,
				  gde_encuestas_pendientes.sync_status as estado 
				FROM 
				  gde_encuestas_pendientes, 
				  gde_formularios
				WHERE 
				  gde_formularios.formulario = gde_encuestas_pendientes.formulario
				AND
				  gde_encuestas_pendientes.sync_status IS NOT NULL
				  $filtro
					";
		return guarani_db::consultar($sql);
	}
	
	function get_listado_log_sincronizacion($filtro)
	{
		if(!empty($filtro)){
			$filtro = "WHERE $filtro";
		}
		$sql = "SELECT log, momento, status, detalle
  				FROM gde_sync_kolla
				$filtro
				ORDER BY 1 DESC";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene encuestas pendientes con un estado de sincronizacion dado.
	 * @param type $fecha
	 */
	function get_respuestas_pendientes($estado)
	{
		$e = quote($estado);
		$sql = "SELECT 
			h.kolla_id_habilitacion as habilitacion, 
			p.hash as cui, 
			p.formulario as formulario --Ver cual es el form que le mando como id!!
		--	,p.fecha_respuesta
		  FROM 
			gde_encuestas_pendientes p
			INNER JOIN gde_formularios f ON p.formulario = f.formulario
			INNER JOIN gde_habilitaciones h ON f.habilitacion = h.habilitacion
		  WHERE 
			 p.sync_status = $e;
		";
		return guarani_db::consultar($sql, PDO::FETCH_NUM);
	}
	
	/**
	 * Obtiene una encuesta pendiente. Hash ya es unico, habilitacion y formulario
	 * son de control.
	 * @param type $fecha
	 */
	function get_encuesta_pendiente($habilitacion_kolla, $hash, $formulario)
	{
		//NOTAR QUE SE CASTEAN LAS COSAS A INT porque puede venir un string de Kolla y rompe la op.
		$hab = quote((int)$habilitacion_kolla);
		$formulario = quote((int)$formulario); 
		$hash = quote($hash);

		$sql = "SELECT
		  fecha_respuesta,
		  sync_status as estado
		FROM
		gde_encuestas_pendientes p
		INNER JOIN gde_formularios f ON p.formulario = f.formulario
		INNER JOIN gde_habilitaciones h ON f.habilitacion = h.habilitacion
		WHERE
			h.kolla_id_habilitacion = $hab
		AND p.hash = $hash
		AND p.formulario = $formulario
		";
		return guarani_db::consultar_fila($sql);
	}
	
	
	/**
	 * Obtiene respuestas inexistentes reportadas o no reportadas
	 * @param boolean $reportadas 
	 */
	function get_respuestas_inexistentes($reportadas)
	{
		$status = $reportadas? 1 : 0;
		$sql = "SELECT
			p.habilitacion as habilitacion,
			p.codigo_externo as cui,
			p.concepto_externo as formulario
		--	,p.fecha_respuesta
		  FROM
			gde_sync_resp_inex p
		  WHERE
			 p.reportado = $status;
		";
		return guarani_db::consultar($sql, PDO::FETCH_NUM);
	}
	
	//copy-paste de g3w3
	function get_url_foto($id_imagen)
	{
		$carpeta = substr($id_imagen, 0, 32);
		return "mediana/$carpeta/$id_imagen";
	}

    function get_tipos_relevamiento()
    {
		return array(	array('valor' => encuesta::tipo_relevamiento_becas, 'descr' => encuesta::tipo_relevamiento_becas_descr),
						array('valor' => encuesta::tipo_relevamiento_encuestas, 'descr' => encuesta::tipo_relevamiento_encuestas_descr)
					);
	}

	function get_encuestados()
	{
		$sql = "SELECT	gde_encuestado.encuestado as valor,
						gde_encuestado.nombre as descr
				FROM	gde_encuestado
		";
		return guarani_db::consultar($sql);
	}

	function get_habilitacion_encuestados_x_tipos($tipo_relevamiento, $tipo)
	{
		$where = '';
		if ($tipo_relevamiento == encuesta::tipo_relevamiento_becas) {
			$where = " AND gde_encuestado.encuestado = '".encuesta::encuestado_alumno."'";
		}
		$tipo = toba::db()->quote($tipo);
		$sql = "SELECT	gde_encuestado.encuestado as valor,
						gde_encuestado.nombre as descr
				FROM	gde_encuestado_tipos
						JOIN gde_encuestado ON gde_encuestado_tipos.encuestado = gde_encuestado.encuestado
				WHERE	gde_encuestado_tipos.tipo = $tipo
						$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la configuración de datos censales visibles y obligatorios para una habilitación y un rango de orden.
	 * 
	 * @param int $indice_inicial
	 * @param int $indice_final
	 * @return array
	 */
	function get_datos_censales_config($indice_inicial, $indice_final, $habilitacion=null)
	{
		$indice_inicial = toba::db()->quote($indice_inicial);
		$indice_final = toba::db()->quote($indice_final);
		$select = "'".comunes::no."' as obligatorio";
		$from = '';
		if (!is_null($habilitacion)) {
			$select = 'gde_datos_censales_config.obligatorio';
			$habilitacion = toba::db()->quote($habilitacion);
			$from = "LEFT OUTER JOIN gde_datos_censales_config ON mdp_datos_censales_config.tabla = gde_datos_censales_config.tabla AND mdp_datos_censales_config.campo = gde_datos_censales_config.campo AND gde_datos_censales_config.habilitacion = $habilitacion";
		}
		$sql = "SELECT		mdp_datos_censales_config.tabla,
							mdp_datos_censales_config.campo,
							mdp_datos_censales_config.etiqueta,
							mdp_datos_censales_config.orden,
							$select
				FROM		mdp_datos_censales_config
							$from
				WHERE		mdp_datos_censales_config.orden BETWEEN $indice_inicial AND $indice_final AND
							mdp_datos_censales_config.visible = '".comunes::si."'
				ORDER BY	mdp_datos_censales_config.orden
		";
		return guarani_db::consultar($sql);
	}

	function get_reporte_encuestas_actuacion_docente($where = '')
	{
		if (!empty($where)) {
			$where = "AND $where";
		}
		$where_gatillo = 'AND true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo_docente = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_docentes', 'sga_docentes');
			$where_gatillo_comision = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_comisiones', 'sga_comisiones'); 
			$where_gatillo = "AND (gde_items.docente IS NOT NULL AND $where_gatillo_docente OR gde_items.docente IS NULL AND $where_gatillo_comision)";
		}
		$sql = "SELECT		CAST(gde_habilitaciones.habilitacion as text) || '-' || cast(gde_formularios.formulario as text) as id,
							gde_encuestas.encuesta as encuesta,
							gde_encuestas.nombre as encuesta_titulo,
							gde_encuestas.kolla_unidad_gestion as kolla_unidad_gestion,
							gde_habilitaciones.habilitacion as habilitacion, 
							gde_habilitaciones.titulo as habilitacion_titulo,
							gde_formularios.formulario as formulario,
							COALESCE(gde_formularios.formulario_g2, gde_formularios.formulario) as formulario_externo,
							gde_formularios.titulo as formulario_titulo, 
							gde_items.item as elemento,
							COALESCE(gde_items.elemento_g2, gde_items.item) as elemento_externo,
							gde_encuestas.kolla_id_encuesta as kolla_id_encuesta,
							gde_habilitaciones.kolla_id_habilitacion as kolla_id_habilitacion,
							gde_habilitaciones.kolla_password as kolla_password,
							(SELECT COUNT(*) FROM gde_encuestas_pendientes WHERE gde_formularios.formulario = gde_encuestas_pendientes.formulario) as cant_encuestados,
							(SELECT COUNT(*) FROM gde_encuestas_pendientes WHERE gde_formularios.formulario = gde_encuestas_pendientes.formulario AND gde_encuestas_pendientes.fecha_respuesta IS NOT NULL) as cant_respondidas,
							sga_elementos.nombre as actividad_nombre,
							sga_periodos.nombre as periodo_lectivo_nombre,
							sga_comisiones.nombre as comision_nombre,
							sga_comisiones.comision,
							gde_encuestas.subtipo as subtipo_encuesta,
							gde_subtipos.nombre as subtipo_encuesta_desc,
							COALESCE(vw_personas.apellido_nombres, '- SIN DOCENTE -') as apellido_nombre_docente,
							sga_docentes.docente
                FROM		gde_habilitaciones
							JOIN gde_grupos ON gde_habilitaciones.habilitacion = gde_grupos.habilitacion
							JOIN gde_encuestas ON gde_grupos.encuesta = gde_encuestas.encuesta
							JOIN gde_subtipos ON gde_encuestas.subtipo = gde_subtipos.subtipo
							JOIN gde_formularios ON gde_habilitaciones.habilitacion = gde_formularios.habilitacion
							JOIN gde_formulario_items ON gde_formularios.formulario = gde_formulario_items.formulario
							JOIN gde_items ON (gde_formulario_items.item = gde_items.item AND gde_grupos.habilitacion = gde_items.habilitacion AND gde_grupos.encuesta = gde_items.encuesta)
							LEFT JOIN sga_comisiones ON sga_comisiones.comision = gde_formularios.comision
							LEFT JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							LEFT JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							LEFT JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
							LEFT JOIN sga_docentes ON gde_items.docente = sga_docentes.docente
							LEFT JOIN vw_personas ON sga_docentes.persona = vw_personas.persona
				WHERE		gde_habilitaciones.kolla_id_habilitacion IS NOT NULL AND
							gde_habilitaciones.tipo IN (".encuesta::tipo_docente.", ".encuesta::tipo_academica.", ".encuesta::tipo_docente_academica.", ".encuesta::tipo_pares.") AND
							gde_formularios.estado = '".comunes::estado_activo."' AND
							gde_formularios.generado_en_kolla = '".comunes::si."' AND
							gde_items.estado = '".comunes::estado_activo."' AND
							gde_items.generado_en_kolla = '".comunes::si."'
							$where_gatillo
							$where
				ORDER BY	COALESCE(vw_personas.apellido_nombres, 'zzzzz'),
							encuesta_titulo,
							actividad_nombre
		";
		return guarani::db()->consultar($sql, null, false);
	}
	
	function get_id($id, $elemento_externo)
	{
		$sql = "
				SELECT	cast(h.habilitacion as text) || '-' || cast(f.formulario as text) as id,
						e.encuesta as encuesta,
						e.nombre as encuesta_titulo,
						e.kolla_unidad_gestion as kolla_unidad_gestion,
						h.habilitacion as habilitacion, 
						h.titulo as habilitacion_titulo,
						f.formulario as formulario,
						COALESCE(f.formulario_g2, f.formulario) as formulario_externo,
						f.titulo as formulario_titulo, 
						i.item as elemento,
						COALESCE(i.elemento_g2, i.item) as elemento_externo,
						e.kolla_id_encuesta as kolla_id_encuesta,
						h.kolla_id_habilitacion as kolla_id_habilitacion,
						h.kolla_password as kolla_password,
						(SELECT COUNT(*) FROM gde_encuestas_pendientes as ep WHERE ep.formulario = f.formulario) as cant_encuestados,
						(SELECT COUNT(*) FROM gde_encuestas_pendientes as ep WHERE ep.formulario = f.formulario AND ep.fecha_respuesta IS NOT NULL) as cant_respondidas,
						sga_elementos.nombre as actividad_nombre,
						p_pl.nombre as periodo_lectivo_nombre,
						sga_comisiones.nombre as comision_nombre,
						sga_comisiones.comision,
						e.subtipo as subtipo_encuesta,
						st.nombre as subtipo_encuesta_desc,
						p.apellido_nombres as apellido_nombre_docente,
						d.docente
                FROM    gde_habilitaciones as h
						JOIN gde_grupos as g ON h.habilitacion = g.habilitacion
						JOIN gde_encuestas as e ON g.encuesta = e.encuesta
						JOIN gde_subtipos as st ON e.subtipo = st.subtipo
						JOIN gde_formularios as f ON h.habilitacion = f.habilitacion
						JOIN gde_formulario_items as fi ON f.formulario = fi.formulario
						JOIN gde_items as i ON (fi.item = i.item AND g.habilitacion = i.habilitacion AND g.encuesta = i.encuesta)
						LEFT JOIN sga_comisiones ON sga_comisiones.comision = f.comision
						LEFT JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = sga_comisiones.periodo_lectivo
						LEFT JOIN sga_periodos as p_pl ON p_pl.periodo = pl.periodo
						LEFT JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
						LEFT JOIN sga_docentes as d ON i.docente = d.docente
						LEFT JOIN vw_personas as p ON p.persona = d.persona
                WHERE	h.kolla_id_habilitacion IS NOT NULL
						AND h.tipo IN (".encuesta::tipo_docente.",".encuesta::tipo_docente_academica.",".encuesta::tipo_academica.")     
						AND f.estado = '".comunes::estado_activo."' -- Formularios activos
						AND f.generado_en_kolla = '".comunes::si."' -- Formularios generados en Kolla
						AND i.estado = '".comunes::estado_activo."'
						AND i.generado_en_kolla = '".comunes::si."'
						AND cast(h.habilitacion as text) || '-' || cast(f.formulario as text) = '$id'
						AND COALESCE(i.elemento_g2, i.item) = '$elemento_externo'
                 ORDER BY 6";
		return guarani::db()->consultar_fila($sql);
	}
	
	function get_listado_convocatorias($where) 
	{
		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT	gde_habilitaciones.habilitacion,
						gde_habilitaciones.titulo,
						gde_habilitaciones.fecha_desde,
						gde_habilitaciones.fecha_hasta,
						gde_habilitaciones.becas_convocatoria AS convocatoria
						
				FROM	gde_habilitaciones
				WHERE	gde_habilitaciones.becas_convocatoria IS NOT null
						$where";
		
		return guarani::db()->consultar($sql);
	}

	function get_definicion_convocatoria_apiv1($becas_convocatoria)
	{
		$becas_convocatoria = toba::db()->quote($becas_convocatoria);
		$sql = "SELECT	gde_habilitaciones.habilitacion,
						gde_habilitaciones.titulo,
						gde_habilitaciones.descripcion,
						gde_habilitaciones.fecha_desde,
						gde_habilitaciones.fecha_hasta,
						gde_habilitaciones.fecha_evaluacion_ha_desde,
						gde_habilitaciones.fecha_evaluacion_ha_hasta as fecha_evaluacion_ha,
						gde_habilitaciones.activo,
						gde_habilitaciones.kolla_id_habilitacion,
						gde_habilitaciones.kolla_unidad_gestion,
						gde_habilitaciones.edad_desde,
						gde_habilitaciones.edad_hasta,
						gde_habilitaciones.trabaja,
						gde_habilitaciones.tiene_hijos,
						gde_habilitaciones.alquila,
						gde_habilitaciones.tiene_cobertura_de_salud,
						gde_habilitaciones.vive_cursa_misma_localidad,
						gde_habilitaciones.alcance,
						gde_alcances.nombre as alcance_nombre
				FROM	gde_habilitaciones
						JOIN gde_alcances ON gde_habilitaciones.alcance = gde_alcances.alcance
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		$definicion = guarani::db()->consultar_fila($sql);
		if ($definicion) {
			switch ($definicion['alcance']) {
				case encuesta::alcance_toda_institucion:
					$entidades = array();
					break;
				case encuesta::alcance_responsables_academicas:
					$entidades = $this->get_responsables_academicas_habilitacion($definicion['habilitacion']);
					break;
				case encuesta::alcance_tipos_propuesta:
					$entidades = $this->get_tipos_propuesta_habilitacion($definicion['habilitacion']);
					break;
				case encuesta::alcance_propuestas:
					$entidades = $this->get_propuestas_habilitacion($definicion['habilitacion']);
					break;
			}
			$definicion['alcance'] = array('valor' => $definicion['alcance'], 'descripcion' => $definicion['alcance_nombre'], 'entidades' => $entidades);
			$definicion['modalidades'] = $this->get_modalidades_habilitacion($definicion['habilitacion']);
			$definicion['ubicaciones'] = $this->get_ubicaciones_habilitacion($definicion['habilitacion']);
			$definicion['datos_censales'] = aplanar_matriz($this->get_datos_censales_config_habilitacion($definicion['habilitacion']));
			return array($definicion);
		} else {
			return array();
		}
	}

	function get_definicion_convocatoria($becas_convocatoria)
	{
		$becas_convocatoria = toba::db()->quote($becas_convocatoria);
		$sql = "SELECT	gde_habilitaciones.habilitacion,
						gde_habilitaciones.titulo,
						gde_habilitaciones.descripcion,
						gde_habilitaciones.fecha_desde,
						gde_habilitaciones.fecha_hasta,
						gde_habilitaciones.fecha_evaluacion_ha_desde,
						gde_habilitaciones.fecha_evaluacion_ha_hasta,
						gde_habilitaciones.activo,
						gde_habilitaciones.kolla_id_habilitacion,
						gde_habilitaciones.kolla_unidad_gestion,
						gde_habilitaciones.edad_desde,
						gde_habilitaciones.edad_hasta,
						gde_habilitaciones.trabaja,
						gde_habilitaciones.tiene_hijos,
						gde_habilitaciones.alquila,
						gde_habilitaciones.tiene_cobertura_de_salud,
						gde_habilitaciones.vive_cursa_misma_localidad,
						gde_habilitaciones.alcance,
						gde_alcances.nombre as alcance_nombre
				FROM	gde_habilitaciones
						JOIN gde_alcances ON gde_habilitaciones.alcance = gde_alcances.alcance
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		$definicion = guarani::db()->consultar_fila($sql);
		if ($definicion) {
			switch ($definicion['alcance']) {
				case encuesta::alcance_toda_institucion:
					$entidades = array();
					break;
				case encuesta::alcance_responsables_academicas:
					$entidades = $this->get_responsables_academicas_habilitacion($definicion['habilitacion']);
					break;
				case encuesta::alcance_tipos_propuesta:
					$entidades = $this->get_tipos_propuesta_habilitacion($definicion['habilitacion']);
					break;
				case encuesta::alcance_propuestas:
					$entidades = $this->get_propuestas_habilitacion($definicion['habilitacion']);
					break;
			}
			$definicion['alcance'] = array('valor' => $definicion['alcance'], 'descripcion' => $definicion['alcance_nombre'], 'entidades' => $entidades);
			$definicion['modalidades'] = $this->get_modalidades_habilitacion($definicion['habilitacion']);
			$definicion['ubicaciones'] = $this->get_ubicaciones_habilitacion($definicion['habilitacion']);
			$definicion['datos_censales'] = aplanar_matriz($this->get_datos_censales_config_habilitacion($definicion['habilitacion']));
			$definicion['documentos'] = $this->get_requisitos_habilitacion($definicion['habilitacion']);
			return array($definicion);
		} else {
			return array();
		}
	}

	function get_responsables_academicas_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT		gde_responsables_academicas.responsable_academica as valor,
							sga_responsables_academicas.nombre as descripcion
				FROM		gde_responsables_academicas
							JOIN sga_responsables_academicas ON gde_responsables_academicas.responsable_academica = sga_responsables_academicas.responsable_academica
				WHERE		gde_responsables_academicas.habilitacion = $habilitacion
				ORDER BY	gde_responsables_academicas.responsable_academica
		";
		return guarani::db()->consultar($sql);
	}

	function get_tipos_propuesta_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT		gde_propuestas_tipos.propuesta_tipo as valor,
							sga_propuestas_tipos.descripcion
				FROM		gde_propuestas_tipos
							JOIN sga_propuestas_tipos ON gde_propuestas_tipos.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
				WHERE		gde_propuestas_tipos.habilitacion = $habilitacion
				ORDER BY	gde_propuestas_tipos.propuesta_tipo
		";
		return guarani::db()->consultar($sql);
	}

	function get_propuestas_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT		gde_propuestas.propuesta as valor,
							sga_propuestas.nombre as descripcion
				FROM		gde_propuestas
							JOIN sga_propuestas ON gde_propuestas.propuesta = sga_propuestas.propuesta
				WHERE		gde_propuestas.habilitacion = $habilitacion
				ORDER BY	gde_propuestas.propuesta
		";
		return guarani::db()->consultar($sql);
	}

	function get_modalidades_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT		gde_modalidades.modalidad as valor,
							sga_modalidad_cursada.nombre as descripcion
				FROM		gde_modalidades
							JOIN sga_modalidad_cursada ON gde_modalidades.modalidad = sga_modalidad_cursada.modalidad
				WHERE		gde_modalidades.habilitacion = $habilitacion
				ORDER BY	gde_modalidades.modalidad
		";
		return guarani::db()->consultar($sql);
	}

	function get_ubicaciones_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT		gde_ubicaciones.ubicacion as valor,
							sga_ubicaciones.nombre as descripcion,
							sga_ubicaciones_tipos.nombre as tipo,
							sga_ubicaciones.localidad
				FROM		gde_ubicaciones
							JOIN sga_ubicaciones ON gde_ubicaciones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_ubicaciones_tipos ON sga_ubicaciones.ubicacion_tipo = sga_ubicaciones_tipos.ubicacion_tipo
				WHERE		gde_ubicaciones.habilitacion = $habilitacion
				ORDER BY	gde_ubicaciones.ubicacion
		";
		return guarani::db()->consultar($sql);
	}

	function get_datos_censales_config_habilitacion($habilitacion)
	{
		$habilitacion = toba::db()->quote($habilitacion);
		$sql = "SELECT		gde_datos_censales_config.campo
				FROM		gde_datos_censales_config
				WHERE		gde_datos_censales_config.habilitacion = $habilitacion AND
							gde_datos_censales_config.obligatorio = '".comunes::si."'
				ORDER BY	gde_datos_censales_config.campo
		";
		$configuraciones = guarani::db()->consultar($sql);

		// Campos dependientes de otros que son obligatorios.
		$where_padre = guarani_sql::armar_condicion_in($configuraciones, 'mdp_datos_censales_dep', 'campo_padre', 'campo');
		$sql = "SELECT	mdp_datos_censales_dep.campo_hijo as campo
				FROM	mdp_datos_censales_dep
						JOIN mdp_datos_censales_config ON mdp_datos_censales_dep.tabla_hijo = mdp_datos_censales_config.tabla AND mdp_datos_censales_dep.campo_hijo = mdp_datos_censales_config.campo
				WHERE	$where_padre
		";
		$configuraciones_dep = guarani::db()->consultar($sql);
		
		return array_merge($configuraciones, $configuraciones_dep);
	}

        function get_requisitos_habilitacion($habilitacion)
	{
		$sql = "SELECT  gde_becas_requisitos.requisito,
                                sga_requisitos.nombre,
                                gde_becas_requisitos.obligatorio
                        FROM gde_becas_requisitos
                        JOIN sga_requisitos ON (sga_requisitos.requisito = gde_becas_requisitos.requisito)
                        WHERE gde_becas_requisitos.habilitacion = $habilitacion
                        ORDER BY gde_becas_requisitos.requisito
		";
		return guarani::db()->consultar($sql);
	}

	function existe_convocatoria($becas_convocatoria)
	{
		$becas_convocatoria = toba::db()->quote($becas_convocatoria);
		$sql = "SELECT	COUNT(gde_habilitaciones.habilitacion) as cant
				FROM	gde_habilitaciones
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		$rs = guarani::db()->consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	function es_convocatoria_a_futuro($becas_convocatoria)
	{
		$becas_convocatoria = toba::db()->quote($becas_convocatoria);
		$sql = "SELECT	(CURRENT_DATE < gde_habilitaciones.fecha_desde) as a_futuro
				FROM	gde_habilitaciones
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		$rs = guarani::db()->consultar_fila($sql);
		return $rs['a_futuro'];
	}

        /**
         * Devuelve lista con los aspirantes de una convocatoria a beca.
         *
         * @param int       $becas_convocatoria Convocatoria a beca.
         * @param string    $where              Filtro
         * @param string    $con_reaperturas    S = devuelve solo aspirantes con reaperturas, N = devuelve solo aspirantes sin reaperturas, por defecto devuelve todos los aspirantes.
         *
         * @return array Lista con los aspirantes de una convocatoria a beca.
         */
	function get_datos_aspirantes_convocatoria($becas_convocatoria, $where = '', $con_reaperturas = '')
	{
		if ($where) {
			$where = 'AND ' . $where;
		}

                $where_reaperturas = '';
                // Devuelve solo aspirantes sin reaperturas
                if ($con_reaperturas == comunes::no) {
                    $where_reaperturas = 'AND gde_becas_reapertura.reapertura IS NULL';
                // Devuelve solo aspirantes con reaperturas
                } elseif ($con_reaperturas == comunes::si) {
                    $where_reaperturas = 'AND gde_becas_reapertura.reapertura IS NOT NULL';
                }
		
		$orden_tipo_email = persona::orden_tipo_email();
		
		$becas_convocatoria = toba::db()->quote($becas_convocatoria);
		$sql = "SELECT	mdp_personas.persona,
						mdp_personas_documentos.pais_documento,
						mdp_personas_documentos.tipo_documento,
						mdp_tipo_documento.desc_abreviada AS tipo_documento_desc_abreviada,
						mdp_personas_documentos.nro_documento,
						COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
						COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
						mdp_personas.sexo as genero,
						mdp_personas.identidad_genero,
						mdp_personas.identidad_genero_otro,
						personas_documento_cuit.nro_documento as cuil,
						(SELECT		mdp_personas_contactos.email
						FROM		mdp_personas_contactos
						WHERE		mdp_personas_contactos.persona = mdp_personas.persona AND
									mdp_personas_contactos.contacto_tipo IN ('".persona::mail_primario."','".persona::mail_secundario."','".persona::mail_institucional."') AND
									mdp_personas_contactos.email IS NOT NULL
						ORDER BY	$orden_tipo_email
						LIMIT 1) as email,
						mdp_personas.fecha_nacimiento,
						mdp_personas.nacionalidad,
						mdp_nacionalidades.descripcion as nacionalidad_descripcion,
						gde_formularios.formulario,
						gde_encuestas_pendientes.hash,
						gde_habilitaciones.habilitacion,
						TO_CHAR(COALESCE(gde_becas_reapertura.fecha_respuesta_original, gde_encuestas_pendientes.fecha_respuesta), '".comunes::formato_fecha_hora_rest."') AS fecha_respuesta,
                                                gde_becas_reapertura.reapertura,
                                                TO_CHAR(gde_becas_reapertura.fecha_reapertura, '".comunes::formato_fecha_hora_rest."') AS fecha_reapertura,
                                                CASE
                                                    WHEN gde_becas_reapertura.reapertura IS NULL THEN NULL
                                                    WHEN gde_becas_reapertura.reapertura IS NOT NULL THEN TO_CHAR(gde_encuestas_pendientes.fecha_respuesta, '".comunes::formato_fecha_hora_rest."')
                                                END fecha_respuesta_reapertura,
                                                array_to_json(gde_becas_reapertura.datos_censales) AS reapertura_datos_censales,
                                                array_to_json(gde_becas_reapertura.requisitos) AS reapertura_requisitos
				FROM	gde_encuestas_pendientes
						JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
						JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
						JOIN mdp_personas ON gde_encuestas_pendientes.persona = mdp_personas.persona
						JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
						JOIN mdp_nacionalidades ON mdp_personas.nacionalidad = mdp_nacionalidades.nacionalidad
						JOIN mdp_tipo_documento ON (mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento)
						LEFT JOIN mdp_personas_documentos as personas_documento_cuit ON (mdp_personas.persona = personas_documento_cuit.persona AND personas_documento_cuit.tipo_documento = ".persona::doc_CUIL.")
                                                LEFT JOIN gde_becas_reapertura ON (gde_encuestas_pendientes.respuesta = gde_becas_reapertura.respuesta)
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria AND
                                        COALESCE(gde_becas_reapertura.fecha_respuesta_original, gde_encuestas_pendientes.fecha_respuesta) IS NOT NULL
                                        $where
                                        $where_reaperturas
		";
		$aspirantes = guarani::db()->consultar($sql);
		foreach ($aspirantes as $clave => $valor) {
			$aspirantes[$clave]['nacionalidad'] = array('valor' => $valor['nacionalidad'], 'descripcion' => $valor['nacionalidad_descripcion']);
			$aspirantes[$clave]['datos_censales'] = $this->get_datos_censales_aspirante($valor['persona'], $valor['habilitacion'], $valor['fecha_respuesta']);
			$aspirantes[$clave]['datos_academicos'] = $this->get_datos_academicos_aspirante($valor['persona'], $valor['formulario']);
			$aspirantes[$clave]['documentos'] = $this->get_documentos_aspirante($valor['persona'], $valor['habilitacion']);

                        // Si no tiene reapertura
                        if (is_null($valor['reapertura'])) {
                            $aspirantes[$clave]['reaperturas'] = [];
                        // Si tiene reapertura
                        } else {
                            $aspirantes[$clave]['reaperturas'][0]['fecha_reapertura'] = $valor['fecha_reapertura'];
                            $aspirantes[$clave]['reaperturas'][0]['fecha_respuesta'] = $valor['fecha_respuesta_reapertura'];

                            $aspirantes[$clave]['reaperturas'][0]['datos_censales'] = [];
                            // Si hay datos censales para la reapertura
                            if (!is_null($valor['fecha_respuesta_reapertura']) && !is_null($valor['reapertura_datos_censales'])) {
                                // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                                $reapertura_datos_censales = iconv("ISO-8859-1", "UTF-8", $valor['reapertura_datos_censales']);
                                $reapertura_datos_censales = json_decode($reapertura_datos_censales, true);
                                // Si vienen datos censales para la reapertura
                                if (!empty($reapertura_datos_censales)) {
                                    $aspirantes[$clave]['reaperturas'][0]['datos_censales'] = $this->get_datos_censales_aspirante($valor['persona'], $valor['habilitacion'], $valor['fecha_respuesta_reapertura'], $reapertura_datos_censales);
                                }
                           }
                            
                            $aspirantes[$clave]['reaperturas'][0]['documentos'] = [];
                            // Si hay requisitos para la reapertura
                            if (!is_null($valor['fecha_respuesta_reapertura']) && !is_null($valor['reapertura_requisitos'])) {
                                // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                                $reapertura_requisitos = iconv("ISO-8859-1", "UTF-8", $valor['reapertura_requisitos']);
                                $reapertura_requisitos = json_decode($reapertura_requisitos, true);
                                // Si vienen requisitos para la reapertura
                                if (!empty($reapertura_requisitos)) {
                                    $aspirantes[$clave]['reaperturas'][0]['documentos'] = $this->get_documentos_aspirante($valor['persona'], $valor['habilitacion'], $reapertura_requisitos);
                                }
                            }
                        }
		}
		return $aspirantes;
	}

	function get_datos_academicos_aspirante($persona, $formulario)
	{
                $sql = "SELECT	gde_encuesta_pendiente_propuestas.propuesta,
                                sga_propuestas.nombre AS descripcion,
                                gde_encuesta_pendiente_propuestas.promedio_con_aplazos,
                                gde_encuesta_pendiente_propuestas.promedio_sin_aplazos,
                                gde_encuesta_pendiente_propuestas.cantidad_actividades_aprobadas
                        FROM	gde_encuesta_pendiente_propuestas
                        JOIN    sga_propuestas ON (gde_encuesta_pendiente_propuestas.propuesta = sga_propuestas.propuesta)
                        WHERE	gde_encuesta_pendiente_propuestas.persona = $persona
                        AND     gde_encuesta_pendiente_propuestas.formulario = $formulario
               ";
		return guarani::db()->consultar($sql);
	}

	function get_datos_censales_aspirante($persona, $habilitacion, $fecha, $datos_censales = [])
	{
		$persona = toba::db()->quote($persona);
		$habilitacion = toba::db()->quote($habilitacion);
		$fecha = toba::db()->quote($fecha);

                $where_datos_censales = '';
                // Si se filtra por $datos_censales (reapertura)
                if (!empty($datos_censales)) {
                    $where_datos_censales = 'AND ' . guarani_sql::armar_condicion_in($datos_censales, 'gde_datos_censales_config', 'campo');
                }

		$sql = "SELECT	gde_datos_censales_config.tabla,
						gde_datos_censales_config.campo,
						mdp_datos_censales_config.etiqueta
				FROM	gde_datos_censales_config
						JOIN mdp_datos_censales_config ON gde_datos_censales_config.tabla = mdp_datos_censales_config.tabla AND gde_datos_censales_config.campo = mdp_datos_censales_config.campo
				WHERE	gde_datos_censales_config.habilitacion = $habilitacion
                                        AND gde_datos_censales_config.obligatorio = '".comunes::si."'
                                        $where_datos_censales";
		$configuraciones = guarani::db()->consultar($sql);
		// Campos dependientes de otros que son obligatorios.
		$where_padre = guarani_sql::armar_condicion_in_dupla($configuraciones, 'mdp_datos_censales_dep', 'tabla_padre', 'tabla', $configuraciones, 'mdp_datos_censales_dep', 'campo_padre', 'campo'); 
		$sql = "SELECT	mdp_datos_censales_dep.tabla_hijo as tabla,
						mdp_datos_censales_dep.campo_hijo as campo,
						mdp_datos_censales_config.etiqueta
				FROM	mdp_datos_censales_dep
						JOIN mdp_datos_censales_config ON mdp_datos_censales_dep.tabla_hijo = mdp_datos_censales_config.tabla AND mdp_datos_censales_dep.campo_hijo = mdp_datos_censales_config.campo
				WHERE	$where_padre
		";
		$configuraciones_dep = guarani::db()->consultar($sql);
		$configuraciones = array_merge($configuraciones, $configuraciones_dep);
		$datos_censales = array();
		foreach ($configuraciones as $config) {
			// Se busca el dato censal más próximo anterior a la fecha en que se repondió la encuesta.
			$tabla_historica = str_replace('mdp_', 'his_', $config['tabla']);
			$sql = "SELECT		$tabla_historica.{$config['campo']} as valor
					FROM		his_datos_censales
								JOIN $tabla_historica ON his_datos_censales.dato_censal = $tabla_historica.dato_censal
					WHERE		his_datos_censales.persona = $persona AND
								$tabla_historica.fecha_alta <= $fecha
					ORDER BY	$tabla_historica.fecha_alta DESC
					LIMIT		1
			";
			$rs = guarani::db()->consultar_fila($sql);
			$datos_censales[] = ['campo' => $config['campo'], 'etiqueta' => $config['etiqueta'], 'valor' => ($rs['valor'] ?? null)];
		}
		return $datos_censales;
	}

	/**
	 * Retorna los datos de una habilitación de acuerdo a la convocatoria asociada.
	 * 
	 * @param int $becas_convocatoria
	 * @return array
	 */
	function get_datos_habilitacion_convocatoria($becas_convocatoria)
	{
		$becas_convocatoria = toba::db()->quote($becas_convocatoria);
		$sql = "SELECT	gde_habilitaciones.habilitacion,
						gde_habilitaciones.kolla_id_habilitacion,
						gde_habilitaciones.kolla_unidad_gestion,
						gde_habilitaciones.tehuelche_unidad_gestion,
						gde_habilitaciones.fecha_desde,
						gde_habilitaciones.fecha_hasta
				FROM	gde_habilitaciones
				WHERE	gde_habilitaciones.becas_convocatoria = $becas_convocatoria
		";
		return guarani::db()->consultar_fila($sql);
	}


        /**
	 * Retorna los documentos de un aspirante en una convocatoria.
	 *
	 * @param int $persona
	 * @param int $habilitacion
	 * @param int[] $requisitos
	 * @return array
	 */
	function get_documentos_aspirante($persona, $habilitacion, $requisitos = [])
	{

                $where_requisitos = '';
                // Si se filtra por $requisitos (reapertura)
                if (!empty($requisitos)) {
                    $where_requisitos = 'AND ' . guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos', 'requisito');
                }

		$sql = "SELECT 	sga_requisitos.requisito,
                                sga_requisitos.nombre,
                                gde_alumnos_requisitos.id_documento AS uuid_rdi,
                                gde_alumnos_requisitos.extension,
                                gde_becas_requisitos.obligatorio,
                                CASE gde_alumnos_requisitos.no_posee
                                   WHEN 0 THEN '".comunes::no."'
                                   WHEN 1 THEN '".comunes::si."'
                                END no_posee
                        FROM gde_alumnos_requisitos
                        JOIN sga_requisitos ON (gde_alumnos_requisitos.requisito = sga_requisitos.requisito)
                        JOIN gde_becas_requisitos ON (gde_becas_requisitos.requisito = sga_requisitos.requisito AND gde_becas_requisitos.habilitacion = gde_alumnos_requisitos.habilitacion)
                        WHERE gde_alumnos_requisitos.habilitacion = {$habilitacion}
                        AND gde_alumnos_requisitos.persona = {$persona}
                        $where_requisitos
                        ORDER BY gde_alumnos_requisitos.fecha_alta
		";
		return guarani::db()->consultar($sql);
	}
}