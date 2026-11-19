<?php

namespace siu\modelo\datos\db;

use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;

class inscripcion_examen
{
	/**
	 * parametros: alumno, persona, propuesta, plan_version, ubicacion, param_exa_filtro_actividades_con_mesas, param_exa_rinde_en_cualquier_ubicacion, tipo_plan, param_exa_rendir_regularizada_plan_anterior, param_exa_multiples_actas_examen
	 * param_null: ubicacion, tipo_plan, param_exa_rendir_regularizada_plan_anterior
	 * no_quote: param_exa_filtro_actividades_con_mesas, param_exa_rinde_en_cualquier_ubicacion, param_exa_rendir_regularizada_plan_anterior, param_exa_multiples_actas_examen
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 */
	function lista_materias($parametros)
	{
		// Listado de materias para la inscripción a una mesa de examen.

		$param_exa_filtro_actividades_con_mesas			= $parametros['param_exa_filtro_actividades_con_mesas'];
		$param_exa_rinde_en_cualquier_ubicacion			= $parametros['param_exa_rinde_en_cualquier_ubicacion'];
		$param_exa_rendir_regularizada_plan_anterior	= $parametros['param_exa_rendir_regularizada_plan_anterior'];
		$param_exa_multiples_actas_examen				= $parametros['param_exa_multiples_actas_examen'];

		$alumno = $parametros['alumno'];
		$propuesta = $parametros['propuesta'];
		$plan = $parametros['plan'];
		$plan_version = $parametros['plan_version'];
		$ubicacion = $parametros['ubicacion'];
		$fuera_de_termino = false;
		$interfaz = \comunes::interfaz_autogestion; // Autogestion

		// sólo recuperar las actividades con mesa de examen definida y con períodos de inscripción vigentes.
		$solo_con_mesas_vigentes = false;
		if ($param_exa_filtro_actividades_con_mesas == comunes::si) {
			$solo_con_mesas_vigentes = true;

			// Si el parámetro del sistema :exa_rinde_en_cualquier_ubicacion = N
			// sólo recuperar las mesas de examen de la ubicación del alumno.
			if ($param_exa_rinde_en_cualquier_ubicacion == comunes::si) {
				$ubicacion = 'null';
			}
		}

		$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';

		$sin_acta_generada = ($param_exa_multiples_actas_examen == comunes::si) ? 'false' : 'true';

		$where_solo_con_mesas_vigentes = "";
		if ($solo_con_mesas_vigentes) {
			$where_solo_con_mesas_vigentes = "	AND sga_elementos.elemento IN (
													SELECT * 
													FROM f_actividades_con_mesas_vigentes($propuesta, $plan, $plan_version, $en_periodo_inscripcion, $sin_acta_generada, $ubicacion, $interfaz, $alumno)
												)";
		}

		/* Lista de Actividades del plan actual del alumno */
		$sql = "SELECT	sga_elementos.elemento as id,
							sga_elementos.codigo as codigo,
							sga_elementos_plan.nombre as nombre,
							'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre as descr,
							sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre,
							sga_elementos_plan.creditos,
							sga_elementos_plan.plan_version as plan_version,
							sga_planes_versiones.plan, 
							'PLAN_ACTUAL' as tipo_plan
					FROM sga_elementos_plan
					JOIN sga_elementos_revision		ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
					JOIN sga_elementos				ON sga_elementos.elemento = sga_elementos_revision.elemento
					JOIN sga_g3entidades_subtipos 	ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
					JOIN sga_planes_versiones		ON sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
							
