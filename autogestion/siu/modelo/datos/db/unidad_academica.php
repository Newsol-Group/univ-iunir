<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\CaracteresEspeciales;

class unidad_academica
{
	/**
	 * parametros: 
	 * cache: memoria
	 * filas: 1
	 */
	function info_institucion($parametros)
	{
		$sql = "SELECT
					sga_instituciones.institucion, 
					sga_instituciones.nombre, 
					sga_instituciones.codigo, 
					'logo' as logo
				FROM sga_instituciones,
					 par_configuraciones
				WHERE par_configuraciones.nombre = 'institucion_instalacion'
				AND sga_instituciones.institucion = cast(par_configuraciones.valor as integer)
				";

		$institucion = kernel::db()->consultar_fila($sql);
		return $institucion;
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos()
	{
		// Periodos lectivos que no han finalizado
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT
						pl.anio_academico,
						pl.periodo_lectivo as periodo_lectivo,
						pl.nombre as periodo_lectivo_nombre,
						pl.periodos_lectivo_tipo,
						pl.periodo_generico_nombre as periodo_generico_nombre,
						pl.fecha_inicio as fecha_inicio,
						pl.fecha_fin as fecha_fin,
						to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
						to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
						pl.orden
				FROM	vw_periodos_lectivos as pl
				WHERE	pl.fecha_fin >= CURRENT_DATE
				ORDER BY pl.fecha_inicio, pl.orden";

		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_vigentes()
	{
		// Lista periodos lectivos vigentes, es decir que comenzaron y aun no finalizaron.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT
					pl.anio_academico,
					pl.periodo_lectivo as periodo_lectivo,
					pl.nombre as periodo_lectivo_nombre,
					pl.periodos_lectivo_tipo,
					pl.periodo_generico_nombre as periodo_generico_nombre,
					pl.fecha_inicio as fecha_inicio,
					pl.fecha_fin as fecha_fin,
					to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
					to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
					pl.orden
				FROM
					vw_periodos_lectivos as pl
				WHERE
					CURRENT_DATE BETWEEN pl.fecha_inicio AND pl.fecha_fin
				ORDER BY pl.fecha_inicio, pl.orden
				";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $clave => $periodo) {
			$datos[$clave][catalogo::id] = catalogo::generar_id($periodo['periodo_lectivo']);
		}
		return $datos;
	}

	/**
	 * parametros: responsable_academica
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_vigentes_por_ra($parametros)
	{
		// Lista periodos lectivos que no finalizaron relacionadas con las responsables academicas segun periodo de inscripcion
		$sql = "SELECT DISTINCT
					pl.anio_academico,
					pl.periodo_lectivo as periodo_lectivo,
					pl.nombre as periodo_lectivo_nombre,
					pl.periodos_lectivo_tipo,
					pl.periodo_generico_nombre as periodo_generico_nombre,
					pl.fecha_inicio as fecha_inicio,
					pl.fecha_fin as fecha_fin,
					pl.orden
				FROM
					vw_periodos_lectivos as pl
					JOIN vw_periodos_inscripcion as pi ON pi.periodo = pl.periodo
					JOIN sga_periodos_inscripcion_aplanado as pia ON pia.periodo_insc = pi.periodo_insc
					JOIN vw_planes as p ON p.plan_version = pia.plan_version
					JOIN sga_propuestas_ra as pra ON pra.propuesta = p.propuesta
				WHERE CURRENT_DATE <= pl.fecha_fin 
				  AND pra.responsable_academica = {$parametros['responsable_academica']}
				ORDER BY pl.fecha_inicio, pl.orden
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: anio_academico
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_un_anio_academico($parametros)
	{
		// Periodos lectivos de un año academico	
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT
					pl.anio_academico,
					pl.periodo_lectivo as periodo_lectivo,
					pl.nombre as periodo_lectivo_nombre,
					pl.periodos_lectivo_tipo,
					pl.periodo_generico_nombre as periodo_generico_nombre,
					pl.fecha_inicio as fecha_inicio,
					pl.fecha_fin as fecha_fin,
					to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
					to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
					pl.orden
				FROM
					vw_periodos_lectivos as pl
				WHERE
					pl.anio_academico = {$parametros['anio_academico']}
				ORDER BY pl.fecha_inicio, pl.orden";
					
		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $clave => $periodo) {
			$datos[$clave][catalogo::id] = catalogo::generar_id($periodo['periodo_lectivo']);
		}
		return $datos;
	}
	
	/**
	 * parametros: anio_academico, docente
	 * param_null: docente
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_un_anio_academico_docente($parametros)
	{
		// La misma funcion que la de arriba solo que trae unicamente los periodos en que el docente haya tenido actividad	
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		if (!(is_null($parametros['docente']))){
			$docente = $parametros['docente'];
			$where = " AND sga_docentes_comision.docente = $docente";
		}else{
			$where='';
		}
		
		$sql = "SELECT
					DISTINCT
					pl.anio_academico,
					pl.periodo_lectivo as periodo_lectivo,
					pl.nombre as periodo_lectivo_nombre,
					pl.periodos_lectivo_tipo,
					pl.periodo_generico_nombre as periodo_generico_nombre,
					pl.fecha_inicio as fecha_inicio,
					pl.fecha_fin as fecha_fin,
					to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
					to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
					pl.orden
				FROM vw_periodos_lectivos as pl
				JOIN vw_comisiones ON pl.periodo_lectivo= vw_comisiones.periodo_lectivo
				JOIN sga_docentes_comision ON sga_docentes_comision.comision = vw_comisiones.comision
				JOIN sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
					
				WHERE pl.anio_academico = {$parametros['anio_academico']}
				AND COALESCE(pl.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE	
					$where
					
				ORDER BY pl.fecha_inicio, pl.orden"
				;

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $clave => $periodo) {
			$datos[$clave][catalogo::id] = catalogo::generar_id($periodo['periodo_lectivo']);
		}
		return $datos;
	}
	
	
	
	/**
	 * Periodos de inscricion de periodos lectivos que no finalizaron definidos para una vesion de plan de estudios. 
	 * parametros: plan_version
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_con_fechas_insc($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_hora = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);
		
		$plan_version = $parametros['plan_version'];
		$propuesta_seleccionada = kernel::persona()->get_id_propuesta_activa();

		// Lista periodos lectivos vigentes con sus periodos de inscripción segun version de plan de estudios del alumno (puede haber mas de uno)
		// Si existen periodos de inscripcion por rango de coeficiente, devuelve todos los rangos y sus fechas.
		$sql = "SELECT
					pl.anio_academico,
					pl.periodo_lectivo as periodo_lectivo,
					pl.nombre as periodo_lectivo_nombre,
					pl.periodos_lectivo_tipo,
					pl.periodo_generico_nombre as periodo_generico_nombre,
					pl.fecha_inicio as fecha_inicio,
					pl.fecha_fin as fecha_fin,
					to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
					to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
					pl.orden,
					pl.periodo,
					CASE 
					  WHEN pi.tipo_alumno = 'T' THEN 'Todos los alumnos' 
					  WHEN pi.tipo_alumno = 'I' THEN 'Ingresantes' 
					  WHEN pi.tipo_alumno = 'R' THEN 'Todos los alumnos menos los ingresantes' 
					END habilitado_segun_ingreso,					  
					CASE 
					  WHEN pi.tipo_alumno = 'T' THEN 'Todos los alumnos' 
					  WHEN pi.tipo_alumno = '1' THEN 'Alumnos que cursan por primera vez' 
					  WHEN pi.tipo_alumno = 'R' THEN 'Solo recursantes' 
					END habilitado_segun_cursado,					  
					
					-- Lista las fechas del periodo de inscripcion generico del periodo lectivo o de cada rango de coeficiente
					-- Fechas sin formato
					CASE WHEN pic.id_rango IS NOT NULL THEN pic.fecha_inicio ELSE pi.fecha_inicio END as fecha_inicio_insc_sf,
					CASE WHEN pic.id_rango IS NOT NULL THEN pic.fecha_fin ELSE pi.fecha_fin END as fecha_fin_insc_sf,
					CASE WHEN pic.id_rango IS NOT NULL THEN pic.fecha_tope_bajas ELSE pi.fecha_tope_bajas END  as fecha_tope_bajas_sf,

					-- Fechas con formato
					CASE WHEN pic.id_rango IS NULL THEN pi.sfecha_inicio ELSE to_char(pic.fecha_inicio, $formato_fecha_hora) END as fecha_inicio_insc,
					CASE WHEN pic.id_rango IS NULL THEN pi.sfecha_fin ELSE to_char(pic.fecha_fin,$formato_fecha_hora) END as fecha_fin_insc,
					CASE WHEN pic.id_rango IS NULL THEN pi.sfecha_tope_bajas ELSE to_char(pic.fecha_tope_bajas, $formato_fecha_hora) END as fecha_tope_bajas,
					-- Datos de periodos de inscripcion por coeficiente.
					pic.id_rango,
					CASE WHEN pic.id_rango IS NOT NULL THEN 1 ELSE 0 END as periodo_insc_x_coeficiente,
					pic.coeficiente_desde,
					pic.coeficiente_hasta
					
				FROM	vw_periodos_lectivos as pl
				JOIN	vw_periodos_inscripcion as pi ON pi.periodo = pl.periodo
				JOIN	sga_periodos_inscripcion_aplanado ON sga_periodos_inscripcion_aplanado.periodo_insc = pi.periodo_insc 
				                                         AND sga_periodos_inscripcion_aplanado.plan_version = $plan_version
				LEFT JOIN sga_periodos_inscripcion_coeficiente as pic ON pic.periodo_insc = pi.periodo_insc  
				                                                     AND pic.propuesta = $propuesta_seleccionada
				                                       
				WHERE	pl.fecha_fin >= CURRENT_DATE
				  AND	pi.habilitado = '". \comunes::si . "'
				  AND	pi.habilitado_interfaz IN (". \comunes::interfaz_todas . ", ". \comunes::interfaz_autogestion . ") -- Gestion y Autogestion / Autogestion
					
				ORDER BY pl.fecha_inicio, pl.orden, pi.fecha_inicio, pic.fecha_inicio";

		$datos = kernel::db()->consultar($sql);
		$salida = array();
		/**
		 * Formato: [id_periodo][...datos + id_catalogo...][...periodos_insc (los que haya)...]
		 */
		foreach ($datos as $periodo) {
			$clave = $periodo['periodo'];
			if (empty($salida[$clave])) {
				$salida[$clave] = $periodo;
				$salida[$clave][catalogo::id] = catalogo::generar_id($periodo['periodo_lectivo']);
				$salida[$clave]['periodos_insc'] = array();
			}
			$salida[$clave]['periodos_insc'][] = array(
				'fecha_inicio_insc_sf' => $periodo['fecha_inicio_insc_sf'],
				'fecha_fin_insc_sf' 	=> $periodo['fecha_fin_insc_sf'],
				'fecha_tope_bajas_sf' 	=> $periodo['fecha_tope_bajas_sf'],
				'fecha_inicio_insc' 	=> $periodo['fecha_inicio_insc'],
				'fecha_fin_insc' 	=> $periodo['fecha_fin_insc'],
				'fecha_tope_bajas'	=> $periodo['fecha_tope_bajas'],
				'id_rango' 		=> $periodo['id_rango'],
				'coeficiente_desde' 	=> $periodo['coeficiente_desde'],
				'coeficiente_hasta'	=> $periodo['coeficiente_hasta'],
			);
		}
		return $salida;
	}


