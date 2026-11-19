<?php
namespace siu\modelo\datos\db;

use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;

class inscripcion_cursada
{
	/**
	 * parametros: alumno, persona, propuesta, plan_version, ubicacion, param_cur_ingresantes_oferta_actividades, param_cur_filtro_actividades_con_comision, param_cur_cursa_en_cualquier_ubicacion, param_cur_recursar_regularizadas
	 * param_null: ubicacion
	 * no_quote: param_cur_ingresantes_oferta_actividades, param_cur_filtro_actividades_con_comision, param_cur_cursa_en_cualquier_ubicacion, param_cur_recursar_regularizadas
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 */
	function lista_materias($parametros)
	{
		$param_cur_ingresantes_oferta_actividades = $parametros['param_cur_ingresantes_oferta_actividades'];
		$param_cur_filtro_actividades_con_comision = $parametros['param_cur_filtro_actividades_con_comision'];
		$param_cur_cursa_en_cualquier_ubicacion = $parametros['param_cur_cursa_en_cualquier_ubicacion'];
		$param_cur_recursar_regularizadas = $parametros['param_cur_recursar_regularizadas'];
		$alumno    = $parametros['alumno'];
		$propuesta = $parametros['propuesta'];
		$plan      = $parametros['plan'];
		$plan_version = $parametros['plan_version'];
		$ubicacion    = $parametros['ubicacion'];
		$where = '';
		$fuera_de_termino = false;
		$interfaz = \comunes::interfaz_autogestion; // Autogestion

		/**
		 * 	Si el parámetro del sistema cur_ingresantes_oferta_actividades = PRIMER_AÑO:
		 *  Sólo traer las actividades correspondan al primer año del plan de estudios.
		 */
		$filtro_solo_primer_año = '';

		if ($param_cur_ingresantes_oferta_actividades == "PRIMER_AÑO") {
			// Verifica si es ingresante en base a los periodos de inscripcion vigente a cursada. De allí toma el año académico
			$es_ingresante = catalogo::consultar('alumno', 'es_ingresante_periodos_insc_cursada', array('alumno' => $alumno));
			if ($es_ingresante) {
				$filtro_solo_primer_año = 'AND sga_elementos_plan.anio_de_cursada = 1';
			}
		}

		if ($param_cur_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		}

		$filtro_solo_con_comision_vigente = '';
		//Si el parámetro cur_filtro_actividades_con_comision = S
		//solo recuperar las actividades con comisión definida y con períodos de inscripción vigentes
		if ($param_cur_filtro_actividades_con_comision == comunes::si) {
			$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';
			$filtro_solo_con_comision_vigente = " AND sga_elementos.elemento IN (SELECT * FROM f_actividades_con_comisiones_vigentes($propuesta, $plan, $plan_version, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno)) ";
		}

		// Si el parámetro cur_recursar_regularizadas = 'S', agregar la columna 'regularizada'.
		$select_regularizada = '';
		$filtro_no_regularizadas = '';
		if ($param_cur_recursar_regularizadas == comunes::si) {
			$select_regularizada = ",	CASE
											WHEN (SELECT	COUNT(1)
												    FROM	vw_regularidades_basica
												   WHERE	vw_regularidades_basica.elemento = sga_elementos.elemento AND
															vw_regularidades_basica.alumno = $alumno AND
															vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
															vw_regularidades_basica.es_vigente = 1
												) > 0  THEN 1
											ELSE 0
										END AS regularizada";
		} else {
			// Si el parámetro cur_recursar_regularizadas = 'N', excluir actividades regularizadas vigentes.
			$filtro_no_regularizadas = " AND sga_elementos.elemento NOT IN (SELECT	vw_regularidades_basica.elemento
																			  FROM	vw_regularidades_basica
																			 WHERE	vw_regularidades_basica.alumno = $alumno AND
																					vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
																					vw_regularidades_basica.es_vigente = 1
																			)";
		}

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT	sga_elementos.elemento as id,
						sga_elementos.codigo,
						sga_elementos_plan.nombre,
						'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre as descr,
						sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre,
						sga_elementos_plan.creditos,
						(SELECT sga_responsables_academicas.nombre 
						   FROM sga_elementos_ra
						   JOIN sga_responsables_academicas ON sga_elementos_ra.responsable_academica = sga_responsables_academicas.responsable_academica
						WHERE sga_elementos_ra.elemento = sga_elementos.elemento
						LIMIT 1) as responsable_academica,
						sga_elementos_atrib.insc_cursada_por_prioridad as insc_cursada_por_prioridad,
						sga_elementos_atrib.prioridad_cant_maxima as prioridad_cantidad_maxima
						$select_regularizada
						
				FROM sga_elementos_plan
				JOIN sga_elementos_revision		ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				JOIN sga_elementos				ON sga_elementos.elemento = sga_elementos_revision.elemento
				JOIN sga_g3entidades_subtipos 	ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
				JOIN sga_elementos_atrib		ON sga_elementos_atrib.elemento = sga_elementos.elemento
				
