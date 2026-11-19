<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Personas/Alumnos
 */
class co_alumnos_nsg extends co_alumnos
{
	//Newsol - 06/2025 Agrego el campo anio_cursada para las divisiones
	/**
 	  * Retorna los datos correspondientes a un alumno en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
      *	@param integer/array	alumno id o arreglo de ids de alumno/s.
    	*/
	function get_datos_alumno($alumno, $filtrar_perfil_de_datos=true)
	{
		if (is_array($alumno)) {
			$filtro = guarani_sql::armar_condicion_in($alumno, 'sga_alumnos', 'alumno');
		} else {
			$alumno = toba::db()->quote($alumno);
			$filtro = "sga_alumnos.alumno = $alumno";
		}
		// Se evita invocar al método 'get_listado' porque trae demasiadas columnas innecesarias para la carga externa.
		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						sga_alumnos.propuesta,
						sga_alumnos.regular,
						sga_alumnos.cnt_readmisiones,
						sga_alumnos.ubicacion,
						sga_alumnos.calidad,
						sga_alumnos.modalidad,
						sga_alumnos.division,
						sga_divisiones.nombre AS division_nombre,
						sga_propuestas.nombre AS propuesta_nombre,
						sga_alumnos.plan_version,
						sga_planes_versiones.plan,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						vw_personas.apellido,
						vw_personas.nombres,
						vw_personas.nro_documento,
						vw_personas.persona,
						sga_alumnos.anio_cursada
				FROM	sga_alumnos		
				JOIN	sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				LEFT JOIN sga_divisiones ON sga_alumnos.division = sga_divisiones.division
				WHERE	$filtro
		";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}
	
	//Newsol (NUEVO) - Devuelve el log de inscripciones de los alumnos y llevar registro de sus bajas a cursadas
	function get_log_inscripciones_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$si = comunes::si;
		$no = comunes::no;
		
		$tipo_insc_cursada = 'C';
		$tipo_insc_examen = 'E';
			
		$sql = "-- Insc a cursadas aceptadas y pendientes
				SELECT 	sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_codigo_y_nombre,
						'(' || vw_elementos_plan.codigo || ') ' || vw_elementos_plan.nombre as actividad_codigo_y_nombre,
						negocio_auditoria.logs_sga_insc_cursada.fecha_inscripcion as fecha_inscripcion,
						sga_comisiones.nombre as comision_o_mesa,
						sga_periodos.nombre as periodo_o_turno_nombre,
						f_instancias_insc_cursada(negocio_auditoria.logs_sga_insc_cursada.inscripcion) as instancia_nombre,
						sga_inscripciones_estados.nombre as estado_nombre,
						trim(negocio_auditoria.logs_sga_insc_cursada.auditoria_usuario) as usuario,
						negocio_auditoria.logs_sga_insc_cursada.auditoria_fecha as fecha,
						case negocio_auditoria.logs_sga_insc_cursada.auditoria_operacion when 'I' then 'Alta' else 'Baja' end as operacion,
						case negocio_auditoria.logs_sga_insc_cursada.interfaz when 1 then 'Gestión' else 'Autogestión' end as interfaz,
						CASE
							WHEN CURRENT_DATE <= sga_periodos_lectivos.fecha_fin_dictado THEN '$si'
							ELSE '$no'
						END as vigente,
						'$tipo_insc_cursada' as tipo_inscripcion

				FROM 	sga_alumnos,
						negocio_auditoria.logs_sga_insc_cursada,
						sga_comisiones
						LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra,
						sga_periodos_lectivos,
						sga_periodos,
						sga_inscripciones_estados,
						vw_elementos_plan,
						sga_propuestas
				WHERE  	sga_alumnos.persona = $persona

						AND sga_alumnos.alumno = negocio_auditoria.logs_sga_insc_cursada.alumno
						AND sga_comisiones.comision = negocio_auditoria.logs_sga_insc_cursada.comision
						AND sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
						AND sga_periodos.periodo = sga_periodos_lectivos.periodo
						AND sga_inscripciones_estados.estado = negocio_auditoria.logs_sga_insc_cursada.estado
						AND vw_elementos_plan.elemento = sga_comisiones.elemento
						AND vw_elementos_plan.plan_version = negocio_auditoria.logs_sga_insc_cursada.plan_version
						AND sga_alumnos.propuesta = sga_propuestas.propuesta

				UNION ALL

				SELECT 	sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_codigo_y_nombre,
						'(' || vw_elementos_plan.codigo || ') ' || vw_elementos_plan.nombre as actividad_codigo_y_nombre,
						negocio_auditoria.logs_sga_insc_examen.fecha_inscripcion as fecha_inscripcion,
						vw_mesas_examen.mesa_examen_nombre || ' - ' || to_char(vw_mesas_examen.mesa_examen_fecha,'".comunes::formato_fecha_visual."') as comision_o_mesa,
						vw_mesas_examen.turno_examen_nombre as periodo_o_turno_nombre,
						sga_instancias.nombre as instancia_nombre,
						sga_inscripciones_estados.nombre as estado_nombre,
						trim(negocio_auditoria.logs_sga_insc_examen.auditoria_usuario) as usuario,
						negocio_auditoria.logs_sga_insc_examen.auditoria_fecha as fecha,
						case negocio_auditoria.logs_sga_insc_examen.auditoria_operacion when 'I' then 'Alta' else 'Baja' end as operacion,
						case negocio_auditoria.logs_sga_insc_examen.interfaz when 1 then 'Gestión' else 'Autogestión' end as interfaz,
						CASE
							WHEN CURRENT_DATE <= vw_mesas_examen.mesa_examen_fecha THEN '$si'
							ELSE '$no'
						END as vigente,
						'$tipo_insc_examen' as tipo_inscripcion
				FROM 	sga_alumnos,
						negocio_auditoria.logs_sga_insc_examen,
						sga_propuestas,
						vw_mesas_examen
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra,
						sga_inscripciones_estados,
						vw_elementos_plan,
						sga_instancias
				WHERE 	sga_alumnos.persona = $persona
						AND sga_alumnos.alumno = negocio_auditoria.logs_sga_insc_examen.alumno
						AND sga_alumnos.propuesta = sga_propuestas.propuesta
						AND vw_mesas_examen.llamado_mesa = negocio_auditoria.logs_sga_insc_examen.llamado_mesa
						AND vw_elementos_plan.elemento = vw_mesas_examen.mesa_examen_elemento
						AND vw_elementos_plan.plan_version = negocio_auditoria.logs_sga_insc_examen.plan_version
						AND sga_inscripciones_estados.estado = negocio_auditoria.logs_sga_insc_examen.estado
						AND sga_instancias.instancia = negocio_auditoria.logs_sga_insc_examen.instancia
				ORDER BY 
						2,6,10,3
				";
		
		return guarani_db::consultar($sql);
	}

	// Newsol 02/2022 - Se agregó el $where_con_proyecto_final_vigente para que no corra el control de regularidad a los alumnos que tienen la Tesis o Proyecto Final aún vigente
	function get_alumnos_planes_versiones_ubicaciones($propuestas, $ubicaciones, $anio_academico, $mat_regularidad_alumno, $descartar_ingresantes = false, $where_alumnos_a_verificar)
	{
		$cond_in_propuestas = guarani_sql::armar_condicion_in($propuestas, 'sga_alumnos', 'propuesta', 'propuesta');
		$cond_in_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'sga_alumnos', 'ubicacion', 'ubicacion');
		$anio_academico = toba::db()->quote($anio_academico);