	/**
	 * Recupera periodos lectivos vigentes con las fechas de inscripción por coeficiente definidos para la version del plan del alumno..
	 * parametros: plan_version
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_con_fechas_insc_x_coeficiente($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_hora = 'DD/MM/YYYY HH24:MI';
		
		$plan_version = $parametros['plan_version'];

		// Lista periodos lectivos vigentes con sus periodos de inscripción segun version de plan de estudios del alumno (puede haber mas de uno)
		// Son periodos lectivos con periodos de inscripción por coeficiente.
		$sql = "SELECT
				pl.anio_academico,
				pl.periodo_lectivo as periodo_lectivo,
				pl.nombre as periodo_lectivo_nombre,
				pl.periodos_lectivo_tipo,
				pl.periodo_generico_nombre as periodo_generico_nombre,
				pl.fecha_inicio as fecha_inicio,
				pl.fecha_fin as fecha_fin,
				to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
				to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
				pl.orden,
				pl.periodo,
				-- Fechas de inscripcion de rangos por coeficiente
				pic.fecha_inicio as fecha_inicio_insc_sf,
				pic.fecha_fin as fecha_fin_insc_sf,
				pic.fecha_tope_bajas as fecha_tope_bajas_sf,
				to_char(pic.fecha_inicio, $formato_fecha_hora) as fecha_inicio_insc,
				to_char(pic.fecha_fin,$formato_fecha_hora) as fecha_fin_insc,
				to_char(pic.fecha_tope_bajas, $formato_fecha_hora) as fecha_tope_bajas,
				pic.coeficiente_desde,
				pic.coeficiente_hasta
		
			FROM vw_periodos_lectivos as pl
			JOIN vw_periodos_inscripcion as pi ON pi.periodo = pl.periodo
			JOIN sga_periodos_inscripcion_aplanado as papl ON papl.periodo_insc = pi.periodo_insc AND papl.plan_version = $plan_version
			JOIN sga_periodos_inscripcion_coeficiente as pic ON pic.periodo_insc = pi.periodo_insc
			WHERE pl.fecha_fin >= CURRENT_DATE
			ORDER BY pl.fecha_inicio, pl.orden, pic.fecha_inicio";


		$datos = kernel::db()->consultar($sql);
		return $datos;
	}



	/**
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_vigentes_ids()
	{
		$sql = "SELECT
					pl.periodo_lectivo as periodo_lectivo
				FROM
					vw_periodos_lectivos as pl
				WHERE
					CURRENT_DATE BETWEEN pl.fecha_inicio AND pl.fecha_fin";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: docente
	 * param_null: docente
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_fecha_inactivacion_mayor_igual_fecha_actual($parametros)
	{
		// Periodos lectivos con fecha_inactivacion mayor o igual a la fecha actual
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		if (isset($parametros['docente'])){
			$where = " AND sga_docentes.docente = {$parametros['docente']} ";
		}else{
			$where = '';
		}
		
		$sql = "SELECT
						pl.anio_academico,
						pl.periodo_lectivo as periodo_lectivo,
						pl.nombre as periodo_lectivo_nombre,
						pl.periodos_lectivo_tipo,
						pl.periodo_generico_nombre as periodo_generico_nombre,
						pl.fecha_inicio as fecha_inicio,
						pl.fecha_fin as fecha_fin,
						to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
						to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
						pl.orden,
						(CURRENT_DATE BETWEEN pl.fecha_inicio AND pl.fecha_fin) as vigente
				FROM	vw_periodos_lectivos as pl
					LEFT JOIN vw_comisiones ON  vw_comisiones.periodo_lectivo = pl.periodo_lectivo
					LEFT JOIN sga_docentes_comision ON sga_docentes_comision.comision = vw_comisiones.comision
					LEFT JOIN sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
						
				WHERE	pl.fecha_inactivacion >= CURRENT_DATE
						$where
				
				ORDER BY pl.anio_academico desc, pl.fecha_inicio desc, pl.fecha_fin desc, pl.orden desc";

		$datos = kernel::db()->consultar($sql);
		$res = array();
		$primer_vigente = false;
		foreach ($datos as $clave => $periodo) {
			$res[$clave] = $periodo;
			$res[$clave]['url_perlec'] = catalogo::generar_id($periodo['anio_academico'] . $periodo['periodo_lectivo']);

			//marco solo el primer período vigente
			if ($periodo['vigente'] == true && !$primer_vigente) {
				$primer_vigente = true;
			} else {
				$res[$clave]['vigente'] = false;
			}
		}
		return $res;
	}
	
	/**
	 * parametros: docente
	 * param_null: docente
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_todos($parametros)
	{

		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		// Si viene el docente, solo periodos lectivos en el que el docente haya participado
		$where_docente = '';
		if (isset($parametros['docente'])){
			$docente = " AND sga_docentes.docente = {$parametros['docente']} ";
			$where_docente = " AND pl.periodo_lectivo IN (SELECT c.periodo_lectivo 
			                                              FROM sga_docentes_comision as dc 
			                                              JOIN sga_comisiones as c ON c.comision = dc.comision
			                                              WHERE dc.docente = {$parametros['docente']}
			                                              )";
		}
		
		$sql = "SELECT
					pl.anio_academico,
					pl.periodo_lectivo as periodo_lectivo,
					pl.nombre as periodo_lectivo_nombre,
					pl.periodos_lectivo_tipo,
					pl.periodo_generico_nombre as periodo_generico_nombre,
					pl.fecha_inicio as fecha_inicio,
					pl.fecha_fin as fecha_fin,
					to_char(pl.fecha_inicio_dictado, $formato_fecha_visual) as f_inicio_dictado,
					to_char(pl.fecha_fin_dictado, $formato_fecha_visual) as f_fin_dictado,
					pl.orden,
					(CURRENT_DATE BETWEEN pl.fecha_inicio AND pl.fecha_fin) as vigente
						
				FROM	vw_periodos_lectivos as pl
												  
				WHERE 1 = 1
				$where_docente
						
				ORDER BY pl.anio_academico desc, 
				         pl.fecha_inicio desc, 
					 pl.fecha_fin desc, 
					 pl.orden desc
				";
		
		$datos = kernel::db()->consultar($sql);
		
		$res = array();
		$primer_vigente = false;
		foreach ($datos as $clave => $periodo) {
			$res[$clave] = $periodo;
			$res[$clave]['url_perlec'] = catalogo::generar_id($periodo['anio_academico'] . $periodo['periodo_lectivo']);

			//marco solo el primer período vigente
			if ($periodo['vigente'] == true && !$primer_vigente) {
				$primer_vigente = true;
			} else {
				$res[$clave]['vigente'] = false;
			}
		}
		return $res;
	}
	
	/**
	 * parametros: periodo_lectivo
	 * cache: no
	 * filas: 1
	 */
	function periodos_lectivos_anteriores($parametros)
	{
		$sql = "SELECT pla.periodo_lectivo, pa.nombre, pa.fecha_inicio, pa.fecha_fin 
				FROM sga_periodos_lectivos as pl, 
					sga_periodos as p, 
					sga_periodos_lectivos as pla, 
					sga_periodos as pa 
				WHERE pl.periodo_lectivo = {$parametros['periodo_lectivo']} 
				AND p.periodo = pl.periodo 
				AND pla.periodo_lectivo <> pl.periodo_lectivo 
				AND pa.periodo = pla.periodo 
				AND pa.fecha_inicio < p.fecha_inicio 
				ORDER BY pa.fecha_inicio DESC";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: docente
	 * param_null: docente
	 * cache: memoria
	 * filas: n
	 */
	function periodos_lectivos_fecha_inactivacion_no_finalizada($parametros)
	{
	
		// Si viene el docente, solo periodos lectivos en el que el docente se encuentre en alguna comision
		$where_docente = '';
		if (isset($parametros['docente'])){
			$where_docente = " AND vw_periodos_lectivos.periodo_lectivo IN (SELECT c.periodo_lectivo 
			                                              FROM sga_docentes_comision as dc 
			                                              JOIN sga_comisiones as c ON c.comision = dc.comision
			                                              WHERE dc.docente = {$parametros['docente']}
			                                              )";
		}

	
		// Periodos lectivos con fecha de inactivación NO finalizada
		// Esta query va en correlación con esta otra: docente/lista_comisiones_activas
		$sql = "SELECT	vw_periodos_lectivos.anio_academico,
				vw_periodos_lectivos.periodo_lectivo,
				vw_periodos_lectivos.nombre AS periodo_lectivo_nombre
			FROM 	vw_periodos_lectivos
			WHERE 	vw_periodos_lectivos.fecha_inactivacion >= CURRENT_DATE
			$where_docente
			ORDER BY	vw_periodos_lectivos.anio_academico DESC, 
				vw_periodos_lectivos.fecha_inicio DESC,
				vw_periodos_lectivos.fecha_fin DESC, 
				vw_periodos_lectivos.orden DESC";

		$datos = kernel::db()->consultar($sql);

		foreach ($datos as $clave => $periodo) {
			$datos[$clave]['url_perlec'] = catalogo::generar_id($periodo['anio_academico'] . $periodo['periodo_lectivo']);
		}

		return $datos;
	}