				WHERE	sga_elementos_plan.plan_version = $plan_version
				  AND sga_g3entidades_subtipos.entidad_tipo = 2 
				  AND	sga_elementos.estado = '" . comunes::estado_activo . "'
						$filtro_solo_primer_año
					-- Se obvian las actividades aprobadas
				  AND	sga_elementos.elemento
							NOT IN (SELECT	vw_hist_academica_basica.elemento
									FROM	vw_hist_academica_basica
									WHERE	vw_hist_academica_basica.alumno = $alumno
									AND		vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
						$filtro_solo_con_comision_vigente
						$filtro_no_regularizadas
						$where
						
				 ORDER BY 
						sga_elementos_plan.nombre
				;";

		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$nuevo[$dato['id']] = $dato;
			$nuevo[$dato['id']][catalogo::id] = catalogo::generar_id($dato['id']);
		}
		return $nuevo;
	}

	//--------------------------------------------------------------------------
	//---- Inscripciones disponibles para propuesta
	//--------------------------------------------------------------------------


    /**
     * parametros: propuesta, plan, plan_version, ubicacion, alumno
     * no_quote: propuesta, plan, plan_version, ubicacion, alumno
     * cache: memoria
     * filas: 1
     */
    function hay_actividades_con_comisiones_vigentes($parametros)
    {
        $param_cursa_en_cualquier_ubicacion = \guarani::parametros()->get_valor('cur_cursa_en_cualquier_ubicacion', [$parametros['plan']], false);
        return \toba::consulta_php('co_inscripciones_cursadas')->hay_actividades_con_comisiones_vigentes(
                $parametros['propuesta'],
                $parametros['plan'],
                $parametros['plan_version'],
                $parametros['ubicacion'],
                $parametros['alumno'],
                $param_cursa_en_cualquier_ubicacion,
                \comunes::interfaz_autogestion
        );
    }

    /**
     * parametros: plan_version
     * no_quote: plan_version
     * cache: memoria
     * filas: n
     */
    function get_periodos_inscripcion_vigentes_por_plan_version($parametros)
    {
        return \toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_vigentes_por_plan_version($parametros['plan_version'], \comunes::interfaz_autogestion);
    }

    /**
     * parametros: plan_version
     * no_quote: plan_version
     * cache: memoria
     * filas: n
     */
    function get_excepciones_periodo_inscripcion_por_plan_version($parametros)
    {
        return \toba::consulta_php('co_periodos_de_inscripcion')->get_excepciones_periodo_inscripcion_por_plan_version($parametros['plan_version'], \comunes::interfaz_autogestion);
    }

    /**
     * parametros: plan_version
     * no_quote: plan_version
     * cache: memoria
     * filas: 1
     */
    function get_proximo_periodo_inscripcion_vigente_por_plan_version($parametros)
    {
        return \toba::consulta_php('co_periodos_de_inscripcion')->get_proximo_periodo_inscripcion_vigente_por_plan_version($parametros['plan_version'], \comunes::interfaz_autogestion);
    }
    
    //--------------------------------------------------------------------------
	//---- Comisiones
	//--------------------------------------------------------------------------
	
	/**
	 * parametros: alumno, actividad, propuesta, plan, plan_version, ubicacion, param_cur_usa_subcomisiones, param_cur_cursa_en_cualquier_ubicacion, param_cur_filtra_rango_apellido_comision, param_cur_filtra_rango_apellido_subco, param_cur_controlar_cupo_por, inicial_apellido, division, param_cur_division_estricta
	 * no_quote: param_cur_usa_subcomisiones, param_cur_cursa_en_cualquier_ubicacion, param_cur_filtra_rango_apellido_comision, param_cur_filtra_rango_apellido_subco, param_cur_controlar_cupo_por, param_cur_division_estricta
	 * param_null: division
	 * cache: memoria
	 * filas: n
	 */
	function lista_comisiones($parametros)
	{
		$comisiones = static::lista_parcial_comisiones($parametros);
		$subcomisiones = static::lista_parcial_subcomisiones($parametros);
		foreach ($subcomisiones as $subcom) {
			if (isset($comisiones[$subcom['comision']])) {
				if (!isset($comisiones[$subcom['comision']]['subcomisiones'][$subcom['tipo_clase']])) {
					$comisiones[$subcom['comision']]['subcomisiones'][$subcom['tipo_clase']] = array();
				}
				$comisiones[$subcom['comision']]['subcomisiones'][$subcom['tipo_clase']][] = $subcom;
			}
		}
		kernel::log()->add_debug('-- LISTADO COMISIONES --', $comisiones);
		return $comisiones;
	}

	/**
	 * Retorna el listado de comisiones disponibles para inscribirse a cursar según el filtro especificado
	 * Guarani3 = get_comisiones_disponibles_para_inscripcion()
	 * parametros: alumno, actividad, propuesta, plan, plan_version, ubicacion, inicial_apellido, division, param_cur_usa_subcomisiones, param_cur_cursa_en_cualquier_ubicacion, param_cur_filtra_rango_apellido_comision, param_cur_controlar_cupo_por, param_cur_division_estricta
	 * no_quote: param_cur_usa_subcomisiones, param_cur_cursa_en_cualquier_ubicacion, param_cur_filtra_rango_apellido_comision ,param_cur_controlar_cupo_por, param_cur_division_estricta
	 * param_null: division
	 * cache: no
	 * filas: n
	 */
	protected function lista_parcial_comisiones($parametros)
	{
		$interfaz  = \comunes::interfaz_autogestion; // Autogestion
		$alumno    = $parametros['alumno'];
		$propuesta = $parametros['propuesta'];
		$plan      = $parametros['plan'];
		$plan_version = $parametros['plan_version'];
		$actividad = $parametros['actividad'];
		$ubicacion = $parametros['ubicacion'];
		$division  = $parametros['division'];
		$inicial_apellido = $parametros['inicial_apellido'];
		$param_cur_cursa_en_cualquier_ubicacion   = $parametros['param_cur_cursa_en_cualquier_ubicacion'];
		$param_cur_filtra_rango_apellido_comision = $parametros['param_cur_filtra_rango_apellido_comision'];
		$param_cur_division_estricta              = $parametros['param_cur_division_estricta'];
		$where = '';

		// Filtro por Ubicación.		  
		if ($param_cur_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'NULL';
		}
		
		// Filtro por division (año en que se encuentra el alumno)
		$filtro_division = '';
		if ($param_cur_division_estricta == comunes::si && isset($parametros['division'])) {
			$filtro_division = " AND (sga_comisiones.division = $division OR sga_comisiones.division IS NULL )";
		}
		
		$en_periodo_inscripcion = 'true'; // (!$fuera_de_termino) ? 'true' : 'false';
		$filtra_rango_apellido = ($param_cur_filtra_rango_apellido_comision == comunes::si);
		$where_apellido = '';
		if ($filtra_rango_apellido) {
			$where_apellido = " AND (sga_comisiones.letra_desde IS NULL AND sga_comisiones.letra_hasta IS NULL OR
								sga_comisiones.letra_desde IS NULL AND sga_comisiones.letra_hasta IS NOT NULL AND $inicial_apellido <= sga_comisiones.letra_hasta OR
								sga_comisiones.letra_desde IS NOT NULL AND sga_comisiones.letra_hasta IS NULL AND $inicial_apellido >= sga_comisiones.letra_desde OR
								sga_comisiones.letra_desde IS NOT NULL AND sga_comisiones.letra_hasta IS NOT NULL AND $inicial_apellido BETWEEN sga_comisiones.letra_desde AND sga_comisiones.letra_hasta)";
		}

		$sql = "SELECT 
					sga_comisiones.comision  as comision,
					sga_comisiones.nombre    as comision_nombre,
					sga_comisiones.observaciones    as comision_observaciones,
					sga_periodos_lectivos.periodo_lectivo as periodo_lectivo,
					sga_periodos.nombre as periodo_lectivo_nombre,
					sga_periodos.anio_academico as anio_academico,
					sga_comisiones.turno as turno,
					sga_comisiones.inscripcion_cerrada,
					COALESCE(sga_turnos_cursadas.nombre,'') AS turno_nombre,
					sga_catedras.catedra as catedra,
					COALESCE(sga_catedras.nombre, '') as catedra_nombre,
					sga_ubicaciones.ubicacion as ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					f_docentes_comision(sga_comisiones.comision, true) as docente,
					f_horario_comision(sga_comisiones.comision) as horario,
					f_instancias_comision (sga_comisiones.comision) as instancias,
					f_modalidad_cursada_comision (sga_comisiones.comision) as modalidades,
					(SELECT array_to_string(array_agg(DISTINCT dia_semana), ',')
					FROM sga_comisiones_bh
					JOIN sga_asignaciones ON (sga_comisiones_bh.asignacion = sga_asignaciones.asignacion)
					WHERE sga_comisiones_bh.comision = sga_comisiones.comision) AS dias_semana,
					sga_comisiones.cupo_tipo as cupo_tipo

				FROM sga_comisiones
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno

				WHERE sga_comisiones.elemento = $actividad
				  AND sga_comisiones.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad($propuesta, $plan, $plan_version, $actividad, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno))
				  $where_apellido
				  $filtro_division
				  $where
				
				ORDER BY sga_periodos.anio_academico,sga_periodos.nombre, sga_comisiones.nombre
				";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $dato) {
			$nuevo[$dato['comision']] = $dato;
			$nuevo[$dato['comision']][catalogo::id] = catalogo::generar_id($dato['comision']);
		}
		return $nuevo;
	}

	/**
	 * Recupera las subcomisiones de las comisiones de una actividad.
	 * Subcomisiones de comisiones que tienen períodos de inscripción vigente segun la versión de plan de estudios.
	 * parametros: alumno, actividad, propuesta, plan, plan_version, ubicacion, inicial_apellido, division, param_cur_usa_subcomisiones, param_cur_cursa_en_cualquier_ubicacion, param_cur_filtra_rango_apellido_comision, param_cur_controlar_cupo_por, param_cur_division_estricta
	 * no_quote: param_cur_filtra_rango_apellido_subco, param_cur_controlar_cupo_por, param_cur_division_estricta
	 * cache: no
	 * filas: n
	 */
	protected function lista_parcial_subcomisiones($parametros)
	{
		
		$param_cur_filtra_rango_apellido_subco	= $parametros['param_cur_filtra_rango_apellido_subco'];
		$param_cur_division_estricta			= $parametros['param_cur_division_estricta'];
		$param_cur_cursa_en_cualquier_ubicacion	= $parametros['param_cur_cursa_en_cualquier_ubicacion'];
		
		$interfaz				= \comunes::interfaz_autogestion; // Autogestion
		$alumno					= $parametros['alumno'];
		$ubicacion				= $parametros['ubicacion'];
		$inicial_apellido		= $parametros['inicial_apellido'];
		$division				= $parametros['division'];
		$en_periodo_inscripcion = 'true'; 

		// Filtro por apellido.
		$filtra_rango_apellido = ($param_cur_filtra_rango_apellido_subco == comunes::si);

		// Filtro por Ubicación.		  
		if ($param_cur_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'NULL';
		}
		
		$where_apellido = '';
		if ($filtra_rango_apellido) {
			$where_apellido = " AND (sc.letra_desde IS NULL AND sc.letra_hasta IS NULL OR
									sc.letra_desde IS NULL AND sc.letra_hasta IS NOT NULL AND $inicial_apellido <= sc.letra_hasta OR
									sc.letra_desde IS NOT NULL AND sc.letra_hasta IS NULL AND $inicial_apellido >= sc.letra_desde OR
									sc.letra_desde IS NOT NULL AND sc.letra_hasta IS NOT NULL AND $inicial_apellido BETWEEN sc.letra_desde AND sc.letra_hasta)";
		}

		// Filtro por division (año en que se encuentra el alumno)
		$filtro_division = '';
		if ($param_cur_division_estricta == comunes::si && isset($parametros['division'])) {
			$filtro_division = " AND COALESCE(c.division, $division) = $division ";
		}
		
		$sql = "SELECT		c.comision,
							sc.subcomision,
							sc.nombre as subcomision_nombre,
							sc.tipo_clase,
							tc.nombre as tipo_clase_nombre,
							f_docentes_subcomision(sc.subcomision, true) as docente,
							f_horario_subcomision(sc.subcomision) as horario,
							sc.inscripcion_habilitada,
							sc.letra_desde,
							sc.letra_hasta
								
				FROM		sga_comisiones as c 
							JOIN sga_subcomisiones as sc ON sc.comision = c.comision
							JOIN sga_clases_tipos as tc ON tc.tipo_clase = sc.tipo_clase
				
				WHERE		c.elemento = {$parametros['actividad']}
							AND c.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad({$parametros['propuesta']}, {$parametros['plan']}, {$parametros['plan_version']}, {$parametros['actividad']}, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno))
                                                        AND sc.inscripcion_habilitada = '".comunes::si."'
							$where_apellido
							$filtro_division
				
				ORDER BY 
							c.comision, 
							tc.nombre,
							sc.nombre
				";
		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $dato) {
			$nuevo[$dato['subcomision']] = $dato;
			$nuevo[$dato['subcomision']][catalogo::id] = catalogo::generar_id($dato['subcomision']);
		}
		return $nuevo;
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------
	
	/**
	 * parametros: comision, plan_version
	 * cache: memoria
	 * filas: n
	 */
	function info_comision($parametros)
	{
		//Agrego columna inscripcion_cerrada_codigo
		// @TODO = FALTA TERMINAR G3
		$sql = "SELECT 
						c.comision,
						c.inscripcion_cerrada_codigo as inscripcion_cerrada_codigo,
						c.inscripcion_cerrada as inscripcion_cerrada,
						c.nombre as comision_nombre, 
						c.ubicacion, 
						u.nombre as ubicacion_nombre,
						p.anio_academico, 
						c.periodo_lectivo, 
						p.nombre as periodo_lectivo_nombre,
						c.catedra,
						sga_catedras.nombre as catedra_nombre,
						c.turno as turno,
						sga_turnos_cursadas.nombre as turno_nombre,
						c.cupo as cupo,
						c.elemento as actividad,
						sga_elementos.codigo as actividad_codigo,
						COALESCE(ep.nombre, sga_elementos.nombre) as actividad_nombre,
						COALESCE(ep.nombre_abreviado, sga_elementos.nombre_abreviado) as actividad_nombre_abreviado,
						f_instancias_comision(c.comision) as instancias,
						f_instancias_comision_id(c.comision) as instancias_id
					FROM sga_comisiones as c
					JOIN sga_ubicaciones as u ON u.ubicacion = c.ubicacion
					JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = c.periodo_lectivo
					JOIN sga_periodos as p ON p.periodo = pl.periodo
					JOIN sga_elementos ON sga_elementos.elemento = c.elemento
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
					LEFT JOIN sga_catedras ON sga_catedras.catedra = c.catedra
					LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = c.turno
					LEFT JOIN sga_elementos_plan as ep ON (ep.plan_version  = {$parametros['plan_version']}	AND 
					                                       ep.elemento_revision  = sga_elementos_revision.elemento_revision)
				WHERE	c.comision = {$parametros['comision']} 
			  ";

		$datos = kernel::db()->consultar_fila($sql);
		// Determino la calidad a utilizar
		//  ALE: "Solo verificar el valor de "calidad_insc" para la calidad final de la inscripcion si
		//  el parametro " = P", en ese caso va el valor devuelto por la consulta, sino va R."
		// @TODO = EN G3 las instancias de insc a cursada difieren de la calidad de insc en G2	  
		//		$calidad = \siu\modelo\entidades\parametro::inscripcion_cursada_calidad();
		//		if($calidad != 'P') {
		//			$datos['calidad_insc'] = 'R';
		//		} 
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: memoria
	 * filas: n
	 */
	function info_inscripcion($parametros)
	{
		$sql = "SELECT	sga_comisiones.elemento as actividad
				FROM	sga_insc_cursada
				JOIN	sga_comisiones ON (sga_comisiones.comision = sga_insc_cursada.comision)
				WHERE	sga_insc_cursada.inscripcion = {$parametros['inscripcion']}
			  ";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: 1
	 */
	function existe_inscripcion($parametros)
	{
		// Esta función no debe usar cache porque verifica si se elimino la inscripción desde Gestión
		$sql = "SELECT EXISTS(SELECT 1 FROM sga_insc_cursada WHERE inscripcion = {$parametros['inscripcion']}) AS existe";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: plan_version, perfil_activo
	 * param_null: plan_version
	 * no_quote: perfil_activo
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 * 
	 * Lista de oferta de comisiones de periodos lectivos vigentes/futuros con fecha de publicacion menor o igual a hoy e inscripcion habilitada
	 * Los filtros por Docente, Responsable Académica, Ubicación, Actividad y Período Lectivo se hacen por Javascript
	 * Se cacheará una consulta por cada plan_version por la que se consulte y por todas las comisones sin filtro de plan_version
	 */
	function info_oferta_comisiones_vigentes($parametros)
	{
		//---- FILTRO ----------------------------------------------------------
		// Si viene el valor de plan_version, entonces la consulta la realiza un alumno, sino un docente y debe ver todo.
		if (!is_null($parametros['plan_version'])) {
			// La consulta se realiza desde la interfaz del ALUMNO. Solo ve comisiones de actividades del plan del alumno
			$from_actividad = " vw_actividades_plan as act";
			$where_actividad = " act.plan_version = {$parametros['plan_version']} AND ";
		} else {
			// La consulta se realiza desde la interfaz DOCENTE
			$from_actividad = " sga_elementos as act";
			$where_actividad = "";
		}
		
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$aula_a_determinar = kernel::db()->quote(kernel::traductor()->trans('aula_a_determinar'));
		
		$fecha_publicacion_comisiones_filtro = "";
		// Si se ingresa con el perfil 'Alumno'
		if ($parametros['perfil_activo'] == \siu\modelo\entidades\persona::perfil_alumno) {
			$fecha_publicacion_comisiones_filtro = "AND pl.fecha_publicacion_comisiones <= CURRENT_DATE   -- Alumno";
		// Si se ingresa con el perfil 'Docente'
		} elseif ($parametros['perfil_activo'] == \siu\modelo\entidades\persona::perfil_docente) {
			$fecha_publicacion_comisiones_filtro = "AND COALESCE(pl.fecha_publicacion_comisiones_docente, CURRENT_DATE) <= CURRENT_DATE	  -- Docente";
		}

		$si = kernel::db()->quote(comunes::si);

		//---- Lista de Comisiones ---------------------------------------------
		$sql = "-- Comisiones sin subcomision
				SELECT 		
					(p.anio_academico || '-' || c.periodo_lectivo) as anio_perlec, 
					p.anio_academico as anio_academico, 
					c.periodo_lectivo as periodo_lectivo, 
					p.nombre as periodo_lectivo_nombre,
					-- p.fecha_inicio,	p.fecha_fin,
					(act.nombre || '-' || act.elemento) as actividad_orden,
					act.elemento as actividad,
					act.codigo as actividad_codigo,
					act.nombre as actividad_nombre,
					c.comision as comision,
					c.nombre as comision_nombre, 
					c.ubicacion as ubicacion, 
					u.nombre as ubicacion_nombre,
					c.catedra as catedra,
					sga_catedras.nombre as catedra_nombre,
					c.turno as turno,
					sga_turnos_cursadas.nombre as turno_nombre,
					sga_comisiones_cupo.cupo as cupo,
					sga_comisiones_cupo.cant_inscriptos as cant_inscriptos,
					f_instancias_comision(c.comision) as instancias,
					f_docentes_comision(c.comision, true) as docentes_resp,
					-- f_horario_comision(c.comision) as horario,
					sga_clases_tipos.nombre as tipo_clase_nombre,
					sga_asignaciones.dia_semana as dia_semana,
					to_char(sga_asignaciones.hora_inicio, $formato_hora_visual) || ' a ' || to_char(sga_asignaciones.hora_finalizacion, $formato_hora_visual) as horario,
					COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 1), $aula_a_determinar) as espacio,

					-- datos subcomision
					cast(null as integer) as subcomision,
					cast(null as varchar(100)) as subcomision_nombre,
					(case 
					   when sga_asignaciones.dia_semana = 'Lunes' then 1
					   when sga_asignaciones.dia_semana = 'Martes' then 2
					   when sga_asignaciones.dia_semana = 'Miercoles' then 3
					   when sga_asignaciones.dia_semana = 'Jueves' then 4
					   when sga_asignaciones.dia_semana = 'Viernes' then 5
					   when sga_asignaciones.dia_semana = 'Sabado' then 6
					   when sga_asignaciones.dia_semana = 'Domingo' then 7
					   else	0 
					end) as orden_dia,
					sga_asignaciones.hora_inicio as orden_hora,
					c.observaciones as observaciones
				FROM
					$from_actividad
					JOIN sga_comisiones as c ON c.elemento = act.elemento
					JOIN sga_ubicaciones as u ON u.ubicacion = c.ubicacion
					JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = c.periodo_lectivo
					JOIN sga_periodos as p ON p.periodo = pl.periodo
					LEFT JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = c.comision
					LEFT JOIN sga_catedras ON sga_catedras.catedra = c.catedra
					LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = c.turno
					LEFT JOIN sga_comisiones_bh ON sga_comisiones_bh.comision = c.comision
					LEFT JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
					LEFT JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
				WHERE		
					$where_actividad
					p.fecha_fin >= CURRENT_DATE
				AND NOT EXISTS (SELECT 1 FROM sga_subcomisiones as s WHERE s.comision = c.comision) 
				AND c.inscripcion_habilitada = $si
				-- Si existen asignaciones, excluir las que corresponden a clases agregadas por fuera de las bandas horarias de la comisión.
				AND (sga_asignaciones.asignacion IS NULL OR sga_asignaciones.fecha_desde <> sga_asignaciones.fecha_hasta)
				$fecha_publicacion_comisiones_filtro

			UNION ALL	   
				
				-- Comisiones CON subcomisiones
			    SELECT 
					p.anio_academico || '-' || c.periodo_lectivo as anio_perlec, 
					p.anio_academico as anio_academico, 
					c.periodo_lectivo as periodo_lectivo, 
					p.nombre as periodo_lectivo_nombre,
					-- p.fecha_inicio,	p.fecha_fin,
					(act.nombre || '-' || act.elemento) as actividad_orden,
					act.elemento as actividad,
					act.codigo as actividad_codigo,
					act.nombre as actividad_nombre,
					c.comision as comision,
					c.nombre as comision_nombre, 
					c.ubicacion as ubicacion, 
					u.nombre as ubicacion_nombre,
					c.catedra as catedra,
					sga_catedras.nombre as catedra_nombre,
					c.turno as turno,
					sga_turnos_cursadas.nombre as turno_nombre,
					sga_subcomisiones_cupo.cupo as cupo,
					sga_subcomisiones_cupo.cant_inscriptos as cant_inscriptos,
					f_instancias_comision(c.comision) as instancias,
					f_docentes_subcomision(s.subcomision, true) as docentes_resp,
					sga_clases_tipos.nombre as tipo_clase_nombre,
					sga_asignaciones.dia_semana as dia_semana,
					to_char(sga_asignaciones.hora_inicio, $formato_hora_visual) || ' a ' || to_char(sga_asignaciones.hora_finalizacion, $formato_hora_visual) as horario,
					-- f_horario_subcomision(s.subcomision) as horario,
					COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 1), $aula_a_determinar) as espacio,

					-- datos subcomision
					s.subcomision as subcomision,
					s.nombre as subcomision_nombre,
					(case 
				       when sga_asignaciones.dia_semana = 'Lunes' then 1
				       when sga_asignaciones.dia_semana = 'Martes' then 2
				       when sga_asignaciones.dia_semana = 'Miercoles' then 3
				       when sga_asignaciones.dia_semana = 'Jueves' then 4
				       when sga_asignaciones.dia_semana = 'Viernes' then 5
				       when sga_asignaciones.dia_semana = 'Sabado' then 6
				       when sga_asignaciones.dia_semana = 'Domingo' then 7
				   else	0 
					end) as orden_dia,
					sga_asignaciones.hora_inicio as orden_hora,
					c.observaciones as observaciones
				FROM 
					$from_actividad
					JOIN sga_comisiones as c ON c.elemento = act.elemento 
					JOIN sga_subcomisiones as s	 ON s.comision = c.comision
					JOIN sga_ubicaciones as u ON u.ubicacion = c.ubicacion
					JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = c.periodo_lectivo
					JOIN sga_periodos as p ON p.periodo = pl.periodo
				    LEFT JOIN sga_catedras ON sga_catedras.catedra = c.catedra
					LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = c.turno
					LEFT JOIN sga_subcomisiones_cupo ON sga_subcomisiones_cupo.subcomision = s.subcomision
					LEFT JOIN sga_subcomisiones_bh   ON sga_subcomisiones_bh.subcomision = s.subcomision
					LEFT JOIN sga_clases_tipos       ON sga_clases_tipos.tipo_clase = s.tipo_clase
					LEFT JOIN sga_comisiones_bh      ON sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria
					LEFT JOIN sga_asignaciones       ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				WHERE 
					$where_actividad	
					p.fecha_fin >= CURRENT_DATE 
				AND c.inscripcion_habilitada = $si
				AND s.inscripcion_habilitada = $si
				-- Si existen asignaciones, excluir las que corresponden a clases agregadas por fuera de las bandas horarias de la comisión.
				AND (sga_asignaciones.asignacion IS NULL OR sga_asignaciones.fecha_desde <> sga_asignaciones.fecha_hasta)
				$fecha_publicacion_comisiones_filtro
			
			ORDER BY 1, 5, 9, 25, orden_dia, orden_hora
			";
		// 1(anio_perlec) + 5(actividad_orden) + 9(comision) + 25(subcomision) + 22(dia_semana)

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	function asignaciones($parametros)
	{
		$aula_a_determinar = kernel::db()->quote(kernel::traductor()->trans('aula_a_determinar'));
		
		$sql = "	
				SELECT  
				   sga_comisiones.comision as comision, 
				   sga_comisiones.nombre as nombre, 
				   sga_comisiones.ubicacion as sede, 
				   sga_periodos.anio_academico as anio_academico, 
				-- sga_comisiones.periodo_lectivo , 
				   sga_periodos.nombre as periodo_lectivo,
				--  sga_comisiones.catedra, 
				   sga_catedras.nombre as catedra,
				-- sga_comisiones.turno as turno,
				   substr(sga_turnos_cursadas.nombre,1,1) as turno, 
				   sga_comisiones.cupo as cupo, 
				-- sga_comisiones.elemento as actividad,  
				   vw_actividades.codigo as materia,
				-- COALESCE(ep.nombre, vw_actividades.nombre) as materia_nombre,
				   COALESCE(ep.nombre_abreviado, vw_actividades.nombre_abreviado) as nombre_materia,
				   sga_asignaciones.dia_semana as dia_semana, 
				   sga_asignaciones.fecha_desde as fecha_asign_desde, 
				   sga_asignaciones.fecha_hasta as fecha_asign_hasta, 
				   sga_asignaciones.hora_inicio as hs_comienzo_clase, 
				   sga_asignaciones.hora_finalizacion as hs_finaliz_clase,
				   COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 1), $aula_a_determinar) as aula,
				   cast(null as integer) as edificio,
				   sga_clases_tipos.nombre as tipo_clase
				--   (CASE WHEN sga_asignaciones.asignacion IS NULL THEN 1 ELSE 0 END) as tiene_asignacion_horaria
				FROM
						sga_alumnos
						JOIN sga_insc_cursada ON sga_insc_cursada.alumno = sga_alumnos.alumno 
						JOIN sga_comisiones ON sga_comisiones.comision  = sga_insc_cursada.comision
						JOIN vw_actividades ON vw_actividades.elemento  = sga_comisiones.elemento
						JOIN sga_elementos_plan as ep ON (ep.plan_version = sga_insc_cursada.plan_version AND ep.elemento_revision = vw_actividades.elemento_revision)
						JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
						JOIN sga_periodos ON sga_periodos.periodo   = sga_periodos_lectivos.periodo
						LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
						LEFT JOIN sga_catedras        ON sga_catedras.catedra = sga_comisiones.catedra
						LEFT JOIN sga_comisiones_bh   ON sga_comisiones_bh.comision = sga_comisiones.comision
						LEFT JOIN sga_asignaciones    ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
						LEFT JOIN sga_clases_tipos    ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
				WHERE  sga_alumnos.persona  = {$parametros['persona']}
				  AND sga_periodos.fecha_fin >= CURRENT_DATE
				  AND NOT EXISTS (SELECT 1 FROM sga_subcomisiones as sc WHERE sc.comision = sga_comisiones.comision)
					
				UNION ALL 
				  -- SUBCOMISIONES
				  SELECT  
						sga_comisiones.comision as comision, 
						sga_comisiones.nombre as nombre , 
						sga_comisiones.ubicacion as sede, 
						sga_periodos.anio_academico as anio_academico, 
						-- sga_comisiones.periodo_lectivo , 
						sga_periodos.nombre as periodo_lectivo,
						-- sga_comisiones.catedra, 
						sga_catedras.nombre as catedra,
						-- sga_comisiones.turno as turno,
						sga_turnos_cursadas.nombre as turno, 
						sga_comisiones.cupo as cupo, 
						-- sga_comisiones.elemento as actividad,
						vw_actividades.codigo as materia,
						-- COALESCE(ep.nombre, vw_actividades.nombre) as actividad_nombre,
						COALESCE(ep.nombre_abreviado, vw_actividades.nombre_abreviado) as nombre_materia,
						sga_asignaciones.dia_semana, 
						sga_asignaciones.fecha_desde as fecha_asign_desde, 
						sga_asignaciones.fecha_hasta as fecha_asign_hasta, 
						sga_asignaciones.hora_inicio as hora_inicio_clase, 
						sga_asignaciones.hora_finalizacion as hora_finaliz_clase,
						COALESCE(get_espacios_banda_horaria(sga_subcomisiones_bh.banda_horaria, 1), $aula_a_determinar) as aula,
						cast(null as integer) as edificio,
						sga_clases_tipos.nombre as tipo_clase
				--   (CASE WHEN sga_asignaciones.asignacion IS NULL THEN 1 ELSE 0 END) as tiene_asignacion_horaria
					FROM  
						sga_alumnos
						JOIN sga_insc_cursada ON  sga_insc_cursada.alumno = sga_alumnos.alumno
						JOIN sga_insc_subcomision ON sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion
						JOIN sga_comisiones ON sga_comisiones.comision  = sga_insc_cursada.comision
						JOIN vw_actividades ON vw_actividades.elemento  = sga_comisiones.elemento
						JOIN sga_elementos_plan as ep ON (ep.plan_version = sga_insc_cursada.plan_version AND ep.elemento_revision = vw_actividades.elemento_revision)
						JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
						JOIN sga_periodos ON sga_periodos.periodo   = sga_periodos_lectivos.periodo
						LEFT JOIN sga_subcomisiones_bh ON sga_subcomisiones_bh.subcomision = sga_insc_subcomision.subcomision
						LEFT JOIN sga_comisiones_bh ON  sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria
						LEFT JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
						LEFT JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
						LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
						LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
					WHERE sga_alumnos.persona  = {$parametros['persona']}
					  AND sga_periodos.fecha_fin >= CURRENT_DATE
				";


		$datos = kernel::db()->consultar($sql);

		$nuevo = Array();
		if ($datos) {
			foreach (array_keys($datos) as $id) {
				foreach (array_keys($datos[$id]) as $campo) {
					if ($campo == 'dia_semana') {
						$datos[$id][$campo] = $this->dia_a_numero($datos[$id][$campo]);
					}
					$nuevo[$id][strtoupper($campo)] = $datos[$id][$campo];
				}
			}
		}
		return $nuevo;
	}

	protected function dia_a_numero($dia)
	{
		$dia = strtolower($dia);
		switch ($dia) {
			case 'domingo': return 1;
			case 'lunes': return 2;
			case 'martes': return 3;
			case 'miercoles': return 4;
			case 'jueves': return 5;
			case 'viernes': return 6;
			case 'sabado': return 7;
		}
	}

	/**
	 * parametros: persona, comisiones_str, param_cur_controla_cupo_por
	 * no_quote: comisiones_str, param_cur_controla_cupo_por
	 * cache: no
	 * filas: n
	 */
	function info_cupo_comisiones($parametros)
	{
		// Recupero constantes relacionadas con el cupo en la comision.
		$cupo_por_comision  = kernel::db()->quote(\comision::cupo_tipo_comision); 
		$cupo_por_propuesta = kernel::db()->quote(\comision::cupo_tipo_propuesta);

		if ($parametros['param_cur_controla_cupo_por'] == 'comision') {
			
			$sql = "-- Inscripciones a comisiones sin subcomisiones.
					SELECT	0 as con_subcomision,
							sga_comisiones.comision,
							CASE sga_comisiones.cupo_tipo 
							   WHEN $cupo_por_comision THEN sga_comisiones_cupo.cupo
							   WHEN $cupo_por_propuesta THEN sga_comisiones_propuestas_cupo.cupo
							END as cupo,
							CASE sga_comisiones.cupo_tipo 
							   WHEN $cupo_por_comision THEN sga_comisiones_cupo.cant_inscriptos
							   WHEN $cupo_por_propuesta THEN sga_comisiones_propuestas_cupo.cant_inscriptos
							END as cant_inscriptos,
			                cast(null as integer) as subcomision,
			                cast(null as varchar(100)) as subcomision_nombre,
			                cast(null as varchar(50)) as tipo_clase
					FROM	sga_alumnos
					JOIN	sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
					JOIN	sga_comisiones ON sga_comisiones.comision = sga_insc_cursada.comision
					JOIN	sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_insc_cursada.comision
					LEFT JOIN	sga_comisiones_propuestas_cupo ON (sga_comisiones_propuestas_cupo.comision = sga_insc_cursada.comision AND 
					                                               sga_comisiones_propuestas_cupo.propuesta = sga_alumnos.propuesta)
					WHERE   sga_alumnos.persona = {$parametros['persona']}
					  AND	sga_insc_cursada.comision IN ({$parametros['comisiones_str']})
					  AND	sga_insc_cursada.comision NOT IN (SELECT comision FROM sga_subcomisiones WHERE comision IN ({$parametros['comisiones_str']}))
					
					UNION ALL
					
					-- inscripciones a subcomisiones de la persona. de las comisiones pasadas por parametro  
					SELECT	1 as con_subcomision,
							sga_subcomisiones.comision,
							sga_subcomisiones_cupo.cupo as cupo,
							sga_subcomisiones_cupo.cant_inscriptos as cant_inscriptos, 
			                sga_subcomisiones.subcomision as subcomision,
			                sga_subcomisiones.nombre as subcomision_nombre,
			                sga_clases_tipos.nombre as tipo_clase
					FROM	sga_alumnos
					JOIN	sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
					JOIN	sga_insc_subcomision ON sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion
					JOIN	sga_subcomisiones ON sga_insc_subcomision.subcomision = sga_subcomisiones.subcomision
					JOIN	sga_subcomisiones_cupo ON sga_insc_subcomision.subcomision = sga_subcomisiones_cupo.subcomision
					JOIN	sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase
					WHERE   sga_alumnos.persona = {$parametros['persona']}
					  AND	sga_insc_cursada.comision IN ({$parametros['comisiones_str']})
					ORDER BY 2
					";
		} else {
			// Cupo por aula/bandas horarias compartidas. Busca en todas las comisiones que comparten alguna banda horaria
			// con la comision a evaluar.
			$sql = "-- Comisiones sin subcomisiones.
					SELECT	0 as con_subcomision,
							sga_comisiones.comision,
							CASE sga_comisiones.cupo_tipo
							  WHEN $cupo_por_comision THEN COALESCE(sga_comisiones.cupo,0)
							  WHEN $cupo_por_propuesta THEN COALESCE((SELECT cpc.cupo FROM sga_comisiones_propuestas_cupo as cpc 
							                                          WHERE cpc.comision = sga_insc_cursada.comision 
																	  AND cpc.propuesta = sga_alumnos.propuesta),0)
							END as cupo,
							(SELECT COALESCE(SUM(CASE c.cupo_tipo
							                      WHEN $cupo_por_comision THEN COALESCE(cc.cant_inscriptos,0)
												  WHEN $cupo_por_propuesta THEN COALESCE((SELECT cpc.cant_inscriptos 
												                                            FROM sga_comisiones_propuestas_cupo as cpc 
												                                           WHERE cpc.comision = c.comision 
																						     AND cpc.propuesta = sga_alumnos.propuesta),0)
												 END),0)
								FROM sga_comisiones as c 
								JOIN sga_comisiones_cupo as cc ON cc.comision = c.comision
								WHERE c.comision IN (SELECT DISTINCT bh2.comision 
														FROM sga_comisiones_bh
														JOIN sga_comisiones_bh as bh2 ON bh2.asignacion = sga_comisiones_bh.asignacion
														WHERE sga_comisiones_bh.comision = sga_comisiones.comision
													 UNION
													 SELECT sga_comisiones.comision -- Por si la comision no tiene bandas horarias definidas
													)
							) as cant_inscriptos,
			                cast(null as integer) as subcomision,
			                cast(null as varchar(100)) as subcomision_nombre,
			                cast(null as varchar(50)) as tipo_clase
					
					FROM	sga_alumnos
					JOIN	sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
					JOIN	sga_comisiones ON sga_comisiones.comision = sga_insc_cursada.comision
					JOIN	sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_insc_cursada.comision
					WHERE   sga_alumnos.persona = {$parametros['persona']}
					  AND	sga_insc_cursada.comision IN ({$parametros['comisiones_str']})
					  AND	sga_insc_cursada.comision NOT IN (SELECT comision FROM sga_subcomisiones WHERE comision IN ({$parametros['comisiones_str']}))
													
					UNION ALL 
					-- Comisiones con subcomisiones
					SELECT	1 as con_subcomision,
							sc.comision,
							COALESCE(sc.cupo,0) as cupo,
							(SELECT COALESCE(SUM(sga_subcomisiones_cupo.cant_inscriptos),0) 
									  FROM sga_subcomisiones_cupo
									 WHERE sga_subcomisiones_cupo.subcomision IN (COALESCE(( 
									        SELECT DISTINCT sga_subcomisiones_bh.subcomision
									          FROM sga_comisiones_bh
									          JOIN sga_subcomisiones_bh ON sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
									         WHERE sga_comisiones_bh.asignacion IN (
									                 SELECT sga_comisiones_bh.asignacion
									                   FROM sga_subcomisiones_bh 
													   JOIN sga_comisiones_bh ON sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria
									                  WHERE sga_subcomisiones_bh.subcomision = sc.subcomision 
											         UNION 
													 SELECT sc.subcomision -- Por si la subcomision no tiene bandas horarias definidas
													 )
										)
							) as cant_inscriptos,
			                sc.subcomision as subcomision,
			                sc.nombre as subcomision_nombre,
			                sga_clases_tipos.nombre as tipo_clase
					FROM	sga_alumnos
					JOIN	sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
					JOIN	sga_insc_subcomision ON sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion
					JOIN	sga_subcomisiones as sc ON sga_insc_subcomision.subcomision = sc.subcomision
					JOIN	sga_subcomisiones_cupo ON sga_insc_subcomision.subcomision = sga_subcomisiones_cupo.subcomision
					JOIN	sga_clases_tipos ON sc.tipo_clase = sga_clases_tipos.tipo_clase
					WHERE   sga_alumnos.persona = {$parametros['persona']}
					  AND	sga_insc_cursada.comision IN ({$parametros['comisiones_str']})
					";
		}

		$datos = kernel::db()->consultar($sql);

		return $datos;
	}
	
	/**
	 * parametros: comisiones, param_cur_controlar_cupo_por, propuesta
	 * no_quote: comisiones, param_cur_controlar_cupo_por
	 * cache: no
	 * filas: n
	 */
	function cupo_comisiones($parametros) {

		$si = kernel::db()->quote(\comunes::si);
		$no = kernel::db()->quote(\comunes::no);
		$comisiones = implode(', ', $parametros['comisiones']);
		
		// Recupero constantes relacionadas con el cupo en la comision.
		$cupo_por_comision  = kernel::db()->quote(\comision::cupo_tipo_comision); 
		$cupo_por_propuesta = kernel::db()->quote(\comision::cupo_tipo_propuesta);
		
		if ($parametros['param_cur_controlar_cupo_por'] == 'comision') {
			$cant_insc = ", CASE sga_comisiones.cupo_tipo 
			                   WHEN $cupo_por_comision  THEN sga_comisiones_cupo.cant_inscriptos  -- Cupo total de la comision 
			                   WHEN $cupo_por_propuesta THEN (SELECT cpc.cant_inscriptos
					                                            FROM sga_comisiones_propuestas_cupo as cpc
					                                           WHERE cpc.comision = sga_comisiones.comision 
																 AND cpc.propuesta = {$parametros['propuesta']}
															 )  -- Inscriptos de la propuesta del alumno
                            END as cant_inscriptos
						";

			 
		} else {
			// Busca los inscriptos a todas las comisiones que comparten las bandas horarias
			// de la comision que seleccionó el alumno para inscribirse.
			$cant_insc = ", (SELECT COALESCE(SUM(CASE c.cupo_tipo 
							                          WHEN $cupo_por_comision  THEN cc.cant_inscriptos
							                          WHEN $cupo_por_propuesta THEN COALESCE((SELECT cpc.cant_inscriptos 
																								FROM sga_comisiones_propuestas_cupo as cpc 
													                                           WHERE cpc.comision = c.comision 
																							     AND cpc.propuesta = {$parametros['propuesta']}),0)
													 END), 0)
								   FROM sga_comisiones as c
								   JOIN sga_comisiones_cupo as cc ON cc.comision = c.comision
								  WHERE c.comision IN (SELECT DISTINCT bh2.comision 
									                     FROM sga_comisiones_bh
									                     JOIN sga_comisiones_bh as bh2 ON bh2.asignacion = sga_comisiones_bh.asignacion
									                    WHERE sga_comisiones_bh.comision = sga_comisiones.comision
														UNION 
														SELECT sga_comisiones.comision -- Por si la comision no tiene bandas horarias definidas
														)
							) as cant_inscriptos
						";
							
		}

		$sql = "SELECT 
					sga_comisiones.comision,
					CASE sga_comisiones.cupo_tipo 
					  WHEN $cupo_por_comision THEN -- Cupo total de la comision
					    CASE 
					      WHEN (COALESCE(sga_comisiones_cupo.cupo,0) = 0 OR sga_comisiones_cupo.cupo > sga_comisiones_cupo.cant_inscriptos) THEN $si
					      ELSE $no
						END   
					  WHEN $cupo_por_propuesta THEN -- Cupo por propuesta del alumno.
					    COALESCE((SELECT CASE
						                  WHEN COALESCE(cpc.cupo,0) = 0 OR cpc.cupo > cpc.cant_inscriptos THEN $si 
                                          ELSE $no 
                                        END 										  
						           FROM sga_comisiones_propuestas_cupo as cpc 
						          WHERE cpc.comision = sga_comisiones.comision 
								    AND cpc.propuesta = {$parametros['propuesta']}), $si) 
					END as tiene_cupo,
					CASE sga_comisiones.cupo_tipo 
					  WHEN $cupo_por_comision  THEN sga_comisiones.cupo   -- Cupo total de la comision
					  WHEN $cupo_por_propuesta THEN (SELECT cpc.cupo 
					                                   FROM sga_comisiones_propuestas_cupo as cpc 
					                                  WHERE cpc.comision = sga_comisiones.comision 
													    AND cpc.propuesta = {$parametros['propuesta']}
													 ) -- Cupo por propuesta del alumno
					END as cupo
					$cant_insc
				FROM sga_comisiones 
				JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
				WHERE sga_comisiones.comision IN ($comisiones)
				";
		
		$datos = kernel::db()->consultar($sql);
		
		$cupos = [];
		foreach ($datos as $dato){
			$cupos[$dato['comision']] = $dato;
		}

		return $cupos;
	}
	
	/**
	 * parametros: subcomisiones, param_cur_controlar_cupo_por
	 * no_quote: subcomisiones, param_cur_controlar_cupo_por
	 * cache: no
	 * filas: n
	 */
	function cupo_subcomisiones($parametros) {

		$si = kernel::db()->quote(\comunes::si);
		$no = kernel::db()->quote(\comunes::no);
		$subcomisiones = implode(', ', $parametros['subcomisiones']);
		
		if ($parametros['param_cur_controlar_cupo_por'] == 'comision') {
			$cant_insc = ', sga_subcomisiones_cupo.cant_inscriptos as cant_inscriptos';
		} else {
			// Busca los inscriptos a todas las comisiones que comparten las bandas horarias
			// de la comision que seleccionó el alumno para inscribirse.
			$cant_insc = ', (	SELECT COALESCE(SUM(scp.cant_inscriptos), 0) 
								FROM sga_subcomisiones_cupo AS scp
								WHERE scp.subcomision IN ( 
									SELECT DISTINCT sga_subcomisiones_bh.subcomision
									FROM	sga_comisiones_bh,
											sga_subcomisiones_bh
									WHERE	sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
											AND sga_comisiones_bh.asignacion IN (
												SELECT sga_comisiones_bh.asignacion
												FROM	sga_subcomisiones_bh, 
														sga_comisiones_bh 
												WHERE	sga_subcomisiones_bh.subcomision = sga_subcomisiones_cupo.subcomision
														AND sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria)
									)	
							) as cant_inscriptos';
		}

		$sql = "SELECT 
					sga_subcomisiones_cupo.subcomision,
					CASE 
						WHEN (sga_subcomisiones_cupo.cupo IS NULL OR sga_subcomisiones_cupo.cupo = 0 OR sga_subcomisiones_cupo.cupo > sga_subcomisiones_cupo.cant_inscriptos) THEN $si
						ELSE $no
					END as tiene_cupo,
					sga_subcomisiones_cupo.cupo
				$cant_insc
				FROM sga_subcomisiones_cupo
				WHERE sga_subcomisiones_cupo.subcomision IN ($subcomisiones)";
		
		$datos = kernel::db()->consultar($sql);
		
		$cupos = array();
		foreach ($datos as $dato){
			$cupos[$dato['subcomision']] = $dato;
		}

		return $cupos;
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: n
	 */
	function inscripciones($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$interfaz  = \comunes::interfaz_autogestion; // Autogestion
		
		// Inscripciones a actividades de periodos lectivos actuales o futuros de una persona.
		$sql = "SELECT 
					sga_insc_cursada.inscripcion as inscripcion,
					sga_alumnos.alumno as alumno,
					sga_alumnos.propuesta as propuesta,
					sga_propuestas.codigo as propuesta_codigo,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					sga_propuestas.propuesta_tipo as propuesta_tipo,
					sga_alumnos.legajo as legajo,
					sga_comisiones.elemento as actividad,
					sga_elementos.codigo as actividad_codigo,
					COALESCE(ep.nombre_abreviado, sga_elementos.nombre_abreviado) as actividad_nombre_abreviado,
					COALESCE(ep.nombre, sga_elementos.nombre) as actividad_nombre,
					sga_comisiones.comision as comision,
					sga_comisiones.nombre as comision_nombre,
					sga_periodos.anio_academico as anio_academico,
					sga_comisiones.periodo_lectivo as periodo_lectivo,
					sga_periodos.nombre as periodo_lectivo_nombre,
					sga_comisiones.turno as turno,
					sga_turnos_cursadas.nombre as turno_nombre,
					sga_insc_cursada.fecha_inscripcion as fecha_inscripcion,
					to_char(sga_insc_cursada.fecha_inscripcion, $formato_fecha_visual) as fecha_inscripcion_display,
					f_instancias_insc_cursada (sga_insc_cursada.inscripcion) as instancias,
					sga_inscripciones_estados.nombre as estado_inscripcion,
					sga_insc_cursada.nro_transaccion as nro_transaccion,
					sga_comisiones.catedra as catedra,
					sga_catedras.nombre as catedra_nombre,
					sga_planes.plan as plan,
					sga_planes.codigo as plan_codigo,
					sga_insc_cursada.plan_version as plan_version,
					f_docentes_comision(sga_comisiones.comision, true) as docente,
					f_comision_con_tope_bajas_vigente(sga_insc_cursada.plan_version, sga_insc_cursada.comision, $interfaz, sga_alumnos.alumno, sga_insc_cursada.fecha_inscripcion) as baja_habilitada,
					sga_insc_cursada.estado as estado,
					sga_insc_cursada.sq_token as sq_token,
					sga_comisiones.cobrable as comision_cobrable,
					COALESCE(sga_comisiones_excep_perlect.fecha_fin_dictado, sga_periodos_lectivos.fecha_fin_dictado) as fecha_fin_dictado,
					f_modalidad_cursada_comision(sga_comisiones.comision) as modalidad,
					sga_comisiones.ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					f_horario_comision(sga_comisiones.comision) as horario_descr
				FROM
					sga_alumnos
					JOIN sga_insc_cursada					ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
					JOIN sga_propuestas						ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
					JOIN sga_planes_versiones				ON (sga_planes_versiones.plan_version = sga_insc_cursada.plan_version)
					JOIN sga_planes							ON (sga_planes.plan = sga_planes_versiones.plan)
					JOIN sga_inscripciones_estados			ON (sga_inscripciones_estados.estado = sga_insc_cursada.estado)
					JOIN sga_comisiones						ON (sga_comisiones.comision = sga_insc_cursada.comision)
					JOIN sga_elementos						ON (sga_elementos.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_revision				ON (sga_elementos_revision.elemento = sga_elementos.elemento)
					JOIN sga_elementos_plan as ep			ON (ep.plan_version = sga_insc_cursada.plan_version AND ep.elemento_revision = sga_elementos_revision.elemento_revision)
					JOIN sga_periodos_lectivos				ON (sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo)
					JOIN sga_periodos						ON (sga_periodos.periodo = sga_periodos_lectivos.periodo)
					JOIN sga_ubicaciones					ON (sga_comisiones.ubicacion = sga_ubicaciones.ubicacion)
					LEFT JOIN sga_catedras					ON (sga_catedras.catedra = sga_comisiones.catedra)
					LEFT JOIN sga_turnos_cursadas			ON (sga_turnos_cursadas.turno = sga_comisiones.turno)
					LEFT JOIN sga_comisiones_excep_perlect	ON (sga_comisiones_excep_perlect.comision = sga_comisiones.comision)
				WHERE 
					sga_alumnos.persona  = {$parametros['persona']}
				AND sga_periodos.fecha_fin >= CURRENT_DATE
					
				-- Inscripciones sin prioridad  
				-- inscripciones con prioridad ya procesadas y aceptada la preinscripcion.
				-- inscripciones por prioridad sin procesar y solo muestra la de prioridad 1 (al borrar se borran las otras inscripciones a la actividad)
				AND (sga_insc_cursada.tipo = 'I' OR 
					 (sga_insc_cursada.tipo = 'P' AND sga_insc_cursada.estado_preinscripcion = 'A') OR
					 (sga_insc_cursada.tipo = 'P' AND sga_insc_cursada.estado_preinscripcion = 'P' AND sga_insc_cursada.prioridad = 1)
					)
				";

		$datos = kernel::db()->consultar($sql);

		foreach ($datos as $key => $dato) {
			$datos[$key][catalogo::id] = catalogo::generar_id($dato['inscripcion']);
			$datos[$key]['url_actividad'] = catalogo::generar_id($dato['actividad']);
			$datos[$key]['url_comision'] = catalogo::generar_id($dato['propuesta'] . $dato['plan'] . $dato['plan_version'] . $dato['actividad'] . $dato['comision']);
		}

		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: n
	 */
	function info_comprobante_inscripcion($parametros)
	{
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
					vw_personas.apellido_nombres_elegido as alumno_nombre,
					vw_personas.tipo_nro_documento as identificacion,
					sga_alumnos.propuesta as propuesta,
					sga_propuestas.codigo as propuesta_codigo,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					sga_insc_cursada.alumno as alumno,
					sga_alumnos.legajo as legajo,
					sga_comisiones.elemento as actividad,
					sga_elementos.codigo as actividad_codigo,
					COALESCE(ep.nombre_abreviado, sga_elementos.nombre_abreviado) as actividad_nombre_abreviado,
					COALESCE(ep.nombre, sga_elementos.nombre) as actividad_nombre,
					sga_comisiones.comision as comision,
					sga_comisiones.nombre as comision_nombre,
					f_subcomisiones_insc_cursada(sga_insc_cursada.inscripcion, false, false) as subcomisiones,
					sga_periodos.anio_academico as anio_academico,
					sga_comisiones.periodo_lectivo as periodo_lectivo,
					sga_periodos.nombre as periodo_lectivo_nombre,
					sga_comisiones.turno as turno_cursada,
					sga_turnos_cursadas.nombre as turno_cursada_nombre,
					sga_insc_cursada.fecha_inscripcion as fecha_inscripcion,
					to_char(sga_insc_cursada.fecha_inscripcion, $formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion,
					to_char(COALESCE(sga_comisiones_excep_perlect.fecha_inicio_dictado, sga_periodos_lectivos.fecha_inicio_dictado), $formato_fecha_visual) as fecha_inicio_dictado,
					f_horario_comision(sga_comisiones.comision) AS horario,
					f_instancias_insc_cursada (sga_insc_cursada.inscripcion) as instancias,
					f_modalidad_cursada_comision (sga_comisiones.comision) as modalidades,
					sga_inscripciones_estados.nombre as estado_inscripcion,
					sga_insc_cursada.nro_transaccion as nro_transaccion,
					sga_comisiones.catedra as catedra,
					sga_catedras.nombre as catedra_nombre,
					sga_planes.codigo as plan_codigo,
					sga_planes.nombre as plan_nombre,
					sga_ubicaciones.nombre as ubicacion_nombre,
					f_horario_comision_aula(sga_comisiones.comision, true) AS espacio_fisico
				FROM 
					sga_insc_cursada
					JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno	
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_insc_cursada.plan_version
					JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
					JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
					JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_cursada.estado
					JOIN sga_comisiones ON sga_comisiones.comision = sga_insc_cursada.comision
					JOIN sga_elementos						ON (sga_elementos.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_revision				ON (sga_elementos_revision.elemento = sga_elementos.elemento)
					JOIN sga_elementos_plan as ep			ON (ep.plan_version = sga_insc_cursada.plan_version AND ep.elemento_revision = sga_elementos_revision.elemento_revision)
					JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
					JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
					JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
					LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
					LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
					LEFT JOIN sga_comisiones_excep_perlect ON sga_comisiones_excep_perlect.comision = sga_comisiones.comision
				WHERE 
					sga_insc_cursada.inscripcion = {$parametros['inscripcion']} 
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
	 * parametros: alumno, nro_transaccion
	 * cache: no
	 * filas: n
	 */
	function info_comisiones_por_inscripcion_prioridad($parametros)
	{
	
		$sql = "SELECT  
                            sga_insc_cursada.prioridad as prioridad,
                            sga_comisiones.nombre as comision_nombre,
                            sga_periodos.anio_academico as anio_academico,
                            sga_periodos.nombre as periodo_lectivo_nombre,
                            sga_catedras.nombre as catedra_nombre,
                            sga_turnos_cursadas.nombre as turno_cursada_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,
                            f_subcomisiones_insc_cursada(sga_insc_cursada.inscripcion, false, false) as subcomisiones,
                            f_instancias_insc_cursada(sga_insc_cursada.inscripcion) as instancias,
                            f_modalidad_cursada_comision(sga_comisiones.comision) as modalidades,
                            sga_inscripciones_estados.nombre as estado_inscripcion,
                            f_horario_comision_aula(sga_comisiones.comision, true) AS espacio_fisico
			
			FROM sga_insc_cursada
                       JOIN sga_comisiones ON (sga_comisiones.comision = sga_insc_cursada.comision)
                       JOIN sga_periodos_lectivos ON (sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo)
                       JOIN sga_periodos ON (sga_periodos.periodo = sga_periodos_lectivos.periodo)
                       JOIN sga_ubicaciones ON (sga_ubicaciones.ubicacion = sga_comisiones.ubicacion)
                       JOIN sga_inscripciones_estados ON (sga_inscripciones_estados.estado = sga_insc_cursada.estado_preinscripcion)
                       LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
                       LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			
			WHERE sga_insc_cursada.alumno = {$parametros['alumno']}
			  AND sga_insc_cursada.nro_transaccion = {$parametros['nro_transaccion']}
			
			ORDER BY sga_insc_cursada.prioridad
                        ";

		$datos = kernel::db()->consultar($sql);

		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: n
	 */
	function info_comprobante_baja($parametros)
	{
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);
		
		$sql = "SELECT 
					vw_personas.apellido_nombres_elegido as alumno_nombre,
					vw_personas.tipo_nro_documento as identificacion,
					sga_propuestas.codigo as propuesta,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					sga_alumnos.legajo as legajo,
					sga_elementos.codigo as actividad_codigo,
					COALESCE(ep.nombre_abreviado, sga_elementos.nombre_abreviado) as actividad_nombre_abreviado,
					COALESCE(ep.nombre, sga_elementos.nombre) as actividad_nombre,
					sga_comisiones.comision as comision,
					sga_comisiones.nombre as comision_nombre,
					sga_periodos.anio_academico as anio_academico,
					sga_periodos.nombre as periodo_lectivo_nombre,
					sga_turnos_cursadas.nombre as turno_cursada_nombre,
					sga_insc_cursada_log.fecha_inscripcion as fecha_inscripcion,
					to_char(sga_insc_cursada_log.fecha_inscripcion, $formato_fecha_hora_visual_sin_segundos) as fecha_inscripcion,
					to_char(sga_insc_cursada_log.fecha_operacion, $formato_fecha_hora_visual_sin_segundos) as fecha_baja_inscripcion,
					sga_insc_cursada_log.nro_transaccion_log as nro_transaccion,
					sga_catedras.nombre as catedra_nombre,
					sga_planes.codigo as plan_codigo,
					sga_planes.nombre as plan_nombre,
					sga_ubicaciones.nombre as ubicacion_nombre,
					f_modalidad_cursada_comision (sga_comisiones.comision) as modalidades
				FROM 
					sga_insc_cursada_log
					JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada_log.alumno
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_insc_cursada_log.plan_version
					JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
					JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
					JOIN sga_comisiones ON sga_comisiones.comision = sga_insc_cursada_log.comision
					JOIN sga_elementos						ON (sga_elementos.elemento = sga_comisiones.elemento)
					JOIN sga_elementos_revision				ON (sga_elementos_revision.elemento = sga_elementos.elemento)
					JOIN sga_elementos_plan as ep			ON (ep.plan_version = sga_insc_cursada_log.plan_version AND ep.elemento_revision = sga_elementos_revision.elemento_revision)
					JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
					JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
					JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
					LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
					LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
				WHERE 
					sga_insc_cursada_log.inscripcion = {$parametros['inscripcion']} 
			 ";

		$datos = kernel::db()->consultar_fila($sql);

		return $datos;
	}

	//--------------------------------------------------------------------------
	//---- Modificación de DATOS
	//--------------------------------------------------------------------------
	
	/**
	 * Genera la inscripción a una comisión y/o subcomisiones de esa comision.
	 * 
	 * parametros: comision, alumno, persona, plan_version, prioridad, estado, tipo, estado_preinscripcion, fuera_de_termino, nro_transaccion, instancias, subcomisiones, _interfaz, periodo_lectivo, param_cur_controlar_cupo, param_cur_controlar_cupo_por, param_cur_insc_siempre_pendiente
	 * param_null: nro_transaccion
	 * no_quote: instancias, subcomisiones, param_cur_controlar_cupo, param_cur_controlar_cupo_por, param_cur_insc_siempre_pendiente
	 * cache: no
	 * filas: 1
	 */
	function inscripcion_comision($parametros)
	{
		$nro_transaccion = !isset($parametros['nro_transaccion']) ? 'NULL' : $parametros['nro_transaccion'];
		// subcomisiones e instancias son un array de integer.
		// Ejemplo:  1,2      se convierte en array[1,2]
		//           23       se convierte en array[23]
		//           34,45,50 se convierte en array[34,45,50]

		$array_instancias = catalogo::array_to_postgres($parametros['instancias']);
		$array_subcomisiones = catalogo::array_to_postgres($parametros['subcomisiones']);

		$sql = "SELECT * FROM f_insc_cursada_inscribir (" .
				"{$parametros['comision']}, " .
				"{$parametros['alumno']}," .
				"{$parametros['persona']}," .
				"{$parametros['plan_version']}," .
				"{$parametros['prioridad']}," .
				"{$parametros['estado']}," .
				"{$parametros['tipo']}," .
				"{$parametros['estado_preinscripcion']}," .
				"{$parametros['fuera_de_termino']}," .
				"$nro_transaccion," .
				"$array_instancias," .
				"$array_subcomisiones," .
				"{$parametros['_interfaz']}," .
				"null," . // autorizado por
				"{$parametros['periodo_lectivo']}," .
				"'{$parametros['param_cur_controlar_cupo']}'," .
				"'{$parametros['param_cur_controlar_cupo_por']}'," .
				"'{$parametros['param_cur_insc_siempre_pendiente']}'" .
				")";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

	/**
	 * parametros: inscripcion
	 * cache: no
	 * filas: 1
	 */
	function eliminar_inscripcion_comision($parametros)
	{
		$sql = "select * from f_insc_cursada_eliminar({$parametros['inscripcion']})";
		kernel::log()->add_debug('eliminar_inscripcion', $sql);
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
		$sql_rechazo_manual = "SELECT mr.nombre as rechazo_masivo_desc
								FROM sga_insc_cursada_log
									 LEFT JOIN sga_inscripciones_motivos_rechazo as mr ON     mr.motivo_rechazo = sga_insc_cursada_log.motivo_rechazo
								WHERE sga_insc_cursada_log.inscripcion = {$parametros['inscripcion_rechazada']}";

		//Requisitos no cumplidos (puede o no haber)
		$sql_rechazo_automatico = "SELECT req_no_cumplidos.mensaje_validacion as requisito_no_cumplido  
								FROM sga_insc_cursada_log
									 LEFT JOIN sga_insc_cursada_log_requisitos as req_no_cumplidos ON req_no_cumplidos.inscripcion = sga_insc_cursada_log.inscripcion
								WHERE sga_insc_cursada_log.inscripcion = {$parametros['inscripcion_rechazada']}";

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
