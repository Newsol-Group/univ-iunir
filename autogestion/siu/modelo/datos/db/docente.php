<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use \siu\modelo\_comun\comunes;
use siu\modelo\transacciones\zona_comisiones;

class docente
{
	/**
	 * parametros: persona
	 * cache: memoria
	 * cache_expiracion: 600
	 * filas: n
	 */
	function agenda_cursadas($parametros)
	{
		$aula_a_determinar = kernel::db()->quote(kernel::traductor()->trans('aula_a_determinar'));
		$edificio_a_determinar = kernel::db()->quote(kernel::traductor()->trans('edificio_a_determinar'));
		
		// FALTA IMPLEMENTAR ESTE METODO...
		$sql = "SELECT 
						vw_comisiones.anio_academico as anio_academico,
						vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
						vw_comisiones.comision as comision,
						vw_comisiones.comision_nombre as comision_nombre,
						sga_elementos.nombre as actividad_nombre,
						sga_elementos.codigo as actividad_codigo,
						sga_ubicaciones.nombre as ubicacion_nombre,
						sga_catedras.nombre as catedra_nombre,
						to_char(vw_comisiones.fecha_inicio_dictado, 'DD/MM/YYYY') as fecha_inicio_dictado,
						to_char(vw_comisiones.fecha_fin_dictado, 'DD/MM/YYYY') as fecha_fin_dictado,
						sga_asignaciones.dia_semana as dia_semana, 
						to_char(sga_asignaciones.hora_inicio, 'H24:M') as hora_inicio_clase,
						to_char(sga_asignaciones.hora_finalizacion, 'H24:M') as hora_finalizacion_clase,
						sga_clases_tipos.descripcion as tipo_clase_desc,
						COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 1), $aula_a_determinar) as aula,
						COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 7), $edificio_a_determinar) as edificio,
						(SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = vw_comisiones.comision) as cant_inscriptos
						
					FROM sga_docentes_comision
						JOIN vw_comisiones ON vw_comisiones.comision = sga_docentes_comision.comision
						JOIN sga_elementos ON sga_elementos.elemento = vw_comisiones.elemento
						JOIN sga_comisiones_bh ON sga_comisiones_bh.comision = vw_comisiones.comision
						JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
						JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
						JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
				
					WHERE sga_docentes_comision.docente = {$parametros['docente']}
					  AND vw_comisiones.fecha_inactivacion >= CURRENT_DATE
					  AND COALESCE(vw_comisiones.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
					  
				  ORDER BY vw_comisiones.anio_academico, vw_comisiones.elemento_nombre, vw_comisiones.fecha_inicio    
					";  
		
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		kernel::log()->add_debug('datos agenda', $datos);
		
		return $datos;
		/*
		$nuevo = array();
		foreach ($datos as $id => $dato) {
			$nuevo[$id]['MATERIA'] = $dato[5];
			$nuevo[$id]['MATERIA_NOMBRE'] = $dato[6];
			$nuevo[$id]['MATERIA_NOMBRE_COMPLETO'] = $dato[6] . " (" . $dato[5] . ")";
			$nuevo[$id]['DIA_SEMANA_DESC'] = $dato[10];
			$nuevo[$id]['HORA_COMIENZO_CLASE'] = $dato[11];
			$nuevo[$id]['HORA_FIN_CLASE'] = $dato[16];
			$nuevo[$id]['COMISION_NOMBRE'] = $dato[4];
			$nuevo[$id]['COMISION_CATEDRA'] = $dato[7];
			$nuevo[$id]['TURNO'] = $this->mensaje_turno($dato[8]);
			$nuevo[$id]['AULA_NOMBRE'] = $dato[13];
			$nuevo[$id]['VCEDIFICIODESC'] = $dato[15];
			$nuevo[$id]['TIPO_CLASE_DESC'] = $dato[18];
			$nuevo[$id]['SEDE_DISPLAY'] = $dato[21];
		}
		return $nuevo;
		*/
	}

	/**
	 * parametros: docente
	 * cache: memoria
	 * cache_expiracion: 600
	 * filas: n
	 */
	function agenda_examenes($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$mesa_estado_activo = kernel::db()->quote(\mesa_de_examen::mesa_estado_activo);
		
		$sql = " SELECT 
                        to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen,
                        to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_hora_visual) as hora_inicio,
                        to_char(cast(vw_mesas_examen.mesa_examen_hora_finalizacion as time), $formato_hora_visual) as hora_finalizacion,
                        vw_mesas_examen.mesa_examen_elemento as actividad,
                        sga_elementos.codigo as actividad_codigo,
                        sga_elementos.nombre as actividad_nombre,
                        vw_mesas_examen.anio_academico,
                        vw_mesas_examen.turno_examen as turno_examen,
                        vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
                        vw_mesas_examen.llamado as llamado,
                        vw_mesas_examen.llamado_nombre as llamado_nombre,
                        vw_mesas_examen.mesa_examen as mesa_examen,
                        vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
                        sga_tribunal_roles.nombre as rol_docente,
                        sga_ubicaciones.nombre as ubicacion_nombre,
						get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa, 7) as edificacion_nombre,
                        get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre,
                        vw_mesas_examen.mesa_examen_fecha as fecha_examen_orden,
                        vw_mesas_examen.mesa_examen_hora_inicio as hora_inicio_orden

                    FROM sga_docentes_mesa_llamado
                         JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
                         JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
                         JOIN sga_tribunal_roles ON sga_tribunal_roles.rol = sga_docentes_mesa_llamado.rol
                         JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion

                 WHERE sga_docentes_mesa_llamado.docente = {$parametros['docente']}
                   AND COALESCE(vw_mesas_examen.turno_examen_fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
                   AND vw_mesas_examen.mesa_examen_fecha >= CURRENT_DATE
                   AND vw_mesas_examen.llamado_mesa_estado = $mesa_estado_activo
			ORDER BY sga_elementos.nombre, 
                          vw_mesas_examen.mesa_examen_fecha,
                          vw_mesas_examen.mesa_examen_hora_inicio
				 ";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		
		$nuevo = array();
		foreach($datos as $id => $dato) {
			$nuevo[$id]['ACTIVIDAD_NOMBRE_COMPLETO'] = $dato[5]." (".$dato[4].")";
			$nuevo[$id]['ACTIVIDAD'] = $dato[3];
			$nuevo[$id]['ACTIVIDAD_NOMBRE'] = $dato[5];
			$nuevo[$id]['ANIO_ACADEMICO'] = $dato[6];
			$nuevo[$id]['TURNO_EXAMEN'] = $dato[7];
			$nuevo[$id]['TURNO_EXAMEN_NOMBRE'] = $dato[8];
			$nuevo[$id]['MESA_EXAMEN'] = $dato[11];
			$nuevo[$id]['MESA_EXAMEN_NOMBRE'] = $dato[12];
			$nuevo[$id]['LLAMADO'] = $dato[9];
			$nuevo[$id]['LLAMADO_NOMBRE'] = $dato[10];
			$nuevo[$id]['DOCENTE_ROL'] = $dato[13];
			$nuevo[$id]['FECHA'] = $dato[0];
			$nuevo[$id]['HORA_INI'] = $dato[1];
			$nuevo[$id]['HORA_FIN'] = $dato[2];
			$nuevo[$id]['HORARIO'] = $dato[1].' - '.$dato[2];
			$nuevo[$id]['AULA_NOMBRE'] = $dato[16];
			$nuevo[$id]['EDIFICIO_NOMBRE'] = $dato[15];
			$nuevo[$id]['UBICACION_NOMBRE'] = $dato[14];
			$nuevo[$id]['AULA'] = ($dato[16])?$dato[16].' - '.$dato[15]: "";
			foreach($nuevo[$id] as $clave => $valor){
				if (empty($valor)){
					$nuevo[$id][$clave] = "Sin definir";
				}
			}
		}
		return $nuevo;		
	}

	/**
	 * parametros: anio_academico, docente, periodo_lectivo, actividad, ubicacion
	 * param_null: anio_academico, docente, periodo_lectivo, actividad, ubicacion
	 * no_quote: anio_academico, docente, periodo_lectivo, actividad, ubicacion
	 * cache: no
	 * cache_expiracion: 600
	 * filas: n
	 */
	function lista_comisiones($parametros)
	{
		// listado de comisiones y subcomisiones
		// Si es con subcomisiones, muestra todas las subcomisiones de la comision, este o no este asignado el docente a la subcomision.

		$where_periodo_lectivo = '';
		$where_actividad = '';
		$from_docente = '';
		$where_docente = '';
		$from_docente_subcom = '';
		$where_docente_subcom = '';
		$where_ubicacion = '';
		$where_anio_academico = '';
		$responsabilidad_docente = ' CAST(NULL as varchar(50)) as resp_docente';

		// ********************** FILTROS *********************************
		// Filtro por Docente
		if ($parametros['docente'] != NULL) {

			// Solo comisiones donde el docente esta asignado
			$from_docente = " JOIN sga_docentes_comision ON (sga_docentes_comision.docente  = {$parametros['docente']} AND sga_docentes_comision.comision = sga_comisiones.comision) 
			                  JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad 
							";

			// Solo subcomisiones donde este el docente
			$from_docente_subcom = " JOIN sga_docentes_subcomision ON (sga_docentes_subcomision.docente  = {$parametros['docente']} AND sga_docentes_subcomision.subcomision = sga_subcomisiones.subcomision)
			                         JOIN sga_docentes_comision ON (sga_docentes_comision.comision = sga_comisiones.comision AND sga_docentes_comision.docente = sga_docentes_subcomision.docente)
									 JOIN sga_docentes_responsabilidades ON sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad
								";

			$responsabilidad_docente = " sga_docentes_responsabilidades.nombre as resp_docente ";
		}

		// Filtro por Periodo Lectivo
		if ($parametros['periodo_lectivo'] != NULL && $parametros['periodo_lectivo'] != '') {
			if (\guarani_arreglos::count($parametros['periodo_lectivo']) == 1) {
				$where_periodo_lectivo = " AND sga_comisiones.periodo_lectivo = " . current($parametros['periodo_lectivo']);
			} else {
				$periodo_lectivo = implode(',', $parametros['periodo_lectivo']);
				$where_periodo_lectivo = " AND sga_comisiones.periodo_lectivo IN ($periodo_lectivo)";
			}
		}
		// Filtro por Actividad    
		if ($parametros['actividad'] != NULL) {
			// Recupera comisiones de la actividad seleccionada
			$where_actividad = " AND sga_comisiones.elemento = {$parametros['actividad']}";
		}
		// Filtro por Ubicacion    
		if ($parametros['ubicacion'] != NULL) {
			// Recupera comisiones de la ubicacion seleccionada
			$where_ubicacion = " AND sga_comisiones.ubicacion = {$parametros['ubicacion']}";
		}
		//Filtro por A�o Academico
		if (!empty($parametros['anio_academico'])) {
			$where_anio_academico = " AND sga_periodos.anio_academico = {$parametros['anio_academico']}";
		}

		// Listado de comisiones
		$sql = "-- Comisiones sin subcomisiones		   
			SELECT
			   cast(sga_comisiones.comision as text)  as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.anio_academico || ' - ' || sga_periodos.nombre as periodo_lectivo_nombre_full,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   sga_comisiones_cupo.cupo as cupo_comision,
			   (SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = sga_comisiones.comision) as cant_inscriptos_comision,
			   --f_horario_comision(sga_comisiones.comision) as horario_comision,
			   f_bandas_horarias_comision(sga_comisiones.comision, true, true, true) as horario_comision,
			   -- subcomisiones
			   cast(null as integer) as subcomision,
			   cast(null as varchar(100)) as subcomision_nombre,
			   cast(null as varchar(50)) as tipo_clase,
			   0 as cupo_subcomision,
			   0 as cant_inscriptos_subcomision,
			   cast(null as varchar(200)) as horario_subcomision,
			   0 as es_subcomision,
			   sga_comisiones.entidad,
			   $responsabilidad_docente
			FROM sga_comisiones
			JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
			$from_docente
			JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			WHERE sga_comisiones.estado = " . kernel::db()->quote(zona_comisiones::COMISION_ESTADO_ACTIVA) . "
			  AND COALESCE(sga_periodos_lectivos.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
			  AND NOT EXISTS (SELECT 1 FROM sga_subcomisiones WHERE comision = sga_comisiones.comision)
			  $where_anio_academico
			  $where_periodo_lectivo
			  $where_actividad
			  $where_ubicacion

			UNION ALL 

			-- Comisiones con subcomisiones.
			SELECT
			  cast(sga_comisiones.comision as text) || '_' || cast(sga_subcomisiones.subcomision as text) as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.anio_academico || ' - ' || sga_periodos.nombre as periodo_lectivo_nombre_full,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   0 as cupo_comision,
			   0 as cant_inscriptos_comision,
			   cast(null as varchar(200)) as horario_comision,
			   -- Subcomisiones	
			   sga_subcomisiones.subcomision as subcomision,
			   sga_subcomisiones.nombre as subcomision_nombre,
			   sga_clases_tipos.nombre as tipo_clase,
			   sga_subcomisiones_cupo.cupo as cupo_subcomision,
			   (SELECT COUNT(1) FROM sga_insc_subcomision WHERE subcomision = sga_subcomisiones.subcomision) as cant_inscriptos_subcomision,
			   f_horario_subcomision(sga_subcomisiones.subcomision) as horario_subcomision,
			   1 as es_subcomision,
			   sga_comisiones.entidad,
			   $responsabilidad_docente
			FROM 
			   sga_comisiones
			   JOIN sga_subcomisiones ON sga_subcomisiones.comision = sga_comisiones.comision
			   $from_docente_subcom
			   JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
			   JOIN sga_subcomisiones_cupo ON sga_subcomisiones_cupo.subcomision = sga_subcomisiones.subcomision
			   JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			   JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			   JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			   JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			   LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			   LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			
			WHERE sga_comisiones.estado = " . kernel::db()->quote(zona_comisiones::COMISION_ESTADO_ACTIVA) . "
			  AND COALESCE(sga_periodos_lectivos.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
			  $where_anio_academico
			  $where_periodo_lectivo
			  $where_actividad
			  $where_ubicacion

			 ORDER BY periodo_lectivo_nombre_full desc,
			         actividad_orden, 
			         comision_nombre 
			";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		//todo: migrar a sistemas de codificador_modelo() -> en reporte inscripciones no se usa mas
		foreach ($datos as $id => $dato) {
			$id_c = catalogo::generar_id($dato['comision']);
			$id_s = catalogo::generar_id($dato['subcomision']);
			$nuevo[$id] = $dato;
			$nuevo[$id]['comision_id'] = $id_c;
			$nuevo[$id]['subcomision_id'] = $id_s;
		}
		return $nuevo;
	}
 
	/**
	 * parametros: anio_academico, docente
	 * no_quote: anio_academico, docente
	 * cache: memoria
	 * filas: n
	 */
	function lista_comisiones_docentes($parametros)
	{
		// listado de comisiones que pueden tener subcomisiones pero solo cuando el docente es de la comision
		//con cantidad de inscriptos 
		// Listado de comisiones
		$sql = "
			SELECT
			   CAST(sga_comisiones.comision AS text) AS id,
			   sga_periodos.anio_academico,
			   sga_periodos.nombre AS periodo_lectivo_nombre,
			   sga_periodos.anio_academico || ' - ' || sga_periodos.nombre AS periodo_lectivo_nombre_full,
			   sga_comisiones.periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' AS actividad_orden,
			   sga_comisiones.elemento AS actividad,
			   vw_actividades.codigo AS actividad_codigo,
			   vw_actividades.nombre AS actividad_nombre,
			   vw_actividades.nombre_abreviado AS actividad_nombre_abreviado,
			   sga_comisiones.comision,
			   sga_comisiones.nombre AS comision_nombre,
			   sga_comisiones.turno,
			   sga_turnos_cursadas.nombre AS turno_nombre,
			   sga_catedras.nombre AS catedra_nombre,
			   sga_ubicaciones.ubicacion,
			   sga_ubicaciones.nombre AS ubicacion_nombre,
			   (SELECT COUNT(1) FROM sga_insc_cursada WHERE comision = sga_comisiones.comision) AS cant_inscriptos_comision
			FROM 
				sga_comisiones
			    JOIN sga_docentes_comision		ON  sga_docentes_comision.comision			= sga_comisiones.comision
				JOIN sga_ubicaciones			ON sga_ubicaciones.ubicacion				= sga_comisiones.ubicacion
				JOIN vw_actividades				ON vw_actividades.elemento					= sga_comisiones.elemento
				JOIN sga_periodos_lectivos		ON sga_periodos_lectivos.periodo_lectivo	= sga_comisiones.periodo_lectivo
				JOIN sga_periodos				ON sga_periodos.periodo						= sga_periodos_lectivos.periodo
				LEFT JOIN sga_catedras			ON sga_catedras.catedra						= sga_comisiones.catedra
			    LEFT JOIN sga_turnos_cursadas	ON sga_turnos_cursadas.turno				= sga_comisiones.turno
			WHERE sga_docentes_comision.docente		= {$parametros['docente']} 
			  AND sga_comisiones.estado			= " . kernel::db()->quote(zona_comisiones::COMISION_ESTADO_ACTIVA) . "
			  AND sga_periodos.anio_academico	= {$parametros['anio_academico']}
			  AND COALESCE(sga_periodos_lectivos.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
			";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		//todo: migrar a sistemas de codificador_modelo() -> en reporte inscripciones no se usa mas
		foreach ($datos as $id => $dato) {
			$id_c = catalogo::generar_id($dato['comision']);
			$nuevo[$id] = $dato;
			$nuevo[$id]['comision_id'] = $id_c;
		}
		return $nuevo;
	}
	/**
	 * parametros: docente, periodo_lectivo, actividad, ubicacion, comisiones_activas
	 * param_null: docente, periodo_lectivo, actividad, ubicacion, comisiones_activas
	 * no_quote: docente, periodo_lectivo, actividad, ubicacion, comisiones_activas
	 * cache: no
	 * cache_expiracion: 600
	 * filas: n
	 */
	function lista_comisiones_activas($parametros)
	{
		// listado de comisiones y subcomisiones
		// Si es con subcomisiones, muestra todas las subcomisiones de la comision, este o no este asignado el docente a la subcomision.
		// Comisiones de actividades de periodos lectivos con fecha de inactivaci�n >= a hoy

		$where_periodo_lectivo = '';
		$where_actividad = '';
		$from_docente = '';
		$where_docente = '';
		$from_docente_subcom = '';
		$where_docente_subcom = '';
		$where_ubicacion = '';
		$where_comisiones_activas = '';
		$responsabilidad_docente = ' CAST(NULL as varchar(50)) as resp_docente';

		// ********************** FILTROS *********************************
		// Filtro por Docente
		if ($parametros['docente'] != NULL) {

			// Solo comisiones donde el docente esta asignado
			$from_docente  = " JOIN sga_docentes_comision ON (sga_docentes_comision.docente  = {$parametros['docente']} AND sga_docentes_comision.comision = sga_comisiones.comision)
			                   JOIN sga_docentes_responsabilidades ON sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad  
							 ";

			// Solo subcomisiones donde este el docente
			$from_docente_subcom = " JOIN sga_docentes_subcomision ON (sga_docentes_subcomision.docente  = {$parametros['docente']} AND sga_docentes_subcomision.subcomision = sga_subcomisiones.subcomision)
									 JOIN sga_docentes_comision ON (sga_docentes_comision.comision = sga_comisiones.comision AND sga_docentes_comision.docente = sga_docentes_subcomision.docente)
									 JOIN sga_docentes_responsabilidades ON sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad 
									";			

			$responsabilidad_docente = " sga_docentes_responsabilidades.nombre as resp_docente ";
		}
		 
		// Filtro por Periodo Lectivo
		if ($parametros['periodo_lectivo'] != NULL && $parametros['periodo_lectivo'] != '') {
			if (\guarani_arreglos::count($parametros['periodo_lectivo']) == 1) {
				$where_periodo_lectivo = " AND sga_comisiones.periodo_lectivo = ". current($parametros['periodo_lectivo']);
			} else {
				$periodo_lectivo = implode(',',$parametros['periodo_lectivo']);
				$where_periodo_lectivo = " AND sga_comisiones.periodo_lectivo IN ($periodo_lectivo)";
			}
		}
		// Filtro por Actividad    
		if ($parametros['actividad'] != NULL) {
			// Recupera comisiones de la actividad seleccionada
			$where_actividad = " AND sga_comisiones.elemento = {$parametros['actividad']}";
		}
		// Filtro por Ubicacion    
		if ($parametros['ubicacion'] != NULL) {
			// Recupera comisiones de la ubicacion seleccionada
			$where_ubicacion = " AND sga_comisiones.ubicacion = {$parametros['ubicacion']}";
		}
			
		// Comisiones activas ?
		if ((!empty($parametros['comisiones_activas'])) && ($parametros['comisiones_activas'])) {
			$where_comisiones_activas = " AND sga_periodos_lectivos.fecha_inactivacion >= CURRENT_DATE";
		}

		// Listado de comisiones
		$sql = "-- Comisiones sin subcomisiones		   
			SELECT
			   cast(sga_comisiones.comision as text)  as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.anio_academico || ' - ' || sga_periodos.nombre as periodo_lectivo_nombre_full,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   sga_comisiones_cupo.cupo as cupo_comision,
			   sga_comisiones_cupo.cant_inscriptos as cant_inscriptos_comision,
			   --f_horario_comision(sga_comisiones.comision) as horario_comision,
			   f_bandas_horarias_comision(sga_comisiones.comision, true, true, true) as horario_comision,
			   -- subcomisiones
			   cast(null as integer) as subcomision,
			   cast(null as varchar(100)) as subcomision_nombre,
			   cast(null as varchar(50)) as tipo_clase,
			   0 as cupo_subcomision,
			   0 as cant_inscriptos_subcomision,
			   cast(null as varchar(200)) as horario_subcomision,
			   0 as es_subcomision,
			   sga_comisiones.entidad,
			   $responsabilidad_docente
			FROM sga_comisiones
			JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
			$from_docente
			JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			WHERE NOT EXISTS (SELECT 1 FROM sga_subcomisiones WHERE comision = sga_comisiones.comision)
			  AND COALESCE(sga_periodos_lectivos.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE	
			  $where_periodo_lectivo
			  $where_actividad
			  $where_ubicacion
			  $where_comisiones_activas

			UNION ALL 

			-- Comisiones con subcomisiones.
			SELECT
			  cast(sga_comisiones.comision as text) || '_' || cast(sga_subcomisiones.subcomision as text) as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.anio_academico || ' - ' || sga_periodos.nombre as periodo_lectivo_nombre_full,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   0 as cupo_comision,
			   0 as cant_inscriptos_comision,
			   cast(null as varchar(200)) as horario_comision,
			   -- Subcomisiones
			   sga_subcomisiones.subcomision as subcomision,
			   sga_subcomisiones.nombre as subcomision_nombre,
			   sga_clases_tipos.nombre as tipo_clase,
			   sga_subcomisiones_cupo.cupo as cupo_subcomision,
			   sga_subcomisiones_cupo.cant_inscriptos as cant_inscriptos_subcomision,
			   f_horario_subcomision(sga_subcomisiones.subcomision) as horario_subcomision,
			   1 as es_subcomision,
			   sga_comisiones.entidad,
			   $responsabilidad_docente
			
			FROM sga_comisiones
			JOIN sga_subcomisiones ON sga_subcomisiones.comision = sga_comisiones.comision
			$from_docente_subcom
			JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
			JOIN sga_subcomisiones_cupo ON sga_subcomisiones_cupo.subcomision = sga_subcomisiones.subcomision
			JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			
			WHERE COALESCE(sga_periodos_lectivos.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
			  $where_periodo_lectivo
			  $where_actividad
			  $where_ubicacion
			  $where_comisiones_activas
			ORDER BY periodo_lectivo_nombre_full,
			         actividad_orden, 
			         comision_nombre
			";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		//todo: migrar a sistemas de codificador_modelo() -> en reporte inscripciones no se usa mas
		foreach ($datos as $id => $dato) {
			$id_c = catalogo::generar_id($dato['comision']);
			$id_s = catalogo::generar_id($dato['subcomision']);
			$nuevo[$id] = $dato;
			$nuevo[$id]['comision_id'] = $id_c;
			$nuevo[$id]['subcomision_id'] = $id_s;
		}
		return $nuevo;

	}


	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function comision_cabecera($parametros)
	{
		// listado de comisiones y subcomisiones
		// Si es con subcomisiones, muestra todas las subcomisiones de la comision, este o no este asignado el docente a la subcomision.
		// Comisiones de actividades de periodos lectivos con fecha de inactivaci�n >= a hoy

		// Listado de comisiones
		$sql = "-- Comisiones sin subcomisiones
			SELECT
			   cast(sga_comisiones.comision as text)  as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   sga_comisiones_cupo.cupo as cupo_comision,
			   sga_comisiones_cupo.cant_inscriptos as cant_inscriptos_comision,
			   -- f_horario_comision(sga_comisiones.comision) as horario_comision,
			   f_bandas_horarias_comision(sga_comisiones.comision, true, true, true) as horario_comision,
			   -- subcomisiones
			   cast(null as integer) as subcomision,
			   cast(null as varchar(100)) as subcomision_nombre,
			   cast(null as varchar(50)) as tipo_clase,
			   0 as cupo_subcomision,
			   0 as cant_inscriptos_subcomision,
			   cast(null as varchar(200)) as horario_subcomision,
			   0 as es_subcomision,
			   sga_comisiones.entidad
			FROM
			   sga_comisiones
			   JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
			   JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			   JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			   JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			   JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			   LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			   LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			WHERE 
			      sga_comisiones.comision = {$parametros['comision']}
			  AND NOT EXISTS (SELECT 1 FROM sga_subcomisiones WHERE comision = sga_comisiones.comision)
		";

		$datos = kernel::db()->consultar($sql);
		return $datos;

	}

	/**
	 * el id es comision si no tiene subco, o comision_subcomision en otro caso
	 * parametros: id
	 * cache: no
	 * filas: n
	 */
	function subcomision_cabecera($parametros)
	{
		// listado de comisiones y subcomisiones
		// Si es con subcomisiones, muestra todas las subcomisiones de la comision, este o no este asignado el docente a la subcomision.
		// Comisiones de actividades de periodos lectivos con fecha de inactivacion >= a hoy

		// ********************** FILTROS *********************************
		// Filtro por Docente
		// Solo comisiones donde el docente esta asignado
		$from_docente = " JOIN sga_docentes_comision ON (sga_docentes_comision.docente = {$parametros['docente']} AND sga_docentes_comision.comision = sga_comisiones.comision) ";

		// Solo subcomisiones donde este el docente
		$from_docente_subcom = " JOIN sga_docentes_subcomision ON (sga_docentes_subcomision.docente = {$parametros['docente']} AND sga_docentes_subcomision.subcomision = sga_subcomisiones.subcomision) ";

		$sql = "-- Comisiones sin subcomisiones
			SELECT
			   cast(sga_comisiones.comision as text)  as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   sga_comisiones_cupo.cupo as cupo_comision,
			   sga_comisiones_cupo.cant_inscriptos as cant_inscriptos_comision,
			   --f_horario_comision(sga_comisiones.comision) as horario_comision,
			   f_bandas_horarias_comision(sga_comisiones.comision, true, true, true) as horario_comision,
			   -- subcomisiones
			   cast(null as integer) as subcomision,
			   cast(null as varchar(100)) as subcomision_nombre,
			   cast(null as varchar(50)) as tipo_clase,
			   0 as cupo_subcomision,
			   0 as cant_inscriptos_subcomision,
			   cast(null as varchar(200)) as horario_subcomision,
			   0 as es_subcomision,
			   sga_comisiones.entidad
			FROM
			   sga_comisiones
			   JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
			   $from_docente
			   JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			   JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			   JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			   JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			   LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			   LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			WHERE 
			      cast(sga_comisiones.comision as text) = {$parametros['id']}
			  AND NOT EXISTS (SELECT 1 FROM sga_subcomisiones WHERE comision = sga_comisiones.comision)

		  UNION ALL

			-- Comisiones con subcomisiones.
			SELECT
			  cast(sga_comisiones.comision as text) || '_' || cast(sga_subcomisiones.subcomision as text) as id,
			   sga_periodos.anio_academico as anio_academico,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_periodos.fecha_inicio as periodo_fecha_inicio,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   sga_ubicaciones.ubicacion as ubicacion,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   0 as cupo_comision,
			   0 as cant_inscriptos_comision,
			   cast(null as varchar(200)) as horario_comision,
			   -- Subcomisiones
			   sga_subcomisiones.subcomision as subcomision,
			   sga_subcomisiones.nombre as subcomision_nombre,
			   sga_clases_tipos.nombre as tipo_clase,
			   sga_subcomisiones_cupo.cupo as cupo_subcomision,
			   sga_subcomisiones_cupo.cant_inscriptos as cant_inscriptos_subcomision,
			   f_horario_subcomision(sga_subcomisiones.subcomision) as horario_subcomision,
			   1 as es_subcomision,
			   sga_comisiones.entidad
			FROM
			   sga_comisiones
			   JOIN sga_subcomisiones ON sga_subcomisiones.comision = sga_comisiones.comision
			   $from_docente_subcom
			   JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_subcomisiones.tipo_clase
			   JOIN sga_subcomisiones_cupo ON sga_subcomisiones_cupo.subcomision = sga_subcomisiones.subcomision
			   JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			   JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			   JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			   JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			   LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			   LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			WHERE 
			     (cast(sga_comisiones.comision as text) || '_' || cast(sga_subcomisiones.subcomision as text) = {$parametros['id']}
			      OR cast(sga_comisiones.comision as text) = {$parametros['id']}
				  )
			";
		//este ultimo OR es para poder usar esta consulta sin subcomisiones (por ej, desde actas hacer un link a la zona)
		//va a traer todas las subcomisiones
		$datos = kernel::db()->consultar($sql);
		return $datos;

	}
	
	/**
	 * parametros: docente, periodo_lectivo, actividad, ubicacion
	 * param_null: periodo_lectivo, actividad, ubicacion
	 * cache: no
	 * cache_expiracion: 600
	 * filas: n
	 */
	function lista_comisiones_rech($parametros)
	{
		// ********************* Filtros **********************************************
		$where_periodo = '';
		$where_actividad = '';
		$where_ubicacion = '';
		// Filtro por Período lectivo
		if ($parametros["periodo_lectivo"] != "''"){
			$where_periodo = " AND sga_comisiones.periodo_lectivo = {$parametros['periodo_lectivo']}";
		}

		// Filtro por Actividad
		if ($parametros["actividad"] != "''"){
			$where_actividad = " AND vw_actividades.elemento = {$parametros['actividad']}";
		}

		// Filtro por Ubicaci�n de la ubicaci�n
		if ($parametros["ubicacion"] != "''"){
			$where_ubicacion = " AND sga_ubicaciones.ubicacion = {$parametros['ubicacion']}";
		}
		// ********************* Fin Filtros *******************************************

		
		$sql = "-- Comisiones sin subcomisiones
			SELECT 
			   0 as es_subcomision,
			   sga_comisiones.comision as comision,
			   sga_comisiones.nombre as comision_nombre,
			   sga_comisiones.elemento as actividad,
			   vw_actividades.codigo as actividad_codigo,
			   vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as actividad_orden,
			   vw_actividades.nombre as actividad_nombre,
			   vw_actividades.nombre_abreviado as actividad_nombre_abreviado,
			   sga_periodos.anio_academico as anio_academico,
			   sga_comisiones.periodo_lectivo as periodo_lectivo,
			   sga_periodos.fecha_inicio as fecha_inicio,
			   sga_periodos.nombre as periodo_lectivo_nombre,
			   sga_comisiones.turno as turno,
			   sga_turnos_cursadas.nombre as turno_nombre,
			   f_instancias_comision (sga_comisiones.comision) as instancias,
			   sga_comisiones.catedra as catedra,
			   sga_catedras.nombre as catedra_nombre,
			   dr.responsabilidad as responsabilidad,
			   dr.nombre as responsabilidad_nombre,
			   sga_ubicaciones.nombre as ubicacion_nombre,
			   sga_comisiones_cupo.cupo as cupo_comision,
			   sga_comisiones_cupo.cant_inscriptos as cant_inscriptos_comision,
			   f_horario_comision(sga_comisiones.comision) as horario_comision,
			   -- subcomisiones
			   cast(null as integer) as subcomision,
			   cast(null as varchar(100)) as subcomision_nombre,
			   cast(null as varchar(50)) as tipo_clase,
			   0 as cupo_subcomision,
			   0 as cant_inscriptos_subcomision,
			   cast(null as varchar(200)) as horario_subcomision,
			   cast(0 as integer) as cantidad_rechazados
			FROM 
			   sga_docentes_comision
			   JOIN sga_comisiones ON sga_comisiones.comision = sga_docentes_comision.comision
			   JOIN sga_comisiones_cupo ON sga_comisiones_cupo.comision = sga_comisiones.comision
			   JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
			   JOIN vw_actividades ON vw_actividades.elemento = sga_comisiones.elemento
			   JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
			   JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
			   LEFT JOIN sga_docentes_responsabilidades as dr ON dr.responsabilidad = sga_docentes_comision.responsabilidad  
			   LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
			   LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
			WHERE sga_docentes_comision.docente  = {$parametros['docente']}
			  AND sga_periodos_lectivos.fecha_inactivacion >= CURRENT_DATE
			  AND COALESCE(sga_periodos_lectivos.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
			  $where_periodo
			  $where_actividad
			  $where_ubicacion
			ORDER BY 8, 10
			";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		$comisiones_ids = "";
		foreach ($datos as $id => $dato) {
			$id_c = catalogo::generar_id($dato['comision']);
			$nuevo[$id] = $dato;
			$nuevo[$id]['comision_id'] = $id_c;
			$comisiones_ids .= $dato['comision'] . ",";
		}

		if (empty($comisiones_ids)) {
			return array();
		}

		$comisiones_ids = substr($comisiones_ids, 0, -1);

		// comisiones con alumnos rechazados
		$sql_rechazados = "SELECT comision, count(*) as cantidad
							 FROM sga_insc_cursada_log 
							WHERE comision IN (" . $comisiones_ids . ")
							  AND operacion = 'R' 
							GROUP BY comision
							";

		$rechazados = kernel::db()->consultar($sql_rechazados);

		// mapeo de alumnos rechazados
		$rechazados_ordenados = array();
		foreach ($rechazados as $value) {
			$rechazados_ordenados[$value['comision']] = $value['cantidad'];
		}

		// merge de arreglos $nuevo y $rechazados_ordenados
		foreach ($nuevo as $id => $dato) {
			if (isset($rechazados_ordenados[$dato['comision']])) {
				$nuevo[$id]['cantidad_rechazados'] = $rechazados_ordenados[$dato['comision']];
			}

		}
		return $nuevo;

	}

	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function alum_inscriptos_comision($parametros)
	{
		// Alumnos inscriptos en una comisi�n
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
			   vw_personas.persona,
			   sga_alumnos.legajo as legajo,
			   sga_alumnos.alumno as alumno,
			   vw_personas.tipo_nro_documento as tipo_nro_documento,
			   vw_personas.apellido_nombres_elegido as apellido_nombres,
			   vw_personas.tipo_nro_documento,
			   sga_alumnos.propuesta as propuesta,
			   sga_propuestas.codigo as propuesta_codigo,
			   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
			   {$this->subquery_email()},
			   {$this->subquery_telefono()},
			   sga_insc_cursada.fecha_inscripcion as fecha_inscripcion,
			   to_char(sga_insc_cursada.fecha_inscripcion, $formato_fecha_visual) as fecha_inscripcion_display,
			   f_instancias_insc_cursada (sga_insc_cursada.inscripcion) as instancias,
			   sga_inscripciones_estados.nombre as estado_inscripcion,
			   sga_insc_cursada.nro_transaccion as nro_transaccion,
			   sga_insc_cursada.inscripcion as inscripcion
			FROM 
			   sga_insc_cursada
			   JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_cursada.estado
			   JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
			   JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
			   JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
			WHERE sga_insc_cursada.comision = {$parametros['comision']}
		  ORDER BY vw_personas.apellido_nombres
			";

		$datos = kernel::db()->consultar($sql);
		Return $datos;
	}
	
	function subquery_email($as = 'email') {
		
		$mail_primario = kernel::db()->quote(\persona::mail_primario);
		$mail_secundario = kernel::db()->quote(\persona::mail_secundario);
		$mail_institucional = kernel::db()->quote(\persona::mail_institucional);
		
		$orden_tipo_email = \persona::orden_tipo_email();

		$sql = "COALESCE((SELECT CONCAT( mdp_contactos_tipo.descripcion,': ',mdp_personas_contactos.email) as coal_email
						  FROM mdp_personas_contactos
						  JOIN negocio.mdp_contactos_tipo on negocio.mdp_contactos_tipo.contacto_tipo = negocio.mdp_personas_contactos.contacto_tipo
						  WHERE mdp_personas_contactos.persona = vw_personas.persona
						    AND mdp_personas_contactos.contacto_tipo IN ($mail_primario, $mail_secundario, $mail_institucional)
						  ORDER BY $orden_tipo_email
						  LIMIT 1), null) AS $as";
		return $sql;
	}

	function subquery_telefono($as = 'telefono') {
		
		$tel_celular = kernel::db()->quote(\persona::tel_celular);
		$tel_fijo = kernel::db()->quote(\persona::tel_fijo);
		$tel_laboral = kernel::db()->quote(\persona::tel_laboral);
		$tel_familiar = kernel::db()->quote(\persona::tel_familiar);
		
		$orden_tipo_telefono = \persona::orden_tipo_telefono();

		$sql = "COALESCE((SELECT CONCAT(mdp_contactos_tipo.descripcion,': ','(',mdp_personas_contactos.telefono_codigo_area,')',' ',mdp_personas_contactos.telefono_numero) as coal_telefono
						  FROM negocio.mdp_personas_contactos
						  JOIN negocio.mdp_contactos_tipo on negocio.mdp_contactos_tipo.contacto_tipo = negocio.mdp_personas_contactos.contacto_tipo
						  WHERE negocio.mdp_personas_contactos.persona = negocio.vw_personas.persona 
						    AND mdp_personas_contactos.contacto_tipo IN ($tel_celular, $tel_fijo, $tel_laboral, $tel_familiar)
						  ORDER BY $orden_tipo_telefono
						LIMIT 1), null) as $as";
		return $sql;
	}

	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function alum_rechazados_comision($parametros)
	{
		// Alumnos inscriptos y rechazados en una comision
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
			   sga_alumnos.legajo as legajo,
			   vw_personas.tipo_nro_documento as tipo_nro_documento,
			   vw_personas.apellido_nombres_elegido as apellido_nombres,
			   vw_personas.tipo_nro_documento,
			   sga_alumnos.propuesta as propuesta,
			   sga_propuestas.codigo as propuesta_codigo,
			   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
			   mdp_personas_contactos.email as email,
			   sga_insc_cursada_log.fecha_inscripcion as fecha_inscripcion,
			   to_char(sga_insc_cursada_log.fecha_inscripcion, $formato_fecha_visual) as fecha_inscripcion_display,
			   f_instancias_insc_cursada (sga_insc_cursada_log.inscripcion) as instancias,
			   sga_insc_cursada_log.nro_transaccion as nro_transaccion
			FROM 
			   sga_insc_cursada_log
			   JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada_log.alumno
			   JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
			   JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
			   LEFT JOIN mdp_personas_contactos ON (mdp_personas_contactos.persona = vw_personas.persona AND mdp_personas_contactos.contacto_tipo = 'MP')
			WHERE sga_insc_cursada_log.comision = {$parametros['comision']}
			  AND sga_insc_cursada_log.operacion = 'R'
			ORDER BY vw_personas.apellido_nombres_elegido
			";

		$datos = kernel::db()->consultar($sql);
		Return $datos;
	}

	/**
	 * parametros: comision, subcomision
	 * cache: no
	 * filas: n
	 */
	function alum_inscriptos_subcomision($parametros)
	{
		// Alumnos inscriptos en una subcomision
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
			   sga_alumnos.legajo as legajo,
			   vw_personas.tipo_nro_documento as tipo_nro_documento,
			   vw_personas.apellido_nombres_elegido as apellido_nombres,
			   vw_personas.tipo_nro_documento,
			   sga_alumnos.propuesta as propuesta,
			   sga_propuestas.codigo as propuesta_codigo,
			   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
			   {$this->subquery_email()},
			   {$this->subquery_telefono()},
			   sga_insc_cursada.fecha_inscripcion as fecha_inscripcion,
			   to_char(sga_insc_cursada.fecha_inscripcion, $formato_fecha_visual) as fecha_inscripcion_display,
			   f_instancias_insc_cursada (sga_insc_cursada.inscripcion) as instancias,
			   sga_inscripciones_estados.nombre as estado_inscripcion,
			   sga_insc_cursada.nro_transaccion as nro_transaccion,
			   sga_insc_cursada.inscripcion as inscripcion
			FROM 
			   sga_insc_cursada
			   JOIN sga_insc_subcomision ON sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion
			   JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_cursada.estado
			   JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
			   JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
			   JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
			   LEFT JOIN mdp_personas_contactos ON (mdp_personas_contactos.persona = vw_personas.persona AND mdp_personas_contactos.contacto_tipo = 'MP')
			WHERE sga_insc_cursada.comision = {$parametros['comision']}
			  AND sga_insc_subcomision.subcomision = {$parametros['subcomision']}
			ORDER BY apellido_nombres
			";

		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['inscripcion']);
			$nuevo[$id][catalogo::id] = $id;
			$nuevo[$id] = $dato;
		}
		return $nuevo;
	}

	/**
	 * parametros: docente, turno_examen, actividad, ubicacion, fecha_desde, fecha_hasta, anio_academico, mostrar_mesas_fuera_calendario, mesas_con_inscriptos
	 * param_null: turno_examen, actividad, ubicacion, fecha_desde, fecha_hasta, anio_academico, mostrar_mesas_fuera_calendario, mesas_con_inscriptos
	 * no_quote: mesas_con_inscriptos
	 * cache: no
	 * filas: n
	 */
	function lista_mesas($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_fecha_bd = kernel::db()->quote(\comunes::formato_fecha_bd);
		$si = kernel::db()->quote(\comunes::si);
		$mesa_estado_activo = kernel::db()->quote(\mesa_de_examen::mesa_estado_activo);
		// ********************* Filtros **********************************************
		$where_turno = '';
		$where_actividad = '';
		$where_ubicacion = '';
		$where_f_desde = '';
		$where_f_hasta = '';
		$where_anio_academico = '';
		$where_mesas_en_turno_fc = '';
		$where_mesas_con_inscriptos = '';
		
		// Por defecto solo muestro mesas de examen que estan dentro de un turno de examen.
		$where_mesas_en_turno_fc = " AND vw_mesas_examen.mesa_examen_en_turno = 'S'";
		if (!empty($parametros['mostrar_mesas_fuera_calendario']))
			$where_mesas_en_turno_fc = ""; // Muestra todas las mesas de examen (dentro de un turno y fuera de calendario)
		
		// Filtro por A�o Academico
		if (!empty($parametros['anio_academico']))
			$where_anio_academico = " AND vw_mesas_examen.anio_academico = {$parametros['anio_academico']}";

		// Filtro por Turno de examen
		if (!empty($parametros['turno_examen']))
			$where_turno = " AND vw_mesas_examen.turno_examen = {$parametros['turno_examen']}";

		// Filtro por Actividad (ID de la actividad)
		if (!empty($parametros['actividad']))
			$where_actividad = " AND vw_mesas_examen.mesa_examen_elemento = {$parametros['actividad']}";

		// Filtro por Ubicacion
		if (!empty($parametros['ubicacion']))
			$where_ubicacion = " AND vw_mesas_examen.mesa_examen_ubicacion = {$parametros['ubicacion']}";

		//Filtro por Fecha desde de la mesa
		if (!empty($parametros['fecha_desde']))
			$where_f_desde = " AND vw_mesas_examen.mesa_examen_fecha >= to_date({$parametros['fecha_desde']}, $formato_fecha_bd)";

		//Filtro por Fecha hasta de la mesa
		if (!empty($parametros['fecha_hasta']))
			$where_f_hasta = " AND vw_mesas_examen.mesa_examen_fecha <= to_date({$parametros['fecha_hasta']}, $formato_fecha_bd)";
			
		//Filtro por mesas con inscriptos
		if (!is_null($parametros['mesas_con_inscriptos']) && $parametros['mesas_con_inscriptos'] == \comunes::si)
			$where_mesas_con_inscriptos = " AND (CASE vw_mesas_examen.mesa_examen_en_turno
					                 WHEN {$si} THEN (SELECT count(*) FROM sga_insc_examen WHERE llamado_mesa = vw_mesas_examen.llamado_mesa)
					                 ELSE (-- Acta de examen fuera de calendario. 
									       SELECT count(*) 
					                         FROM sga_evaluaciones as e
											 JOIN sga_eval_detalle_examenes as d ON d.evaluacion = e.evaluacion  
											 JOIN sga_evaluaciones_tipos as et ON et.evaluacion_tipo = e.evaluacion_tipo
										   WHERE e.entidad = vw_mesas_examen.llamado_mesa_entidad
					                         AND et.automatica = {$si})
					                END) > 0";
				
		// ********************* Fin Filtros *******************************************

		// Recupera las mesas de examen donde esta el docente.
		// Mesas examen en un rango de fechas (si se ingresó el filtro)
		// Mesas que estan dentro de un turno y llamado
		$sql = "SELECT	
					vw_mesas_examen.mesa_examen_elemento as actividad,
					sga_elementos.codigo as actividad_codigo,
					vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
					vw_mesas_examen.mesa_examen as mesa_examen,
					vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
					vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					vw_mesas_examen.mesa_examen_catedra as catedra,
					sga_catedras.nombre as catedra_nombre,
					vw_mesas_examen.llamado_mesa as llamado_mesa,
					vw_mesas_examen.llamado_nombre as llamado_nombre,
					to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_display,
					vw_mesas_examen.mesa_examen_hora_inicio as hora_inicio,
					-- to_char(vw_mesas_examen.mesa_examen_hora_inicio,'HH:MI') || ' - ' || to_char(vw_mesas_examen.mesa_examen_hora_finalizacion,'HH:MI') as hora_inicio_finalizacion,
					vw_mesas_examen.anio_academico as anio_academico,
					vw_mesas_examen.turno_examen as turno_examen,
					vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
					sga_tribunal_roles.nombre as rol_docente,
					f_instancias_mesa_examen(vw_mesas_examen.mesa_examen) as instancias,
					f_modalidad_cursada_examen(vw_mesas_examen.mesa_examen) as modalidades,
					vw_mesas_examen.mesa_examen_en_turno as mesa_examen_en_turno,
					CASE vw_mesas_examen.mesa_examen_en_turno
					  WHEN {$si} THEN (SELECT count(*) FROM sga_insc_examen WHERE llamado_mesa = vw_mesas_examen.llamado_mesa)
					  ELSE (SELECT count(*) 
					          FROM sga_evaluaciones as e
							  JOIN sga_eval_detalle_examenes as d ON d.evaluacion = e.evaluacion  
							  JOIN sga_evaluaciones_tipos as et ON et.evaluacion_tipo = e.evaluacion_tipo
							 WHERE e.entidad = vw_mesas_examen.llamado_mesa_entidad
					           AND et.automatica = {$si})  -- Acta de examen fuera de calendario.
					END as cant_inscriptos
						
				FROM sga_docentes_mesa_llamado
				JOIN sga_tribunal_roles ON sga_tribunal_roles.rol = sga_docentes_mesa_llamado.rol
				JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
				JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
				LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra

				WHERE sga_docentes_mesa_llamado.docente = {$parametros['docente']}
				  AND COALESCE(vw_mesas_examen.turno_examen_fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
				  AND vw_mesas_examen.llamado_mesa_estado = $mesa_estado_activo
				  $where_mesas_en_turno_fc
				  $where_anio_academico
				  $where_turno
				  $where_actividad
				  $where_ubicacion
				  $where_f_desde
				  $where_f_hasta
				  $where_mesas_con_inscriptos
				ORDER BY 
				    3, -- nombre actividad
					vw_mesas_examen.mesa_examen_fecha,
					vw_mesas_examen.mesa_examen_hora_inicio
  				;";
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['llamado_mesa']);
			$nuevo[$id][catalogo::id] = $id;
			$nuevo[$id] = $dato;

		}
		kernel::log()->add_debug('lista_mesas', $nuevo);
		return $nuevo;
	}


	/**
	 * parametros: llamado_mesa
	 * cache: no
	 * filas: n
	 */
	function datos_mesa_examen($parametros)
	{
		// Recupera los datos de la mesa de examen
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$sql = "SELECT
					vw_mesas_examen.llamado_mesa,
					vw_mesas_examen.mesa_examen_elemento as actividad,
					sga_elementos.codigo as actividad_codigo,
					vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
					vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
					sga_ubicaciones.nombre as ubicacion_nombre,
					sga_catedras.nombre as catedra_nombre,
					to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen,
					to_char(CURRENT_DATE + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual) ||
						COALESCE(' - ' || to_char(CURRENT_DATE + vw_mesas_examen.mesa_examen_hora_finalizacion, $formato_hora_visual), '') as hora_inicio_finalizacion,
					vw_mesas_examen.anio_academico as anio_academico,
					vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
					get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa) as espacio_nombre,
					f_instancias_mesa_examen(vw_mesas_examen.mesa_examen) as instancias,
					f_modalidad_cursada_examen(vw_mesas_examen.mesa_examen) as modalidades,
					f_docentes_mesa_examen(vw_mesas_examen.llamado_mesa, true) as docentes,
					vw_mesas_examen.llamado_nombre as llamado_nombre
				FROM vw_mesas_examen
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
				JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
				LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
				WHERE vw_mesas_examen.llamado_mesa = {$parametros['llamado_mesa']}
  				;";

		return kernel::db()->consultar_fila($sql);
	}

	/**
	 *
	 * parametros: docente, fecha_examen_desde, fecha_examen_hasta
         * param_null: fecha_examen_desde, fecha_examen_hasta
	
	 * cache: no
	 * filas: n
	 */
	function lista_mesas_examen_docente($parametros)
	{
		// Lista mesas de examen donde se encuentra el docente con fecha en forma descendente.
		// Mesas de examen dentro y fuera de calendario
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		if (!is_null($parametros['fecha_examen_desde']) && !is_null($parametros['fecha_examen_hasta'])) {
				$filtro_fecha = "AND vw_mesas_examen.mesa_examen_fecha BETWEEN " . $parametros['fecha_examen_desde']. " AND " .$parametros['fecha_examen_hasta'];
			}
		$sql = "SELECT	
						vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
						vw_mesas_examen.mesa_examen as mesa_examen,
						vw_mesas_examen.llamado_mesa as llamado_mesa,
						vw_mesas_examen.mesa_examen_elemento as actividad,
						sga_elementos.codigo as actividad_codigo,
						vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
						'(' || sga_elementos.codigo || ') ' || vw_mesas_examen.mesa_examen_elemento_nombre AS actividad_descr,
						vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre,
						COALESCE(vw_mesas_examen.turno_examen_nombre, ".kernel::db()->quote(kernel::traductor()->trans('fuera_de_calendario')).") as turno_examen_nombre,
						COALESCE(vw_mesas_examen.llamado_nombre, ".kernel::db()->quote(kernel::traductor()->trans('fuera_de_calendario')).") as llamado_nombre,
						vw_mesas_examen.mesa_examen_fecha as fecha,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) || ' ' || cast(vw_mesas_examen.mesa_examen_hora_inicio as time) || ' - ' || cast(vw_mesas_examen.mesa_examen_hora_finalizacion as time) as fecha_formato_visual,
						get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa) as espacio_nombre,
						sga_evaluaciones.evaluacion as evaluacion,
						0 as solicitar_libro,
						(SELECT count(*) FROM sga_insc_examen WHERE llamado_mesa = vw_mesas_examen.llamado_mesa) as cant_inscriptos,			
						f_instancias_mesa_examen(vw_mesas_examen.mesa_examen) as instancias,
						(vw_mesas_examen.mesa_examen_fecha || ' ' || vw_mesas_examen.mesa_examen_hora_inicio)::timestamp as fecha_con_hora
				FROM sga_docentes_mesa_llamado
				JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
				JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad
				JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
				JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
				
				WHERE 	sga_docentes_mesa_llamado.docente = {$parametros['docente']} 
                  $filtro_fecha
                  AND COALESCE(vw_mesas_examen.turno_examen_fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
				  AND sga_evaluaciones_tipos.automatica = 'S'
				  AND vw_mesas_examen.llamado_mesa_estado = 'A'
						
				ORDER BY vw_mesas_examen.mesa_examen_elemento_nombre,
				         vw_mesas_examen.mesa_examen_fecha DESC, 
				         vw_mesas_examen.mesa_examen_hora_inicio
						 
				";
		
		$datos = kernel::db()->consultar($sql);
		
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['llamado_mesa']);
			$nuevo[$id][catalogo::id] = $id;
			$nuevo[$id] = $dato;

		}
		return $nuevo;
	}
	
	/**
	 *
	 * parametros: docente, turno, actividad, ubicacion, fuera_calendario, turnos_examen_no_finalizados
	 * param_null: turno, actividad, ubicacion, fuera_calendario, turnos_examen_no_finalizados
	 * no_quote: fuera_calendario, turnos_examen_no_finalizados
	 * cache: no
	 * filas: n
	 */
	function lista_mesas_examen_docente_dentro_o_fuera_calendario($parametros)
	{
		// Filtro por si es una mesa dentro de un turno de examen o fuera de calendario 
		if ($parametros['fuera_calendario']) {
			$where = " AND vw_mesas_examen.mesa_examen_en_turno = 'N'";
		} else {
			$where = " AND vw_mesas_examen.mesa_examen_en_turno = 'S'";
		}
		
		// Filtro por Actividad    
		if (!empty($parametros['actividad'])) {
			$where .= " AND vw_mesas_examen.mesa_examen_elemento = {$parametros['actividad']}";
		}
		// Filtro por Ubicacion    
		if (!empty($parametros['ubicacion'])) {
			$where .= " AND vw_mesas_examen.mesa_examen_ubicacion = {$parametros['ubicacion']}";
		}

		// Turnos de examen no finalizados ?
		if ((!empty($parametros['turnos_examen_no_finalizados'])) && ($parametros['turnos_examen_no_finalizados'])) {
			// $where .= " AND	vw_mesas_examen.anio_academico IN (SELECT anio_academico FROM vw_turnos_examen WHERE turno_examen_fecha_fin >= CURRENT_DATE) ";
			// $where .= " AND	vw_mesas_examen.turno_examen_fecha_fin >= CURRENT_DATE) ";
			$where .= " AND	vw_mesas_examen.turno_examen_fecha_inactivacion >= CURRENT_DATE ";
		}
		
		// Filtro por turno
		if (!empty($parametros['turno'])) {
			$where .= " AND vw_mesas_examen.turno_examen = {$parametros['turno']}";
		}
		
		// Lista mesas de examen donde se encuentra el docente.
		// Mesas de examen dentro y fuera de calendario
		$sql = "SELECT		vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
							vw_mesas_examen.mesa_examen as mesa_examen,
							vw_mesas_examen.llamado_mesa as llamado_mesa,
							vw_mesas_examen.mesa_examen_elemento as actividad,
							sga_elementos.codigo as actividad_codigo,
							vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
							vw_mesas_examen.mesa_examen_elemento_nombre || ' (' || sga_elementos.codigo || ')' AS actividad_descr,
							vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							COALESCE(vw_mesas_examen.turno_examen_nombre, ".kernel::db()->quote(kernel::traductor()->trans('fuera_de_calendario')).") as turno_examen_nombre,
							COALESCE(vw_mesas_examen.llamado_nombre, ".kernel::db()->quote(kernel::traductor()->trans('fuera_de_calendario')).") as llamado_nombre,
							vw_mesas_examen.mesa_examen_fecha as fecha,
							to_char(vw_mesas_examen.mesa_examen_fecha,'" . comunes::formato_fecha_visual . "') || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), '" . comunes::formato_hora_visual . "') as fecha_formato_visual,
							get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa) as espacio_nombre,
							sga_evaluaciones.evaluacion as evaluacion,
							0 as solicitar_libro,
							(vw_mesas_examen.mesa_examen_fecha || ' ' || vw_mesas_examen.mesa_examen_hora_inicio)::timestamp as fecha_con_hora
				
				FROM 		sga_docentes_mesa_llamado
							JOIN vw_mesas_examen ON sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa
							JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad
							JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
							JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
							JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
							
				WHERE 		sga_docentes_mesa_llamado.docente = {$parametros['docente']}
						AND COALESCE(vw_mesas_examen.turno_examen_fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
						AND sga_evaluaciones_tipos.automatica = 'S'
						AND vw_mesas_examen.llamado_mesa_estado = 'A'
						$where
				ORDER BY	vw_mesas_examen.mesa_examen_elemento_nombre,
				            vw_mesas_examen.mesa_examen_fecha, 
				            vw_mesas_examen.mesa_examen_hora_inicio
				";
		$datos = kernel::db()->consultar($sql);
		
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['llamado_mesa']);
			$nuevo[$id][catalogo::id] = $id;
			$nuevo[$id] = $dato;
		}
		return $nuevo;
	}

	/**
	 * parametros: docente
	 * cache: memoria
	 * filas: n
	 */
	function lista_mesas_examen_mensajes($parametros)
	{
		
		$si = kernel::db()->quote(\comunes::si);
		$no = kernel::db()->quote(\comunes::no);
		$mesa_estado_activo = kernel::db()->quote(\mesa_de_examen::mesa_estado_activo);
		$fuera_de_calendario = kernel::db()->quote(kernel::traductor()->trans('fuera_de_calendario'));
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		
		// https://repositorio.siu.edu.ar/trac/g3w2/ticket/1394
		// Lista mesas de examen donde se encuentra el docente.
		// Mesas de examen dentro y fuera de calendario
		$sql = "SELECT 	vw_mesas_examen.llamado_mesa,
						vw_mesas_examen.mesa_examen,
						vw_mesas_examen.mesa_examen_nombre,
						(SELECT count(*) FROM sga_insc_examen WHERE llamado_mesa = vw_mesas_examen.llamado_mesa) as cant_inscriptos,
						sga_elementos.codigo as actividad_codigo,
						vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
						COALESCE(vw_mesas_examen.turno_examen_nombre, $fuera_de_calendario) as turno_examen_nombre,
						COALESCE(vw_mesas_examen.llamado_nombre, $fuera_de_calendario) as llamado_nombre,
						vw_mesas_examen.mesa_examen_fecha as fecha,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_hora_visual) as fecha_formato_visual,
						sga_ubicaciones.ubicacion as ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre
				FROM sga_docentes_mesa_llamado
				JOIN vw_mesas_examen	ON (sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa)	
				JOIN sga_elementos		ON (sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento)
				JOIN sga_ubicaciones	ON (sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion)
				
				WHERE sga_docentes_mesa_llamado.docente = {$parametros['docente']}
				  AND ((vw_mesas_examen.mesa_examen_en_turno = $si AND vw_mesas_examen.turno_examen_fecha_inactivacion >= CURRENT_DATE) OR
				       (vw_mesas_examen.mesa_examen_en_turno = $no AND (vw_mesas_examen.mesa_examen_fecha BETWEEN (CURRENT_DATE - 30) AND (CURRENT_DATE + 30)))
				      )
				  AND COALESCE(vw_mesas_examen.turno_examen_fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
				  AND vw_mesas_examen.llamado_mesa_estado = $mesa_estado_activo
				ORDER BY	vw_mesas_examen.mesa_examen_fecha,
							vw_mesas_examen.mesa_examen_hora_inicio
				";
				
		$datos = kernel::db()->consultar($sql);
		
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['llamado_mesa']);
			$nuevo[$id] = $dato;
		}
		return $nuevo;
	}
	
	/**
	 * parametros: llamado_mesa, incluir_otros_alumnos, evaluacion
         * param_null: incluir_otros_alumnos, evaluacion
	 * no_quote: llamado_mesa, incluir_otros_alumnos, evaluacion
	 * cache: no
	 * filas: n
	 */
	function alum_inscriptos_examen($parametros)
	{
		// Alumnos inscriptos en una mesa de examen. Solo alumnos Activos.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

                $otros_alumnos_sql = "";
                // Alumnos agregados desde la solapa "Otros Alumnos" o "Acta de Examen fuera de calendario"
                if (!empty($parametros['incluir_otros_alumnos'])) {
                    $otros_alumnos_sql = "
                    UNION
                    
                    SELECT
                       null AS inscripcion,
                       sga_alumnos.legajo AS legajo,
                       sga_alumnos.persona,
                       sga_alumnos.alumno AS alumno,
                       vw_personas.apellido_nombres_elegido AS nombre_alumno,
                       vw_personas.tipo_nro_documento AS tipo_nro_documento,
                       vw_personas.id_imagen AS id_imagen,
                       sga_alumnos.propuesta AS propuesta,
                       sga_propuestas.codigo AS propuesta_codigo,
                       sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
                       {$this->subquery_email()},
                       {$this->subquery_telefono()},
                       sga_eval_detalle_examenes.fecha AS fecha_inscripcion,
                       to_char(sga_eval_detalle_examenes.fecha, $formato_fecha_visual) AS fecha_inscripcion_display,
                       sga_instancias.nombre AS instancia_nombre,
                       '".\inscripcion_examen::estado_aceptada_descr."' AS estado_inscripcion,
                       null AS nro_transaccion,
                       2 AS orden_query_union
                    FROM sga_eval_detalle_examenes
                    JOIN sga_instancias ON (sga_instancias.instancia = sga_eval_detalle_examenes.instancia)
                    JOIN sga_alumnos ON (sga_alumnos.alumno = sga_eval_detalle_examenes.alumno)
                    JOIN sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
                    JOIN vw_personas ON (vw_personas.persona = sga_alumnos.persona)
                    WHERE sga_eval_detalle_examenes.evaluacion = {$parametros['evaluacion']}";
                }

                // https://www.geeksforgeeks.org/postgresql-distinct-on-expression/
		$sql = "
                    SELECT DISTINCT ON (nombre_alumno) nombre_alumno, *
                    FROM (
                        SELECT
			   sga_insc_examen.inscripcion as inscripcion,
			   sga_alumnos.legajo as legajo,
			   sga_alumnos.persona,
			   sga_alumnos.alumno as alumno,
			   vw_personas.apellido_nombres_elegido as nombre_alumno,
			   vw_personas.tipo_nro_documento as tipo_nro_documento,
			   vw_personas.id_imagen as id_imagen,
			   sga_alumnos.propuesta as propuesta,
			   sga_propuestas.codigo as propuesta_codigo,
			   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
			   {$this->subquery_email()},
                           {$this->subquery_telefono()},
			   sga_insc_examen.fecha_inscripcion as fecha_inscripcion,
			   to_char(sga_insc_examen.fecha_inscripcion, $formato_fecha_visual) as fecha_inscripcion_display,
			   sga_instancias.nombre as instancia_nombre,
			   sga_inscripciones_estados.nombre as estado_inscripcion,
			   sga_insc_examen.nro_transaccion as nro_transaccion,
                           1 AS orden_query_union
			FROM sga_insc_examen 
			JOIN sga_instancias ON sga_instancias.instancia = sga_insc_examen.instancia
			JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_examen.estado
			JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_examen.alumno
			JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
			JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
			WHERE sga_insc_examen.llamado_mesa = {$parametros['llamado_mesa']}

                        {$otros_alumnos_sql}

                        ORDER BY nombre_alumno, orden_query_union

                    ) AS tabla_final";
		return kernel::db()->consultar($sql);
	}

        /**
	 * parametros: docente, id_acta, actividad, ubicacion, turnos_examen_no_finalizados
	 * param_null: id_acta, actividad, ubicacion, turnos_examen_no_finalizados
	 * no_quote: turnos_examen_no_finalizados
	 * cache: no
	 * filas: n
	 */
	function lista_actas_fc_docente($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$docente = $parametros['docente'];
		
		if (!empty($parametros['id_acta'])) {
			$where = ' AND sga_actas.id_acta = '.$parametros['id_acta'];
		} else {
			$where = '';
		}
		
		// Filtro por Actividad    
		if (!empty($parametros['actividad'])) {
			// Recupera comisiones de la actividad seleccionada
			$where .= " AND sga_elementos.elemento = {$parametros['actividad']}";
		}
		// Filtro por Ubicacion    
		if (!empty($parametros['ubicacion'])) {
			// Recupera comisiones de la ubicacion seleccionada
			$where .= " AND sga_ubicaciones.ubicacion = {$parametros['ubicacion']}";
		}

		// Turnos de examen no finalizados ?
		if ((!empty($parametros['turnos_examen_no_finalizados'])) && ($parametros['turnos_examen_no_finalizados'])) {
			// $where .= " AND	vw_mesas_examen.anio_academico IN (SELECT anio_academico FROM vw_turnos_examen WHERE turno_examen_fecha_fin >= CURRENT_DATE)";
			// $where .= " AND	vw_mesas_examen.turno_examen_fecha_fin >= CURRENT_DATE) ";
			$where .= " AND	vw_mesas_examen.turno_examen_fecha_inactivacion >= CURRENT_DATE ";
		}
		
		$sql = "SELECT
					sga_actas.id_acta,
					sga_elementos.nombre || ' (' || sga_elementos.codigo || ')' AS actividad_descr,
					sga_actas.nro_acta,
					to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha,
					sga_ubicaciones.nombre AS ubicacion_descr,
					sga_docentes_mesa_llamado.docente
				FROM
					sga_docentes_mesa_llamado
					JOIN vw_mesas_examen ON sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa
					JOIN sga_actas ON sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
					JOIN sga_ubicaciones ON vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_elementos ON vw_mesas_examen.mesa_examen_elemento = sga_elementos.elemento
				WHERE sga_docentes_mesa_llamado.docente = $docente  
				  AND vw_mesas_examen.llamado_mesa_estado = 'A'
				  AND sga_actas.origen = 'E'             
				  AND vw_mesas_examen.mesa_examen_en_turno = 'N'
				  $where
				ORDER BY 
						sga_elementos.nombre,
						vw_mesas_examen.mesa_examen_fecha, 
						vw_mesas_examen.mesa_examen_hora_inicio
			";
		
		$datos = kernel::db()->consultar($sql);
		
		$nuevo = array();
		foreach ($datos as $dato) {
			$clave = $dato['id_acta'];
			$id = catalogo::generar_id($clave);
			$nuevo[$id][catalogo::id] = $id;
			$nuevo[$id] = $dato;

		}
		return $nuevo;
	}
	
	/**
	 * parametros: id_acta
	 * cache: no
	 * filas: n
	 */
	function alum_inscriptos_acta_fc($parametros)
	{
		$id_acta = $parametros['id_acta'];
		$estado_cerrada = kernel::db()->quote(\acta::estado_cerrada);

		$sql = "(
					SELECT 
						sga_alumnos.alumno,
						sga_alumnos.persona,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres_elegido as apellido_nombres,
						vw_personas.tipo_nro_documento,
						sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado
					FROM 
						sga_eval_detalle_examenes
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle_examenes.alumno
						JOIN vw_personas ON vw_personas.persona	= sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					WHERE sga_eval_detalle_examenes.id_acta = $id_acta
				)
				UNION ALL
				(
					SELECT 
						sga_alumnos.alumno,
						sga_alumnos.persona,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres_elegido  as apellido_nombres,
						vw_personas.tipo_nro_documento,
						sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado
					FROM 
						sga_actas
						JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_actas_detalle.alumno
						JOIN vw_personas ON vw_personas.persona	= sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					WHERE sga_actas.id_acta = $id_acta
					  AND sga_actas.estado = $estado_cerrada
					  AND sga_actas_detalle.estado = 'A'
					  AND sga_actas_detalle.rectificado = 'N'
				)
				ORDER BY 
					apellido_nombres
				;";

		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: id_acta, origen
	 * cache: no
	 * filas: n
	 */
	function get_datos_cabecera_acta($parametros)
	{
		// Datos de la cabecera de las actas de Regulares/Promociones/Examenes. Reporte de Actas.
		// origen: R = Acta de Regulares / P = Acta de Promociones / E = Examenes

		// Recupera Actas de Regulares seg�n filtro ingresado
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		IF ($parametros['origen'] == "'R'" OR  $parametros['origen'] == "'P'") {
			// Cabecera del Acta de Regulares y Promociones
			$sql = "SELECT
						sga_periodos.anio_academico as anio_academico,
						sga_periodos.nombre as periodo_lectivo_nombre,
						sga_elementos.codigo as actividad_codigo,
						f_nombre_actividad_comision(sga_actas.comision, sga_comisiones.elemento, 1) as actividad_nombre,
						sga_comisiones.nombre as comision_nombre,
						sga_comisiones.comision as comision,
						sga_actas.id_acta as id_acta,
						sga_actas.nro_acta as nro_acta,
						to_char(sga_actas.fecha_cierre, $formato_fecha_visual) as fecha_cierre,
						CASE sga_actas.tipo_acta
							WHEN 'N' THEN 'Normal'
							WHEN 'R' THEN 'Rectificativa'
						END as tipo_acta,
						sga_actas.version,
						sga_actas_estados.nombre as estado_acta,
						sga_ubicaciones.nombre as ubicacion_nombre,
						sga_actas.observaciones as observaciones,
						f_docentes_comision(sga_actas.comision, true) as docentes,
						f_instancias_acta(sga_actas.id_acta) as instancias,
						f_libros_acta(sga_actas.id_acta, 2) as libro
					FROM
						sga_actas
						JOIN sga_comisiones ON sga_comisiones.comision = sga_actas.comision
						JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
						JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
						JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
						JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
						JOIN sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado
					WHERE sga_actas.id_acta = {$parametros['id_acta']} 
				";
		} elseif ($parametros['origen'] == "'E'") {
			// Cabecera del Acta de Examen
			$sql = "SELECT
						vw_mesas_examen.anio_academico as anio_academico,
						vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
						vw_mesas_examen.llamado_nombre as llamado_nombre,
						vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
						sga_elementos.codigo as actividad_codigo,
						vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen,
						sga_actas.id_acta as id_acta,
						sga_actas.nro_acta as nro_acta,
						to_char(sga_actas.fecha_cierre, $formato_fecha_visual) as fecha_cierre,
						CASE sga_actas.tipo_acta
							WHEN 'N' THEN 'Normal'
							WHEN 'R' THEN 'Rectificativa'
						END as tipo_acta,
						sga_actas.version,
						sga_actas_estados.nombre as estado_acta,
						sga_ubicaciones.nombre as ubicacion_nombre,
						sga_actas.observaciones as observaciones,
						f_docentes_mesa_examen(sga_actas.llamado_mesa, true) as docentes,
						f_instancias_acta(sga_actas.id_acta) as instancias,
						f_libros_acta(sga_actas.id_acta, 2) as libro
					FROM 
						sga_actas
						JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_actas.llamado_mesa
						JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
						JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
						JOIN sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado
					WHERE sga_actas.id_acta = {$parametros['id_acta']} 
					";
		}
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}


	/**
	 * parametros: docente, origen_acta, anio_academico, periodo_lectivo, actividad_codigo
	 * param_null: origen_acta, anio_academico, periodo_lectivo, actividad_codigo
	 * cache: no
	 * filas: n
	 */
	function lista_actas_cursadas_promociones($parametros)
	{
		// Listado de actas de regulares o promociones en donde se encuentra el docente.
		// origen_acta = R = Acta de Cursadas / P = Acta de Promociones / NULL = Acta de Regulares y Promociones

		// ********************** Filtros de b�squeda ***************************
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		// A�o academico
		if (empty($parametros["anio_academico"])) {
			$where_anio = '';
		} else {
			$where_anio = " AND vw_comisiones.anio_academico = {$parametros['anio_academico']}";
		}
		// Periodo Lectivo
		if (empty($parametros["periodo_lectivo"])) {
			$where_periodo_lectivo = '';
		} else {
			$where_periodo_lectivo = " AND vw_comisiones.periodo_lectivo = {$parametros['periodo_lectivo']}";
		}

//		// Nombre actividad	
//		if (empty($parametros["actividad_nombre"])){
//			$where_actividad = '';
//		} else {
//			$term = kernel::db()->quote_like(strtolower(substr($parametros['actividad_nombre'], 1, -1)), '%', '%');
//			$where_actividad = " AND sga_elementos.nombre ILIKE $term";
//		}

		// Codigo actividad	
		if (empty($parametros["actividad_codigo"])) {
			$where_actividad = '';
		} else {
			$where_actividad = " AND vw_comisiones.elemento_codigo = {$parametros['actividad_codigo']}";
		}

		// Actas de Regulares o Promociones, o ambas.
		if (empty($parametros["origen_acta"])) {
			$where_origen_acta = '';
		} else {
			$where_origen_acta = " AND sga_actas.origen = {$parametros['origen_acta']}";
		}
		// ********************** FIN Filtros de b�squeda ***************************

		// Recupera Actas de Regulares según filtro ingresado
		$sql = "SELECT
						vw_comisiones.anio_academico as anio_academico,
						vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
						vw_comisiones.elemento_codigo as actividad_codigo,
						vw_comisiones.elemento_nombre as actividad_nombre,
						vw_comisiones.comision_nombre as comision_nombre,
						vw_comisiones.comision as comision,
						sga_actas.id_acta as id_acta,
						sga_actas.origen as origen,
						sga_actas.nro_acta as nro_acta,
						to_char(sga_actas.fecha_cierre, $formato_fecha_visual) as fecha_cierre,
						CASE sga_actas.tipo_acta
							WHEN 'N' THEN 'Normal'
							WHEN 'R' THEN 'Rectificativa'
						END as tipo_acta,
						sga_actas_estados.nombre as estado_acta,
						sga_ubicaciones.nombre as ubicacion_nombre,
						vw_comisiones.fecha_inicio as periodo_lectivo_fecha_inicio

					FROM sga_docentes_comision
					JOIN vw_comisiones ON vw_comisiones.comision = sga_docentes_comision.comision
					JOIN sga_actas ON vw_comisiones.comision = sga_actas.comision
					JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
					JOIN sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado

					WHERE sga_docentes_comision.docente = {$parametros['docente']} 
					AND sga_actas.estado <> 'B' -- Anulada
					$where_origen_acta
					$where_actividad 
					$where_anio
					$where_periodo_lectivo
					ORDER BY 1, 
							vw_comisiones.fecha_inicio, 	
							4  -- Nombre de la actividad 
							
				";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['url_acta'] = catalogo::generar_id($valor['id_acta']);
			$datos[$clave]['actividad_nombre_completo'] = $valor['actividad_nombre'] . " (" . $valor['actividad_codigo'] . ")";
		}
		return $datos;
	}

	/**
	 * parametros: id_acta
	 * cache: no
	 * filas: n
	 */
	function detalle_acta_cursada_promocion($parametros)
	{
		// Listado de Alumnos en un acta de Regulares o de Promocion en estado abierta o cerrada
		$orden_acta_abierta = '2'; // Esto debe recuperarse de un parametro del sistema. Solo usado en Actas abiertas
		$mostrar_alumnos = 'T'; // Todos los alumnos del acta

		// Recupera todos los alumnos del Acta
		$sql = "SELECT * FROM get_alumnos_acta({$parametros['id_acta']},'" . $orden_acta_abierta . "', '" . $mostrar_alumnos . "', NULL, NULL)";
		$datos = kernel::db()->consultar($sql);
		Return $datos;
	}

	/**
	 * parametros: docente, anio_academico, turno_examen, actividad_nombre
	 * param_null: anio_academico, turno_examen, actividad_nombre
	 * cache: no
	 * filas: n
	 */
	function lista_actas_examenes($parametros)
	{
		// ********************** Filtros de b�squeda ***************************
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		// A�o academico
		if (empty($parametros["anio_academico"])) {
			$where_anio = '';
		} else {
			$where_anio = " AND vw_mesas_examen.anio_academico = {$parametros['anio_academico']}";
		}
		// Turno Examen
		if (empty($parametros["turno_examen"])) {
			$where_turno_examen = '';
		} else {
			$where_turno_examen = " AND vw_mesas_examen.turno_examen = {$parametros['turno_examen']}";
		}

		// Nombre actividad
		if (empty($parametros["actividad_nombre"]) or $parametros["actividad_nombre"] == "''") {
			$where_actividad = '';
		} else {
			$where_actividad = " AND sga_elementos.nombre LIKE " . $parametros["actividad_nombre"] ;
		}
		// ********************** FIN Filtros de b�squeda ***************************

		// Recupera Actas de Examen donde se encuentra el docente
		$sql = "SELECT
                       vw_mesas_examen.anio_academico as anio_academico,
                       vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
                       vw_mesas_examen.llamado_nombre as llamado_nombre,
                       vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
                       sga_elementos.codigo as actividad_codigo,
                       vw_mesas_examen.mesa_examen_elemento_nombre as actividad_nombre,
                       to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen,
                       sga_actas.id_acta as id_acta,
					   sga_actas.origen as origen,
                       sga_actas.nro_acta as nro_acta,
					   to_char(sga_actas.fecha_cierre, $formato_fecha_visual) as fecha_cierre,
                       CASE sga_actas.tipo_acta
                         WHEN 'N' THEN 'Normal'
                         WHEN 'R' THEN 'Rectificativa'
                       END as tipo_acta,
                       sga_actas_estados.nombre as estado_acta,
                       sga_ubicaciones.nombre as ubicacion_nombre,
					   vw_mesas_examen.mesa_examen_fecha as fecha_examen_orden,
					   vw_mesas_examen.mesa_examen_hora_inicio as hora_inicio_orden
                       
                  FROM sga_docentes_mesa_llamado
                  JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
				  JOIN sga_actas ON sga_actas.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
       	          JOIN sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado
                  JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
       	          JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
					   
                 WHERE sga_docentes_mesa_llamado.docente = {$parametros['docente']} 
				   AND COALESCE(vw_mesas_examen.turno_examen_fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
       	           AND sga_actas.origen = 'E' -- Actas de Examen.
       	           AND sga_actas.estado <> 'B' -- Anulada
                   $where_actividad 
                   $where_anio
       	           $where_turno_examen
				   ORDER BY 1, 
							vw_mesas_examen.mesa_examen_fecha,
							vw_mesas_examen.mesa_examen_hora_inicio,
							6 
                ";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['url_acta'] = catalogo::generar_id($valor['id_acta']);
			$datos[$clave]['actividad_nombre_completo'] = $valor['actividad_nombre'] . " (" . $valor['actividad_codigo'] . ")";
		}
		Return $datos;
	}

	/**
	 * parametros: id_acta
	 * cache: no
	 * filas: n
	 */
	function detalle_acta_examen($parametros)
	{

		// Listado de Alumnos en un acta de Examen en estado abierta o cerrada
		$orden_acta_abierta = '2'; // Esto debe recuperarse de un parametro del sistema. Solo usado en Actas abiertas
		$mostrar_alumnos = 'T'; // Todos los alumnos del acta

		$sql = "SELECT * FROM get_alumnos_acta({$parametros['id_acta']},'" . $orden_acta_abierta . "', '" . $mostrar_alumnos . "', NULL, NULL)";
		$datos = kernel::db()->consultar($sql);
		Return $datos;
	}


	private function mensaje_turno($id)
	{
		switch ($id) {
			case "T":
				return "Tarde";
				break;
			case "N":
				return "Noche";
				break;
			case "M":
				return "Ma�ana";
				break;
			default:
				return "No informa";
				break;
		}
	}

	/**
	 * parametros: apellido
     * no_quote: apellido
	 * cache: no
	 * filas: n
	 */
	public function get_docentes_activos($parametros)
	{
		$term = kernel::db()->quote_like($parametros['apellido'], '%', '%');

		$sql = "SELECT	p.persona, p.apellido, p.nombres, d.legajo, d.docente
				FROM	mdp_personas as p
						JOIN sga_docentes as d ON d.persona = p.persona
				WHERE	d.estado = 'A'
				AND		(f_limpiar_acentos(d.legajo) ILIKE $term OR
						f_limpiar_acentos(p.apellido) ILIKE $term OR
						f_limpiar_acentos(p.nombres) ILIKE $term)
				ORDER BY p.apellido, p.nombres";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $clave => $dato){
			$nuevo[$clave] = $dato;
			$nuevo[$clave][catalogo::id] = catalogo::generar_id($dato['docente']);
		}
		return $nuevo;
	}

	/**
	 * parametros: docente
	 * cache: memoria
	 * filas: 1
	 */
	public function datos_basicos($parametros)
	{
		$sql = "SELECT	vw_personas.persona, 
						vw_personas.apellido, 
						vw_personas.nombres,
						vw_personas.apellido_nombres,
						sga_docentes.legajo, 
						sga_docentes.docente, 
						sga_docentes.estado
				FROM	sga_docentes 
				JOIN	vw_personas ON sga_docentes.persona = vw_personas.persona
				WHERE	sga_docentes.docente = {$parametros['docente']}";
		return kernel::db()->consultar_fila($sql);
	}


	/**
	 * parametros: docente
	 * cache: memoria
	 * filas: 1
	 */
	public function get_ubicaciones_por_propuestas($parametros)
	{
		$sql = "SELECT DISTINCT sga_ubicaciones.ubicacion, sga_ubicaciones.nombre
				FROM	sga_docentes_ra
				JOIN	sga_propuestas_ra		ON (sga_propuestas_ra.responsable_academica = sga_docentes_ra.responsable_academica)
				JOIN	sga_propuestas_oferta 	ON (sga_propuestas_oferta.propuesta = sga_propuestas_ra.propuesta)
				JOIN	sga_ubicaciones 		ON (sga_ubicaciones.ubicacion = sga_propuestas_oferta.ubicacion)

				WHERE	sga_docentes_ra.docente = {$parametros['docente']}
				ORDER BY sga_ubicaciones.nombre
				";
		return kernel::db()->consultar($sql);

	}

	/**
	 *    parametros: term, docente
	 *    param_null: term, docente
	 *    cache: memoria
	 *
	 *
	 */
	function get_docentes_actas_fuera_calendario($parametros)
	{
		$where = '';
		if (!empty($parametros['term'])) {
			//Elimino los quotes
			$term_sin_quotes = substr($parametros['term'], 1, -1);
			//Agrego los % %
			$term = kernel::db()->quote_like($term_sin_quotes, '%', '%');
			$where = " AND (f_limpiar_acentos(vw_personas.apellido_nombres) ILIKE $term OR
					      f_limpiar_acentos(sga_docentes.legajo)	ILIKE $term OR
						  f_limpiar_acentos(vw_personas.nro_documento)	ILIKE $term )";
		} else {
			if (!empty($parametros['docente'])) {
				$where = " AND sga_docentes.docente = {$parametros['docente']}";
			}
		}

		$sql = "SELECT	sga_docentes.docente,
						sga_docentes.legajo,
						vw_personas.apellido_nombres,
						vw_personas.nro_documento,
						vw_personas.tipo_nro_documento,
						f_limpiar_acentos(vw_personas.apellido_nombres)
				FROM 	sga_docentes
				JOIN	vw_personas		ON (vw_personas.persona = sga_docentes.persona)
				WHERE sga_docentes.estado = '" . \comunes::estado_activo . "' " . $where . "
				ORDER BY 6";
		if (!empty($parametros['term'])) {
			return kernel::db()->consultar($sql);
		}

		return kernel::db()->consultar_fila($sql);

	}

	/**
	 *    cache: memoria
	 *
	 *
	 */
	function get_tribunales_roles()
	{
		$sql = "SELECT		sga_tribunal_roles.rol,
							sga_tribunal_roles.nombre
				FROM		sga_tribunal_roles
				ORDER BY	sga_tribunal_roles.nombre
				";
		return kernel::db()->consultar($sql);
	}


	/**
	 *
	 *    cache: memoria
	 *
	 */
	function get_lista_ubicaciones()
	{

		$sql = "SELECT 	sga_ubicaciones.ubicacion as ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre
				FROM sga_ubicaciones;";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: term, comisiones_activas, turnos_examen_no_finalizados
	 * param_null: comisiones_activas, turnos_examen_no_finalizados
	 * no_quote: term, comisiones_activas, turnos_examen_no_finalizados
	 * cache: no
	 * filas: n
	 */
	function buscar_alumno($parametros)
	{
		$termino = kernel::db()->quote_like($parametros['term'], '%', '%');
		$docente = kernel::db()->quote(kernel::persona()->get_id_docente());
		
		// Comisiones activas ?
		if ((!empty($parametros['comisiones_activas'])) && ($parametros['comisiones_activas'])) {
			$where_comisiones_activas = " AND vw_comisiones.fecha_inactivacion >= CURRENT_DATE";
		} else {
			$where_comisiones_activas = '';
		}

		$where_turnos_examen_no_finalizados = '';

		// Turnos de examen no finalizados ?
		if ((!empty($parametros['turnos_examen_no_finalizados'])) && ($parametros['turnos_examen_no_finalizados'])) {
			$where_turnos_examen_no_finalizados = " AND	vw_mesas_examen.anio_academico IN (
																		SELECT vw_turnos_examen.anio_academico
																		FROM vw_turnos_examen
																		WHERE vw_turnos_examen.turno_examen_fecha_fin >= CURRENT_DATE
																		)";
		}
		
		$sql = "SELECT 
					DISTINCT vw_personas.persona,
					vw_personas.apellido_nombres_elegido || ' (' || vw_personas.tipo_nro_documento || ')' || COALESCE(' | Legajo: ' || sga_alumnos.legajo, '') AS persona_descr
				FROM 
					sga_alumnos
					JOIN vw_personas ON (vw_personas.persona = sga_alumnos.persona)
				WHERE
					(
						f_limpiar_acentos(vw_personas.apellido_nombres_elegido) ILIKE $termino
						OR f_limpiar_acentos(sga_alumnos.legajo) ILIKE $termino
						OR f_limpiar_acentos(vw_personas.nro_documento) ILIKE $termino
					)
					AND sga_alumnos.alumno IN 
					(
						(-- Alumnos de las comisiones del docente
							SELECT 
								sga_insc_cursada.alumno
							FROM 
								sga_insc_cursada
								JOIN sga_docentes_comision	ON	(sga_docentes_comision.comision	= sga_insc_cursada.comision)
								JOIN vw_comisiones			ON	(sga_insc_cursada.comision		= vw_comisiones.comision)
							WHERE
								sga_docentes_comision.docente = $docente
								$where_comisiones_activas
						)
						UNION ALL
						(-- Alumnos de las mesas del docente
							SELECT 
								sga_insc_examen.alumno
							FROM 
								sga_insc_examen
								JOIN sga_docentes_mesa_llamado	ON	(sga_docentes_mesa_llamado.llamado_mesa	= sga_insc_examen.llamado_mesa)
								JOIN vw_mesas_examen			ON 	(vw_mesas_examen.llamado_mesa			= sga_insc_examen.llamado_mesa)
							WHERE
								sga_docentes_mesa_llamado.docente = $docente 
								$where_turnos_examen_no_finalizados
						)
						-- Alumnos de actas FC ---
						UNION ALL
						(-- Alumnos en actas abiertas del docente
							SELECT 
								sga_eval_detalle_examenes.alumno
							FROM
			 				   	sga_docentes_mesa_llamado	
			 				   	JOIN vw_mesas_examen 			ON 	(vw_mesas_examen.llamado_mesa 		= sga_docentes_mesa_llamado.llamado_mesa)
			 				   	JOIN sga_actas 					ON  (sga_actas.llamado_mesa 			= vw_mesas_examen.llamado_mesa) 	
			 				   	JOIN sga_eval_detalle_examenes	ON 	(sga_eval_detalle_examenes.id_acta 	= sga_actas.id_acta)
							WHERE
								sga_docentes_mesa_llamado.docente = $docente
								$where_turnos_examen_no_finalizados
						)
						UNION ALL
						(-- Alumnos en actas cerradas donde se encuentra el docente
							SELECT 
								sga_actas_detalle.alumno
							FROM 
								sga_docentes_mesa_llamado
								JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
								JOIN sga_actas ON sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
								JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
							WHERE
								sga_docentes_mesa_llamado.docente = $docente
								AND sga_actas.estado = 'C'
						)
						
					)
				ORDER BY
					persona_descr";
		
		$datos = kernel::db()->consultar($sql);
		
		if (!empty($datos)){
			foreach($datos as $key => $value) {
				$datos[$key][catalogo::id] = catalogo::generar_id($value['persona']);
			}
		}
		return $datos;
	}
	
	/**
	 * parametros: persona, comisiones_activas, turnos_examen_no_finalizados
	 * param_null: comisiones_activas, turnos_examen_no_finalizados
	 * no_quote: comisiones_activas, turnos_examen_no_finalizados
	 * cache: no
	 */
	function buscar_procedencia_alumno($parametros)
	{
		$docente = kernel::db()->quote(kernel::persona()->get_id_docente());
			
		// Comisiones activas ?
		if ((!empty($parametros['comisiones_activas'])) && ($parametros['comisiones_activas'])) {
			$where_comisiones_activas = " AND vw_comisiones.fecha_inactivacion >= CURRENT_DATE";
		} else {
			$where_comisiones_activas = '';
		}

		$where_turnos_examen_no_finalizados = '';

		// Turnos de examen no finalizados ?
		if ((!empty($parametros['turnos_examen_no_finalizados'])) && ($parametros['turnos_examen_no_finalizados'])) {
			$where_turnos_examen_no_finalizados = " AND	vw_mesas_examen.anio_academico IN (
																		SELECT vw_turnos_examen.anio_academico
																		FROM vw_turnos_examen
																		WHERE vw_turnos_examen.turno_examen_fecha_fin >= CURRENT_DATE
																		)";
		}

		$sql = "SELECT 
					sga_insc_cursada.comision
				FROM 
					vw_personas
					JOIN sga_alumnos			ON	(vw_personas.persona 			= sga_alumnos.persona)
					JOIN sga_insc_cursada		ON	(sga_alumnos.alumno 			= sga_insc_cursada.alumno)
					JOIN vw_comisiones			ON	(sga_insc_cursada.comision 		= vw_comisiones.comision)
					JOIN sga_docentes_comision	ON	(sga_docentes_comision.comision = sga_insc_cursada.comision)
				WHERE
					vw_personas.persona = {$parametros['persona']}
					AND sga_docentes_comision.docente = $docente
					$where_comisiones_activas";
		
		$datos_comision = kernel::db()->consultar_fila($sql);
		
		$sql = "SELECT 
					sga_insc_examen.llamado_mesa
				FROM 
					sga_alumnos
					JOIN vw_personas				ON	(vw_personas.persona 					= sga_alumnos.persona)
					JOIN sga_insc_examen			ON	(sga_alumnos.alumno 					= sga_insc_examen.alumno)
					JOIN vw_mesas_examen			ON	(vw_mesas_examen.llamado_mesa 			= sga_insc_examen.llamado_mesa)
					JOIN sga_docentes_mesa_llamado	ON	(sga_docentes_mesa_llamado.llamado_mesa = sga_insc_examen.llamado_mesa)
				WHERE
					vw_personas.persona = {$parametros['persona']}
					AND sga_docentes_mesa_llamado.docente = $docente
					$where_turnos_examen_no_finalizados";
		
		$datos_mesa = kernel::db()->consultar_fila($sql);
		
		$sql = "-- Actas abiertas donde esta asignado el docente y donde existe el alumno a buscar
				SELECT 
					sga_actas.id_acta
				FROM 
					sga_eval_detalle_examenes
					JOIN sga_alumnos				ON	(sga_eval_detalle_examenes.alumno 		= sga_alumnos.alumno)
					JOIN sga_actas					ON	(sga_eval_detalle_examenes.id_acta 		= sga_actas.id_acta)
					JOIN vw_mesas_examen			ON	(sga_actas.llamado_mesa 				= vw_mesas_examen.llamado_mesa)
					JOIN sga_docentes_mesa_llamado	ON	(sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa)
				WHERE
					sga_alumnos.persona = {$parametros['persona']}
					AND sga_docentes_mesa_llamado.docente = $docente
					$where_turnos_examen_no_finalizados
					
				UNION ALL
				
				-- Actas cerradas donde se encuentra el alumno
				SELECT 
					sga_actas.id_acta
				FROM 
					sga_alumnos
					JOIN sga_actas_detalle			ON	(sga_actas_detalle.alumno 				= sga_alumnos.alumno)
					JOIN sga_actas					ON	(sga_actas.id_acta 						= sga_actas_detalle.id_acta)
					JOIN vw_mesas_examen			ON	(sga_actas.llamado_mesa 				= vw_mesas_examen.llamado_mesa)
					JOIN sga_docentes_mesa_llamado	ON	(sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa)
				WHERE
					sga_alumnos.persona = {$parametros['persona']}
					AND sga_actas_detalle.estado = 'A'
					AND sga_actas_detalle.rectificado = 'N'
					AND sga_actas.estado = 'C'
					AND sga_docentes_mesa_llamado.docente = $docente
					$where_turnos_examen_no_finalizados
				";
		
		$datos_acta = kernel::db()->consultar_fila($sql);
		
		$salida = array(
			'comision' => !empty($datos_comision['comision']) ? $datos_comision['comision'] : false,
			'llamado_mesa' => !empty($datos_mesa['llamado_mesa']) ? $datos_mesa['llamado_mesa'] : false,
			'acta' => !empty($datos_acta['id_acta']) ? $datos_acta['id_acta'] : false,
		);
		
		return $salida;
	}

	/**
	* Lista de actividades de comisiones/mesas de examen donde estuvo asignado el docente (en term viene el dato ingresando en el combo)
	* parametros: term, docente
    * no_quote: term
	* cache: memoria
	* filas: n
	*/
	function lista_actividades($parametros)
	{
        $filtro_termino = "";

        if ($parametros['term'] != ""){

            $term = kernel::db()->quote_like($parametros['term'], '%', '%');

			$filtro_termino = " AND (
						f_limpiar_acentos(codigo) ILIKE $term OR
	    				f_limpiar_acentos(nombre) ILIKE $term
                        )";
        }


        // Recupera las materias de las comisiones y mesas donde estuvo alguna vez asignado el docente.
        $sql = "SELECT elemento, nombre, codigo
                FROM sga_elementos
                WHERE estado <> 'B'
                AND (elemento IN (SELECT c.elemento 
				                    FROM sga_docentes_comision as cd, sga_comisiones as c
                                   WHERE cd.docente = {$parametros['docente']}
                                     AND c.comision = cd.comision
                                   ) OR
                     elemento IN (SELECT me.elemento 
					                FROM sga_docentes_mesa_llamado as dll,  sga_llamados_mesa as llm, sga_mesas_examen as me
                                   WHERE dll.docente = {$parametros['docente']}
                                     AND llm.llamado_mesa = dll.llamado_mesa
                                     AND me.mesa_examen = llm.mesa_examen)
                    )  
                $filtro_termino
                ";

		$datos = kernel::db()->consultar($sql);

		$res = array();
		foreach ($datos as $dato){
			$paquete = array();
			$paquete['id'] = catalogo::generar_id($dato['elemento']);
			$paquete['elemento'] = $dato['elemento'];
			$paquete['codigo'] = $dato['codigo'];
			$paquete['nombre'] = $dato['nombre'];
			$paquete['label'] = "{$dato['nombre']} ({$dato['codigo']})";
			$res[] = $paquete;
		}
		return $res;
	}


    /**
     * parametros: formulario
     * cache: no
     * filas: n
     */

    function get_docentes_formulario ($parametros)
    {
        $sql = "SELECT  
					sga_docentes_comision.docente,
                    sga_docentes_comision.responsabilidad,
                    sga_docentes.legajo,
                    sga_docentes.persona,
                    mdp_personas.apellido || ' ' || mdp_personas.nombres as nombre_persona,
                    sga_docentes_responsabilidades.nombre as responsabilidad_nombre,
                    fi.item as elemento
					
                FROM gde_formularios as f
                JOIN gde_formulario_items as fi ON fi.formulario = f.formulario
                JOIN gde_items as i ON i.item = fi.item
                JOIN sga_docentes_comision ON (sga_docentes_comision.comision = f.comision AND sga_docentes_comision.docente = i.docente)
                JOIN sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
                JOIN mdp_personas ON mdp_personas.persona = sga_docentes.persona
                JOIN sga_docentes_responsabilidades ON sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad
				
                WHERE f.formulario = {$parametros['formulario']}
                ORDER BY sga_docentes_comision.responsabilidad";

        $docentes = kernel::db()->consultar($sql);

        foreach ($docentes as $key => $docente){
            $docentes[$key]['elemento_hash'] = catalogo::generar_id($docente['elemento']);
        }

        return $docentes;
    }


	/**
	 * Lista las clases de las comisiones en las que esta asignado el docente con fecha menor o igual a hoy.
	 * Usado desde el Home del docente.
	 * parametros: docente, filas
	 * no_quote: docente, filas
	 * cache: memoria
	 * cache_expiracion: 3600
	 * filas: n
	 */
    function get_clases_comisiones_docente($parametros)
	{
		// Permiso para cargar asistencia de alumnos en una clase.
		$derecho_registrar_asistencia = 'asistencia_editar';
		
		$cant_filas = " LIMIT 10 ";
		$filas = filter_var($parametros['filas'], FILTER_SANITIZE_NUMBER_INT);
		if ($filas > 0) {
			$cant_filas = " LIMIT $filas ";
		}		
		
		$sql = "SELECT	vw_comisiones.comision,
						vw_comisiones.comision_nombre as comision_nombre,
						vw_comisiones.elemento as actividad,
						vw_comisiones.elemento_nombre as actividad_nombre,
						vw_clases.clase,
						vw_clases.dia_semana as dia_semana,
						vw_clases.fecha as fecha,
						CASE 
						  WHEN vw_clases.fecha = CURRENT_DATE THEN 'Hoy'
						  WHEN vw_clases.fecha = CURRENT_DATE - 1 THEN 'Ayer'
						  WHEN vw_clases.fecha = CURRENT_DATE + 1 THEN 'Ma�ana'
						  ELSE to_char(vw_clases.fecha, 'DD/MM/YYYY')
						END as fecha_clase_desc, 
						to_char(vw_clases.fecha, 'DD/MM/YYYY') as fecha_clase,
						vw_clases.tipo_clase_nombre as tipo_clase_nombre,
						COALESCE(to_char(vw_clases.hora_inicio,'HH:MM'),'') as hs_comienzo_clase,
						COALESCE(to_char(vw_clases.hora_finalizacion,'HH:MM'),'') as hs_finalizacion_clase,
						vw_clases.cantidad_horas_dictadas as cantidad_horas_dictadas,
						get_espacios_clase(vw_clases.clase, 1) as espacio_desc,
						(SELECT CASE WHEN COUNT(1) > 0 THEN true ELSE false END 
						   FROM gdu_derechos_personas
						  WHERE entidad = vw_comisiones.entidad  
						    AND persona = sga_docentes.persona
							AND derecho = '$derecho_registrar_asistencia'
						) as puede_cargar_asistencia

				FROM	sga_docentes 
				JOIN	sga_docentes_comision ON sga_docentes_comision.docente = sga_docentes.docente
				JOIN	vw_comisiones ON vw_comisiones.comision = sga_docentes_comision.comision
				JOIN	vw_clases ON vw_clases.comision = sga_docentes_comision.comision   

				WHERE	sga_docentes.docente = {$parametros['docente']}
				  AND	vw_clases.valido = 1	
				  AND	vw_clases.fecha  <= CURRENT_DATE 
				  AND   vw_comisiones.fecha_inactivacion >= CURRENT_DATE
				  AND   COALESCE(vw_comisiones.fecha_publicacion_comisiones_docente, CURRENT_DATE) <= CURRENT_DATE
				ORDER BY vw_clases.fecha DESC, 
				         vw_clases.hora_inicio, 
						 vw_comisiones.elemento_nombre 
				$cant_filas
				;";
				
				$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
	
				return $datos;
		
	}	


	
}