	/**
	 * cache: memoria
	 * filas: 1
	 */
	function turnos_examen()
	{
		// Turnos de Examen que no han finalizado
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT
					sga_periodos.anio_academico,
					sga_turnos_examen.turno_examen,
					sga_periodos.nombre as turno_examen_nombre,
					to_char(sga_periodos.fecha_inicio, $formato_fecha_visual) as f_inicio_turno,
					to_char(sga_periodos.fecha_fin, $formato_fecha_visual) as f_fin_turno
				FROM
					sga_turnos_examen,
					sga_periodos
				WHERE sga_periodos.periodo = sga_turnos_examen.periodo
				  AND sga_periodos.fecha_fin >= CURRENT_DATE
				ORDER BY sga_periodos.anio_academico, sga_periodos.fecha_inicio;
				";

		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	  Recupera los turnos de examen con períodos de inscripcion habilitados para la version del plan del alumno
	  que esta logueado.
	 * parametros: plan_version
	 * cache: memoria
	 * filas: 1
	 */
	function turnos_examen_por_plan($parametros)
	{
		// Recupera Turnos de examen donde existen periodos de inscripcion en los llamados
		// que son para la version del plan del alumno
		// Turnos de examen con fecha de publicacion menor o igual a hoy y turnos que no finalizaron.	
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT DISTINCT
					vw_turnos_examen.anio_academico as anio_academico,
					vw_turnos_examen.turno_examen as turno_examen,
					vw_turnos_examen.turno_examen_nombre as turno_examen_nombre,
					to_char(vw_turnos_examen.turno_examen_fecha_inicio, $formato_fecha_visual) as f_inicio_turno,
					to_char(vw_turnos_examen.turno_examen_fecha_fin, $formato_fecha_visual) as f_fin_turno,
					vw_turnos_examen.turno_examen_fecha_inicio
				FROM
					vw_turnos_examen
				WHERE vw_turnos_examen.turno_examen_fecha_fin >= CURRENT_DATE
				  AND vw_turnos_examen.turno_examen_fecha_publicacion_mesas <= CURRENT_DATE
				  AND vw_turnos_examen.llamado_periodo IN (
						-- Existen periodos de inscripcion a llamados del turno relacionados con la version del plan del alumno
						SELECT periodo_llamado.periodo
						  FROM sga_periodos as periodo_llamado,
						       vw_periodos_inscripcion,
						       sga_periodos_inscripcion_aplanado
						 WHERE periodo_llamado.fecha_fin >= CURRENT_DATE
						   AND vw_periodos_inscripcion.periodo = periodo_llamado.periodo
						   AND vw_periodos_inscripcion.periodo_generico_tipo  = 3  -- Periodos genericos de llamados de un turno
						   AND sga_periodos_inscripcion_aplanado.periodo_insc = vw_periodos_inscripcion.periodo_insc
						   AND sga_periodos_inscripcion_aplanado.plan_version = {$parametros['plan_version']}
						)
				ORDER BY vw_turnos_examen.anio_academico, 
						vw_turnos_examen.turno_examen_fecha_inicio;
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function turnos_examen_vigentes()
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT
					sga_periodos.anio_academico,
					sga_turnos_examen.turno_examen,
					sga_periodos.nombre as turno_examen_nombre,
					to_char(sga_periodos.fecha_inicio, $formato_fecha_visual) as f_inicio_turno,
					to_char(sga_periodos.fecha_fin, $formato_fecha_visual) as f_fin_turno
				FROM
					sga_turnos_examen,
					sga_periodos
				WHERE
					sga_periodos.periodo = sga_turnos_examen.periodo
					AND CURRENT_DATE BETWEEN sga_periodos.fecha_inicio AND sga_periodos.fecha_fin
				ORDER BY sga_periodos.fecha_inicio;
			";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: docente
	 * param_null: docente
	 * cache: memoria
	 * filas: n
	 */
	function turnos_examen_todos($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		// Si viene el docente, solo turnos de examen donde el docente este en alguna mesa
		$where_docente = '';
		if (isset($parametros['docente'])){
			$where_docente = " AND sga_turnos_examen.turno_examen IN (SELECT ll.turno_examen
			                                              FROM sga_docentes_mesa_llamado as dmll 
			                                              JOIN sga_llamados_mesa as llm ON llm.llamado_mesa = dmll.llamado_mesa
			                                              JOIN sga_llamados_turno as ll ON ll.llamado = llm.llamado
			                                              WHERE dmll.docente = {$parametros['docente']}
			                                              )";
		}
		
		$sql = "SELECT		
					sga_periodos.anio_academico,
					sga_turnos_examen.turno_examen,
					sga_periodos.nombre as turno_examen_nombre,
					to_char(sga_periodos.fecha_inicio, $formato_fecha_visual) as f_inicio_turno,
					to_char(sga_periodos.fecha_fin, $formato_fecha_visual) as f_fin_turno
				FROM sga_turnos_examen
				JOIN sga_periodos ON sga_periodos.periodo = sga_turnos_examen.periodo
				WHERE 1 = 1
				$where_docente
					
				ORDER BY sga_periodos.anio_academico DESC, 
					sga_periodos.fecha_inicio DESC
				";
		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $clave => $turno) {
			$datos[$clave]['url_turno'] = catalogo::generar_id("{$turno['anio_academico']}{$turno['turno_examen']}");
		}
		return $datos;
	}