		if ($descartar_ingresantes) {
			$where_anio = "AND	sga_propuestas_aspira.anio_academico < $anio_academico";
		} else {
			$where_anio = "AND	sga_propuestas_aspira.anio_academico <= $anio_academico";
		}
		
		$distinct = ($mat_regularidad_alumno == 'Persona') ? 'DISTINCT' : '';

		$where_con_proyecto_final_vigente = " NOT EXISTS (Select * from vw_regularidades a, sga_elementos e, sga_elementos_revision r, sga_elementos_plan p 
						where a.alumno = sga_alumnos.alumno and a.plan_version = p.plan_version and p.elemento_revision = r.elemento_revision
						and e.elemento = r.elemento and (e.nombre like '%FINAL%' or e.nombre like '%TESI%') and a.es_vigente = 1
						and a.elemento = e.elemento)";

		$sql = "SELECT	$distinct sga_alumnos.persona,
						sga_alumnos.alumno,
						sga_alumnos.regular,
						vw_personas.apellido_nombres,
						vw_personas.tipo_nro_documento,
						sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado AS propuesta_descr,
						sga_alumnos.ubicacion
				FROM 	sga_alumnos
						JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
						JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
						JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
						JOIN sga_propuestas_aspira ON (sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta)
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				WHERE 	sga_alumnos.calidad = '".alumno::calidad_activo."' AND
						sga_propuestas_tipos.control_regularidad = '".comunes::si."' AND
						sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_pendiente."', '".propuesta::inscripcion_aceptada."') AND
						$cond_in_propuestas AND
						$cond_in_ubicaciones AND
						$where_anio AND
						$where_alumnos_a_verificar AND
						$where_con_proyecto_final_vigente
        		";
		
		return guarani_db::consultar($sql);
	}

	// Newsol (NUEVO) - Devuelve los alumnos regulares en una propuesta
	function get_alumnos_regulares_x_propuesta($filtro)
	{
		if ($filtro) {
			$filtro = ' AND '.$filtro;
		}

		$sql = "SELECT	
						vw_personas.tipo_nro_documento as documento_principal,
						vw_personas.apellido_nombres as apellido_y_nombre,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre
				FROM	
						vw_personas
						JOIN sga_alumnos ON sga_alumnos.persona = vw_personas.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				WHERE 		sga_alumnos.calidad = 'A'
				AND		sga_alumnos.regular = 'S'
				AND		sga_propuestas.propuesta_tipo not in (208)
				$filtro
				ORDER BY propuesta_nombre, apellido_y_nombre
		";
		return guarani_db::consultar($sql);
	}

	// Newsol (NUEVO) - Devuelve los alumnos que han terminado de cursar todas las actividades de su plan de estudios
	function get_alumnos_terminan_cursada($filtro)
	{
		if ($filtro) {
			$filtro = ' AND '.$filtro;
		}

		$sql = "SELECT	distinct
						vw_personas.tipo_nro_documento as documento_principal,
						vw_personas.apellido_nombres as apellido_y_nombre,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre

				FROM	
						vw_regularidades_basica as vw_regularidades
						JOIN sga_alumnos ON sga_alumnos.alumno = vw_regularidades.alumno
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_regularidades.plan_version
						JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						JOIN sga_planes as planes ON planes.plan = sga_alumnos.plan_version
						LEFT JOIN vw_comisiones ON vw_comisiones.comision = vw_regularidades.comision
						LEFT JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = vw_comisiones.periodo_lectivo
				WHERE 		sga_alumnos.calidad = 'A' AND	
						NOT EXISTS (SELECT * FROM vw_actividades_plan a WHERE a.propuesta = sga_alumnos.propuesta and a.plan = sga_planes.plan
								   AND a.elemento = vw_regularidades.elemento and a.entidad_subtipo <> 52 )		
				$filtro
				ORDER BY propuesta_nombre
		";
		return guarani_db::consultar($sql);
	}

	/**Newsol (NUEVO) - Retorna los Colegios Secundarios de los Alumnos declarados en el último dato censal
	 * @param <type> $filtro
	 * @return <type>
	 */
	function get_colegios_secundarios($filtro = '')
	{

		$where = '';
		if ($filtro != '') {
			$where .= " AND $filtro";
		}
		$sql = "SELECT	vw_perfil_de_datos_propuestas.propuesta as propuesta,
						vw_perfil_de_datos_propuestas.codigo,
						vw_perfil_de_datos_propuestas.nombre AS propuesta_nombre,
						vw_personas.apellido_nombres AS persona_nombre,
						vw_personas.tipo_nro_documento AS tipo_nro_documento,
						sga_colegios_secundarios.nombre, 
						sga_colegios_secundarios.cue,
						(trim(sga_colegios_secundarios.calle) || ' ' || sga_colegios_secundarios.nro) as domicilio,
						vw_localidades.localidad,
						vw_localidades.localidad_nombre,
						vw_localidades.dpto_partido_nombre,
						vw_localidades.provincia_nombre

				FROM	sga_alumnos,
						vw_perfil_de_datos_propuestas,
						vw_personas,
						mdp_datos_estudios,
						sga_colegios_secundarios,
						vw_localidades

				WHERE	sga_alumnos.propuesta			= vw_perfil_de_datos_propuestas.propuesta
				AND	vw_personas.persona			= sga_alumnos.persona
				AND	mdp_datos_estudios.persona		= vw_personas.persona
				AND	mdp_datos_estudios.colegio		= sga_colegios_secundarios.colegio
				AND 	sga_colegios_secundarios.localidad	= vw_localidades.localidad
				$where

				ORDER BY vw_perfil_de_datos_propuestas.propuesta, vw_personas.apellido, vw_personas.nombres";
		return guarani_db::consultar($sql);
	}

	//Newsol - 08/2022 - Se cambió la tabla sga_propuestas por vw_perfil_de_datos_propuestas porque generaba problemas en los perfiles de datos
	function get_propuestas_aspira($where)
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		// Recupera las inscripciones a propuesta del alumno
		$sql = "SELECT 
				    sga_propuestas_aspira.propuesta_aspira,
					-- Corte de control
				    sga_propuestas_aspira.propuesta,
					CASE
						WHEN sga_alumnos.coeficiente IS NULL THEN '(' || vw_perfil_de_datos_propuestas.codigo || ') - ' || vw_perfil_de_datos_propuestas.nombre    
						ELSE '(' || vw_perfil_de_datos_propuestas.codigo || ') - ' || vw_perfil_de_datos_propuestas.nombre || ' - Coeficiente: ' || sga_alumnos.coeficiente
					END as propuesta_nombre,

					'(' || vw_perfil_de_datos_propuestas.codigo || ') - ' || vw_perfil_de_datos_propuestas.nombre as propuesta_nombre,    
				    -- Columnas a mostrar
					sga_propuestas_aspira.anio_academico, 
				   	sga_propuestas_aspira.fecha_inscripcion as fecha_inscripcion,
					get_fecha_ingreso_alumno(sga_alumnos.alumno, sga_propuestas_aspira.periodo_insc) as fecha_ingreso,
					sga_situacion_aspirante.resultado_asp,
					CASE 
				    	WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' THEN '" . propuesta::inscripcion_rechazada_descr . "'
				    	WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_pendiente . "' THEN '" . propuesta::inscripcion_pendiente_descr . "'
				    	WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_aceptada . "' THEN '" . propuesta::inscripcion_aceptada_descr . "'
				    END as estado_inscr_descr,
				    sga_tipos_ingreso.nombre as tipo_ingreso_nombre,
				    sga_ubicaciones.nombre as ubicacion_nombre,
				    u_insc.nombre as ubicacion_nombre_insc,
				    sga_modalidad_cursada.nombre as modalidad_nombre,
				    m_insc.nombre as modalidad_nombre_insc,
				    sga_planes.nombre as plan_nombre,
					p_insc.nombre as plan_nombre_insc,
					sga_alumnos.alumno,
					sga_alumnos.legajo as legajo,
					CASE WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' THEN '-' ELSE sga_alumnos_calidad.nombre END as calidad,
					CASE 
					  WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' THEN 
					     -- Motivo del cambio de calidad a pasivo por tener inscripcion rechazada
					    (SELECT mc.nombre 
					       FROM sga_alumnos_hist_calidad as hc
					       JOIN sga_alumnos_motivos_calidad as mc ON mc.motivo_calidad = hc.motivo_calidad
					      WHERE hc.alumno = sga_alumnos.alumno
					        AND hc.calidad = 'P'
							AND date(hc.fecha) = sga_propuestas_aspira.fecha_rechazo
						  LIMIT 1)
					  ELSE
						 -- Busco ultimo cambio de calidad (calidad actual) 
				        (SELECT mc.nombre 
					       FROM sga_alumnos_hist_calidad as hc
					       JOIN sga_alumnos_motivos_calidad as mc ON mc.motivo_calidad = hc.motivo_calidad
					      WHERE hc.alumno = sga_alumnos.alumno
					        AND hc.calidad = sga_alumnos.calidad
						 ORDER BY hc.fecha DESC, hc.cambio_calidad DESC 
						 LIMIT 1)
					END	as desc_motivo_cambio_calidad,					
					CASE WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' THEN '-' ELSE sga_alumnos.regular END as regular,    
					sga_propuestas_aspira.observaciones,
					CASE 
				      WHEN sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' AND sga_propuestas_aspira.observaciones_rechazo IS NULL THEN sga_situacion_aspirante.nombre
					  ELSE sga_propuestas_aspira.observaciones_rechazo
				    END as observaciones_rechazo,
					to_char(sga_propuestas_aspira.fecha_rechazo,'DD/MM/YYYY') as fecha_rechazo_inscripcion,
					f_orientaciones_alumno(sga_alumnos.alumno) as orientacion,
					sga_propuestas_aspira.nro_transaccion	
									
				FROM sga_propuestas_aspira
				JOIN vw_perfil_de_datos_propuestas ON sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta
				JOIN sga_alumnos ON (sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta)
				JOIN sga_alumnos_calidad ON sga_alumnos_calidad.calidad = sga_alumnos.calidad
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_alumnos.ubicacion
				JOIN sga_ubicaciones as u_insc ON u_insc.ubicacion = sga_propuestas_aspira.ubicacion
				JOIN sga_modalidad_cursada ON sga_modalidad_cursada.modalidad   = sga_alumnos.modalidad
				JOIN sga_modalidad_cursada as m_insc ON m_insc.modalidad = sga_propuestas_aspira.modalidad
				JOIN sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
				JOIN sga_planes_versiones as pv_insc ON pv_insc.plan_version = sga_propuestas_aspira.plan_version
				JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
				JOIN sga_planes as p_insc ON p_insc.plan = pv_insc.plan
				LEFT JOIN sga_tipos_ingreso ON sga_tipos_ingreso.tipo_ingreso = sga_propuestas_aspira.tipo_ingreso
				
				WHERE true 
				$where
				";
		
		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_propuestas'));
		return guarani_db::consultar($sql, null, false);
	}


	/** Newsol 09/2022 - Agrego un campo (fecha_aprob_tesis) para saber si tiene o no la tesis aprobada
	 * Obtiene un listado de alumnos con sus datos principales y resúmen de actividad académica 
	 * (materias aprobadas y rendidas, promedios con y sin aplazos).
	 * Utiliza la funcion "f_get_promedios_actividades_alumno"
	 *
	 * @param str $where filtro sobre los alumnos a retornar.
	 * @return array
	 */
	function get_reporte_alumnos_promedio($where='')
	{
		$mail_prim =  toba::db()->quote(persona::mail_primario);
        	$mail_sec  =  toba::db()->quote(persona::mail_secundario);
        	$mail_inst =  toba::db()->quote(persona::mail_institucional);

        	$tel_cel =  toba::db()->quote(persona::tel_celular);
        	$tel_fam =  toba::db()->quote(persona::tel_familiar);
        	$tel_fijo =  toba::db()->quote(persona::tel_fijo);
        	$tel_lab =  toba::db()->quote(persona::tel_laboral);
        	$otro =  toba::db()->quote(persona::otro_contacto);
		
		if ($where) {
			$where = " WHERE $where";
		}
		
		$sql = "
				-- Actividades realizadas por el alumno
				CREATE TEMP TABLE _Tactividades_aux (elemento integer,id_acta integer,origen char(1), equivalencia_tramite integer, equivalencia integer, fecha date, escala_nota integer, nota varchar(10), resultado char(1), promediable char(1));
				CREATE TEMP TABLE _Tactividades (elemento integer,id_acta integer,origen char(1), equivalencia_tramite integer, equivalencia integer, fecha date, escala_nota integer, nota varchar(10), resultado char(1), promediable char(1));
  
				-- Propuesta del alumno (Incluye propuestas vinculadas)
				CREATE TEMP TABLE _temp_prop_alumno (alumno integer, propuesta integer, plan integer, plan_version integer);
				CREATE TEMP TABLE _temp_relacion (relacion integer); -- Tabla de propuestas relacionadas";
		
		guarani_db::ejecutar($sql);
		
		$sql = "
				SELECT				
					vw_personas.apellido_nombres AS apellido_y_nombres,
					vw_personas.tipo_nro_documento AS documento_principal,
					sga_alumnos.persona,
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					sga_alumnos.calidad,
					sga_alumnos.regular,
					f.fecha_aprob_tesis,
					f.promedio,
					f.promedio_sin_aplazos,
					f.cant_actividades_aprobadas,
					f.cant_actividades_plan,
					TO_CHAR(f.fecha_ultimo_examen,'DD/MM/YYYY') as fecha_ultimo_examen,
					vw_planes.propuesta_nombre as propuesta_nombre,
					vw_planes.propuesta_codigo as propuesta_codigo,
					'(' || vw_planes.propuesta_codigo || ') ' || vw_planes.propuesta_nombre AS propuesta_descr,
					vw_planes.plan_nombre    as  plan_nombre,
					vw_planes.plan_codigo    as plan_codigo,
					vw_planes.version_nombre as plan_version_nombre,
					vw_planes.version_codigo as plan_version_codigo,
					sga_ubicaciones.nombre   as ubicacion_nombre,
					sga_alumnos.ubicacion,
					sga_propuestas_aspira.anio_academico as anio_academico_ingreso,
					TO_CHAR(get_fecha_ingreso_alumno(sga_alumnos.alumno),'DD/MM/YYYY') as fecha_ingreso,
					(SELECT r.anio_academico FROM sga_reinscripciones as r 
					  WHERE r.alumno = sga_alumnos.alumno ORDER BY r.anio_academico DESC   LIMIT 1
					 ) as ultima_reinscripcion,
					COALESCE((
						SELECT mdp_personas_contactos.email
						FROM mdp_personas_contactos
						WHERE mdp_personas_contactos.persona = vw_personas.persona
						AND contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
						ORDER BY contacto_tipo = $mail_prim DESC, contacto_tipo = $mail_sec DESC, contacto_tipo = $mail_inst DESC
						LIMIT 1)) AS email,
					COALESCE((
						SELECT (mdp_personas_contactos.telefono_codigo_area || '|' ||mdp_personas_contactos.telefono_numero)
						FROM mdp_personas_contactos
						WHERE mdp_personas_contactos.persona = vw_personas.persona
						AND contacto_tipo IN ($tel_cel, $tel_fam, $tel_fijo, $tel_lab)
						ORDER BY contacto_tipo = $tel_cel DESC, contacto_tipo = $tel_fam DESC, contacto_tipo = $tel_fijo DESC, contacto_tipo = $tel_lab DESC
						LIMIT 1)) AS nro_telefono					 				 
				FROM		
					sga_alumnos
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
					JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp AND sga_situacion_aspirante.resultado_asp IN ('A', 'P'))
					JOIN vw_planes ON vw_planes.plan_version = sga_alumnos.plan_version
					JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion 
					JOIN LATERAL negocio_nsg.get_promedios_actividades_alumno(sga_alumnos.alumno, sga_alumnos.plan_version, false) as f ON true
				$where
				ORDER BY	
					vw_planes.propuesta_nombre,
					sga_ubicaciones.nombre,
					vw_personas.apellido,
					vw_personas.nombres
		";
		$datos = guarani_db::consultar($sql);
		
		$sql = "
				-- Borro tablas temporales.
				DROP TABLE _Tactividades;
				DROP TABLE _Tactividades_aux;
				DROP TABLE _temp_prop_alumno;
				DROP TABLE _temp_relacion;
				";
		guarani_db::ejecutar($sql);
		return $datos;
		
	}

	//Newsol (NUEVO) - Obtiene la cantidad de cursadas aprobadas para el control de regularidad
	function get_cant_cur_aprobadas_ctrl_regularidad($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
			$sql = "SELECT	COUNT(1) AS cant
				FROM 	vw_regularidades_basica,
					sga_alumnos,
				        sga_propuestas,
				        sga_propuestas_tipos
				WHERE	vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."'
				AND 	sga_alumnos.alumno = vw_regularidades_basica.alumno
				AND 	sga_propuestas.propuesta = sga_alumnos.propuesta
				AND 	sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
				$where
				";

			$rs = guarani_db::consultar_fila($sql);
			return $rs['cant'];
	}

	//Newsol (NUEVO) - Obtiene la cantidad de exámenes aprobados para el control de regularidad
	function get_cant_exa_aprobadas_ctrl_regularidad($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

			$sql = "SELECT	COUNT(1) AS cant
				FROM 	vw_hist_academica_basica,
					sga_alumnos,
				        sga_propuestas,
				        sga_propuestas_tipos
				WHERE	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
				AND 	sga_alumnos.alumno = vw_hist_academica_basica.alumno
				AND 	sga_propuestas.propuesta = sga_alumnos.propuesta
				AND 	sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
				$where
				";

			$rs = guarani_db::consultar_fila($sql);
			return $rs['cant'];
	}

	/**Newsol (NUEVO) - Retorna el listado de alumnos con regularidades aprobadas que aún deben el trabajo final integrador
     	*/
	function get_listado_regularidades_aprobadas_sin_tfi($where='')
	{
		if ($where) {
			$where = "AND $where";
		}
		
		$sql = "select negocio_nsg.f_reporte_regularidades_aprobadas()";
		guarani_db::consultar($sql);

		$sql = "SELECT '(' || vw_planes.propuesta_codigo || ') ' || vw_planes.propuesta_nombre AS propuesta_descr,
					'(' || vw_planes.version_codigo || ') ' || vw_planes.version_nombre as plan_descr,
				vw_personas.apellido_nombres AS apellido_y_nombres,
				vw_personas.tipo_nro_documento AS documento_principal,
				negocio_nsg.tmp_regularidades_aprobadas.cant_actividades_aprobadas,
				negocio_nsg.tmp_regularidades_aprobadas.cant_actividades_plan
                	FROM vw_personas,  
				negocio_nsg.tmp_regularidades_aprobadas
                	LEFT JOIN sga_alumnos ON sga_alumnos.alumno = negocio_nsg.tmp_regularidades_aprobadas.alumno
                	LEFT JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
			JOIN vw_planes ON vw_planes.plan_version = sga_alumnos.plan_version
			WHERE vw_personas.persona = sga_alumnos.persona
			$where

			ORDER BY 1,2,3
		";
		return guarani_db::consultar($sql);		
	}	

	//Newsol (NUEVO) - Ficha del alunmno: Recupera los datos de facturación de cada propuesta en la que está inscripto el alumno
	function get_datos_facturacion_x_propuesta($where)
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT 
			    sga_propuestas.propuesta,
				'(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre as propuesta_nombre,    
			    -- Columnas a mostrar
				mfp.descripcion as forma_pago,
				sdf.porcentaje_facturacion_alumno, 
				mmpam.descripcion as medio_pago_alumno_mat, 
				sdf.nro_tarjeta_alumno_mat, 
				sdf.cbu_alumno_mat, 
				sdf.porcentaje_facturacion_empresa, 
				mmpem.descripcion as medio_pago_empresa_mat, 
				sdf.nro_tarjeta_empresa_mat, 
				sdf.cbu_empresa_mat, 
				mmpac.descripcion as medio_pago_alumno_cuota, 
				sdf.nro_tarjeta_alumno_cuota, 
				sdf.cbu_alumno_cuota, 
				mmpec.descripcion as medio_pago_empresa_cuota, 
				sdf.nro_tarjeta_empresa_cuota, 
				sdf.cbu_empresa_cuota, 
				sga_instituciones.nombre as nombre_empresa
				FROM sga_propuestas
				JOIN sga_alumnos ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN negocio_nsg.nsg_datos_facturacion as sdf ON sga_alumnos.alumno = sdf.alumno
				LEFT JOIN negocio_nsg.nsg_medio_pago as mmpam ON mmpam.medio_pago = sdf.medio_pago_alumno_mat
				LEFT JOIN negocio_nsg.nsg_medio_pago as mmpem ON mmpem.medio_pago = sdf.medio_pago_empresa_mat
				LEFT JOIN negocio_nsg.nsg_medio_pago as mmpac ON mmpac.medio_pago = sdf.medio_pago_alumno_cuota
				LEFT JOIN negocio_nsg.nsg_medio_pago as mmpec ON mmpec.medio_pago = sdf.medio_pago_empresa_cuota
				LEFT JOIN sga_instituciones ON sga_instituciones.institucion = sdf.empresa
				LEFT JOIN negocio_nsg.nsg_forma_pago as mfp ON mfp.forma_pago = sdf.forma_pago
				WHERE true
				$where
				";
		
		return guarani_db::consultar($sql);
	}

	//Newsol (NUEVO) - Ficha del alunmno: Recupera los datos de becas del alumno en cada una de las propuestas en las que está inscripto
	function get_datos_becas_x_propuesta($where)
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "	SELECT 
			    sga_propuestas.propuesta,
				'(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre as propuesta_nombre,    
			    -- Columnas a mostrar
				sdb.beca_fecha_vigencia_desde, 
				sdb.beca_fecha_vigencia_hasta, 
				coalesce(mtp.porcentaje,0) as beneficio, 
				0 as bonificacion,							
				mtp.descripcion as tipo_beneficio_nombre, 
				'' as nombre_institucion
				FROM sga_propuestas
				JOIN sga_alumnos ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN negocio_nsg.nsg_datos_beneficios as sdb ON sga_alumnos.alumno = sdb.alumno
				LEFT JOIN negocio_nsg.nsg_tipo_beneficio as mtp ON mtp.tipo_beneficio = sdb.tipo_beneficio
				WHERE mtp.es_beca_o_bonif = 'Beca'
				$where
				UNION				
					SELECT 
			    	sga_propuestas.propuesta,
				'(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre as propuesta_nombre,    
			   	sdb.beca_fecha_vigencia_desde, 
				sdb.beca_fecha_vigencia_hasta, 
				0 as beneficio, 
                		(case when sdb.institucion is not null then coalesce((select coalesce(sdb.bonificacion_cuota,0) + f.bonif_cuota from negocio_nsg.sga_instituciones_ext i, negocio_nsg.nsg_bonif_x_convenio f 
                            		where sdb.institucion is not null and sdb.institucion = i.id_institucion and i.tipo_convenio_fact = f.tipo_convenio_fact and f.tipo_bonif = 1),0) else coalesce(mtp.porcentaje,0) end) as bonificacion,                            
 				mtp.descripcion as tipo_beneficio_nombre, 
				(select sga_instituciones.nombre from sga_instituciones where sdb.institucion = sga_instituciones.institucion) as nombre_institucion
				FROM sga_propuestas
				JOIN sga_alumnos ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN negocio_nsg.nsg_datos_beneficios as sdb ON sga_alumnos.alumno = sdb.alumno
				LEFT JOIN negocio_nsg.nsg_tipo_beneficio as mtp ON mtp.tipo_beneficio = sdb.tipo_beneficio
				LEFT JOIN sga_instituciones ON sga_instituciones.institucion = sdb.institucion
				WHERE mtp.es_beca_o_bonif = 'Bonif'
				$where
				";
		
		return guarani_db::consultar($sql);
	}

	//Newsol (02-2025) - Se cambió el orden, para que sea por propuesta + código-nombre de la materia
	function get_historia_academica($where='', $origen_reporte=true, $origen_invalidar_ha=false)
	{
		$where_actividades = "";
		$where_modulos = "";
		$where_equiv_parciales = "";
		if ($where) {
			$where_equiv_parciales = " WHERE $where";  // Para invalidacion

			$where_actividades = " WHERE $where";
			
			// En el listado de modulos, se consulta directamente por el alumno, no la vista de historia academica.
			$where_modulos = " AND $where";
			$where_modulos = str_replace("AND ha.origen_otra_propuesta = false","",$where_modulos); // Quito esta condición porque no se usa la vista de historia academcia al consultar los modulos reconocidos por resolucion
			$where_modulos = str_replace('ha.', 'sga_alumnos.', $where_modulos); // Cambio ha por sga_alumnos
		}
		
		// Si es el reporte en la Ficha de la persona, no muestro historia academica realizada en otras propuestas 
		// con fecha posterior a fecha de cambio de calidad a Pasivo por Egreso/Abandono/Cambio de propuesta/rechazo inscripcion 
		$where_pasivo = '';
		$optativa_se_cumple_para_generica = ', cast(null as text) as optativa_se_cumple_para';
		$optativa_se_cumple_para_generica_modulos = ', cast(null as text) as optativa_se_cumple_para';
		if ($origen_reporte) {
			$where_pasivo = " AND (	ha.origen_otra_propuesta = false OR
									sga_alumnos.calidad = '".alumno::calidad_activo."' OR 
									(ha.fecha <= COALESCE(get_fecha_pasivo_egreso_abandono_rechazo(sga_alumnos.alumno),CURRENT_DATE)))";

			// En la ficha del alumno visualizo, para el caso de optativas, para que generica se cumple.		
			$optativa_se_cumple_para_generica = ", get_nombre_generica_de_optativa(sga_alumnos.alumno, ha.elemento, sga_alumnos.plan_version) as optativa_se_cumple_para";
		}

		$sql = "SELECT
						COALESCE(COALESCE(ep_alu.nombre, ep_origen.nombre),vw_elementos.nombre) as actividad_nombre,
						sga_propuestas.propuesta,
						ha.id_acta,
						ha.equivalencia,
						ha.equivalencia_tramite,
						ha.fecha as fecha_orden,
						to_char(ha.fecha,'".comunes::formato_fecha_visual."') as fecha,
						ha.nota,
						CASE ha.origen
							WHEN 'A' THEN 'Aprobado'
							WHEN 'B' THEN sga_escalas_notas_resultado.descripcion
							WHEN 'P' THEN sga_instancias_resultado.descripcion
							WHEN 'E' THEN sga_instancias_resultado.descripcion
							ELSE sga_escalas_notas_resultado.descripcion
						END as resultado_descripcion,
						cast(CASE ha.origen
							WHEN 'A' THEN 'Aprob. por Resolución'
							WHEN 'B' THEN 'Equivalencia'
							WHEN 'P' THEN 'Promoción'
							WHEN 'E' THEN 'Examen'
							ELSE ''	
						END as varchar(30)) as tipo,
						propuesta_origen.codigo as propuesta_codigo,
						plan_origen.codigo as plan_codigo,
						pv_origen.plan_version as plan_version_origen,
						sga_propuestas.nombre as propuesta_nombre,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descripcion,
						ha.elemento as actividad,
						vw_elementos.elemento_revision,
						COALESCE(CAST(ha.id_acta as varchar), '') || ':' || COALESCE(CAST(ha.equivalencia as varchar), '') AS clave_acta_equivalencia,
						CASE vw_elementos.entidad_tipo
							WHEN 1 THEN COALESCE(ep_alu.nombre, ep_origen.nombre)
							ELSE '(' || sga_elementos.codigo || ') ' || COALESCE(COALESCE(ep_alu.nombre, ep_origen.nombre),vw_elementos.nombre)
						END AS actividad_codigo_y_nombre,
						CASE ha.origen
							WHEN 'A' THEN doc_apres.documento_numero
							WHEN 'B' THEN cast(get_resolucion_equivalencia(sga_equiv_otorgada.equivalencia, sga_equiv_otorgada.grupo_equivalencia, sga_equiv_tramite.documento, 2) as varchar(30))
							WHEN 'P' THEN sga_actas.nro_acta
							WHEN 'E' THEN sga_actas.nro_acta
							ELSE ''	
						END as acta_o_resolucion,
						CASE ha.origen
							WHEN 'E' THEN 
							     (SELECT CAST(me.anio_academico as varchar) || (CASE me.mesa_examen_en_turno WHEN 'S' THEN ' - ' || me.turno_examen_nombre ELSE '' END)
							        FROM vw_mesas_examen as me WHERE me.llamado_mesa = ha.llamado_mesa)
							WHEN 'P' THEN
							     (SELECT CAST(co.anio_academico as varchar) || ' - ' || co.periodo_lectivo_nombre FROM vw_comisiones as co WHERE co.comision = ha.comision)
							ELSE ''
						END AS periodo_o_turno,
						CASE ha.origen WHEN 'E' THEN sga_instancias.nombre ELSE '' END as instancia,							
						ha.creditos as creditos,
						".guarani_sql::get_case('ha', 'origen_otra_propuesta', 'op', toba::consulta_php('co_comunes')->get_si_no_binario()).",
						ha.origen_otra_propuesta,	
						ha.origen,
						ha.alumno,
						ha.alumno2,
						0 as es_modulo,
						sga_planes_versiones.plan_version as plan_version,
						ha.reconocimiento_act as reconocimiento_act,
						CAST(CASE 
								WHEN ha.origen IN ('P', 'E') THEN (SELECT l.nombre 
																	FROM sga_actas_detalle as d																	
																	JOIN sga_actas_folios as af ON (af.id_acta = d.id_acta AND af.folio = d.folio)
																	JOIN sga_libros_tomos as lt ON lt.libro_tomo = af.libro_tomo
																	JOIN sga_libros_actas as l ON l.libro = lt.libro
																	WHERE d.id_acta = ha.id_acta
																	  AND d.alumno = ha.alumno2
																	LIMIT 1)
								ELSE ''	
							END as varchar(60)
							) as libro_nombre,
						CAST(CASE 
								WHEN ha.origen IN ('P', 'E') THEN (SELECT af.folio_fisico
																	FROM sga_actas_detalle as d																	
																	JOIN sga_actas_folios as af ON (af.id_acta = d.id_acta AND af.folio = d.folio)
																	WHERE d.id_acta = ha.id_acta
																	  AND d.alumno = ha.alumno2
																	LIMIT 1)		
							END as varchar(60)
							) as folio_fisico
						$optativa_se_cumple_para_generica
						
					FROM vw_hist_academica_basica as ha
					JOIN vw_elementos ON vw_elementos.elemento = ha.elemento
					JOIN sga_elementos ON sga_elementos.elemento = ha.elemento
					JOIN sga_alumnos ON sga_alumnos.alumno = ha.alumno
						
					-- Propuesta, plan y plan-versión actuales del alumno.
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
					JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
					
					-- Propuesta, plan y plan-versión de origen.
					JOIN sga_planes_versiones as pv_origen ON pv_origen.plan_version = ha.plan_version
					JOIN sga_planes as plan_origen ON plan_origen.plan = pv_origen.plan
					JOIN sga_propuestas as propuesta_origen ON propuesta_origen.propuesta = plan_origen.propuesta
					LEFT JOIN sga_elementos_plan as ep_origen ON (ep_origen.plan_version = ha.plan_version AND ep_origen.elemento_revision = vw_elementos.elemento_revision)

					LEFT JOIN sga_instancias ON sga_instancias.instancia = ha.instancia
					LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = ha.resultado
					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = ha.instancia AND sga_instancias_resultado.resultado = ha.resultado)
					LEFT JOIN sga_actas ON sga_actas.id_acta = ha.id_acta
					LEFT JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = ha.equivalencia_tramite
					LEFT JOIN sga_equiv_otorgada ON sga_equiv_otorgada.equivalencia = ha.equivalencia
					LEFT JOIN sga_reconocimiento ON sga_reconocimiento.nro_tramite = ha.reconocimiento_nro_tramite
					LEFT JOIN sga_documentos as doc_apres ON doc_apres.documento = sga_reconocimiento.documento
					-- Propuesta, plan y plan-versión actuales del alumno.
					LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.plan_version = sga_alumnos.plan_version AND ep_alu.elemento_revision = vw_elementos.elemento_revision)
					$where_actividades
					$where_pasivo
					";

        	if ($origen_invalidar_ha) {
        		// Se visualizan equivalencias parciales para poder ser invalidadas
			$sql .= " UNION 
				SELECT
					COALESCE(ep_alu.nombre, ha.actividad_nombre) as actividad_nombre,
					sga_propuestas.propuesta,
					cast(NULL as integer) as id_acta,
					ha.equivalencia,
					ha.equivalencia_tramite,
					ha.fecha as fecha_orden,
					to_char(ha.fecha,'".comunes::formato_fecha_visual."') as fecha,
					ha.nota,
					ha.resultado_descripcion,
					cast('Equivalencia Parcial' as varchar(30)) as tipo,
					propuesta_origen.codigo as propuesta_codigo,
					plan_origen.codigo as plan_codigo,
					pv_origen.plan_version as plan_version_origen,
					sga_propuestas.nombre as propuesta_nombre,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descripcion,
					ha.elemento as actividad,
					ha.elemento_revision,
					COALESCE(CAST(ha.equivalencia as varchar), '') AS clave_acta_equivalencia,
					'(' || ha.actividad_codigo || ') ' || COALESCE(ep_alu.nombre, ha.actividad_nombre) as actividad_codigo_y_nombre,
 					ha.nro_resolucion_descripcion as acta_o_resolucion,
					'' as periodo_o_turno,
					'' as instancia,							
					ha.creditos as creditos,
					".guarani_sql::get_case('ha', 'origen_otra_propuesta', 'op', toba::consulta_php('co_comunes')->get_si_no_binario()).",
					ha.origen_otra_propuesta,	
					ha.origen,
					ha.alumno,
					ha.alumno as alumno2,
					0 as es_modulo,
					ha.plan_version,
					cast(null as integer) as reconocimiento_act,
					'' as libro_nombre,
					cast(null as varchar(60)) as folio_fisico
					$optativa_se_cumple_para_generica
						
					FROM vw_equiv_parciales as ha
					JOIN sga_alumnos ON sga_alumnos.alumno = ha.alumno
					
					-- JOIN vw_elementos ON vw_elementos.elemento = ha.elemento
					-- JOIN sga_elementos ON sga_elementos.elemento = ha.elemento
						
					-- Propuesta, plan y plan-versión actuales del alumno.
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
					JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
					
					-- Propuesta, plan y plan-versión de origen.
					JOIN sga_planes_versiones as pv_origen ON pv_origen.plan_version = ha.plan_version
					JOIN sga_planes as plan_origen ON plan_origen.plan = pv_origen.plan
					JOIN sga_propuestas as propuesta_origen ON propuesta_origen.propuesta = plan_origen.propuesta
					LEFT JOIN sga_elementos_plan as ep_origen ON (ep_origen.plan_version = ha.plan_version AND ep_origen.elemento_revision = ha.elemento_revision)

					-- LEFT JOIN sga_instancias ON sga_instancias.instancia = ha.instancia
					-- LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = ha.resultado
					-- LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = ha.instancia AND sga_instancias_resultado.resultado = ha.resultado)
					-- JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = ha.equivalencia_tramite
					-- JOIN sga_equiv_otorgada ON sga_equiv_otorgada.equivalencia = ha.equivalencia
					
					-- Datos de la actividad segun plan-versión actual del alumno.
					LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.plan_version = sga_alumnos.plan_version AND ep_alu.elemento_revision = ha.elemento_revision)
					$where_equiv_parciales
					";
		}
        
        	if ($origen_reporte || $origen_invalidar_ha) {
			// ------------------------------------------------------------------------------------
			// Se recuperan módulos y actividades genéricas reconocidas por resolución
			// Usado en:
			// * Ficha de la Persona - Solapa Historia Academica: Solo se recuperan reconocimientos totales 
			// * Invalidar Historia Academica: Se recuperan reconocimientos totales y parciales.
			// ------------------------------------------------------------------------------------
			$filtro_reconocimiento_total = " AND sga_reconocimiento_act.reconocimiento_total = 'S' ";
			if ($origen_invalidar_ha) {
				$filtro_reconocimiento_total = ""; // En invalidacion de HA listo reconocimientos totales y parciales de modulos.
			}
			
			$sql .= " UNION 
				-- Modulos/Genéricas reconocidas en su totalidad (las actividades se recuperan en la query anterior).
				SELECT
						COALESCE(sga_elementos_plan.nombre, vw_elementos.nombre) as actividad_nombre,
						sga_propuestas.propuesta,
						cast(NULL as integer) AS id_acta,
						cast(NULL as integer) AS equivalencia,
						cast(NULL as integer) AS equivalencia_tramite,
						sga_reconocimiento.fecha as fecha_orden,
						to_char(sga_reconocimiento.fecha,'".comunes::formato_fecha_visual."') as fecha,
						sga_reconocimiento_act.nota as nota,
						'Aprobado' as resultado_descripcion,
						cast('Aprob. por Resolución' as varchar(30)) as tipo,
						sga_propuestas.codigo as propuesta_codigo,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.plan_version as plan_version_origen,
						sga_propuestas.nombre as propuesta_nombre,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descripcion,
						sga_reconocimiento_act.elemento as actividad,
						vw_elementos.elemento_revision,
						cast(NULL as varchar(100)) AS clave_acta_equivalencia,
						COALESCE(sga_elementos_plan.nombre, vw_elementos.nombre) as actividad_codigo_y_nombre,
						sga_documentos.documento_numero as acta_o_resolucion,
						cast(null as varchar(100)) as periodo_o_turno,
						cast(NULL as varchar(50)) as instancia,
						CASE sga_elementos_revision.origen_creditos
						  WHEN 'M' THEN sga_elementos_plan.creditos 
						  WHEN 'A' THEN sga_reconocimiento_act.creditos 
						  ELSE cast(NULL as decimal(8,2))
						END	as creditos, 
						'No' as op,
						false as origen_otra_propuesta,
						'A' as origen, 
						sga_reconocimiento.alumno,
						sga_reconocimiento.alumno as alumno2,
						CASE 
						  WHEN (SELECT COUNT(1) FROM sga_elementos_comp WHERE elemento_padre = sga_elementos_revision.elemento_revision) > 0 THEN 1 
						  ELSE 0 
						END es_modulo, -- Solo habilito lupa de modulo si el modulo tiene componentes.
						sga_planes_versiones.plan_version,
						sga_reconocimiento_act.reconocimiento_act as reconocimiento_act,
						cast(null as varchar(60)) as libro_nombre,
						cast(null as varchar(60)) as folio_fisico
						$optativa_se_cumple_para_generica_modulos
                    FROM
						sga_reconocimiento
						JOIN sga_reconocimiento_act ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_reconocimiento.alumno
						JOIN sga_documentos ON sga_documentos.documento = sga_reconocimiento.documento
						JOIN vw_elementos ON vw_elementos.elemento = sga_reconocimiento_act.elemento
						JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version = sga_reconocimiento.plan_version AND
						                            sga_elementos_plan.elemento_revision = vw_elementos.elemento_revision)
						JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision							
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_reconocimiento.plan_version
						JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						
                    WHERE	sga_reconocimiento.estado = 'C' 
						AND sga_reconocimiento.tipo_reconocimiento = 'AM' -- Aprobacion de Actividades/Modulos
						$filtro_reconocimiento_total
						AND sga_reconocimiento_act.estado = 'A'
						AND sga_reconocimiento_act.rectificado = 'N'
						AND vw_elementos.entidad_tipo = 1  -- Modulos
						$where_modulos
				";
			}
		
	       if ($origen_reporte ) {
						
				$sql .= " UNION 					
					-- Modulos/Genericas de cumplimiento por Creditos (regla 105), que estan cumplidos y que fueron reconociendose creditos en forma parcial
					SELECT
						vw_modulos_plan.nombre as actividad_nombre,
						sga_propuestas.propuesta,
						cast(NULL as integer) AS id_acta,
						cast(NULL as integer) AS equivalencia,
						cast(NULL as integer) AS equivalencia_tramite,
						(SELECT max(coalesce(coalesce(d.fecha_hasta, d.fecha_desde),r2.fecha_cierre))
							FROM sga_reconocimiento as r2
							JOIN sga_reconocimiento_act as ra2 ON ra2.nro_tramite = r2.nro_tramite 
							LEFT JOIN sga_reconocimiento_doc as d ON d.nro_tramite = r2.nro_tramite 
							WHERE r2.alumno = sga_alumnos.alumno 
							AND r2.estado = 'C' 
							AND r2.tipo_reconocimiento = 'AM' 
							AND ra2.elemento = vw_modulos_plan.elemento
							AND ra2.reconocimiento_total = 'N'
							AND ra2.estado = 'A'
							AND ra2.rectificado = 'N'
						) as fecha_orden,
						(SELECT to_char(max(coalesce(coalesce(d.fecha_hasta, d.fecha_desde),r2.fecha_cierre)), '".comunes::formato_fecha_visual."')
							FROM sga_reconocimiento as r2
							JOIN sga_reconocimiento_act as ra2 ON ra2.nro_tramite = r2.nro_tramite 
							LEFT JOIN sga_reconocimiento_doc as d ON d.nro_tramite = r2.nro_tramite 
							WHERE r2.alumno = sga_alumnos.alumno 
							AND r2.estado = 'C' 
							AND r2.tipo_reconocimiento = 'AM' 
							AND ra2.elemento = vw_modulos_plan.elemento
							AND ra2.reconocimiento_total = 'N'
							AND ra2.estado = 'A'
							AND ra2.rectificado = 'N'
						) as fecha,
						CAST(NULL as varchar(10)) as nota,
						'Aprobado' as resultado_descripcion,
						cast('Aprob. por Resolución' as varchar(30)) as tipo,
						sga_propuestas.codigo as propuesta_codigo,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.plan_version as plan_version_origen,
						sga_propuestas.nombre as propuesta_nombre,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descripcion,
						vw_modulos_plan.elemento as actividad,
						vw_modulos_plan.elemento_revision,
						cast(NULL as varchar(100)) AS clave_acta_equivalencia,
						vw_modulos_plan.nombre as actividad_codigo_y_nombre,
						CAST(NULL AS varchar(30)) as acta_o_resolucion,
						cast(NULL as varchar(100)) as periodo_o_turno,
						cast(NULL as varchar(50)) as instancia,
						-- sga_reconocimiento_act.creditos as creditos,
						CASE sga_elementos_revision.origen_creditos
						  WHEN 'M' THEN vw_modulos_plan.creditos 
						  WHEN 'A' THEN 
						            (SELECT SUM(ra2.creditos)
						               FROM sga_reconocimiento as r2
						               JOIN sga_reconocimiento_act as ra2 ON ra2.nro_tramite = r2.nro_tramite 
						              WHERE r2.alumno = sga_alumnos.alumno 
						            	AND r2.estado = 'C' 
						                AND r2.tipo_reconocimiento = 'AM' 
						                AND ra2.elemento = vw_modulos_plan.elemento
						                AND ra2.reconocimiento_total = 'N'
						            	AND ra2.estado = 'A'
						            	AND ra2.rectificado = 'N'
						            ) 
						  ELSE cast(NULL as decimal(8,2))
						END	as creditos, -- Total creditos reconocidos para el modulo.
						'No' as op,
						false as origen_otra_propuesta,
						'A' as origen, 
						sga_alumnos.alumno,
						sga_alumnos.alumno as alumno2,
						CASE 
						  WHEN (SELECT COUNT(1) FROM sga_elementos_comp WHERE elemento_padre = sga_elementos_revision.elemento_revision) > 0 THEN 1 
						  ELSE 0 
						END es_modulo, -- Solo habilito lupa de modulo si el modulo tiene componentes.
						sga_alumnos.plan_version,
						CAST(NULL as integer) as reconocimiento_act,
						cast(null as varchar(60)) as libro_nombre,
						cast(null as varchar(60)) as folio_fisico
						$optativa_se_cumple_para_generica_modulos
						
                    FROM sga_alumnos 
					JOIN vw_modulos_plan ON vw_modulos_plan.plan_version = sga_alumnos.plan_version
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = vw_modulos_plan.elemento_revision
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
					JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						
					WHERE 
						-- Modulo que se cumple por creditos. 
						sga_elementos_revision.regla = 105	-- Obtener n cantidad de créditos
						-- AND vw_modulos.entidad_subtipo = 6	-- Area de Actividades Extracurriculares
					  
					    -- El alumno llegó a la cantidad de creditos requeridos en el módulo, reconocidos en forma parcial.
					    AND (SELECT SUM(ra2.creditos)
						       FROM sga_reconocimiento as r2
						       JOIN sga_reconocimiento_act as ra2 ON ra2.nro_tramite = r2.nro_tramite 
						      WHERE r2.alumno = sga_alumnos.alumno 
							    AND r2.estado = 'C' 
						        AND r2.tipo_reconocimiento = 'AM' 
						        AND ra2.elemento = vw_modulos_plan.elemento
						        AND ra2.reconocimiento_total = 'N'
							    AND ra2.estado = 'A'
							    AND ra2.rectificado = 'N'
						    ) >= sga_elementos_revision.parametros::decimal(8,2)
					    $where_modulos
					";
        
		} 
        
        
		// Agrego el ordenamiento
		$sql = $sql . " ORDER BY propuesta_nombre, actividad_codigo_y_nombre";

		return guarani_db::consultar($sql);
	}

	//Newsol - 06/2025 Agrego el campo anio_cursada para las divisiones y el anio que tiene aprobado el alumno
	/**
 	  * Retorna los datos correspondientes a un alumno en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
          * @param integer alumno id.
    	*/
	function get_datos_alumnos_filtro($alumno, $filtro)
	{
		$alumno = toba::db()->quote($alumno);

		if ($filtro) {
			$filtro = ' AND '.$filtro;
		}
		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						sga_alumnos.propuesta,
						sga_alumnos.regular,
						sga_alumnos.cnt_readmisiones,
						sga_alumnos.ubicacion,
						sga_alumnos.calidad,
						sga_alumnos.modalidad,
						sga_alumnos.division,
						sga_divisiones.nombre AS division_nombre,
						sga_propuestas.nombre AS propuesta_nombre,
						sga_alumnos.plan_version,
						sga_planes_versiones.plan,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						vw_personas.apellido,
						vw_personas.nombres,
						vw_personas.nro_documento,
						vw_personas.persona,
						sga_alumnos.anio_cursada,
						sga_propuestas_aspira.fecha_inscripcion,
						coalesce((select max(ep.anio_de_cursada) from sga_insc_cursada ic, sga_comisiones sc, sga_periodos_lectivos pl, sga_periodos p, sga_elementos_revision er, sga_elementos_plan ep
					 	where ic.alumno = sga_alumnos.alumno and ic.comision = sc.comision and sc.periodo_lectivo = pl.periodo_lectivo and ic.plan_version = ep.plan_version 
					 	and ep.elemento_revision = er.elemento_revision and er.elemento = sc.elemento and p.periodo = pl.periodo 
					 	and now() between p.fecha_inicio and p.fecha_fin),1) as anio
				FROM	sga_alumnos		
				JOIN	sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN 	sga_propuestas_aspira ON sga_propuestas_aspira.persona = sga_alumnos.persona and sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
				LEFT JOIN sga_divisiones ON sga_alumnos.division = sga_divisiones.division
				WHERE	sga_alumnos.alumno = $alumno
				$filtro
		";
		return guarani_db::consultar_fila($sql, null);
	}

	/**Newsol (NUEVO) - Retorna el listado de promedios de los alumnos
     	*/
	function get_reporte_promedios($where='')
	{

		$pfecha_desde = strpos($where, 'fecha_desde');
		$pfecha_hasta = strpos($where, 'fecha_hasta');

		$fecha_desde = date(substr($where, $pfecha_desde+21, 10));
		$fecha_hasta = date(substr($where, $pfecha_hasta+21, 10));

		$fecha_desde = date(toba::db()->quote($fecha_desde));
		$fecha_hasta = date(toba::db()->quote($fecha_hasta));
		
		$ppropuesta = strpos($where, 'propuesta');
		$where = substr($where, $ppropuesta, 16); 
		$where = " AND $where";
	
		
		$sql = "
				-- Actividades realizadas por el alumno
				CREATE TEMP TABLE _Tactividades_aux (elemento integer,id_acta integer,origen char(1), equivalencia_tramite integer, equivalencia integer, fecha date, escala_nota integer, nota varchar(10), resultado char(1), promediable char(1));
				CREATE TEMP TABLE _Tactividades (elemento integer,id_acta integer,origen char(1), equivalencia_tramite integer, equivalencia integer, fecha date, escala_nota integer, nota varchar(10), resultado char(1), promediable char(1));
  
				-- Propuesta del alumno (Incluye propuestas vinculadas)
				CREATE TEMP TABLE _temp_prop_alumno (alumno integer, propuesta integer, plan integer, plan_version integer);
				CREATE TEMP TABLE _temp_relacion (relacion integer); -- Tabla de propuestas relacionadas";
		
		guarani_db::ejecutar($sql);
		
		$sql = "
				SELECT				
					vw_personas.apellido_nombres AS apellido_y_nombres,
					vw_personas.tipo_nro_documento AS documento_principal,
					sga_alumnos.persona,
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					(select nombre from sga_propuestas where sga_propuestas.propuesta = sga_alumnos.propuesta) as propuesta_descripcion,
					f.promedio,
					f.promedio_sin_aplazos
				FROM
					sga_alumnos
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN LATERAL negocio_nsg.get_promedios_actividades_alumno(sga_alumnos.alumno, sga_alumnos.plan_version, $fecha_desde, $fecha_hasta, false) as f ON true
				WHERE 	f.promedio > 0
				$where
				ORDER BY	
					vw_personas.apellido,
					vw_personas.nombres
		";
		$datos = guarani_db::consultar($sql);
		
		$sql = "
				-- Borro tablas temporales.
				DROP TABLE _Tactividades;
				DROP TABLE _Tactividades_aux;
				DROP TABLE _temp_prop_alumno;
				DROP TABLE _temp_relacion;
				";
		guarani_db::ejecutar($sql);
		return $datos;
		
	}

}
?>