					WHERE sga_elementos_plan.plan_version = $plan_version
					  AND sga_g3entidades_subtipos.entidad_tipo = 2 
							AND	sga_elementos.estado = '" . comunes::estado_activo . "'
							$where_solo_con_mesas_vigentes
							-- Se obvian las actividades aprobadas
							AND	sga_elementos.elemento
								NOT IN (SELECT	vw_hist_academica_basica.elemento
										FROM	vw_hist_academica_basica
										WHERE	vw_hist_academica_basica.alumno = $alumno
										AND		vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
					";

		/* 	Lista las actividades regularizadas aprobadas por el alumno en algun plan anterior o version de plan anterior
		  que no existe en la version del plan actual del alumno.
		  Regularidades vigentes (para rendir examen regular) y no vigentes (para exámenes de reválidas de cursadas)
		 * Quitamos el $where_solo_con_mesas_vigentes ya que saca las mesas vigentes segun plan_actual. y no plan anterior
		 */
		if ($param_exa_rendir_regularizada_plan_anterior == comunes::si || $param_exa_rendir_regularizada_plan_anterior == 'A') {
			$sql .= "  
				UNION
					SELECT	vw_regularidades_basica.elemento as id,
							sga_elementos.codigo as codigo,
							sga_elementos_plan.nombre as nombre,
							'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre as descr,
							sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre,
							vw_regularidades_basica.creditos as creditos,
							sga_elementos_plan.plan_version as plan_version,
							sga_planes_versiones.plan, 
							'PLAN_ANTERIOR' as tipo_plan
					FROM vw_regularidades_basica
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_regularidades_basica.plan_version
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento = vw_regularidades_basica.elemento
					JOIN sga_elementos ON sga_elementos.elemento = sga_elementos_revision.elemento
					JOIN sga_g3entidades_subtipos 	ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
					JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND 
							                    sga_elementos_plan.plan_version      = vw_regularidades_basica.plan_version)

					WHERE	vw_regularidades_basica.alumno     = $alumno 
					AND sga_g3entidades_subtipos.entidad_tipo = 2 
					AND		vw_regularidades_basica.resultado  = '" . comunes::resultado_nota_aprobado . "'
					-- AND		vw_regularidades_basica.es_vigente = 1
					AND		vw_regularidades_basica.origen_otra_propuesta = false
					AND		sga_elementos.estado  = '" . comunes::estado_activo . "'
					-- No se listan las que esten en el plan actual
					AND		sga_elementos.elemento NOT IN (SELECT elemento FROM vw_actividades_plan WHERE plan_version = $plan_version)
				
					-- No se listan las que esten aprobadas
					AND		sga_elementos.elemento NOT IN (SELECT vw_hist_academica_basica.elemento
																FROM vw_hist_academica_basica
																WHERE vw_hist_academica_basica.alumno = $alumno
																	AND vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
					ORDER BY nombre;
		";
		}

		$datos = kernel::db()->consultar($sql);
		$nuevo = array();

		foreach ($datos as $dato) {
			$nuevo[$dato['id']] = $dato;
			$nuevo[$dato['id']][catalogo::id] = catalogo::generar_id($dato['id']);
		}

		kernel::log()->add_debug('-- LISTADO ACTIVIDADES MESA DE EXAMEN --', $nuevo);
		return $nuevo;
	}