	/**
	 * parametros: docente
	 * param_null: docente
	 * cache: memoria
	 * filas: n
	 */
	function turnos_examen_de_anios_academicos_con_turno_no_finalizado($parametros)
	{
	
		// Si viene el docente, solo turnos de examen donde el docente este en alguna mesa
		$where_docente = '';
		if (isset($parametros['docente'])){
			$where_docente = " AND sga_turnos_examen.turno_examen IN (SELECT ll.turno_examen
			                                              FROM sga_docentes_mesa_llamado as dmll 
			                                              JOIN sga_llamados_mesa as llm ON llm.llamado_mesa = dmll.llamado_mesa
			                                              JOIN sga_llamados_turno as ll ON ll.llamado = llm.llamado
			                                              WHERE dmll.docente = {$parametros['docente']}
			                                              )";
		}
	
		// Turnos de examen que pertenezcan a un año academico que tenga al menos un turno sin finalizar.
		// Esta query va en correlación con esta otra: docente/lista_mesas_examen_docente_dentro_o_fuera_calendario
		$sql = "SELECT sga_periodos.anio_academico as anio_academico,
				sga_turnos_examen.turno_examen as turno_examen,
				sga_periodos.nombre as turno_examen_nombre
			FROM sga_turnos_examen
			JOIN sga_periodos ON sga_periodos.periodo = sga_turnos_examen.periodo
			WHERE	sga_periodos.anio_academico IN (SELECT vw_turnos_examen.anio_academico
								FROM vw_turnos_examen
								WHERE vw_turnos_examen.turno_examen_fecha_fin >= CURRENT_DATE)
			$where_docente										
			ORDER BY sga_periodos.anio_academico DESC, 
				sga_periodos.fecha_fin DESC
			";

		$datos = kernel::db()->consultar($sql);

		foreach ($datos as $clave => $turno) {
			$datos[$clave]['url_turno'] = catalogo::generar_id($turno['anio_academico'] . $turno['turno_examen']);
		}

		return $datos;
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function anios_academicos()
	{
		$sql = "SELECT anio_academico 
				FROM sga_anios_academicos 
				ORDER BY anio_academico DESC";

		$datos = kernel::db()->consultar($sql);

		foreach ($datos as $key => $dato) {
			$datos[$key][catalogo::id] = catalogo::generar_id($dato['anio_academico']);
		}

		return $datos;
	}

	/**
	 * parametros: actividad
	 * cache: memoria
	 * filas: 1
	 */
	function actividad($parametros)
	{
		$sql = "SELECT
					act.codigo,
					act.nombre,
					act.elemento
				FROM
					vw_actividades as act
				WHERE
					act.elemento = {$parametros['actividad']}";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * Lista de materias por plan version y nombre
	 * parametros: term, plan_version
	 * param_null: plan_version
	 * no_quote: term
	 * cache: memoria
	 * filas: n
	 */
	function actividades($parametros)
	{
		$term = kernel::db()->quote_like($parametros['term'], '%', '%');

		if (isset($parametros['plan_version'])) {
			$from_actividad = 'vw_actividades_plan as act';
			$where_actividad = "act.plan_version = {$parametros['plan_version']} AND (
				f_limpiar_acentos(act.codigo) ILIKE $term OR
				f_limpiar_acentos(act.nombre) ILIKE $term)";
		} else {
			$from_actividad = 'vw_actividades as act';
			$where_actividad = "f_limpiar_acentos(act.codigo) ILIKE $term OR
				 f_limpiar_acentos(act.nombre) ILIKE $term";
		}

		$sql = "SELECT
					act.codigo,
					act.nombre,
					act.elemento
				FROM
					$from_actividad
				WHERE
					$where_actividad";
		return kernel::db()->consultar($sql);
	}