	/**
	 * parametros: alumno, actividad, propuesta, plan, plan_version, ubicacion, modalidad, division, param_exa_rinde_en_cualquier_ubicacion, param_exa_mostrar_mesas_todas_modalidades, param_cur_division_estricta, tipo_plan, param_exa_multiples_actas_examen, param_exa_mostrar_mesas_con_cupo_completo
	 * no_quote: param_exa_rinde_en_cualquier_ubicacion, param_exa_mostrar_mesas_todas_modalidades, param_cur_division_estricta, param_exa_multiples_actas_examen, tipo_plan, param_exa_mostrar_mesas_con_cupo_completo
	 * param_null: ubicacion, division, tipo_plan
	 * cache: no
	 * filas: n
	 * 
	 * @todo parametros alumno, inicial_apellido
	 * 
	 * Guarani3 = \php\nucleo\examenes\mesas_de_examen\co_mesas_examen->get_mesas_disponibles_para_inscripcion()
	 */
	function get_mesas_disponibles_para_inscripcion($parametros)
	{
		// @sergio VER ESTO
		$formato_fecha_visual       = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual        = kernel::db()->quote(\comunes::formato_hora_visual);
		$inst_exam_regular          = \mesa_de_examen::inst_exam_regular;
		$inst_exam_revalida_cursada = \mesa_de_examen::inst_exam_revalida_cursada;

		$en_periodo_inscripcion = (!$parametros['fuera_de_termino']) ? 'true' : 'false';

		$alumno    = $parametros['alumno'];
		$elemento  = $parametros['actividad'];
		$propuesta = $parametros['propuesta'];
		$plan = $parametros['plan'];
		$plan_version = $parametros['plan_version'];
		$ubicacion = $parametros['ubicacion'];
		$modalidad = $parametros['modalidad'];
		$param_exa_rinde_en_cualquier_ubicacion = $parametros['param_exa_rinde_en_cualquier_ubicacion'];
		$param_exa_mostrar_mesas_todas_modalidades = $parametros['param_exa_mostrar_mesas_todas_modalidades'];
		$param_cur_division_estricta = $parametros['param_cur_division_estricta'];
		$param_exa_multiples_actas_examen = $parametros['param_exa_multiples_actas_examen'];
		$param_exa_mostrar_mesas_con_cupo_completo = $parametros['param_exa_mostrar_mesas_con_cupo_completo'];

		$interfaz = \comunes::interfaz_autogestion; // Autogestion

		$filtro_modalidad = '';
		// si exa_mostrar_mesas_todas_modalidades = N
		if ($param_exa_mostrar_mesas_todas_modalidades == comunes::no) {
			$filtro_modalidad = "AND $modalidad IN (SELECT	modalidad
													FROM	sga_mesas_examen_modalidad
													WHERE	sga_mesas_examen_modalidad.mesa_examen = sga_mesas_examen.mesa_examen)
								";
		}
		// Mostrar mesas con cupo completo
		$filtro_mesas_cupo_completo = '';
		if ($param_exa_mostrar_mesas_con_cupo_completo == comunes::no) {
			$filtro_mesas_cupo_completo = "AND (sga_llamados_mesa_cupo.cupo = 0 OR (sga_llamados_mesa_cupo.cupo > 0 AND sga_llamados_mesa_cupo.cant_inscriptos < sga_llamados_mesa_cupo.cupo))";
		}

		$filtro_division_estricta = '';

		if ($param_cur_division_estricta == comunes::si && isset($parametros['division'])) {
			$filtro_division_estricta = " AND (sga_mesas_examen.division = {$parametros['division']} OR sga_mesas_examen.division is null)";
		}

		// Si exa_rinde_en_cualquier_ubicacion = N
		if ($param_exa_rinde_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'NULL';
		}

		$sin_acta_generada = ($param_exa_multiples_actas_examen == comunes::si) ? 'false' : 'true';