	/**
	 *
	 * parametros: actividad
	 * 
	 * cache: memoria
	 * filas: n
	 */
	function datos_actividad($parametros)
	{

		$sql = "SELECT nombre, codigo
			   FROM sga_elementos
			   WHERE elemento = {$parametros['actividad']}";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 *
	 * parametros: actividad
	 * 
	 * cache: memoria
	 * filas: n
	 */
	function get_escala_nota_defecto_actividad($parametros)
	{

		$sql = "SELECT sga_elementos_atrib.escala_nota_examen
			   FROM sga_elementos_atrib
			   WHERE sga_elementos_atrib.elemento = {$parametros['actividad']}";

		$resultado = kernel::db()->consultar_fila($sql);

		return isset($resultado['escala_nota_examen']) ? $resultado['escala_nota_examen'] : '';
	}

	/**
	 * Retorna las responsables academicas de una propuesta.
	 * parametros: propuesta
	 * cache: memoria
	 * filas: n
	 */
	function get_responsables_propuesta($parametros)
	{
		$sql = "SELECT 
						spr.propuesta as propuesta,
						sra.responsable_academica as responsable_academica,
						sra.nombre as nombre,
						sra.codigo as codigo,
						sra.institucion as institucion,
						sra.responsable_academica_tipo as responsable_academica_tipo
				FROM sga_propuestas_ra as spr
				     JOIN sga_responsables_academicas as sra ON spr.responsable_academica = sra.responsable_academica
				WHERE spr.propuesta = {$parametros['propuesta']}";
		return kernel::db()->consultar($sql);
	}

	/**
	 * Retorna las instancias.
	 * parametros: aplica_a
	 * cache: memoria
	 * filas: n
	 */
	function get_instancias($parametros)
	{
		$sql = "SELECT	instancia, nombre
				FROM	sga_instancias
				WHERE	aplica_a = '{$parametros['aplica_a']}'
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * Retorna las instancias de mesa de examen.
	 * parametros: 
	 * cache: memoria
	 * filas: n
	 */
	function get_instancias_mesas_examen()
	{
		return $this->get_instancias(array('aplica_a' => 'M'));
	}

	/**
	 * Retorna las instancias de comisiones.
	 * parametros: 
	 * cache: memoria
	 * filas: n
	 */
	function get_instancias_comisiones()
	{
		return $this->get_instancias(array('aplica_a' => 'C'));
	}

	/**
	 * Lista de docentes de la unidad académica
	 * parametros: _ua, term
	 * cache: memoria
	 * filas: n
	 */
	function docentes($parametros)
	{
		$flitro_termino = "";

		if ($parametros['term'] != "''") {

			$termino = strtolower(substr($parametros['term'], 1, -1));
			$term = kernel::db()->quote_like($termino, '%', '%');
			$term_sin_car_esp = kernel::db()->quote_like(CaracteresEspeciales::limpiar_acentos($termino), '%', '%');

			$flitro_termino = " AND (
                        LOWER(sd.legajo) LIKE $term OR
                        LOWER(sp.nombres || sp.apellido) LIKE $term OR
                        LOWER(sd.legajo) LIKE $term_sin_car_esp OR
                        LOWER(sp.nombres || sp.apellido) LIKE $term_sin_car_esp
                        )";
		}


		$sql = "SELECT sd.unidad_academica UA, sd.legajo LEGAJO,sp.nombres || ' ' || sp.apellido NOMBRE
				  FROM sga_docentes sd, mdp_personas sp	
				  WHERE sd.unidad_academica = {$parametros['_ua']}
				  AND sp.nro_inscripcion = sd.nro_inscripcion
				  AND sd.unidad_academica = sp.unidad_academica" . $flitro_termino;

		$datos = kernel::db()->consultar($sql);

		$res = array();
		foreach ($datos as $dato) {
			$paquete = array();
			$paquete['id'] = catalogo::generar_id($dato['legajo']);
			$paquete['legajo'] = $dato['legajo'];
			$paquete['label'] = "({$dato['legajo']}) {$dato['nombre']}";
			$res[] = $paquete;
		}

		return $res;
	}

}