		// Para actividades regularizadas en planes anteriores. Solo mesas que tengan la instancia 3-Regular y 5-Reválida de Cursada.
		$filtro_mesas_regulares = '';
		if ($parametros['tipo_plan'] != \planes::tipo_plan_actual) {
			$filtro_mesas_regulares = " AND EXISTS (SELECT 1 FROM sga_mesas_examen_instancias 
			                                         WHERE sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen 
												       AND sga_mesas_examen_instancias.instancia in ($inst_exam_regular, $inst_exam_revalida_cursada))";
		}
		

		$sql = "SELECT	sga_mesas_examen.mesa_examen as mesa_examen,
						sga_mesas_examen.nombre as mesa_examen_nombre,
						sga_llamados_mesa.entidad,
						sga_llamados_mesa.llamado_mesa as llamado_mesa,
						sga_periodos_llamado.nombre as llamado_nombre,
						sga_llamados_mesa.fecha as fecha_examen,
						sga_llamados_mesa.hora_inicio,
						to_char(sga_llamados_mesa.fecha, $formato_fecha_visual) || ' ' || to_char(cast(sga_llamados_mesa.hora_inicio as time), $formato_fecha_visual) as fecha_visual,
						to_char(date '2001-01-01' + sga_llamados_mesa.hora_inicio, $formato_hora_visual) as hora_inicio,
						sga_llamados_mesa_cupo.cupo as cupo,
						sga_llamados_mesa_cupo.cant_inscriptos as cant_inscriptos,
						CASE 
						  WHEN COALESCE(sga_llamados_mesa.cupo,0) > 0 THEN
						     COALESCE((sga_llamados_mesa_cupo.cant_inscriptos * 100 / sga_llamados_mesa_cupo.cupo)::integer,0)
						  ELSE 0
						END as porcentaje_ocupacion,
						sga_catedras.nombre as catedra_nombre,
						sga_mesas_examen.ubicacion as ubicacion,
						sga_mesas_examen.division as division,
						sga_ubicaciones.nombre as ubicacion_nombre,
						f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) as modalidad_nombre,
						sga_periodos_llamado.anio_academico as anio_academico,
						sga_llamados_turno.turno_examen as turno_examen,
						periodos_turnos_examen.nombre as turno_examen_nombre,
						f_espacios_llamado_mesa(sga_llamados_mesa.llamado_mesa, 1) as espacio_nombre,
						f_docentes_mesa_examen (sga_llamados_mesa.llamado_mesa, true) as docentes
						
				FROM	sga_mesas_examen							
						JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
						JOIN sga_llamados_mesa ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
						JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
						JOIN sga_periodos as sga_periodos_llamado ON sga_periodos_llamado.periodo = sga_llamados_turno.periodo
						JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
						JOIN sga_periodos as periodos_turnos_examen ON sga_turnos_examen.periodo = periodos_turnos_examen.periodo
						JOIN sga_llamados_mesa_cupo ON sga_llamados_mesa_cupo.llamado_mesa = sga_llamados_mesa.llamado_mesa
						LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_mesas_examen.catedra

				WHERE sga_mesas_examen.elemento = $elemento
				  AND sga_llamados_mesa.fecha >= CURRENT_DATE
				  AND sga_llamados_mesa.llamado_mesa IN (SELECT * FROM f_mesas_vigentes_una_actividad($elemento, $propuesta, $plan, $plan_version, $en_periodo_inscripcion, $sin_acta_generada, $ubicacion, $interfaz, $alumno))
				  $filtro_modalidad
				  $filtro_division_estricta
				  $filtro_mesas_regulares
				  $filtro_mesas_cupo_completo
				
				ORDER BY	
						sga_llamados_mesa.fecha,
						sga_llamados_mesa.hora_inicio
  				;";


		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$nuevo[$dato['llamado_mesa']] = $dato;
			$nuevo[$dato['llamado_mesa']][catalogo::id] = catalogo::generar_id($dato['llamado_mesa']);
		}
		return $nuevo;
	}

	/**
	 * parametros: mesas , tipo_plan
	 * no_quote: mesas , tipo_plan
	 * param_null:  tipo_plan
	 * cache: memoria
	 * filas: n

	 */
	function get_instancias_mesas($parametros)
	{
		if (empty($parametros['mesas'])) {
			return array();
		}
		$and="";
                // Si el tipo de plan es "PLAN_ANTERIOR" la instancia debe ser "Regular" o "Reválida de Cursada"
		if ($parametros['tipo_plan'] == \planes::tipo_plan_anterior) {
                        $inst_exam_regular          = \mesa_de_examen::inst_exam_regular;
                        $inst_exam_revalida_cursada = \mesa_de_examen::inst_exam_revalida_cursada;
			$and=" AND  sga_instancias.instancia IN ($inst_exam_regular, $inst_exam_revalida_cursada) ";
		}
		$sql = "SELECT  	sga_mesas_examen.mesa_examen,
							sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre,
							sga_instancias.orden as instancia_orden
				FROM		sga_mesas_examen
							JOIN sga_mesas_examen_instancias	ON (sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen)
							JOIN sga_instancias					ON (sga_instancias.instancia				= sga_mesas_examen_instancias.instancia)
							
				WHERE		sga_mesas_examen.mesa_examen in ({$parametros['mesas']})
                                $and
				ORDER BY	sga_instancias.orden";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $dato) {
			$nuevo[$dato['mesa_examen']][$dato['instancia']] = $dato;
		}

		return $nuevo;
	}

	/**
	 * parametros: llamado_mesa
	 * cache: memoria
	 * filas: n
	 */
	function get_instancias_llamado_mesa($parametros)
	{
		if (empty($parametros['llamado_mesa'])) {
			return array();
		}
		$sql = "SELECT	sga_instancias.instancia,
						sga_instancias.nombre as instancia_nombre,
						sga_instancias.orden as instancia_orden
				FROM	sga_mesas_examen
						JOIN sga_llamados_mesa				ON (sga_llamados_mesa.mesa_examen			= sga_mesas_examen.mesa_examen)
						JOIN sga_mesas_examen_instancias	ON (sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen)
						JOIN sga_instancias					ON (sga_instancias.instancia				= sga_mesas_examen_instancias.instancia)


				WHERE	sga_llamados_mesa.llamado_mesa = {$parametros['llamado_mesa']}
				ORDER BY sga_instancias.orden";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: persona
	 * cache: no
	 * filas: n
	 */
	function inscripciones($parametros)
	{
		// Listado de inscripciones a examenes de una persona a mesas de examen con fecha mayor o igual a hoy	
		// Inscripciones en todas sus propuestas.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);
		$interfaz  = \comunes::interfaz_autogestion; // Autogestion

		$sql = "SELECT 	sga_insc_examen.inscripcion				as inscripcion,
						sga_insc_examen.alumno					as alumno,
						sga_alumnos.propuesta					as propuesta,
						sga_alumnos.legajo						as legajo,
						sga_insc_examen.instancia				as instancia,
						sga_instancias.nombre					as instancia_nombre,
						vw_mesas_examen.mesa_examen_elemento 	as actividad,
						sga_elementos.codigo					as actividad_codigo,
						sga_elementos_plan.nombre				as actividad_nombre,
						sga_elementos_plan.nombre_abreviado		as actividad_nombre_abreviado,
						vw_mesas_examen.llamado_mesa			as llamado_mesa,
						vw_mesas_examen.anio_academico			as anio_academico,
						vw_mesas_examen.turno_examen			as turno_examen,
						vw_mesas_examen.turno_examen_nombre		as turno_examen_nombre,
						vw_mesas_examen.llamado					as llamado,
						vw_mesas_examen.llamado_nombre			as llamado_nombre,
						vw_mesas_examen.mesa_examen				as mesa_examen,
						vw_mesas_examen.mesa_examen_nombre		as mesa_examen_nombre,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as mesa_examen_fecha,
						to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual) as mesa_examen_hora_inicio,
						to_char(sga_insc_examen.fecha_inscripcion, $formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion,
						sga_insc_examen.nro_transaccion as nro_transaccion,
						sga_insc_examen.estado as estado,
						sga_inscripciones_estados.nombre as estado_nombre,
                        f_mesa_con_tope_bajas_vigente(sga_insc_examen.plan_version, vw_mesas_examen.llamado_mesa, $interfaz, sga_alumnos.alumno) as puede_desinscribirse

				FROM	sga_alumnos
						JOIN sga_insc_examen		ON (sga_insc_examen.alumno				= sga_alumnos.alumno)
						JOIN vw_mesas_examen 		ON (vw_mesas_examen.llamado_mesa		= sga_insc_examen.llamado_mesa)
						JOIN sga_instancias 		ON (sga_instancias.instancia			= sga_insc_examen.instancia)
						JOIN sga_elementos			ON (sga_elementos.elemento	= vw_mesas_examen.mesa_examen_elemento)
						JOIN sga_elementos_revision	ON (sga_elementos_revision.elemento	= sga_elementos.elemento)
						JOIN sga_elementos_plan		ON (sga_elementos_plan.plan_version	= sga_insc_examen.plan_version 	AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
						JOIN sga_inscripciones_estados	ON (sga_inscripciones_estados.estado	= sga_insc_examen.estado)
				WHERE	sga_alumnos.persona = {$parametros['persona']}	
					AND	vw_mesas_examen.mesa_examen_fecha >= CURRENT_DATE
				";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $key => $dato) {
			$datos[$key][catalogo::id] = catalogo::generar_id($dato['inscripcion']);
			$datos[$key]['url_actividad'] = catalogo::generar_id($dato['actividad']);
		}
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: n
	 * 
	 * Datos de una inscripción. Comprobante de impresión de la inscripción a examen.
	 */
	function info_comprobante_inscripcion($parametros)
	{

		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);

		$sql = "SELECT
                        sga_propuestas.nombre                                as propuesta_nombre,
                        sga_propuestas.codigo                                as propuesta,
                        vw_personas.apellido_nombres_elegido as alumno_nombre,
						vw_personas.tipo_nro_documento                        as identificacion,
                        sga_alumnos.legajo                                    as legajo,
                        sga_instancias.nombre                                as instancia_nombre,
                        sga_elementos.codigo                           		 as actividad_codigo,
                        sga_elementos_plan.nombre                            as actividad_nombre,
                        sga_elementos_plan.nombre_abreviado                	as actividad_nombre_abreviado,
                        vw_mesas_examen.anio_academico                        as anio_academico,
                        vw_mesas_examen.turno_examen_nombre                    as turno_examen_nombre,
                        vw_mesas_examen.llamado_nombre                        as llamado_nombre,
                        vw_mesas_examen.mesa_examen_nombre                    as mesa_examen_nombre,
                        sga_inscripciones_estados.nombre                    as estado_inscripcion,
                        to_char(sga_insc_examen.fecha_inscripcion, $formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion,
                        sga_insc_examen.nro_transaccion                        as nro_transaccion,
                        to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_mesa_examen,
                        to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual) as hora_inicio,
                        to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_finalizacion, $formato_hora_visual) as hora_finalizacion,
                        sga_catedras.nombre                                    as catedra_nombre,
                        sga_ubicaciones.nombre                                as ubicacion_nombre,
						f_espacios_llamado_mesa(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre
							
                FROM
                        sga_insc_examen
                        JOIN sga_alumnos ON sga_alumnos.alumno  = sga_insc_examen.alumno
                        JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
                        JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
                        JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_insc_examen.llamado_mesa
                        JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
                        JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
                        JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version = sga_insc_examen.plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
                        JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion 
                        JOIN sga_instancias ON sga_instancias.instancia = sga_insc_examen.instancia
                        JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_examen.estado
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra

                WHERE    
                    sga_insc_examen.inscripcion = {$parametros['inscripcion']}
				";

		$datos = kernel::db()->consultar_fila($sql);
		if ($datos) {
			foreach (array_keys($datos) as $campos) {
				$datos[$campos] = $datos[$campos];
			}
		}
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: n
	 * 
	 * Comprobante de baja de inscripción a examen
	 */
	function info_comprobante_baja($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);

		$sql = "SELECT	vw_personas.apellido_nombres_elegido as alumno_nombre,
						vw_personas.tipo_nro_documento as identificacion,
						sga_propuestas.codigo as propuesta,
						sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
						sga_alumnos.legajo as legajo,
						sga_instancias.nombre as instancia_nombre,
						sga_elementos.codigo as actividad_codigo,
						sga_elementos_plan.nombre as actividad_nombre,
						sga_elementos_plan.nombre_abreviado as actividad_nombre_abreviado,
						vw_mesas_examen.anio_academico as anio_academico,
						vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
						vw_mesas_examen.llamado_nombre as llamado_nombre,
						vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
						to_char(sga_insc_examen_log.fecha_inscripcion, $formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion,
						to_char(sga_insc_examen_log.fecha_operacion, $formato_fecha_hora_visual_sin_segundos) as fecha_baja_inscripcion,
						sga_insc_examen_log.nro_transaccion as nro_transaccion_alta,
						sga_insc_examen_log.nro_transaccion_log  as nro_transaccion_baja,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_mesa_examen,
						to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual) as hora_inicio,
						sga_catedras.nombre as catedra_nombre,
						sga_ubicaciones.nombre as ubicacion_nombre,
						f_espacios_llamado_mesa(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre
						
				FROM	sga_insc_examen_log
						JOIN sga_alumnos ON sga_alumnos.alumno  = sga_insc_examen_log.alumno
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_insc_examen_log.llamado_mesa
                        JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
                        JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
                        JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version = sga_insc_examen_log.plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
						JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion 
						JOIN sga_instancias ON sga_instancias.instancia = sga_insc_examen_log.instancia
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
						
				WHERE	sga_insc_examen_log.inscripcion = {$parametros['inscripcion']}	
				";

		$datos = kernel::db()->consultar_fila($sql);
		if ($datos) {
			foreach (array_keys($datos) as $campos) {
				$datos[$campos] = $datos[$campos];
			}
		}
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: memoria
	 * filas: n
	 */
	function info_inscripcion($parametros)
	{
		$sql = "SELECT	sga_mesas_examen.elemento as actividad 
				FROM	sga_insc_examen 
						JOIN sga_llamados_mesa ON (sga_llamados_mesa.llamado_mesa	= sga_insc_examen.llamado_mesa) 
						JOIN sga_mesas_examen  ON (sga_mesas_examen.mesa_examen		= sga_llamados_mesa.mesa_examen)
				WHERE	sga_insc_examen.inscripcion = {$parametros['inscripcion']}
			  ";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	//--------------------------------------------------------------------------
	//--- Modificacion de datos
	//--------------------------------------------------------------------------
	/**
	 * parametros: persona, alumno, actividad, llamado_mesa, instancia, estado, _interfaz, plan_version, param_exa_insc_unico_llamado, param_exa_insc_cant_max_una_actividad_por_llamado, param_exa_insc_cant_max_una_actividad_por_turno, param_exa_insc_siempre_pendiente
	 * no_quote: param_exa_insc_unico_llamado, param_exa_insc_cant_max_una_actividad_por_llamado, param_exa_insc_cant_max_una_actividad_por_turno, param_exa_insc_siempre_pendiente
	 * cache: no
	 * filas: 1
	 */
	function inscripcion_mesa_examen($parametros)
	{
		/**
		 * @todo: pasarlos desde la llamada
		 */
		$parametros['fuera_de_termino'] = "'N'";
		$parametros['autorizado_por'] = 'NULL';

		$sql = "select * from  f_insc_examen_inscribir(
									{$parametros['llamado_mesa']},
									{$parametros['alumno']},
									{$parametros['persona']},
									{$parametros['plan_version']},
									{$parametros['actividad']},
									{$parametros['estado']},
									{$parametros['fuera_de_termino']},
									{$parametros['autorizado_por']},
									{$parametros['instancia']},
									{$parametros['_interfaz']},
									'{$parametros['param_exa_insc_unico_llamado']}',
									'{$parametros['param_exa_insc_cant_max_una_actividad_por_llamado']}',
									'{$parametros['param_exa_insc_cant_max_una_actividad_por_turno']}',
									'{$parametros['param_exa_insc_siempre_pendiente']}'
								)";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: inscripcion, _interfaz
	 * cache: no
	 * filas: 1
	 */
	function eliminar_inscripcion_mesa($parametros)
	{
		// Elimina una inscripción a examen. Pasa la inscripción a una tabla de logs de inscripciones dadas de baja
		$sql = "SELECT * FROM f_insc_examen_eliminar( {$parametros['inscripcion']}, {$parametros['_interfaz']})";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: inscripcion_rechazada
	 * cache: no
	 * filas: 1
	 */
	function get_motivo_rechazo_inscripcion($parametros)
	{
		//Motivo de Rechazo (puede no existir). Para rechazos masivos.
		$sql_rechazo_manual = "	SELECT	mr.nombre as rechazo_masivo_desc
								FROM	sga_insc_examen_log
										LEFT JOIN sga_inscripciones_motivos_rechazo as mr ON mr.motivo_rechazo = sga_insc_examen_log.motivo_rechazo
								WHERE	sga_insc_examen_log.inscripcion = {$parametros['inscripcion_rechazada']}";

		//Requisitos no cumplidos (puede o no haber)
		$sql_rechazo_automatico = "	SELECT	req_no_cumplidos.mensaje_validacion as requisito_no_cumplido  
									FROM	sga_insc_examen_log
											LEFT JOIN sga_insc_examen_log_requisitos as req_no_cumplidos ON req_no_cumplidos.inscripcion = sga_insc_examen_log.inscripcion
									WHERE	sga_insc_examen_log.inscripcion = {$parametros['inscripcion_rechazada']}";

		$datos_rechazo_manual = kernel::db()->consultar_fila($sql_rechazo_manual);
		$datos_rechazo_automatico = kernel::db()->consultar($sql_rechazo_automatico);

		$nuevo = array('rechazo_masivo_desc' => null, 'requisitos_no_cumplidos' => null);
		if (!empty($datos_rechazo_manual)) {
			$nuevo['rechazo_masivo_desc'] = $datos_rechazo_manual['rechazo_masivo_desc'];
		}
		if (!empty($datos_rechazo_automatico)) {
			foreach ($datos_rechazo_automatico as $dato) {
				if ($dato['requisito_no_cumplido'] != null) {
					$nuevo['requisitos_no_cumplidos'][] = $dato['requisito_no_cumplido'];
				}
			}
		}
		return $nuevo;
	}

}
?>