<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Personas/Alumnos
 */
class co_alumnos
{
	/**
	 * Obtiene una lista de todos los alumnos con los datos asociados al mismo (propuesta, plan, etc), y los datos de la 
	 * persona correspondiente a ese alumno. Una dada persona puede aparecer màs de una vez, ya que si está inscripta en
	 * varias propuestas se consideran alumnos diferentes.
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 		sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							sga_propuestas.codigo AS propuesta_codigo,
							sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado AS propuesta_descr,
							sga_alumnos.plan_version,
							sga_planes_versiones.version AS plan_version_codigo,
							sga_planes_versiones.nombre AS plan_version_nombre,
							sga_planes_versiones.plan,
							sga_planes.codigo AS plan_codigo,
							sga_planes.nombre AS plan_nombre,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_alumnos.modalidad,
							sga_modalidad_cursada.nombre AS modalidad_descr,
							sga_alumnos.cnt_readmisiones,
							".guarani_sql::get_case_si_no('sga_alumnos', 'regular', 'regular_descr').",
							sga_alumnos.calidad,
							sga_alumnos_calidad.nombre AS calidad_descr,
							vw_personas.apellido,
        					vw_personas.nombres,
        					vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							vw_personas.nacionalidad,
				        	vw_personas.usuario,
				        	vw_personas.documento,
				        	vw_personas.nro_documento,
				        	vw_personas.tipo_documento,
				        	vw_personas.desc_tipo_documento
				FROM 		sga_alumnos,
							vw_personas,
							sga_alumnos_calidad,
							sga_modalidad_cursada,
							sga_ubicaciones,
							sga_propuestas,
							sga_planes_versiones,
							sga_planes
				WHERE  		sga_alumnos.persona = vw_personas.persona AND
							sga_alumnos.calidad = sga_alumnos_calidad.calidad AND
							sga_alumnos.modalidad = sga_modalidad_cursada.modalidad AND
							sga_alumnos.ubicacion = sga_ubicaciones.ubicacion AND
							sga_alumnos.propuesta = sga_propuestas.propuesta AND
							sga_alumnos.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan
							$where
				ORDER BY	vw_personas.apellido, 
							vw_personas.nombres,
							sga_propuestas.nombre
				;";
		
		return guarani_db::consultar($sql);
		
	}

	/**
	 * Retorna el listado de alumnos.
	 * Si el usuario logueado tiene perfil de datos con dominio "Responsables Académicas"
	 * restringe los alumnos de aquellas propuestas que gestiona la RA.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_perfil_de_datos($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 		sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							vw_perfil_de_datos_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							sga_alumnos.plan_version,
							sga_planes_versiones.version as plan_version_codigo,
							sga_planes.codigo as plan_codigo,
							sga_ubicaciones.nombre as ubicacion_nombre,
							vw_personas.apellido,
        					vw_personas.nombres,
							vw_personas.apellido_nombres as apellido_y_nombre,
							vw_personas.tipo_nro_documento as documento_principal
				FROM 		sga_alumnos,
							vw_personas,
							sga_ubicaciones,
							vw_perfil_de_datos_propuestas,
							sga_planes_versiones,
							sga_planes
				WHERE  		sga_alumnos.persona = vw_personas.persona AND
							sga_alumnos.ubicacion = sga_ubicaciones.ubicacion AND
							sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta AND
							sga_alumnos.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan
							$where
				ORDER BY	vw_personas.apellido, 
							vw_personas.nombres,
							vw_perfil_de_datos_propuestas.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de todos los alumnos con calidad de activos, según el filtro recibido.
	 *
	 * @param str $where filtro sobre los diferentes atributos de los alumnos a recuperar.
	 */
	function get_alumnos_activos($where='')
	{
		$alumnos_activos = " sga_alumnos.calidad = '".comunes::estado_activo."'";
		$where .= $where ? " AND $alumnos_activos" : $alumnos_activos;
		return $this->get_listado($where);
	}
	
	/**
	 * Retorna el listado de todos los alumnos con calidad activo e inscripcion a propuesta aceptada o pendiente.
	 *
	 * @param str $where filtro sobre los diferentes atributos de los alumnos a recuperar.
	 */
	function get_alumnos_activos_con_insc_aceptada_pendiente($where = '')
	{
		// Alumnos con una inscripcion en la propuesta en estado aceptada/pendiente
		$insc_propuesta = " EXISTS (SELECT 1 
					FROM sga_propuestas_aspira
					JOIN sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
					WHERE sga_propuestas_aspira.propuesta	= sga_alumnos.propuesta 
					  AND sga_propuestas_aspira.persona	= sga_alumnos.persona 
					  AND sga_situacion_aspirante.resultado_asp IN ('". propuesta::inscripcion_aceptada . "','" . propuesta::inscripcion_pendiente ."')
					  ) "; 
					  
		$where .= $where ? " AND $insc_propuesta" : $insc_propuesta;
		return $this->get_alumnos_activos($where);
	}

	
	
	/**
	 * Obtiene una lista de alumnos inscriptos a propuesta.
	 * Los datos del plan, modalidad y ubicación se obtienen del alumno y NO de la inscripción.
	 *
	 * @param str $where filtro sobre los alumnos inscriptos a retornar.
	 * @return array
	 */
	function get_alumnos_inscriptos($where='', $select='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		$select
							vw_personas.apellido,
							vw_personas.nombres,
							sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							vw_personas.apellido_nombres AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS nro_identificacion,
							sga_propuestas.codigo AS propuesta_codigo,
							sga_propuestas.nombre AS propuesta_nombre,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_descr,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_planes.nombre AS plan_nombre,
							sga_planes_versiones.plan_version,
							sga_planes_versiones.version,
							sga_planes_versiones.nombre AS version_nombre,
							sga_modalidad_cursada.nombre AS modalidad_nombre,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_propuestas_aspira.propuesta_aspira,
							to_char(sga_propuestas_aspira.fecha_inscripcion,'".comunes::formato_fecha_visual."') AS fecha_formato_visual
				FROM		sga_alumnos,
							vw_personas,
							sga_propuestas,
							sga_planes,
							sga_planes_versiones,
							sga_modalidad_cursada,
							sga_ubicaciones,
							sga_propuestas_aspira,
							sga_situacion_aspirante
				WHERE		sga_alumnos.persona = vw_personas.persona AND
							sga_alumnos.propuesta = sga_propuestas.propuesta AND
							sga_alumnos.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_alumnos.modalidad = sga_modalidad_cursada.modalidad AND
							sga_alumnos.ubicacion = sga_ubicaciones.ubicacion AND
							sga_alumnos.persona = sga_propuestas_aspira.persona AND
							sga_alumnos.propuesta = sga_propuestas_aspira.propuesta AND
							sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}
	
	
	/**
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
					JOIN LATERAL get_promedios_actividades_alumno(sga_alumnos.alumno, sga_alumnos.plan_version, false) as f ON true
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
	
	/**	 
	 * Obiene la lista de alumnos inscriptos a propuesta según filtros especificados para la solicitud de certificado.
	 *
	 * @param str $where filtro sobre los alumnos inscriptos a retornar.
	 * @return array
	 */
	function get_alumnos_inscriptos_certificado($where='')
	{
		$where = empty($where) ? 'true' : $where;
		$where .= " AND (sga_alumnos.calidad = '".alumno::calidad_activo."' OR
						sga_alumnos.calidad = '".alumno::calidad_pasivo ."' AND
						EXISTS (SELECT	sga_certificados_otorg.nro_solicitud
								FROM	sga_certificados_otorg
								WHERE	sga_certificados_otorg.alumno = sga_alumnos.alumno AND
										sga_certificados_otorg.anulado = 0 AND 
										(sga_certificados_otorg.estado IS NULL OR
										sga_certificados_otorg.estado IN (".tramite_certificacion::estado_adelantado.",".tramite_certificacion::estado_observado.")
										)
								)
						)
		";
		$where .= " AND sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."'";
					
		return $this->get_alumnos_inscriptos($where);
	}

	/**	 
	 * Obiene la lista de alumnos inscriptos a propuesta según filtros especificados para la generación de legajos.
	 *
	 * @param str	$where	filtro sobre los alumnos inscriptos a retornar.
	 * @return array
	 */
	function get_alumnos_inscriptos_gen_legajo($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT		vw_personas.apellido,
							vw_personas.nombres,
							vw_personas.persona,
							sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							vw_personas.apellido_nombres 	AS apellido_y_nombres,
							vw_personas.tipo_nro_documento 	AS nro_identificacion,
							sga_propuestas.codigo 			AS propuesta_codigo,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_planes.nombre 				AS plan_nombre,
							sga_planes_versiones.plan_version,
							sga_modalidad_cursada.nombre 	AS modalidad_nombre,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre			AS ubicacion_nombre,
							sga_propuestas_aspira.propuesta_aspira,
							sga_situacion_aspirante.resultado_asp AS propuesta_inscripcion,
							to_char(sga_propuestas_aspira.fecha_inscripcion,'".comunes::formato_fecha_visual."') AS fecha_formato_visual,
							(	SELECT	alumno_con_leg.legajo
								FROM	sga_alumnos as alumno_con_leg
								WHERE	alumno_con_leg.persona = sga_alumnos.persona AND
										alumno_con_leg.propuesta <> sga_alumnos.propuesta AND
										alumno_con_leg.legajo IS NOT NULL
								LIMIT	1
							) AS legajo_otra_propuesta
				FROM sga_alumnos
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN sga_modalidad_cursada ON sga_alumnos.modalidad = sga_modalidad_cursada.modalidad
				JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona	AND	sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
				JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc
				WHERE sga_alumnos.calidad = '".alumno::calidad_activo."'
				  AND sga_alumnos.legajo IS NULL
				  $where
				ORDER BY vw_personas.apellido, vw_personas.nombres
			";

		return guarani_db::consultar($sql);
	}

    /**
     * Retorna los datos correspondientes a los diferentes motivos de cambios de ubicación posibles, según el filtro dado.
     */
    function get_motivos_ubicaciones($where = '')
    {
    	if ($where) {
    		$where = ' WHERE ' . $where;			        
    	}
    	$motivos_estados = $this->get_opciones_combo_estado_motivo();
    	$sql = "SELECT 		sga_alumnos_motivos_ubicacion.motivo_ubicacion,
							sga_alumnos_motivos_ubicacion.nombre as motivo_ubicacion_nombre,
							sga_alumnos_motivos_ubicacion.descripcion as motivo_ubicacion_descripcion,
							".guarani_sql::get_case('sga_alumnos_motivos_ubicacion', 'estado', 'motivo_ubicacion_estado_descr', $motivos_estados)."
				FROM 		sga_alumnos_motivos_ubicacion
				$where
				ORDER BY 	sga_alumnos_motivos_ubicacion.nombre
				;";
		
		return guarani_db::consultar($sql);
    }
    
    /**
 	  * Retorna la descripción del motivo del cambio de ubicación en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna las descripciones de los motivos de cambios de ubicación según la condición del array 
 	  * (para la carga masiva).
      *	@param integer/array	motivo_cambio_ubic	id o arreglo de ids del o de los motivos para los cuales se consultan los datos.
    */
	function get_motivo_cambio_ubicacion_datos($motivo_cambio_ubic)
	{
		if (is_array($motivo_cambio_ubic)) {
			$filtro = guarani_sql::armar_condicion_in($motivo_cambio_ubic, 'sga_alumnos_motivos_ubicacion', 'motivo_ubicacion');
			$datos = $this->get_motivos_ubicaciones($filtro);
			return $datos;
		} 
		$motivo_cambio_ubic = toba::db()->quote($motivo_cambio_ubic);
		$datos = $this->get_motivos_ubicaciones("sga_alumnos_motivos_ubicacion.motivo_ubicacion = $motivo_cambio_ubic");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
     * Retorna los datos correspondientes a los diferentes motivos de cambios de calidad posibles, según el filtro dado.
     */
    function get_motivos_calidades($where = '')
    {
    	if ($where) {
    		$where = ' WHERE ' . $where;			        
    	}
    	$motivos_estados = $this->get_opciones_combo_estado_motivo();
    	$sql = "SELECT 		sga_alumnos_motivos_calidad.motivo_calidad,
    						sga_alumnos_motivos_calidad.calidad,
							sga_alumnos_motivos_calidad.nombre as motivo_calidad_nombre,
							sga_alumnos_motivos_calidad.descripcion as motivo_calidad_descripcion,
							".guarani_sql::get_case('sga_alumnos_motivos_calidad', 'estado', 'motivo_calidad_estado_descr', $motivos_estados)."
				FROM 		sga_alumnos_motivos_calidad
							$where
				ORDER BY 	sga_alumnos_motivos_calidad.nombre
				;";
		return guarani_db::consultar($sql);
    }
    
    /**
 	  * Retorna la descripción del motivo del cambio de calidad en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna las descripciones de los motivos de cambios de calidad según la condición del array 
 	  * (para la carga masiva).
      *	@param integer/array	motivo_cambio_calidad	id o arreglo de ids del o de los motivos para los cuales se consultan los datos.
    */
	function get_motivo_cambio_calidad_datos($motivo_cambio_calidad)
	{
		if (is_array($motivo_cambio_calidad)) {
			$filtro = guarani_sql::armar_condicion_in($motivo_cambio_calidad, 'sga_alumnos_motivos_calidad', 'motivo_calidad');
			$datos = $this->get_motivos_calidades($filtro);
			return $datos;
		} 
		$motivo_cambio_calidad = toba::db()->quote($motivo_cambio_calidad);
		$datos = $this->get_motivos_calidades("sga_alumnos_motivos_calidad.motivo_calidad = $motivo_cambio_calidad");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
 	  * Retorna los datos de los motivos del cambio de calidad para una Calidad dada.
      *	@param integer	calidad	id de la calidad para la cual se consultan los motivos de cambio.
    */
	function get_opciones_combo_motivos_calidad($calidad)
	{
		$calidad = toba::db()->quote($calidad);
		$datos = $this->get_motivos_calidades("sga_alumnos_motivos_calidad.calidad = $calidad");
		return $datos;
	}
	
	    /**
     * Retorna los datos correspondientes a los diferentes motivos de cambios de modalidad posibles, según el filtro dado.
     */
    function get_motivos_modalidades($where = '')
    {
    	if ($where) {
    		$where = ' WHERE ' . $where;			        
    	}
    	$motivos_estados = $this->get_opciones_combo_estado_motivo();
    	$sql = "SELECT 		sga_alumnos_motivos_modalidad.motivo_modalidad,
							sga_alumnos_motivos_modalidad.nombre as motivo_modalidad_nombre,
							sga_alumnos_motivos_modalidad.descripcion as motivo_modalidad_descripcion,
							".guarani_sql::get_case('sga_alumnos_motivos_modalidad', 'estado', 'motivo_modalidad_estado_descr', $motivos_estados)."
				FROM 		sga_alumnos_motivos_modalidad
				$where
				ORDER BY 	sga_alumnos_motivos_modalidad.nombre
				;";
		
		return guarani_db::consultar($sql);
    }
    
    /**
 	  * Retorna la descripción del motivo del cambio de modalidad en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna las descripciones de los motivos de cambios de modalidad según la condición del array 
 	  * (para la carga masiva).
      *	@param integer/array	motivo_cambio_mod	id o arreglo de ids del o de los motivos para los cuales se consultan los datos.
    */
	function get_motivo_cambio_modalidad_datos($motivo_cambio_mod)
	{
		if (is_array($motivo_cambio_mod)) {
			$filtro = guarani_sql::armar_condicion_in($motivo_cambio_mod, 'sga_alumnos_motivos_modalidad', 'motivo_modalidad');
			$datos = $this->get_motivos_modalidades($filtro);
			return $datos;
		} 
		$motivo_cambio_mod = toba::db()->quote($motivo_cambio_mod);
		$datos = $this->get_motivos_modalidades("sga_alumnos_motivos_modalidad.motivo_modalidad = $motivo_cambio_mod");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
 	  * Retorna los datos de las calidades a las cuales se puede cambiar, realizando un cambio manual, a partir de la 
 	  * calidad actual. La calidad actual puede ser nula en cuyo caso se retornan todas las calidades destino posibles 
 	  * (esto se utiliza para obtener el valor del combo cuando la calidad ya está seteada (es decir ante la edición de
 	  * un cambio de calidad).
      *	@param integer	calidad_origen	id de la calidad a partir de la cual se realiza el cambio.
    */
	function get_calidades_habilitadas_cambio($calidad_origen=null)
	{
		$where = '';
		if (!is_null($calidad_origen)) {
			$calidad_origen = toba::db()->quote($calidad_origen);
			$where = " AND sga_alumnos_calidad_relacion.calidad_origen = $calidad_origen ";
		}
		$sql = "SELECT 		sga_alumnos_calidad_relacion.calidad_origen,
    						sga_alumnos_calidad_relacion.calidad_destino,
    						".guarani_sql::get_case_si_no('sga_alumnos_calidad_relacion', 'cambio_manual', 'cambio_manual_descr').",
    						sga_alumnos_calidad.nombre as calidad_destino_nombre,
    						sga_alumnos_calidad.descripcion as calidad_destino_descripcion
				FROM 		sga_alumnos_calidad_relacion,
							sga_alumnos_calidad
				WHERE		sga_alumnos_calidad_relacion.calidad_destino = sga_alumnos_calidad.calidad AND
							sga_alumnos_calidad_relacion.cambio_manual = 'S' 
							$where
				ORDER BY 	sga_alumnos_calidad.nombre
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
 	  * Retorna los datos de la calidad de un Alumno en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de las calidades de alumnos según la condición del array (para la carga masiva).
      *	@param integer/array	calidad	id o arreglo de ids de la o de las calidades para las cuales se consultan los datos.
    */
	function get_alumno_calidad_datos($calidad)
	{
		if (is_array($calidad)) {
			$filtro = guarani_sql::armar_condicion_in($calidad, 'sga_alumnos_calidad', 'calidad');
			$datos = $this->get_calidades_alumnos($filtro);
			return $datos;
		}
		$calidad = toba::db()->quote($calidad);
		$datos = $this->get_calidades_alumnos("sga_alumnos_calidad.calidad = $calidad");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
	 * Retorna las opciones correspondientes a la calidad del alumno.
    */
    function get_calidades_alumnos($where='')
    {
		if ($where) {
    		$where = ' WHERE ' . $where;			        
    	}
    	$sql = "SELECT 		sga_alumnos_calidad.calidad,
    						sga_alumnos_calidad.nombre as calidad_nombre,
    						sga_alumnos_calidad.descripcion as calidad_descr
				FROM 		sga_alumnos_calidad
				$where
				ORDER BY 	sga_alumnos_calidad.nombre
				;";
		
		return guarani_db::consultar($sql);
    }
	
    /**
 	  * Retorna los datos de la modalidad de cursada de un Alumno en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de las modalidades de cursada según la condición del array (para la carga masiva).
      *	@param integer/array	modalidad	id o arreglo de ids de la o de las modalidades para las cuales se consultan los datos.
    */
    function get_alumno_modalidad_datos($modalidad)
	{
		if (is_array($modalidad)) {
			$filtro = guarani_sql::armar_condicion_in($modalidad, 'sga_modalidad_cursada', 'modalidad');
			$datos = $this->get_modalidades_cursadas_alumnos($filtro);
			return $datos;
		}
		$modalidad = toba::db()->quote($modalidad);
		$datos = $this->get_modalidades_cursadas_alumnos("sga_modalidad_cursada.modalidad = $modalidad");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
	 * Retorna las opciones correspondientes a las modalidades de cursada del alumno en una propuesta.
    */
    function get_modalidades_cursadas_alumnos($where='')
    {
		if ($where) {
    		$where = ' WHERE ' . $where;			        
    	}
    	$sql = "SELECT 		sga_modalidad_cursada.modalidad,
    						sga_modalidad_cursada.nombre as modalidad_nombre,
    						sga_modalidad_cursada.descripcion as modalidad_descr
				FROM 		sga_modalidad_cursada
				$where
				ORDER BY 	sga_modalidad_cursada.nombre
		";
		
		return guarani_db::consultar($sql);
    }

	 /**
	 * Obtiene los ids y nombres de todas las modalidades de cursada de alumnos en una propuesta, según el filtro dado. 
	 * Si el parámetro mesa no es nulo, se retorna además un campo requerido que será true o false dependiendo de si 
	 * la modalidad en cuestión está o no asociada la mesa dada. En caso contrario el campo requerido será siempre true 
	 * (utilizado para un guarani_ei_formulario_ml).
	 * 
	 * @param integer	$mesa	id de la mesa a la que se asocian o no las modalidades.
	 * @param str		$where		filtro sobre las modalidades a retornar.
	 */
	function get_modalidades_asignadas_a_mesa($mesa=null, $where='')
	{
		$filtro = '';
		if ($where) {
			$where .= ' WHERE ' . $where;
		}
		$select = '';
		$from = '';
		if (!empty($mesa)) {
			$mesa = toba::db()->quote($mesa);
			$select .= ", $mesa as mesa_examen	
						, CASE
							WHEN sga_mesas_examen_modalidad.mesa_examen IS NULL 
								THEN false
                    			ELSE true
							END AS requerido
				        ";
			$from = " LEFT JOIN sga_mesas_examen_modalidad ON (sga_mesas_examen_modalidad.modalidad = sga_modalidad_cursada.modalidad AND sga_mesas_examen_modalidad.mesa_examen = $mesa) ";
		} else {
			//Si se buscan TODAS las modalidades, sin que estén asociadas a la mesa, entonces se setea para todas ellas
			//el campo requerido como false.
			$select .= ', 0 AS requerido';
		}

		$sql = "SELECT 		sga_modalidad_cursada.modalidad,
    						sga_modalidad_cursada.nombre as modalidad_nombre,
    						sga_modalidad_cursada.descripcion as modalidad_descr
				            $select
				FROM	    sga_modalidad_cursada
				            $from
				$where					
				ORDER BY	sga_modalidad_cursada.nombre
				;";
		return guarani_db::consultar($sql);
	}
	
    /**
     * Retorna las Ubicaciones posibles para realizar un nuevo cambio de Ubicación de un Alumno en una Propuesta.
     * Para esto se toma en cuenta las ubicaciones donde la propuesta se oferta, y se excluye, si existe, la ubicación
     * actual del Alumno (ya que no tiene sentido realizar un cambio a la misma ubicación).
    */
    function get_ubicaciones_habilitadas_cambio($propuesta, $ubicacion_actual=null)
    {
    	$where = '';
    	if (!is_null($ubicacion_actual)) {
			$ubicacion_actual = toba::db()->quote($ubicacion_actual);
    		$where = "sga_propuestas_oferta.ubicacion <> $ubicacion_actual";			        
    	}
    	return toba::consulta_php('co_propuestas')->get_propuesta_ubicaciones($propuesta, $where, false);
    }
    
    /**
     * Retorna las Modalidades posibles para realizar un nuevo cambio de Modalidad de un Alumno en una Plan.
     * Para esto se toma en cuenta las modalidades definidas para las actividades del plan, y se excluye, si existe, 
     * la modalidad actual del Alumno en el Plan (ya que no tiene sentido realizar un cambio a la misma modalidad).
    */
    function get_modalidades_habilitadas_cambio($plan_version, $modalidad_actual=null)
    {
    	$where = '';
    	if (!is_null($modalidad_actual)) {
			$modalidad_actual = toba::db()->quote($modalidad_actual);
    		$where = "sga_planes_modalidad.modalidad <> $modalidad_actual";			        
    	}
    	return toba::consulta_php('co_planes')->get_modalidades_plan_version($plan_version, $where);
    }
    
    /**
     * Retorna las opciones correspondientes al estado del motivo de cambios de ubicación para un alumno.
    */
    function get_opciones_combo_estado_motivo()
    {
		return array(	array('valor' => comunes::estado_activo , 		'descr' => comunes::estado_activo_desc),
						array('valor' => comunes::estado_baja , 		'descr' => comunes::estado_baja_desc)
					);
    }
    
    /**
	 * Obtiene una lista de todos los alumnos extranjeros con la residencia o visa vencida según el filtro recibido
	 */
	function get_alumnos_residencia_visa_vencida($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT                  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)  as apellido,
                                                COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
						mdp_tipo_documento.desc_abreviada || ' ' || mdp_personas_documentos.nro_documento as persona_documento,
						mug_paises.nombre as pais_nombre,
						mdp_tipo_visa.descripcion as tipo_visa_nombre,
						mdp_tipo_residencia.descripcion	as tipo_residencia_nombre,
						mdp_personas_extranjeros.otorgamiento_visa,
						mdp_personas_extranjeros.vencimiento_visa,
						mdp_personas_extranjeros.otorgamiento_residencia,
						mdp_personas_extranjeros.vencimiento_residencia,
						".guarani_sql::get_case_si_no('mdp_personas_extranjeros', 'residencia_cupo')."
				FROM mdp_personas
				JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
				JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
				JOIN mug_paises ON mdp_personas_documentos.pais_documento = mug_paises.pais
				JOIN mdp_personas_extranjeros ON mdp_personas.persona = mdp_personas_extranjeros.persona
				LEFT JOIN mdp_tipo_visa ON mdp_personas_extranjeros.tipo_visa = mdp_tipo_visa.tipo_visa
				LEFT OUTER JOIN mdp_tipo_residencia ON mdp_personas_extranjeros.tipo_residencia = mdp_tipo_residencia.tipo_residencia
				
				$where
				
				ORDER BY  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
  		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna las opciones Visa / Residencia
    */
    function get_visa_residencia()
    {
		return array(array('valor' => persona::visa,'descr' => persona::visa_desc),
		             array('valor' => persona::residencia,'descr' => persona::residencia_desc));
    }
    
    function get_alumnos_x_prop_aspira_pendientes($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		$sql = "SELECT 	sga_requisitos_aspirante.propuesta_aspira,
						sga_requisitos_aspirante.requisito,
						sga_propuestas_aspira.persona,
						sga_propuestas_aspira.propuesta,
						vw_personas.apellido,
        				vw_personas.nombres,
        				vw_personas.tipo_nro_documento 	AS persona_nro_documento,
        				sga_propuestas.nombre		 	AS propuesta_nombre
		
				FROM 	sga_propuestas_aspira,
						sga_situacion_aspirante,
						sga_requisitos_aspirante,
						vw_personas,
						sga_propuestas
						
				WHERE 	sga_propuestas_aspira.situacion_asp			= sga_situacion_aspirante.situacion_asp
				AND		sga_situacion_aspirante.resultado_asp		= '".propuesta::inscripcion_pendiente."'
				AND		sga_propuestas_aspira.persona				= vw_personas.persona
				AND		sga_propuestas_aspira.propuesta				= sga_propuestas.propuesta
				AND		sga_requisitos_aspirante.propuesta_aspira	= sga_propuestas_aspira.propuesta_aspira
				AND  	sga_requisitos_aspirante.exceptuado   		= 'N'
						$where
				
				ORDER BY vw_personas.apellido
				        ;";
		
		return guarani_db::consultar($sql);
	}
	
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
						vw_personas.persona
				FROM	sga_alumnos		
				JOIN	sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				LEFT JOIN sga_divisiones ON sga_alumnos.division = sga_divisiones.division
				WHERE	$filtro
		";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**
 	  * Retorna los datos basicos de un alumno usado en las reglas de los requisitos.
		Desde las reglas debe utilizarse este metodo para obtener datos de un alumno porque es mas eficiente que usar el metodo get_datos_alumno o get_datos_basicos_alumno.
      
	  *	@param		integer		$alumno		ID de Alumno
      *	@param		boolean		$filtrar_perfil_de_datos		Indica si se desea utilizar perfil de datos.
      *	@return		array					Datos basicos del alumno.
	  
    */
	function get_datos_alumno_reglas($alumno, $filtrar_perfil_de_datos=true)
	{
		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.persona,
						sga_alumnos.propuesta,
						sga_alumnos.plan_version,
						sga_alumnos.regular,
						sga_alumnos.calidad,
						sga_planes_versiones.plan
				FROM	sga_alumnos		
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
				WHERE	sga_alumnos.alumno = $alumno
		";
		return guarani_db::consultar_fila($sql, null, $filtrar_perfil_de_datos);
	}



	function get_datos_alumno_sin_perfil_datos($alumno)
	{
		return $this->get_datos_alumno($alumno, false);
	}

	/**
 	  * Retorna el apellido y nombre de un alumno
      *	@param integer	$alumno Identificacion del alumno.
	  * @return	string	$apellido_nombre	Apellido y Nombres del alumno.
    */
	function get_alumno_nombre($alumno)
	{
		$apellido_nombre = '';
		if (!isset($alumno) || trim($alumno) == '') {
			return $apellido_nombre;
		}
		$sql = "SELECT	vw_personas_nombre.apellido_nombres
				FROM	sga_alumnos
						JOIN vw_personas_nombre ON sga_alumnos.persona = vw_personas_nombre.persona
				 WHERE	sga_alumnos.alumno = $alumno
		";
		$resultado = guarani_db::consultar_fila($sql, null, false);
		if (! empty($resultado)) {
			$apellido_nombre = $resultado['apellido_nombres'];
		} 
		return $apellido_nombre;
	}

	/**
	 * Retorna el nombre de la propuesta de un alumno dado.
	 *
	 * @param integer $alumno
	 * @return string
	 */
	function get_alumno_propuesta_nombre($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_propuestas.nombre
				FROM	sga_alumnos,
						sga_propuestas
				WHERE	sga_alumnos.propuesta = sga_propuestas.propuesta AND
						sga_alumnos.alumno = $alumno
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['nombre'];
	}
	
	/**
	 * Retorna la propuesta de un alumno dado.
	 *
	 * @param integer $alumno
	 * @return integer
	 */
	function get_alumno_propuesta($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_alumnos.propuesta
				FROM	sga_alumnos
				WHERE	sga_alumnos.alumno = $alumno
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['propuesta'];
	}

	/**
	 * Obtiene el alumno asociado a un plan versión. Se utiliza para planes de tipo Personalizado, donde este
	 * será único.
	 */
	function get_alumno_de_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.persona,
						sga_alumnos.ubicacion
				FROM	sga_alumnos
				WHERE	sga_alumnos.plan_version = $plan_version
				";
		return guarani_db::consultar_fila($sql);
	}

	/**
 	  * Retorna los datos básicos correspondientes a un alumno.
      *	@param integer	id del alumno.
    */
	function get_datos_basicos_alumno($alumno, $filtrar_perfil_de_datos=true)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro = "sga_alumnos.alumno = $alumno";

		$sql = "SELECT	vw_personas.persona,
						sga_alumnos.alumno,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						upper(substring(vw_personas.apellido,1,1)) AS inicial_apellido,
						sga_alumnos.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_nombre,
						sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
						sga_planes_versiones.plan,
						sga_alumnos.plan_version,
						sga_alumnos.ubicacion,
						sga_ubicaciones.nombre AS ubicacion_nombre,
                        sga_planes.plan,
						sga_planes.nombre AS plan_nombre,
						sga_planes_versiones.nombre AS plan_version_nombre,
						sga_alumnos.modalidad,
						sga_alumnos.calidad as calidad,
						sga_alumnos.coeficiente
				FROM	sga_alumnos
				JOIN	vw_personas 			ON vw_personas.persona 					= sga_alumnos.persona
				JOIN	sga_propuestas 			ON sga_propuestas.propuesta 			= sga_alumnos.propuesta
				JOIN	sga_planes_versiones	ON sga_planes_versiones.plan_version 	= sga_alumnos.plan_version
				JOIN	sga_ubicaciones 		ON sga_ubicaciones.ubicacion 			= sga_alumnos.ubicacion
				JOIN	sga_planes 				ON sga_planes.plan 						= sga_planes_versiones.plan
				WHERE	$filtro
		";
		return guarani_db::consultar_fila($sql, null, $filtrar_perfil_de_datos);
	}

	/**
	 * Retorna los datos de un alumno a partir de una persona y una propuesta.
	 * 
	 * @param int $persona
	 * @param int $propuesta
	 * @param string $filtrar_perfil_de_datos
	 * @return array
	 */
	function get_alumno($persona, $propuesta)
	{
		$persona = toba::db()->quote($persona);
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.plan_version,
						sga_alumnos.ubicacion,
						sga_alumnos.modalidad,
						sga_alumnos.calidad
				FROM	sga_alumnos
				WHERE	sga_alumnos.persona = $persona AND
						sga_alumnos.propuesta = $propuesta
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el identificador de persona correspondiente a un alumno dado.
	 *
	 * @param integer $alumno
	 * @return integer
	 */
	function get_persona_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_alumnos.persona
				FROM	sga_alumnos
				WHERE	sga_alumnos.alumno = $alumno
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['persona'];
	}

	/**
	 * Retorna el identificador de persona y el tipo de propuesta asociado a un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_persona_proptipo_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_alumnos.persona,
						sga_propuestas.propuesta_tipo
				FROM	sga_alumnos,
						sga_propuestas
				WHERE	sga_alumnos.alumno = $alumno
						AND sga_propuestas.propuesta = sga_alumnos.propuesta;
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Retorna los datos de todos los alumnos en calidad 'Activo' de las propuestas dadas como parámetro,
	 * que NO ESTAN INCLUIDOS en alguna otra acta (no anulada) de una comisión.
	 *
	 * @param integer	$evaluacion			código de evaluación para el control de pertenencia a un acta.
	 * @param integer	$acta				código de acta a la que se quieren asociar los alumnos.
	 * @param integer	$instancia			código de instancia a la cual pertenece el acta.
	 * @param str		$where				filtro sobre los alumnos a retornar.
	 */
	function get_otros_alumnos_acta_cursada($evaluacion, $acta=null, $instancia=null, $where = '')
	{
		$evaluacion = toba::db()->quote($evaluacion);

		// Se botienen la comisión, elemento y fecha de inicio del periodo lectivo 
		$sql = "SELECT	sga_comisiones.comision, 
						to_char(sga_periodos.fecha_inicio,'YYYY-MM-DD') as fecha_inicio_periodo_lectivo,
						sga_comisiones.elemento								 
				FROM	sga_evaluaciones
						JOIN sga_comisiones ON sga_evaluaciones.entidad = sga_comisiones.entidad
						JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
						JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				WHERE	sga_evaluaciones.evaluacion = $evaluacion";
		$rs = guarani_db::consultar_fila($sql);
		$comision = $rs['comision'];
		$elemento = $rs['elemento'];
		$fecha_inicio_periodo_lectivo = $rs['fecha_inicio_periodo_lectivo'];

		$filtro_acta_instancia = '';
		if (!empty($acta) && !empty($instancia)) {
			if ($instancia == comision::inst_cursar) {
				$campo_instancia = 'instancia_cursada';
				$campo_acta = 'id_acta_cursada';
				$campo_acta_cerrada = 'acta_cursada_cerrada';
				$origen = acta::origen_regulares;
			} else {
				$campo_instancia = 'instancia_promocion';
				$campo_acta = 'id_acta_promocion';
				$campo_acta_cerrada = 'acta_promocion_cerrada';
				$origen = acta::origen_promociones;
			}
			$acta = toba::db()->quote($acta);
			$origen = toba::db()->quote($origen);

			$filtro_acta_instancia = "AND sga_alumnos.alumno NOT IN (	SELECT sga_actas_detalle.alumno
																		FROM	sga_actas
																				JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
																		WHERE	sga_actas.comision = $comision AND
																				sga_actas.id_acta <> $acta AND
																				sga_actas.origen = $origen AND
																				sga_actas.estado = '".acta::estado_cerrada."' AND
																				sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
																				sga_actas_detalle.rectificado = '".comunes::no."'
																		UNION
																		SELECT	sga_eval_detalle_cursadas.alumno
																		FROM	sga_evaluaciones 
																				JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion 
																		WHERE	sga_evaluaciones.evaluacion = $evaluacion AND
																				sga_eval_detalle_cursadas.$campo_instancia = 1 AND
																				sga_eval_detalle_cursadas.$campo_acta IS NOT NULL AND
																				sga_eval_detalle_cursadas.$campo_acta <> $acta AND
																				sga_eval_detalle_cursadas.$campo_acta_cerrada = 0)";
		}

		if (!empty($where)) {
			$where = " AND $where";
		}

		$sql = "SELECT		sga_alumnos.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado
				FROM		sga_alumnos
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				WHERE		sga_alumnos.calidad = '".alumno::calidad_activo."' AND
							sga_alumnos.propuesta IN (SELECT DISTINCT propuesta FROM sga_comisiones_propuestas WHERE comision = $comision) AND
							-- La comisión debe estar asociada al plan del alumno.
							-- La actividad debe pertenecer al plan-versión que tenía el alumno en la fecha de inicio del período lectivo.
							get_plan_version_alumno(sga_alumnos.alumno, '$fecha_inicio_periodo_lectivo') IN (	SELECT	DISTINCT vw_elementos_plan.plan_version
																												FROM	sga_comisiones_propuestas
																														JOIN vw_elementos_plan ON sga_comisiones_propuestas.plan = vw_elementos_plan.plan AND vw_elementos_plan.elemento = $elemento
																												WHERE	sga_comisiones_propuestas.comision = $comision)
							$filtro_acta_instancia
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de todos los alumnos en calidad 'Activo' de las propuestas asociadas a la Mesa de Exámen dada como
	 * parámetro, que estén disponibles para asociar a un acta de la Mesa. Esto significa que no estén activos en algún otro 
	 * acta no anulada (abierta o cerrada) de la mesa de exámen (evaluación de la entidad correspondiente a la mesa).
	 *
	 * @param str	$where	filtro sobre los alumnos a retornar.
	 */
	function get_otros_alumnos_disponibles_acta_examen($evaluacion, $param_exa_rendir_regularizada_plan_anterior, $where = '')
	{
		$evaluacion = toba::db()->quote($evaluacion);
		if ($where) {
			$where = ' AND ' . $where;
			/**
			 * El literal [EVALUACION] se agrega en el where en el método filtro_alumnos_otros_disponibles de cn_ent_acta_examen 
			 * para que se reemplace por el valor, para que sea mas eficiente la subquery 
			 */
			$where = str_replace("[EVALUACION]", $evaluacion , $where);
		}
		// Datos del llamado-mesa 
		$sql_datos	= "SELECT	sga_llamados_mesa.llamado_mesa as llamado_mesa,
								to_char(sga_llamados_mesa.fecha, 'YYYY-MM-DD') as fecha_mesa,
								sga_llamados_mesa.mesa_examen as mesa_examen,
								sga_mesas_examen.elemento as elemento
						FROM	sga_evaluaciones
								JOIN sga_llamados_mesa ON sga_llamados_mesa.entidad	= sga_evaluaciones.entidad
								JOIN sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
						WHERE	sga_evaluaciones.evaluacion = $evaluacion	
						LIMIT	1
		";
		$rtn_datos = guarani_db::consultar_fila($sql_datos);
		$mesa_examen = $rtn_datos['mesa_examen'];
		$fecha_mesa = $rtn_datos['fecha_mesa'];
		$elemento = $rtn_datos['elemento'];
		$sql = "SELECT		vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_alumnos.alumno,
							sga_alumnos.legajo,
							get_plan_version_alumno(sga_alumnos.alumno, '$fecha_mesa') as plan_version
				FROM		sga_alumnos
							JOIN vw_personas ON vw_personas.persona	= sga_alumnos.persona
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				WHERE		sga_alumnos.calidad = '".alumno::calidad_activo."' AND
							sga_alumnos.propuesta IN (SELECT DISTINCT propuesta FROM sga_mesas_examen_propuestas WHERE mesa_examen = $mesa_examen) AND
							-- La mesa debe estar asociada al plan del alumno.
							-- La actividad debe pertenecer al plan-versión que tenía el alumno en la fecha de la mesa de examen.
							get_plan_version_alumno(sga_alumnos.alumno, '$fecha_mesa') IN (	SELECT	DISTINCT vw_elementos_plan.plan_version
																							FROM	sga_mesas_examen_propuestas
																									JOIN vw_elementos_plan ON (vw_elementos_plan.plan = sga_mesas_examen_propuestas.plan AND vw_elementos_plan.elemento = $elemento)
																							WHERE	sga_mesas_examen_propuestas.mesa_examen = $mesa_examen)
							$where
		";
		// Si se permite rendir actividades regularizadas en un plan anterior que ya no existe en el plan actual del alumno.				
		// Regularidades vigentes (para rendir examen regular) y no vigentes (para exámenes de reválidas de cursadas)
		if ($param_exa_rendir_regularizada_plan_anterior == comunes::si || $param_exa_rendir_regularizada_plan_anterior == 'G') {
			$sql .= "	UNION
						SELECT		vw_personas.apellido_nombres as apellido_y_nombre,
									vw_personas.tipo_nro_documento as documento_principal,
									sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
									sga_alumnos.alumno,
									sga_alumnos.legajo,
									vw_regularidades_basica.plan_version
						FROM		vw_regularidades_basica
									JOIN vw_personas ON vw_regularidades_basica.persona = vw_personas.persona
									JOIN sga_alumnos ON vw_regularidades_basica.alumno = sga_alumnos.alumno
									JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
									JOIN sga_elementos_revision ON sga_elementos_revision.elemento = vw_regularidades_basica.elemento
									JOIN vw_actividades_plan ON (vw_actividades_plan.elemento_revision = sga_elementos_revision.elemento_revision AND vw_actividades_plan.plan_version = vw_regularidades_basica.plan_version)
						WHERE		vw_regularidades_basica.elemento = $elemento AND
									vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
									vw_regularidades_basica.origen_otra_propuesta = false AND
									vw_actividades_plan.estado = '".comunes::estado_activo."' AND
									-- No se listan los alumnos cuyo plan-versión a la fecha de la mesa incluye la actividad.
									vw_actividades_plan.elemento NOT IN (SELECT	elemento FROM vw_actividades_plan WHERE plan_version = get_plan_version_alumno(sga_alumnos.alumno, '$fecha_mesa')) AND
									-- No se listan los alumnos que aprobaron la actividad.
									vw_actividades_plan.elemento NOT IN (	SELECT	vw_hist_academica_basica.elemento
																			FROM	vw_hist_academica_basica
																			WHERE	vw_hist_academica_basica.alumno = sga_alumnos.alumno AND
																					vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."')
									$where
			";
		}
		$sql .= 'ORDER BY apellido_y_nombre';
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el plan_version correspondiente a un alumno en la fecha dada.
	 *
	 * @param integer	$alumno
	 * @param date		$fecha
	 */
	function get_plan_version($alumno, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		if (!$fecha) {
			$fecha = guarani_fecha::get_hoy();
		}
		$fecha = toba::db()->quote($fecha);
		$sql = "SELECT get_plan_version_alumno($alumno, $fecha) as plan_version";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['plan_version'];
	}

	/**
	 * Retorna el plan-versión correspondiente a un alumno en la fecha en que aprobó por última vez una actividad dada.
	 *
	 * @param integer $alumno
	 * @param integer $actividad
	 * @return date
	 */
	function get_ultima_cursada_plan_version($alumno, $actividad)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$sql = "SELECT get_ultima_cursada_plan_version($alumno, $actividad, 1, 0) as plan_version";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['plan_version'];
	}

	/**
	 * Determina si el alumno recibido es ingresante en el año académico dado.
	 *
	 * @param integer	$alumno
	 * @param integer	$anio_academico
	 * 
	 */
	function es_ingresante($alumno, $anio_academico)
	{
		// Se considera ingresante si la funcion retorna 1 (ingreso en el mimsmo año pasado por parametro) o 2 (el año a verificar es anterior a su ingreso)
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		$sql = "SELECT f_es_ingresante($alumno, $anio_academico) AS es_ingresante";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['es_ingresante'] >= 1);
	}

	/**
	 * Determina si el alumno recibido es ingresante en la fecha y año académico dados.
	 *
	 * @param integer $alumno
	 */
	function es_ingresante_periodos_insc_cursada($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT * FROM f_es_ingresante_periodos_insc_cursada($alumno) as es_ingresante"; 
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['es_ingresante'] == 1);
	}

	/*
	 * Determina si un alumno tiene aprobada una actividad.
	 */
	function actividad_aprobada($alumno, $actividad, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$where = '';
		
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		}
		
		$sql = "SELECT	COUNT(1) AS aprobada
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.elemento = $actividad
				AND		vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
				AND		vw_hist_academica_basica.alumno = $alumno
						$where
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['aprobada'] > 0);
	}

	/*
	 * Determina si un alumno tiene aprobada una actividad y retorna además los créditos que se le otorgaron.
	 */
	function get_creditos_actividad_aprobada($alumno, $actividad, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$where = '';
		
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		}
		
		$sql = "SELECT	vw_hist_academica_basica.creditos
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.elemento = $actividad
				AND		vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
				AND		vw_hist_academica_basica.alumno = $alumno
						$where
		";

		$rs = guarani_db::consultar_fila($sql);
		$aprobada = (!empty($rs));
		$creditos = ($aprobada ? $rs['creditos'] : null);
		return array($aprobada, $creditos);
	}

	/*
	 * Determina si un alumno tiene regularizada una actividad y retorna además los créditos que se le otorgaron.
	 */
	function get_creditos_actividad_regularizada($alumno, $actividad, $fecha=null)
	{
		//Una actividad aprobada se considera siempre regularizada.
		list($actividad_aprobada, $creditos) = $this->get_creditos_actividad_aprobada($alumno, $actividad, $fecha);
		if (!$actividad_aprobada) {
			$alumno = toba::db()->quote($alumno);
			$actividad = toba::db()->quote($actividad);
			$where = '';
			if (!is_null($fecha)) {
				$fecha = toba::db()->quote($fecha);
				$where = " AND vw_regularidades_basica.fecha <= $fecha";
			}
			$sql = "SELECT	vw_regularidades_basica.creditos
					FROM	vw_regularidades_basica
					WHERE	vw_regularidades_basica.alumno = $alumno AND
							vw_regularidades_basica.elemento = $actividad AND
							vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
							vw_regularidades_basica.es_vigente = 1
							$where
			";
			$rs = guarani_db::consultar_fila($sql);
			$actividad_regularizada = (!empty($rs));
			$creditos = ($actividad_regularizada ? $rs['creditos'] : null);
			return array($actividad_regularizada, $creditos);
		}
		return array($actividad_aprobada, $creditos);
	}
	
	/*
	 * Determina si un alumno tiene aprobada una actividad y retorna además las horas que se le otorgaron.
	 */
	function get_horas_actividad_aprobada($alumno, $actividad, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$where = '';
		
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		}
		
		$sql = "SELECT	vw_hist_academica_basica.horas
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.elemento = $actividad
				AND		vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
				AND		vw_hist_academica_basica.alumno = $alumno
						$where
		";

		$rs = guarani_db::consultar_fila($sql);
		$aprobada = (!empty($rs));
		$horas = ($aprobada ? $rs['horas'] : null);
		return array($aprobada, $horas);
	}

	/*
	 * Determina si un alumno tiene regularizada una actividad y retorna además las horas que se le otorgaron.
	 */
	function get_horas_actividad_regularizada($alumno, $actividad, $fecha=null)
	{
		//Una actividad aprobada se considera siempre regularizada.
		list($actividad_aprobada, $horas) = $this->get_horas_actividad_aprobada($alumno, $actividad, $fecha);
		if (!$actividad_aprobada) {
			$alumno = toba::db()->quote($alumno);
			$actividad = toba::db()->quote($actividad);
			$where = '';
			if (!is_null($fecha)) {
				$fecha = toba::db()->quote($fecha);
				$where = " AND vw_regularidades_basica.fecha <= $fecha";
			}
			$sql = "SELECT	vw_regularidades_basica.horas
					FROM	vw_regularidades_basica
					WHERE	vw_regularidades_basica.alumno = $alumno AND
							vw_regularidades_basica.elemento = $actividad AND
							vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
							vw_regularidades_basica.es_vigente = 1
							$where
			";
			$rs = guarani_db::consultar_fila($sql);
			$actividad_regularizada = (!empty($rs));
			$horas = ($actividad_regularizada ? $rs['horas'] : null);
			return array($actividad_regularizada, $horas);
		}
		return array($actividad_aprobada, $horas);
	}
	

	/**
	 * Determina si un alumno tiene aprobada una actividad, que no sea por promoción de una comisión dada.
	 * Se utiliza en el cierre de actas de regulares para salvar el caso en que el acta de promoción de la comisión ya está cerrada.
	 * 
	 * @param int $alumno
	 * @param int $actividad
	 * @param int $comision
	 * @param date $fecha
	 * @return boolean
	 */
	function actividad_aprobada_comision($alumno, $actividad, $comision, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$comision = toba::db()->quote($comision);

		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		}

		$sql = "SELECT	COUNT(1) as aprobada
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.elemento = $actividad AND
						vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
						vw_hist_academica_basica.alumno = $alumno AND
						(vw_hist_academica_basica.origen <> '".acta::origen_promociones."' OR
						(vw_hist_academica_basica.origen = '".acta::origen_promociones."' AND vw_hist_academica_basica.comision <> $comision))
						$where
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['aprobada'] > 0);
	}

	/*
	 * Determina si un alumno tiene aprobada una actividad que no sea por el acta
	 * que se está rectificando.
	 * 
	 * IMPORTANTE:  esta consulta sirve para control de cierre de actas Rectificativas
	 *				de Examen y Promoción
	 */
	function actividad_aprobada_rectificativa($alumno, $actividad, $id_acta_original, $fecha=null, $comision=null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$where = '';
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		}
		
		if (!is_null($comision)) {
			$comision = toba::db()->quote($comision);
			// Se ignora el caso en que la actividad fue aprobada por una promoción de la misma comisión (sólo en el cierre de actas de regulares, originales o rectificativas).
			$where .= " AND (vw_hist_academica_basica.origen <> '".acta::origen_promociones."' OR (vw_hist_academica_basica.origen = '".acta::origen_promociones."' AND vw_hist_academica_basica.comision <> $comision))";
		}

		$sql = "SELECT	COUNT(1) AS aprobada
			FROM	vw_hist_academica_basica
			WHERE	vw_hist_academica_basica.elemento = $actividad
			AND	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
			AND	vw_hist_academica_basica.alumno = $alumno
			AND	(vw_hist_academica_basica.id_acta_original IS NULL -- Aprobación por resolución o equivalencia
				 OR vw_hist_academica_basica.id_acta_original <> $id_acta_original) 
			$where
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['aprobada'] > 0);
	}

	/*
	 * Determina si un alumno tiene aprobada una actividad que no sea por el Trámite de Equivalencia
	 * que se está rectificando.
	 * 
	 * IMPORTANTE:  esta consulta sirve para control Actividad-Equivalencia ejecutado al Rectificar o 
	 * Cerrar Trámites de Equivalencia de tipo rectificativos.
	 */
	function actividad_aprobada_rectificativa_equivalencia($alumno, $actividad, $id_equiv_tramite_original, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$where = '';
		
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		}
		
		$sql = "SELECT	COUNT(1) AS aprobada
			FROM	vw_hist_academica_basica
			WHERE	vw_hist_academica_basica.elemento = $actividad
			AND	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
			AND	vw_hist_academica_basica.alumno = $alumno
			-- Que el orígen de Aprobación de la Actividad sea distinto de equivalencia
			AND	(vw_hist_academica_basica.origen <> '" . acta::origen_equivalencias . "'
				-- O que la Actividad sea aprobada por equivalencia, pero en otro trámite diferente 
				-- del que se está rectificando (tener en cuenta que el campo tramite_original trae
				-- siempre el id, aunque no sea un trámite rectificativo, en cuyo caso trae el mismo 
				-- id que el campo tramite_equivalencia).
				 OR vw_hist_academica_basica.equivalencia_tramite_original <> $id_equiv_tramite_original) 
			$where
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['aprobada'] > 0);
	}
	
	/*
	 * Determina si un alumno tiene aprobada una actividad.
	 */
	function get_cant_examenes_regulares_desaprobados($alumno, $actividad, $fecha)
	{
		$fecha_regular = $this->get_fecha_ultima_cursada_valida($alumno, $actividad);

		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$fecha = toba::db()->quote($fecha);

		$filtro_fecha = empty($fecha_regular) ? "vw_hist_academica_basica.fecha <= $fecha" : 'vw_hist_academica_basica.fecha BETWEEN '.toba::db()->quote($fecha_regular)." AND $fecha";

		$sql = "SELECT	COUNT(1) AS desaprobadas
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.elemento = $actividad AND
						vw_hist_academica_basica.resultado = '".comunes::resultado_nota_desaprobado."' AND
						vw_hist_academica_basica.origen = '".acta::origen_examen."' AND
						vw_hist_academica_basica.instancia = ".mesa_de_examen::inst_exam_regular." AND
						vw_hist_academica_basica.alumno = $alumno
				AND		$filtro_fecha
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['desaprobadas']);
	}

	/**
	 * Obtiene la cantidad de veces que un alumno desaprobó examen en fechas anteriores inscripto al mismo en condición de libre.
	 *
	 */
	function get_cant_examenes_libre_desaprobados($alumno, $elemento, $fecha_examen)
	{
		$alumno = toba::db()->quote($alumno);
		$elemento = toba::db()->quote($elemento);
		$fecha_examen = toba::db()->quote($fecha_examen);
		
		$sql = "SELECT 	COUNT(1) AS cant
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.elemento = $elemento
				AND 	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_desaprobado."'
				AND 	vw_hist_academica_basica.instancia = '".inscripcion_examen::inst_insc_libre."'
				AND 	vw_hist_academica_basica.alumno = $alumno
				AND 	vw_hist_academica_basica.fecha  < $fecha_examen
				";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant']);
	}
	
	function get_fecha_ultima_cursada_valida($alumno, $actividad)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);

		$sql = "SELECT 		vw_regularidades_basica.fecha as fecha_regular 
				FROM 		vw_regularidades_basica
				WHERE 		vw_regularidades_basica.alumno = $alumno AND
							vw_regularidades_basica.elemento = $actividad AND
							vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' -- Aprobado
				ORDER BY	vw_regularidades_basica.fecha DESC
				LIMIT		1
		";

		$rs = guarani_db::consultar_fila($sql);
		return isset($rs['fecha_regular']) ? $rs['fecha_regular'] : null ;
	}
	
	/**
	 * Retorna la cantidad de inscripciones a cursada de un alumno en el período lectivo de una comisión dada.
	 * Se tienen en cuenta inscripciones por actividad y NO por comisión (ya que las inscripciones pueden ser con prioridades).
	 *
	 * @param integer $alumno
	 * @param integer $comision
	 * @return unknown
	 */
	function get_cant_inscripciones_periodo_lectivo($alumno, $comision)
	{
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);

		$sql = "SELECT	COUNT(DISTINCT sga_comisiones.elemento) as cant_inscripciones
				FROM	vw_insc_cursada,
						sga_comisiones
				WHERE	vw_insc_cursada.comision = sga_comisiones.comision AND
						vw_insc_cursada.alumno = $alumno AND
						sga_comisiones.periodo_lectivo = (	SELECT	comisiones.periodo_lectivo
															FROM	sga_comisiones as comisiones
															WHERE	comisiones.comision = $comision)
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant_inscripciones']);
	}
	
	/*
	 * Determina si un alumno tiene regularizada una actividad.
	 */
	function actividad_regularizada($alumno, $actividad, $fecha=null)
	{
		//Una actividad aprobada se considera siempre regularizada.
		$actividad_aprobada = $this->actividad_aprobada($alumno, $actividad, $fecha);
		if (!$actividad_aprobada) {
			$alumno = toba::db()->quote($alumno);
			$actividad = toba::db()->quote($actividad);
			$where = '';
			if (!is_null($fecha)) {
				$fecha = toba::db()->quote($fecha);
				$where = " AND vw_regularidades_basica.fecha <= $fecha";
			}
			$sql = "SELECT	COUNT(1) AS regularizada
					FROM	vw_regularidades_basica
					WHERE	vw_regularidades_basica.alumno = $alumno AND
							vw_regularidades_basica.elemento = $actividad AND
							vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
							vw_regularidades_basica.es_vigente = 1
							$where
			";
			$rs = guarani_db::consultar_fila($sql);
			return ($rs['regularizada'] > 0);
		}
		return true;
	}

	/**
	 * Determina si un alumno tiene una equivalencia vigente de regularidad que sólo es válida para correlativas
	 * (instancia 14), para la actividad dada.
	 * @param int $alumno id del alumno 
	 * @param int $actividad id de la actividad que se desea verificar si el alumno tiene regularizada
	 * @param date $fecha fecha para la cual se controla la vigencia de la actividad regularizada
	 */
	function actividad_regularizada_en_equivalencia_para_correlativas($alumno, $actividad, $fecha)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$where = '';
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_equiv_regularidades_correlativas.fecha <= $fecha";
		}
		$sql = "SELECT	COUNT(1) as regularizada
				FROM	vw_equiv_regularidades_correlativas
				WHERE	alumno			= $alumno
						AND elemento	= $actividad
						AND resultado	= '".comunes::resultado_nota_aprobado."'
						AND (vw_equiv_regularidades_correlativas.fecha_vigencia IS NULL OR vw_equiv_regularidades_correlativas.fecha_vigencia >= CURRENT_DATE)
						$where
			";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['regularizada'] > 0);
	}

	/**
	 * Determina si un alumno tiene regularizada una actividad en un trámite de equivalencia diferente al que se está rectificando.
	 * 
	 * @param int $persona
	 * @param int $actividad
	 * @param int $id_equiv_tramite_original
	 * @param date $fecha
	 * @return boolean
	 */
	function actividad_regularizada_rectificativa_equivalencia($persona, $actividad, $id_equiv_tramite_original, $fecha=null)
	{
		$persona = toba::db()->quote($persona);
		$actividad = toba::db()->quote($actividad);
		$id_equiv_tramite_original = toba::db()->quote($id_equiv_tramite_original);
		$where = '';
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_regularidades_basica.fecha <= $fecha";
		}
		$sql = "SELECT	COUNT(1) as regularizada
				FROM	vw_regularidades_basica
				WHERE	vw_regularidades_basica.persona = $persona AND
						vw_regularidades_basica.elemento = $actividad AND
						vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
						-- Que el orígen de aprobación de la actividad sea distinto de equivalencia
						(vw_regularidades_basica.origen <> '".acta::origen_equivalencias_regulares."'
						-- o que la actividad sea aprobada por equivalencia, pero en otro trámite diferente del que se está rectificando
						OR vw_regularidades_basica.equivalencia_tramite_original <> $id_equiv_tramite_original)
						$where
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['regularizada'] > 0);
	}

	/*
	 * Determina si un alumno está inscripto para cursar una actividad en periodos lectivos vigentes o en cualquier período lectivo.
	 */
	function actividad_inscripto_cursada($alumno, $actividad, $en_periodo_lectivo_vigente = false, $estado_inscripcion = null)
	{
		$alumno     = toba::db()->quote($alumno);
		$actividad  = toba::db()->quote($actividad);
		//$estado_aceptada = toba::db()->quote(inscripcion_cursada::estado_aceptada);
		
		// Filtro por vigencia de la comision.
		$where_vigente = '';
		$from_vigente  = '';
		if ($en_periodo_lectivo_vigente) {
			$from_vigente  = " JOIN vw_periodos_lectivos ON vw_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo ";
			$where_vigente = " AND vw_periodos_lectivos.fecha_fin >= CURRENT_DATE ";
		}
		
		// Filtro por estado de la inscripcion. Por defecto se recupera cualquier estado (aceptado y pendiente)
		$where_estado_inscripcion = '';
		if (!is_null($estado_inscripcion)) {
			$estado_inscripcion = toba::db()->quote($estado_inscripcion);
			$where_estado_inscripcion = " AND vw_insc_cursada.estado  = $estado_inscripcion ";
		}
		
		// Consulta las inscripciones a cursadas del alumno en la propuesta por la que se consulta (no recupera inscripciones de otras propuestas)
		$sql = "SELECT	count(1) as inscripto_cursada
				  FROM	vw_insc_cursada
				  JOIN	sga_comisiones ON sga_comisiones.comision = vw_insc_cursada.comision
				  $from_vigente
				 WHERE	vw_insc_cursada.alumno  = $alumno 
				   AND	sga_comisiones.elemento = $actividad 
				   $where_estado_inscripcion
				   $where_vigente
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['inscripto_cursada'] > 0);
	}

	/*
	 * Determina si un alumno está inscripto para rendir examen de una actividad.
	 */
	function actividad_inscripto_examen($alumno, $actividad, $en_mesa_vigente = false, $estado_inscripcion = null)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		//$estado_aceptada = toba::db()->quote(inscripcion_examen::estado_aceptada);
		
		// Considera vigente a la mesa si la fecha de la mesa es mayor o igual a la fecha actual.
		$from_vigente  = '';
		if ($en_mesa_vigente) {
			$where_vigente = " AND sga_llamados_mesa.fecha >= CURRENT_DATE ";
		}
		
		// Filtro por estado de la inscripcion. Por defecto se recupera cualquier estado (aceptado y pendiente)
		$where_estado_inscripcion = "";
		if (!is_null($estado_inscripcion)) {
			$estado_inscripcion = toba::db()->quote($estado_inscripcion);
			$where_estado_inscripcion = " AND vw_insc_examen.estado  = $estado_inscripcion ";
		}
		
		
		$sql = "SELECT count(1) as inscripto_examen
				  FROM vw_insc_examen 
				  JOIN sga_llamados_mesa ON vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
				  JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				 WHERE vw_insc_examen.alumno     = $alumno
				   AND sga_mesas_examen.elemento = $actividad
				   $where_estado_inscripcion
				   $where_vigente  
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['inscripto_examen'] > 0);
	}

	/**
	 * Determina si el alumno tiene una equivalencia parcial vigente otorgada para la actividad a una fecha dada.
	 * 
	 * @param integer $alumno
	 * @param integer $actividad
	 * @param date $fecha
	 * @return boolean
	 */
	function actividad_equivalencia_parcial($alumno, $actividad, $fecha)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$fecha = toba::db()->quote($fecha);
		$sql = "SELECT	COUNT(1) AS equivalencia_parcial
				FROM	vw_equiv_parciales_basica
				WHERE	vw_equiv_parciales_basica.alumno = $alumno AND
						vw_equiv_parciales_basica.elemento = $actividad AND
						vw_equiv_parciales_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
						vw_equiv_parciales_basica.es_vigente = 1 AND
						vw_equiv_parciales_basica.fecha <= $fecha
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['equivalencia_parcial'] > 0);
	}

	/**
	 * Obtiene el listado de opciones del combo "Completar los campos" 
	 * del form_autocompletar de la generación de legajos
	 *
	 */
	function get_campos_combo_legajo_comp_para()
	{
		return array(	array('valor' => legajos::combo_autocompletar_valor_todos, 'descr' => legajos::combo_autocompletar_descr_todos),
						array('valor' => legajos::combo_autocompletar_valor_vacios , 'descr' => legajos::combo_autocompletar_descr_vacios)
					);
	}
	
	/**
	 * Determina si un nro de legajo se encuentra ya en uso, ya sea de manera global en el sistema 
	 * o por propuesta, dependiendo del parámetro $param_legajo_unico
	 *
	 * @param $legajo				legajo que se quiere consultar
	 * @param $propuesta			propuesta para la que se pretende utilizar el legajo
	 * @param $param_legajo_unico	parámetro que indica si se debe validar a nivel global o por propuesta
	 */
	function legajo_en_uso($legajo, $propuesta, $param_legajo_unico)
	{
		$legajo = toba::db()->quote($legajo);
		$propuesta = toba::db()->quote($propuesta);
		
		if ($param_legajo_unico == comunes::no) {
			// El Número de Legajo es diferente por cada propuesta en las que se encuentra el alumno 
			// Puede haber legajos iguales en distintas propuestas.
			$where = " AND sga_alumnos.propuesta = $propuesta ";
		} else {
			$where = '';	
		}
		
		$sql = "SELECT 
					COUNT (sga_alumnos.alumno) AS cant
				FROM 
					sga_alumnos
				WHERE
					sga_alumnos.legajo = $legajo
					$where
				";
		
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

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
						WHEN sga_alumnos.coeficiente IS NULL THEN '(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre    
						ELSE '(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre || ' - Coeficiente: ' || sga_alumnos.coeficiente
					END as propuesta_nombre,
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
					sga_alumnos.legajo,
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
				JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
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
	
	function get_cambios_ubicacion($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT 
					sga_propuestas.propuesta,
					'(' || sga_propuestas.codigo || ') '|| sga_propuestas.nombre as propuesta_codigo_y_nombre,
					sga_alumnos_hist_ubicacion.cambio_ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					sga_alumnos_hist_ubicacion.fecha,
					sga_alumnos_motivos_ubicacion.nombre as motivo_nombre,
					sga_documentos.documento_numero,
					sga_alumnos_hist_ubicacion.observaciones
				FROM sga_alumnos_hist_ubicacion
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_alumnos_hist_ubicacion.alumno
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_alumnos_hist_ubicacion.ubicacion
				JOIN sga_alumnos_motivos_ubicacion ON  sga_alumnos_motivos_ubicacion.motivo_ubicacion = sga_alumnos_hist_ubicacion.motivo_ubicacion
    			JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				LEFT JOIN sga_documentos ON sga_documentos.documento = sga_alumnos_hist_ubicacion.documento
				$where
				ORDER BY
					sga_propuestas.propuesta,
					sga_alumnos_hist_ubicacion.fecha DESC
				";
		
		return guarani_db::consultar($sql);
	}
	
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
		$sql = $sql . " ORDER BY propuesta_nombre, fecha_orden";

		return guarani_db::consultar($sql);
		}

	function get_revalidas_cursadas($where = '')
	{
		// Listado de examenes de reválidas de cursadas de un alumno
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						vw_mesas_examen.mesa_examen_elemento as actividad,
						'(' || sga_elementos.codigo || ') ' || COALESCE(ep_alu.nombre, ep_origen.nombre) as actividad_codigo_y_nombre,						
						acta_examen.id_acta,
						acta_examen.nro_acta as nro_acta_examen,
						sga_actas_detalle.fecha,
						to_char(sga_actas_detalle.fecha,'".comunes::formato_fecha_visual."') as fecha_examen,
						sga_actas_detalle.nota,
						sga_instancias_resultado.descripcion as resultado_descripcion,
						plan_origen.codigo as plan_codigo,
						acta_cursada.nro_acta as nro_acta_cursada,
						cast(vw_comisiones.anio_academico as varchar) || ' - ' || vw_comisiones.periodo_lectivo_nombre  as  periodo_lectivo,
						CASE sga_actas_detalle.resultado
						   WHEN 'A' THEN to_char(sga_regularidades_venc.fecha_vigencia_anterior,'".comunes::formato_fecha_visual."')
						   ELSE ''
						END as fin_vigencia_a_revalidar,
						CASE sga_actas_detalle.resultado
						   WHEN 'A' THEN to_char(sga_regularidades_venc.fecha_vigencia_nuevo,'".comunes::formato_fecha_visual."')
						   ELSE ''
						END as fin_vigencia_nuevo
						
					FROM sga_alumnos
					JOIN sga_actas_detalle ON sga_actas_detalle.alumno = sga_alumnos.alumno
					JOIN sga_actas as acta_examen ON acta_examen.id_acta = sga_actas_detalle.id_acta
					JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = acta_examen.llamado_mesa
					JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
					JOIN vw_elementos ON vw_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones as pv_origen ON pv_origen.plan_version = sga_actas_detalle.plan_version
					JOIN sga_planes as plan_origen ON plan_origen.plan = pv_origen.plan
					JOIN sga_elementos_plan as ep_origen ON (ep_origen.plan_version = sga_actas_detalle.plan_version AND ep_origen.elemento_revision = vw_elementos.elemento_revision)

					LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_actas_detalle.resultado
					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = sga_actas_detalle.instancia AND sga_instancias_resultado.resultado = sga_actas_detalle.resultado)

					-- Datos de la cursada revalidada
					LEFT JOIN sga_regularidades_venc ON (sga_regularidades_venc.id_acta_revalida = sga_actas_detalle.id_acta AND 
					                                     sga_regularidades_venc.alumno = sga_actas_detalle.alumno)
					LEFT JOIN sga_actas as acta_cursada ON acta_cursada.id_acta = sga_regularidades_venc.id_acta
					LEFT JOIN vw_comisiones ON vw_comisiones.comision = acta_cursada.comision
					-- Nombre de la actividad segun plan actual del alumno.
					LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.plan_version = sga_alumnos.plan_version AND ep_alu.elemento_revision = vw_elementos.elemento_revision)
						
					WHERE sga_actas_detalle.instancia = ".comision::inst_exam_revalida."
					  AND acta_examen.origen = '".acta::origen_examen."' -- Examen
					  AND acta_examen.estado = '".acta::estado_cerrada."'
					  AND sga_actas_detalle.estado = '".acta::detalle_estado_activo."'
					  AND sga_actas_detalle.rectificado = '".comunes::no."'
					  $where
					";

		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_propuestas'));
		return guarani_db::consultar($sql, null, false);
	}
	
	function get_prorrogas_regularidad($where='')
	{
		// Listado de modificaciones en el vencimiento de fin de vigencia de las regularidades (cursadas / equiv. de regularidad)
		if ($where) {
			$where = " WHERE $where";
		}
		// Se utiliza la vista vw_regularidades_basica porque trae regularidades de actividades en otros planes/versiones 
		$sql = " -- Actas de Cursadas
				SELECT	
				    'Cursada' as tipo,
					sga_propuestas.propuesta as propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_regularidades_venc.alumno as alumno,
					sga_alumnos.legajo as legajo,
					vw_elementos.elemento as actividad,
					vw_elementos.codigo,
					COALESCE(ep_alu.nombre, ep_origen.nombre) as actividad_nombre,
					'(' || vw_elementos.codigo || ') ' || COALESCE(ep_alu.nombre, ep_origen.nombre) as actividad_codigo_y_nombre,
					sga_regularidades_venc.fecha_alta as fecha_cambio_orden,
					to_char(sga_regularidades_venc.fecha_alta,'".comunes::formato_fecha_visual."') as fecha_cambio,
					sga_regularidades_venc.id_acta as id_acta_cursada,
					sga_regularidades_venc.equivalencia as equivalencia,
					sga_actas.nro_acta as nro_acta_resol_equiv,
					cast(vw_comisiones.anio_academico as varchar) || '-' || vw_comisiones.periodo_lectivo_nombre as periodo_lectivo,
					COALESCE(to_char(sga_actas_detalle.fecha_vigencia,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_actual,
					COALESCE(to_char(sga_regularidades_venc.fecha_vigencia_anterior,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_anterior,
					COALESCE(to_char(sga_regularidades_venc.fecha_vigencia_nuevo,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_nuevo,
					sga_documentos.documento_numero as resolucion,
					acta_revalida.nro_acta as nro_acta_revalida,
					sga_regularidades_venc.observaciones
					
				FROM
					sga_alumnos
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_regularidades_venc ON sga_regularidades_venc.alumno = sga_alumnos.alumno
					-- Actas de cursadas
					JOIN sga_actas ON sga_actas.id_acta = sga_regularidades_venc.id_acta
					JOIN sga_actas_detalle ON (sga_actas_detalle.id_acta = sga_regularidades_venc.id_acta AND
					                           sga_actas_detalle.alumno  = sga_regularidades_venc.alumno )
					JOIN vw_comisiones ON vw_comisiones.comision = sga_actas.comision
					JOIN vw_elementos  ON vw_elementos.elemento = vw_comisiones.elemento
					JOIN sga_elementos_plan as ep_origen ON (ep_origen.elemento_revision = vw_elementos.elemento_revision AND
 						                                     ep_origen.plan_version = sga_actas_detalle.plan_version)
												 
					LEFT JOIN sga_documentos ON sga_documentos.documento = sga_regularidades_venc.documento
					LEFT JOIN sga_actas as acta_revalida ON acta_revalida.id_acta = sga_regularidades_venc.id_acta_revalida
					-- Nombre de la actividad segun plan actual del alumno.
					LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.plan_version = sga_alumnos.plan_version AND ep_alu.elemento_revision = vw_elementos.elemento_revision)
												 
				$where
				
				UNION ALL 
				
				-- Equivalencias de regularidad y equivalencias parciales
				SELECT	
				    sga_instancias.nombre as tipo,
					sga_propuestas.propuesta as propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_regularidades_venc.alumno as alumno,
					sga_alumnos.legajo as legajo,
					vw_elementos.elemento as actividad,
					vw_elementos.codigo,
					COALESCE(ep_alu.nombre, ep_origen.nombre) as actividad_nombre,
					'(' || vw_elementos.codigo || ') ' || COALESCE(ep_alu.nombre, ep_origen.nombre) as actividad_codigo_y_nombre,
					sga_regularidades_venc.fecha_alta as fecha_cambio_orden,
					to_char(sga_regularidades_venc.fecha_alta,'".comunes::formato_fecha_visual."') as fecha_cambio,
					sga_regularidades_venc.id_acta as id_acta_cursada,
					sga_regularidades_venc.equivalencia as equivalencia,
					doc_equiv.documento_numero as nro_acta_resol_equiv,
					cast(NULL as varchar(100)) as periodo_lectivo,
					COALESCE(to_char(sga_equiv_otorgada.fecha_vigencia,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_actual,
					COALESCE(to_char(sga_regularidades_venc.fecha_vigencia_anterior,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_anterior,
					COALESCE(to_char(sga_regularidades_venc.fecha_vigencia_nuevo,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_nuevo,
					sga_documentos.documento_numero as resolucion,
					acta_revalida.nro_acta as nro_acta_revalida,
					sga_regularidades_venc.observaciones
					
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_regularidades_venc ON sga_regularidades_venc.alumno = sga_alumnos.alumno
				-- Equivalencias de Regularidad y equivalencias parciales
				JOIN sga_equiv_otorgada ON sga_equiv_otorgada.equivalencia = sga_regularidades_venc.equivalencia
				JOIN sga_instancias ON sga_instancias.instancia = sga_equiv_otorgada.instancia
				JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = sga_equiv_otorgada.equivalencia_tramite
				JOIN vw_elementos ON vw_elementos.elemento = sga_equiv_otorgada.elemento
				JOIN sga_elementos_plan as ep_origen ON (ep_origen.elemento_revision = vw_elementos.elemento_revision AND ep_origen.plan_version = sga_equiv_tramite.plan_version)

				LEFT JOIN sga_documentos ON sga_documentos.documento = sga_regularidades_venc.documento
				LEFT JOIN sga_actas as acta_revalida ON acta_revalida.id_acta = sga_regularidades_venc.id_acta_revalida				
				LEFT JOIN sga_documentos as doc_equiv ON doc_equiv.documento = sga_equiv_tramite.documento
				-- Nombre de la actividad segun plan actual del alumno.
				LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.plan_version = sga_alumnos.plan_version AND ep_alu.elemento_revision = vw_elementos.elemento_revision)
				
				$where
				ORDER BY propuesta_nombre, actividad_nombre, fecha_cambio_orden
		";
		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_propuestas'));
		return guarani_db::consultar($sql, null, false);
	}



	
	/**
	 * Retorna tipo de equivalencia
	 * @param integer $equivalencia_tramite
	 * @return varchar 
	 */	
	function get_tipo_equivalencia($equivalencia_tramite)
	{
		$sql = "SELECT 	origen.tipo
				FROM	sga_equiv_tramite as tramite,
						sga_equiv_tramite_origen as origen
				WHERE 	tramite.origen = origen.origen
				AND		tramite.equivalencia_tramite = $equivalencia_tramite";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Retorna detalle de equivalencia interna determinada 
	 * @param integer $equivalencia
	 * @return array 
	 */	
	function get_equivalencia_interna($equivalencia)
	{
		$sql = "SELECT 	elem.nombre as actividad_nombre, 
						prop.nombre as propuesta_nombre, 
						equiv_int.fecha as fecha, 
						equiv_int.nota as nota, 
						equiv_int.origen as origen,
						CASE WHEN equiv_int.resultado = 'A' THEN 'Aprobado'
						WHEN equiv_int.resultado = 'R' THEN 'Reprobado'
						END as resultado
				FROM	sga_equiv_internas as equiv_int,
						sga_elementos 	   as	elem,
						sga_propuestas 	   as prop
				WHERE	elem.elemento = equiv_int.elemento
						AND	prop.propuesta = equiv_int.propuesta
						AND equiv_int.equivalencia = $equivalencia";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna detalle de equivalencia externa determinada 
	 * @param integer $equivalencia
	 * @return array 
	 */	
	function get_equivalencia_externa($equivalencia)
	{
		$sql = "SELECT 	equiv_ext.actividad as actividad_nombre, 
						equiv_ext.fecha as fecha, 
						equiv_ext.nota  as nota, 
						equiv_ext.libro as libro,
						equiv_ext.folio	as folio,
						equiv_ext.acta	as acta
				FROM 	sga_equiv_externas as equiv_ext
				WHERE 	equiv_ext.equivalencia = $equivalencia";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna detalle de componentes de módulos aprobados por resolución
	 * @param integer $elemento 
	 * @param integer $plan_version_actual 
	 * @return array 
	 */	
	function get_componentes_modulos($elemento, $plan_version_actual)
	{
		$sql = "SELECT 
					-- Datos del módulo
					modulo.elemento,
					er_modulo.elemento_revision,
					ep_modulo.creditos as creditos_modulo,

					-- Datos de las componentes
					componente.elemento,
					er_componente.elemento_revision,
					componente.codigo,
					CASE componente.entidad_subtipo 
						WHEN 50 THEN componente.nombre || ' (' || componente.codigo || ')'
						ELSE componente.nombre 
						END as nombre_componente,
					componente.nombre_abreviado,
					COALESCE(ep_componente.creditos, 0)  as creditos_componente,
					sga_elementos_comp.puntaje as puntaje_componente,
					componente.entidad_subtipo ,
					sga_g3entidades_subtipos.nombre as subtipo_nombre,
					componente.estado,
					sga_elementos_comp.orden 
				FROM 
					sga_elementos as modulo,
					sga_elementos_revision as er_modulo,
					sga_elementos_plan as ep_modulo,
					sga_elementos_comp,
					sga_elementos_revision as er_componente,
					sga_elementos as componente,
					sga_g3entidades_subtipos,
					sga_elementos_plan as ep_componente
				WHERE 
					er_modulo.elemento = modulo.elemento
					AND sga_elementos_comp.elemento_padre = er_modulo.elemento_revision
					AND sga_elementos_comp.elemento_hijo  = er_componente.elemento_revision
					AND componente.elemento = er_componente.elemento
					AND sga_g3entidades_subtipos.entidad_subtipo = componente.entidad_subtipo
					AND ep_modulo.elemento_revision = er_modulo.elemento_revision
					AND ep_componente.plan_version = ep_modulo.plan_version
					AND ep_componente.elemento_revision = er_componente.elemento_revision
					AND modulo.elemento = $elemento
					AND ep_modulo.plan_version = $plan_version_actual
				ORDER BY 
					sga_elementos_comp.orden, 
					componente.entidad_subtipo";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de actividades aprobadas para un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_historia_academica_basica_alumno($alumno, $fecha = '')
	{
		$alumno = toba::db()->quote($alumno);
		
		if ($fecha != '') {
			$fecha = toba::db()->quote($fecha);
			$where = " AND vw_hist_academica_basica.fecha <= $fecha";
		} else {
			$where = '';
		}
		
		$sql = "SELECT	vw_hist_academica_basica.elemento,
						coalesce(vw_hist_academica_basica.creditos,0) as creditos,
						coalesce(vw_hist_academica_basica.horas, 0) as horas
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."' AND
						vw_hist_academica_basica.alumno = $alumno
						$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de actividades aprobadas correspondientes a un año de cursada de un plan-versión dado.
	 * 
	 * @param integer $alumno
	 * @param integer $plan_version
	 * @param integer $anio
	 * @param string $operador_anio
	 * @return integer
	 */
	function get_cant_actividades_aprobadas_anio_cursada($alumno, $plan_version, $anio, $operador_anio = '=')
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$anio = toba::db()->quote($anio);
		$sql = "SELECT	COUNT(DISTINCT vw_hist_academica_basica.elemento) as cant
				FROM	vw_hist_academica_basica
						JOIN sga_elementos_revision ON vw_hist_academica_basica.elemento = sga_elementos_revision.elemento
						JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				WHERE	vw_hist_academica_basica.alumno = $alumno AND
						sga_elementos_plan.plan_version = $plan_version AND
						sga_elementos_plan.anio_de_cursada $operador_anio $anio AND
						vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	/**
	 * Retorna la cantidad de actividades aprobadas.
	 * 
	 * @param integer $alumno
	 * @return integer
	 */
	function get_cant_actividades_aprobadas($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	COUNT(DISTINCT vw_hist_academica_basica.elemento) as cant
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.alumno = $alumno AND
						vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	/**
	 * Retorna la cantidad de créditos otorgados por actividades aprobadas correspondientes a un año de cursada de un plan-versión dado.
	 * 
	 * @param integer $alumno
	 * @param integer $plan_version
	 * @param integer $anio
	 * @return integer
	 */
	function get_creditos_actividades_aprobadas_anio_cursada($alumno, $plan_version, $anio)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$anio = toba::db()->quote($anio);
		$sql = "SELECT	COALESCE(SUM(vw_hist_academica_basica.creditos), 0) as creditos
				FROM	vw_hist_academica_basica
						JOIN sga_elementos_revision ON vw_hist_academica_basica.elemento = sga_elementos_revision.elemento
						JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
				WHERE	vw_hist_academica_basica.alumno = $alumno AND
						sga_elementos_plan.plan_version = $plan_version AND
						sga_elementos_plan.anio_de_cursada = $anio AND
						vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['creditos'];
	}

	/**
	 * Retorna la cantidad de créditos otorgados por actividades aprobadas.
	 * 
	 * @param integer $alumno
	 * @return integer
	 */
	function get_creditos_actividades_aprobadas($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	COALESCE(SUM(vw_hist_academica_basica.creditos), 0) as creditos
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.alumno = $alumno AND
						vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['creditos'];
	}

	/**
	 * Retorna la cantidad de créditos otorgados por actividades aprobadas, modulos que se reconocieron creditos y 
	 * creditos globales reconocidos.
	 * 
	 * @param integer $alumno
	 * @param boolean $actividades			# Creditos de actividades aprobadas del plan de estudios
	 * @param boolean $modulos				# Creditos parciales/totales reconocidos a un modulo
	 * @param boolean $creditos_globales	# Creditos reconocidos al alumno para el titulo
	 * @return integer
	 */
	function get_creditos_alumno($alumno, $actividades=false, $modulos=false, $creditos_globales=false)
	{
		$alumno = toba::db()->quote($alumno);
		$cant_actividades = 0;
		if ($actividades) {
			$sql = "SELECT	COALESCE(SUM(vw_hist_academica_basica.creditos), 0) as creditos
					FROM	vw_hist_academica_basica
					WHERE	vw_hist_academica_basica.alumno = $alumno AND
							vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
			";
			$rs = guarani_db::consultar_fila($sql);
			$cant_actividades = $rs['creditos'];
		}

		// Módulos de la versión que reconocen créditos.
		$cant_modulos = 0;
		if ($modulos) {
			$sql = "SELECT	COALESCE(SUM(sga_reconocimiento_act.creditos), 0) as creditos
					FROM	sga_alumnos
							JOIN sga_reconocimiento ON sga_reconocimiento.alumno = sga_alumnos.alumno
							JOIN vw_modulos_plan ON vw_modulos_plan.plan_version = sga_alumnos.plan_version
							JOIN sga_reconocimiento_act ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
					WHERE	sga_alumnos.alumno = $alumno AND
							sga_reconocimiento.tipo_reconocimiento = '".aprobacion_por_resolucion::activ_modulos."' AND
							sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."' AND
							sga_reconocimiento_act.elemento = vw_modulos_plan.elemento AND
							sga_reconocimiento_act.rectificado = '".comunes::no."' AND
							sga_reconocimiento_act.estado = '".aprobacion_por_resolucion::estado_actividad_activo."'
			";
			$rs = guarani_db::consultar_fila($sql);
			$cant_modulos = $rs['creditos'];
		}

		// Créditos reconocidos para el título.
		$cant_globales = 0;
		if ($creditos_globales) {
			$sql = "SELECT	COALESCE(SUM(sga_reconocimiento.total_creditos), 0) as creditos
					FROM	sga_alumnos
							JOIN sga_reconocimiento ON sga_reconocimiento.alumno = sga_alumnos.alumno
					WHERE	sga_alumnos.alumno = $alumno AND
							sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."' AND
							sga_reconocimiento.tipo_reconocimiento = '".aprobacion_por_resolucion::cred_globales."'
			";
			$rs = guarani_db::consultar_fila($sql);
			$cant_globales = $rs['creditos'];
		}

		// Retorno la suma de los créditos obtenidos consultados.
		return ($cant_actividades + $cant_modulos + $cant_globales);
	}

	function get_historia_academica_invalidada($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$tipo_invalidar = toba::db()->quote(alumno::motivo_movimiento_ha_tipo_invalidar);
		
		// Recupera Examenes/Promociones/Equivalencias/Aprob x resolución que fueron invalidadas. 
		// Trae el último registro de cada invalidación registrado en la tabla de movimientos de historia academica (sga_movimientos_ha), 
		// debido a que la nota pudo ser invalidada, luego convalidad y vuelta a invalidarse.
		$sql = "SELECT		sga_propuestas.propuesta,
							vw_hist_academica_inv.origen,
							vw_hist_academica_inv.tipo,
							vw_hist_academica_inv.id_acta,
							vw_hist_academica_inv.equivalencia,
							vw_hist_academica_inv.reconocimiento_act,
							vw_hist_academica_inv.fecha,
							vw_hist_academica_inv.nota,
							vw_hist_academica_inv.resultado_descripcion,
							pv_origen.plan,
							p_origen.codigo 				AS plan_codigo,
							sga_propuestas.nombre 			AS propuesta_nombre,
							vw_hist_academica_inv.elemento 	AS actividad,
							COALESCE(CAST(vw_hist_academica_inv.id_acta as varchar), '') || ':' || COALESCE(CAST(vw_hist_academica_inv.equivalencia as varchar), '') AS clave_acta_equivalencia,
							COALESCE(ep_actual.nombre, vw_hist_academica_inv.actividad_nombre) as actividad_nombre,
							CASE 
							  WHEN vw_hist_academica_inv.actividad_codigo IS NULL THEN COALESCE(ep_actual.nombre, vw_hist_academica_inv.actividad_nombre)
							  ELSE '(' || vw_hist_academica_inv.actividad_codigo || ') ' || COALESCE(ep_actual.nombre, vw_hist_academica_inv.actividad_nombre)
							END as actividad_codigo_y_nombre,
							COALESCE(vw_hist_academica_inv.nro_acta, vw_hist_academica_inv.nro_resolucion_descripcion) AS acta_o_resolucion,
							CASE
						        WHEN vw_hist_academica_inv.origen = '".acta::origen_examen."' 	   THEN  CAST(vw_hist_academica_inv.anio_academico AS varchar) || ' - ' || vw_hist_academica_inv.turno_examen_nombre
						        WHEN vw_hist_academica_inv.origen = '".acta::origen_promociones."' THEN  CAST(vw_hist_academica_inv.anio_academico AS varchar) || ' - ' || vw_hist_academica_inv.periodo_lectivo_nombre
					        ELSE ''
							END AS periodo_o_turno,
							sga_movimientos_ha.movimiento 		AS movimiento,
							sga_movimientos_ha_motivos.nombre 	AS motivo_inv_nombre,
							sga_movimientos_ha.fecha 			AS fecha_inv,
							sga_documentos.documento_numero 	AS resolucion_inv,
							sga_movimientos_ha.observaciones
							
					FROM vw_hist_academica_inv
					JOIN sga_alumnos ON sga_alumnos.alumno = vw_hist_academica_inv.alumno
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones as pv_actual ON pv_actual.plan_version = sga_alumnos.plan_version
					JOIN sga_planes_versiones as pv_origen ON pv_origen.plan_version = vw_hist_academica_inv.plan_version
					JOIN sga_planes as p_origen ON p_origen.plan = pv_origen.plan
					JOIN sga_movimientos_ha ON (sga_movimientos_ha.alumno = vw_hist_academica_inv.alumno AND 
					                            (sga_movimientos_ha.id_acta = vw_hist_academica_inv.id_acta  OR 
					                             sga_movimientos_ha.equivalencia = vw_hist_academica_inv.equivalencia OR 
					                             sga_movimientos_ha.reconocimiento_act = vw_hist_academica_inv.reconocimiento_act)
												)
					JOIN sga_movimientos_ha_motivos ON sga_movimientos_ha_motivos.motivo_movimiento = sga_movimientos_ha.motivo_movimiento
					LEFT JOIN sga_documentos ON sga_documentos.documento = sga_movimientos_ha.documento
					-- Recuperar el nombre actual de la actividad.
					LEFT JOIN sga_elementos_plan as ep_actual ON (ep_actual.plan_version = pv_actual.plan_version  AND ep_actual.elemento_revision = vw_hist_academica_inv.elemento_revision)
					
					WHERE	sga_movimientos_ha.fecha = (SELECT	MAX(m.fecha) AS fecha
														  FROM	sga_movimientos_ha AS m
														 WHERE  m.alumno = vw_hist_academica_inv.alumno
														   AND (m.id_acta = vw_hist_academica_inv.id_acta OR 
															    m.equivalencia = vw_hist_academica_inv.equivalencia OR 
															    m.reconocimiento_act = vw_hist_academica_inv.reconocimiento_act) 
														) 
														
					AND		sga_movimientos_ha_motivos.tipo = $tipo_invalidar
					$where
				ORDER BY	propuesta_nombre,
							actividad_nombre,
							fecha
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_regularidades($where='', $select='', $origen_reporte=false)
	{
		if ($where) {
			$where = " AND $where";
		}
		
		// Si es el reporte en la Ficha de la persona, no muestro historia academica realizada en otras propuestas 
		// con fecha posterior a fecha de cambio de calidad a Pasivo por Egreso/Abandono/Cambio de propuesta/rechazo inscripcion 
		$where_pasivo = '';
		$select_cond_regularidad = "";	                      
		$from_cond_regularidad = "";
		if ($origen_reporte) {
			$where_pasivo = " AND (vw_regularidades.origen_otra_propuesta = false OR 
			                       sga_alumnos.calidad = 'A' OR 
			                      (vw_regularidades.fecha <= COALESCE(get_fecha_pasivo_egreso_abandono_rechazo(sga_alumnos.alumno),CURRENT_DATE)))";
			                      
			// Agrego columna de condicion de regularidad
			$select_cond_regularidad = ", sga_cond_regularidad.nombre as condicion_regularidad";	                      
			$from_cond_regularidad = "LEFT JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta AND sga_actas_detalle.alumno = vw_regularidades.alumno
						LEFT JOIN sga_cond_regularidad ON sga_cond_regularidad.cond_regularidad = sga_actas_detalle.cond_regularidad";	                      
			
		}
		
		// Se utiliza la vista vw_regularidades_basica porque trae regularidades de actividades en otros planes/versiones 
		// y que no estan en la version del plan actual del alumno. Se puso el alias vw_regularidades por los filtros que se arman en las 
		// operaciones desde donde se llama.
		$sql = "SELECT	
						$select
                        vw_elementos.elemento,
						sga_alumnos.legajo,
						vw_personas.tipo_nro_documento as documento_principal,
						vw_personas.apellido_nombres as apellido_y_nombre,
						sga_turnos_cursadas.nombre as turno_nombre,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						vw_regularidades.elemento as actividad,
						COALESCE(CAST(vw_regularidades.id_acta AS varchar), '') || ':' || COALESCE(CAST(vw_regularidades.equivalencia AS varchar), '') AS clave_acta_equivalencia,
						'(' || vw_elementos.codigo || ') ' || COALESCE(COALESCE(ep_alu.nombre, ep_origen.nombre), vw_elementos.nombre) AS actividad_codigo_y_nombre,
						vw_regularidades.nota,
						vw_regularidades.nota || ' (' || sga_escalas_notas_det.descripcion || ')' AS nota_descr,
						sga_instancias_resultado.descripcion as resultado_descripcion,
						vw_regularidades.fecha,
						vw_regularidades.fecha_vigencia,
						CASE 
						    WHEN vw_regularidades.resultado = 'A' THEN	COALESCE(to_char(vw_regularidades.fecha_vigencia,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') 
						    ELSE '' 
						END AS fecha_vigencia_formato,
						vw_regularidades.es_vigente,
						cast(CASE vw_regularidades.origen
						       WHEN 'R' THEN 'Regularidad'
						       WHEN 'P' THEN 'Promoción'
						       WHEN 'C' THEN 'Equivalencia de Regularidad'
						     END as varchar(30)) as tipo,
						COALESCE(CAST(vw_regularidades.id_acta AS varchar), '') || ':' || COALESCE(CAST(vw_regularidades.equivalencia AS varchar), '') AS clave_ficticia_acta_equivalencia,
						vw_regularidades.id_acta,
						vw_regularidades.alumno,
						vw_regularidades.equivalencia,
						vw_comisiones.comision_nombre AS comision_nombre,
						CASE 
						   WHEN vw_regularidades.origen IN ('R','P') THEN vw_comisiones.anio_academico
						   ELSE cast(null as integer)
						END as anio_academico,
						vw_comisiones.periodo_lectivo_nombre AS periodo_lectivo,
						sga_catedras.nombre AS catedra_nombre,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						sga_planes.codigo AS codigo_plan,
						CASE vw_regularidades.origen
						  WHEN 'R' THEN sga_actas.nro_acta
						  WHEN 'P' THEN sga_actas.nro_acta
						  WHEN 'C' THEN cast(get_resolucion_equivalencia(sga_equiv_otorgada.equivalencia, sga_equiv_otorgada.grupo_equivalencia, sga_equiv_tramite.documento, 2) as varchar(30))
						END as acta_o_resolucion,
						sga_escalas_notas_concepto.nombre as concepto_descripcion,
						vw_regularidades.comision,
						vw_regularidades.origen_otra_propuesta,
						vw_regularidades.origen
						$select_cond_regularidad
				FROM	
						vw_regularidades_basica as vw_regularidades
						JOIN sga_alumnos ON sga_alumnos.alumno = vw_regularidades.alumno
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_regularidades.plan_version
						JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						JOIN vw_elementos ON vw_elementos.elemento = vw_regularidades.elemento
						LEFT JOIN sga_elementos_plan as ep_origen ON (ep_origen.elemento_revision = vw_elementos.elemento_revision AND
											                     ep_origen.plan_version = vw_regularidades.plan_version)
						LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = vw_regularidades.instancia AND 
						                                       sga_instancias_resultado.resultado = vw_regularidades.resultado)
						LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = vw_regularidades.escala_nota AND sga_escalas_notas_det.nota = vw_regularidades.nota)
						LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_concepto.concepto = sga_escalas_notas_det.concepto
						LEFT JOIN vw_comisiones ON vw_comisiones.comision = vw_regularidades.comision
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
						LEFT JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
						LEFT JOIN sga_actas ON sga_actas.id_acta = vw_regularidades.id_acta
						LEFT JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = vw_regularidades.equivalencia_tramite
						LEFT JOIN sga_equiv_otorgada ON sga_equiv_otorgada.equivalencia = vw_regularidades.equivalencia
						LEFT JOIN sga_turnos_cursadas ON vw_comisiones.turno_cursada =  sga_turnos_cursadas.turno
						-- Nombre dela actividad segun plan actual del alumno
						LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.elemento_revision = vw_elementos.elemento_revision AND
														           ep_alu.plan_version = sga_alumnos.plan_version)
						$from_cond_regularidad
						
				WHERE true												   
				$where
				$where_pasivo
				ORDER BY propuesta_nombre, fecha
		";
		// No aplico perfil de datos en forma automatica en el "guarani_db::consultar"
		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_propuestas'));
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Obtiene el listado (solo elemento) de las actividades aprobadas o regularizadas vigentes de un alumno
	 * @param	integer $alumno	# id o arreglo de ids de alumno/s.
	 * @param	char 	$tipo	# A = Aprobadas / R = Regularizadas
	 * @return	array			# Array con los ids de actividades 
	 */
	function get_actividades_aprobadas_regularizadas($alumno, $tipo)
	{
		if (is_array($alumno)) {
			$filtro_aprobadas = guarani_sql::armar_condicion_in($alumno, 'vw_hist_academica_basica', 'alumno');
			$filtro_regularizadas = guarani_sql::armar_condicion_in($alumno, 'vw_regularidades_basica.alumno', 'alumno');
		} else {
			$alumno = toba::db()->quote($alumno);
			$filtro_aprobadas = "vw_hist_academica_basica.alumno = $alumno";
			$filtro_regularizadas = "vw_regularidades_basica.alumno = $alumno";
		}
		$resultado_aprobado = toba::db()->quote(comunes::resultado_nota_aprobado);
		
		if ($tipo == 'A') {
			// Aprobadas
			$sql = "SELECT vw_hist_academica_basica.elemento as elemento
					  FROM vw_hist_academica_basica
					 WHERE $filtro_aprobadas
					   AND vw_hist_academica_basica.resultado = $resultado_aprobado	
					";
		}			
		
		if ($tipo == 'R') {
			// Regularizadas aprobadas vigentes
			$sql = "SELECT vw_regularidades_basica.elemento as elemento
					  FROM vw_regularidades_basica
					 WHERE $filtro_regularizadas
					   AND vw_regularidades_basica.resultado = $resultado_aprobado
					   AND vw_regularidades_basica.es_vigente = 1
					";
		}			

		$actividades = guarani_db::consultar($sql);
		return $actividades ;
	}


	/**
	 * Retorna el listado de actividades con regularidad aprobada para un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_regularidades_basica_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	vw_regularidades_basica.elemento,
						coalesce(vw_regularidades_basica.creditos, 0) as creditos,
						coalesce(vw_regularidades_basica.horas, 0) as horas
				FROM	vw_regularidades_basica
				WHERE	vw_regularidades_basica.alumno = $alumno AND
						vw_regularidades_basica.resultado = '".escalas_notas::resultado_aprobado."' AND
						vw_regularidades_basica.es_vigente = 1
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de actividades regularizadas correspondientes a un año de cursada de un plan-versión dado.
	 * Las actividades aprobadas se consideran siempre regularizadas.
	 * 
	 * @param integer $alumno
	 * @param integer $plan_version
	 * @param integer $anio
	 * @param string $operador_anio
	 * @return integer
	 */
	function get_cant_regularidades_anio_cursada($alumno, $plan_version, $anio, $operador_anio = '=')
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$anio = toba::db()->quote($anio);
		$sql = "SELECT	COUNT(DISTINCT hist_academica_regularidades.elemento) as cant
				FROM	(SELECT	vw_hist_academica_basica.elemento
						FROM	vw_hist_academica_basica
								JOIN sga_elementos_revision ON vw_hist_academica_basica.elemento = sga_elementos_revision.elemento
								JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
						WHERE	vw_hist_academica_basica.alumno = $alumno AND
								sga_elementos_plan.plan_version = $plan_version AND
								sga_elementos_plan.anio_de_cursada $operador_anio $anio AND
								vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
						UNION
						SELECT	vw_regularidades_basica.elemento
						FROM	vw_regularidades_basica
								JOIN sga_elementos_revision ON vw_regularidades_basica.elemento = sga_elementos_revision.elemento
								JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
						WHERE	vw_regularidades_basica.alumno = $alumno AND
								sga_elementos_plan.plan_version = $plan_version AND
								sga_elementos_plan.anio_de_cursada $operador_anio $anio AND
								vw_regularidades_basica.resultado = '".escalas_notas::resultado_aprobado."' AND
								vw_regularidades_basica.es_vigente = 1) as hist_academica_regularidades
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	/**
	 * Retorna la cantidad de actividades regularizadas.
	 * Las actividades aprobadas se consideran siempre regularizadas.
	 * 
	 * @param integer $alumno
	 * @return integer
	 */
	function get_cant_regularidades($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	COUNT(DISTINCT hist_academica_regularidades.elemento) as cant
				FROM	(SELECT	vw_hist_academica_basica.elemento
						FROM	vw_hist_academica_basica
						WHERE	vw_hist_academica_basica.alumno = $alumno AND
								vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
						UNION
						SELECT	vw_regularidades_basica.elemento
						FROM	vw_regularidades_basica
						WHERE	vw_regularidades_basica.alumno = $alumno AND
								vw_regularidades_basica.resultado = '".escalas_notas::resultado_aprobado."' AND
								vw_regularidades_basica.es_vigente = 1) as hist_academica_regularidades
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	/**
	 * Retorna la cantidad de créditos otorgados por actividades regularizadas correspondientes a un año de cursada de un plan-versión dado.
	 * Las actividades aprobadas se consideran siempre regularizadas.
	 * 
	 * @param integer $alumno
	 * @param integer $plan_version
	 * @param integer $anio
	 * @return integer
	 */
	function get_creditos_regularidades_anio_cursada($alumno, $plan_version, $anio)
	{
		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$anio = toba::db()->quote($anio);
		$sql = "SELECT	SUM(elemento_creditos.creditos) as creditos
				FROM	(SELECT	DISTINCT ON (hist_academica_regularidades.elemento)
								hist_academica_regularidades.creditos
						FROM	(SELECT	vw_hist_academica_basica.elemento,
										COALESCE(vw_hist_academica_basica.creditos, 0) as creditos
								FROM	vw_hist_academica_basica
										JOIN sga_elementos_revision ON vw_hist_academica_basica.elemento = sga_elementos_revision.elemento
										JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
								WHERE	vw_hist_academica_basica.alumno = $alumno AND
										sga_elementos_plan.plan_version = $plan_version AND
										sga_elementos_plan.anio_de_cursada = $anio AND
										vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
								UNION
								SELECT	vw_regularidades_basica.elemento,
										COALESCE(vw_regularidades_basica.creditos, 0) as creditos
								FROM	vw_regularidades_basica
										JOIN sga_elementos_revision ON vw_regularidades_basica.elemento = sga_elementos_revision.elemento
										JOIN sga_elementos_plan ON sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
								WHERE	vw_regularidades_basica.alumno = $alumno AND
										sga_elementos_plan.plan_version = $plan_version AND
										sga_elementos_plan.anio_de_cursada = $anio AND
										vw_regularidades_basica.resultado = '".escalas_notas::resultado_aprobado."' AND
										vw_regularidades_basica.es_vigente = 1) as hist_academica_regularidades) as elemento_creditos
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['creditos'];
	}

	/**
	 * Retorna la cantidad de créditos otorgados por actividades regularizadas.
	 * Las actividades aprobadas se consideran siempre regularizadas.
	 * 
	 * @param integer $alumno
	 * @return integer
	 */
	function get_creditos_regularidades($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	SUM(elemento_creditos.creditos) as creditos
				FROM	(SELECT	DISTINCT ON (hist_academica_regularidades.elemento)
								hist_academica_regularidades.creditos
						FROM	(SELECT	vw_hist_academica_basica.elemento,
										COALESCE(vw_hist_academica_basica.creditos, 0) as creditos
								FROM	vw_hist_academica_basica
								WHERE	vw_hist_academica_basica.alumno = $alumno AND
										vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
								UNION
								SELECT	vw_regularidades_basica.elemento,
										COALESCE(vw_regularidades_basica.creditos, 0) as creditos
								FROM	vw_regularidades_basica
								WHERE	vw_regularidades_basica.alumno = $alumno AND
										vw_regularidades_basica.resultado = '".escalas_notas::resultado_aprobado."' AND
										vw_regularidades_basica.es_vigente = 1) as hist_academica_regularidades) as elemento_creditos
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['creditos'];
	}

	function get_regularidades_invalidadas($where='', $select='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$tipo_invalidar = toba::db()->quote(alumno::motivo_movimiento_ha_tipo_invalidar);
		
		$sql = "SELECT	$select
						sga_propuestas.propuesta,
						sga_propuestas.nombre AS propuesta_nombre,
						vw_regularidades_inv.elemento AS actividad,
						COALESCE(CAST(vw_regularidades_inv.id_acta as varchar), '') || ':' || COALESCE(CAST(vw_regularidades_inv.equivalencia as varchar), '') AS clave_acta_equivalencia,
						COALESCE(ep_actual.nombre, vw_regularidades_inv.actividad_nombre) AS actividad_nombre,
						'(' || vw_regularidades_inv.actividad_codigo || ') ' || COALESCE(ep_actual.nombre, vw_regularidades_inv.actividad_nombre) AS actividad_codigo_y_nombre,
						vw_regularidades_inv.nota,
						vw_regularidades_inv.origen,
						vw_regularidades_inv.nota || ' (' || sga_escalas_notas_det.descripcion || ')' AS nota_descr,
						vw_regularidades_inv.resultado_descripcion,
						vw_regularidades_inv.fecha,
						vw_regularidades_inv.fecha_vigencia,
						vw_regularidades_inv.tipo,
						vw_regularidades_inv.id_acta,
						vw_regularidades_inv.equivalencia,
						sga_comisiones.nombre AS comision_nombre,
						vw_regularidades_inv.anio_academico,
						vw_regularidades_inv.periodo_lectivo_nombre AS periodo_lectivo,
						sga_catedras.nombre AS catedra_nombre,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						p_origen.codigo AS codigo_plan,
						COALESCE(vw_regularidades_inv.nro_acta, vw_regularidades_inv.nro_resolucion_descripcion) AS acta_o_resolucion,
						sga_escalas_notas_concepto.nombre AS concepto_nombre,
						sga_movimientos_ha_motivos.nombre AS motivo_inv_nombre,
						sga_movimientos_ha.fecha AS fecha_inv,
						sga_documentos.documento_numero AS resolucion_inv,
						sga_movimientos_ha.observaciones
						
					FROM vw_regularidades_inv
					JOIN sga_alumnos ON sga_alumnos.alumno = vw_regularidades_inv.alumno
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones as pv_actual ON pv_actual.plan_version = sga_alumnos.plan_version
					
					JOIN sga_planes_versiones as pv_origen ON pv_origen.plan_version = vw_regularidades_inv.plan_version
					JOIN sga_planes as p_origen ON p_origen.plan = pv_origen.plan
					JOIN sga_movimientos_ha ON (sga_movimientos_ha.alumno = vw_regularidades_inv.alumno AND	
					                            (sga_movimientos_ha.id_acta = vw_regularidades_inv.id_acta OR sga_movimientos_ha.equivalencia = vw_regularidades_inv.equivalencia)
											   )	 
					JOIN sga_movimientos_ha_motivos ON sga_movimientos_ha_motivos.motivo_movimiento = sga_movimientos_ha.motivo_movimiento
					LEFT JOIN sga_documentos ON sga_movimientos_ha.documento = sga_documentos.documento
					LEFT JOIN sga_escalas_notas_det ON (vw_regularidades_inv.escala_nota = sga_escalas_notas_det.escala_nota AND vw_regularidades_inv.nota = sga_escalas_notas_det.nota)
					LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_det.concepto = sga_escalas_notas_concepto.concepto
					LEFT JOIN sga_comisiones ON vw_regularidades_inv.comision = sga_comisiones.comision
					LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
					LEFT JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion

					-- Recuperar el nombre actual de la actividad.
					LEFT JOIN sga_elementos_plan as ep_actual ON (ep_actual.plan_version = pv_actual.plan_version AND ep_actual.elemento_revision = vw_regularidades_inv.elemento_revision)
							
					WHERE	sga_movimientos_ha_motivos.tipo = $tipo_invalidar
					  AND	sga_movimientos_ha.fecha = (SELECT	MAX(m.fecha) AS fecha
														FROM	sga_movimientos_ha AS m
														WHERE  (m.id_acta = vw_regularidades_inv.id_acta OR
																m.equivalencia = vw_regularidades_inv.equivalencia) 
														 AND	m.alumno = vw_regularidades_inv.alumno
														)
					 
					$where
				ORDER BY	propuesta_nombre,
							actividad_nombre,
							fecha
				";
		
		return guarani_db::consultar($sql);
	}

	function get_regularidades_provisorias($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		// Recupera los alumnos que tienen un resultado cargado en la comision, esten o no en un acta abierta de cursada.
		// No recupera al alumno si esta en un acta abierta rectificativa de cursada o si esta en un acta cerrada de cursadas.
		$sql = "SELECT		sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_elementos.elemento as actividad,
							'(' || sga_elementos.codigo || ') ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_codigo_y_nombre,
							sga_eval_detalle_cursadas.nota_cursada,
							sga_eval_detalle_cursadas.nota_cursada || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descr,
							sga_escalas_notas_resultado.descripcion as resultado_descripcion,
							sga_eval_detalle_cursadas.fecha_regular as fecha,
							sga_comisiones.nombre as comision_nombre,
							sga_periodos.anio_academico as anio_academico,
							sga_periodos.nombre as periodo_lectivo,
							sga_planes.codigo as codigo_plan,
							sga_catedras.nombre as catedra_nombre,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_escalas_notas_concepto.nombre as concepto_nombre,
							sga_actas.nro_acta as nro_acta_cursada
							
				FROM		sga_alumnos
						JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
						JOIN sga_eval_detalle_cursadas ON sga_alumnos.alumno = sga_eval_detalle_cursadas.alumno
						JOIN sga_evaluaciones ON sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion
						JOIN sga_comisiones ON sga_evaluaciones.entidad = sga_comisiones.entidad
						JOIN sga_evaluaciones_tipos ON sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo 
						JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
						JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
						JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_eval_detalle_cursadas.plan_version
						JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
						JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
						LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle_cursadas.escala_nota_cursada AND sga_escalas_notas_det.nota = sga_eval_detalle_cursadas.nota_cursada)
						LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_concepto.concepto = sga_escalas_notas_det.concepto
						LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle_cursadas.resultado_cursada
						LEFT JOIN sga_actas ON sga_actas.id_acta = sga_eval_detalle_cursadas.id_acta_cursada
						LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
						
				WHERE		sga_eval_detalle_cursadas.acta_cursada_cerrada = 0 AND
							sga_eval_detalle_cursadas.resultado_cursada IS NOT NULL AND
							sga_evaluaciones_tipos.automatica = 'S' AND
							-- Si esta en un acta que sea un acta abierta y normal (no rectificativa)
							(sga_eval_detalle_cursadas.id_acta_cursada IS NULL OR
							 (sga_eval_detalle_cursadas.id_acta_cursada IS NOT NULL AND sga_actas.estado = 'A' AND sga_actas.tipo_acta = 'N')
							) AND
							-- El alumno no existe en un acta de cursada cerrada para la comision
							NOT EXISTS (SELECT 1 FROM sga_actas as ac, sga_actas_detalle as dac
							              WHERE ac.evaluacion = sga_eval_detalle_cursadas.evaluacion
										    AND ac.origen = 'R'
										    AND ac.tipo_acta = 'N'
											AND ac.estado = 'C'
											AND dac.id_acta = ac.id_acta
											AND dac.alumno = sga_alumnos.alumno
											AND dac.estado = 'A'
										)
							$where
				ORDER BY	propuesta_nombre,
							fecha
		";
		return guarani_db::consultar($sql);
	}
	
	function get_listado_vencimiento_regularidades($where='')
	{
		if ($where) {
			$where = 'AND '.$where;
		}

		// Regularidades aprobadas sin examen final aprobado en la misma propuesta segun filtro ingresado.
		// No se usa la vista vw_regularidades porque la query se hace mas lenta.
		$sql = "SELECT	vw_comisiones.elemento, '(' || vw_comisiones.elemento_codigo || ') ' || vw_comisiones.elemento_nombre as actividad_codigo_y_nombre, 
						vw_comisiones.comision,
						'Comisión: ' || vw_comisiones.comision_nombre || COALESCE(' - Turno: ' || sga_turnos_cursadas.nombre, ' ') || ' - Ubicación: ' || sga_ubicaciones.nombre as info_comision, 
						'Comisión: ' || vw_comisiones.comision_nombre || COALESCE(' - Turno: ' || sga_turnos_cursadas.nombre, ' ') || ' - Ubicación: ' || sga_ubicaciones.nombre as info_comision_desc, 
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as apellido_y_nombres,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_codigo_y_nombre, 
						sga_alumnos.legajo, 
						vw_comisiones.elemento_codigo as actividad_codigo, 
						COALESCE(to_char(sga_actas_detalle.fecha_vigencia, '".comunes::formato_fecha_visual."'), 'Sin Vencimiento') as fecha_vigencia_formato 
				FROM	sga_actas 
						JOIN vw_comisiones ON vw_comisiones.comision = sga_actas.comision
						JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
						JOIN sga_alumnos ON sga_alumnos.alumno = sga_actas_detalle.alumno
						JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
						JOIN sga_elementos_revision ON sga_elementos_revision.elemento = vw_comisiones.elemento
						LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = vw_comisiones.turno_cursada
				WHERE	sga_actas.origen = '".acta::origen_regulares."' AND
						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						-- Actividad NO APROBADA. Se utiliza el IN porque en este caso es mas optimo que el NOT EXISTS.
						sga_alumnos.alumno NOT IN 
									(SELECT	vw_hist_academica_basica.alumno
									FROM	vw_hist_academica_basica
									WHERE	vw_hist_academica_basica.alumno = sga_alumnos.alumno AND
											vw_hist_academica_basica.elemento = vw_comisiones.elemento AND
											vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
											vw_hist_academica_basica.origen_otra_propuesta = false)
						$where
				ORDER BY	vw_comisiones.elemento_nombre,
							vw_comisiones.comision_nombre					
		";

		return guarani_db::consultar($sql);
	}


	function get_equivalencias_parciales($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT	
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						vw_equiv_parciales.nro_resolucion_descripcion,
						'(' || vw_equiv_parciales.actividad_codigo || ') ' || COALESCE(ep_alu.nombre, vw_equiv_parciales.actividad_nombre) AS actividad_codigo_y_nombre,
						vw_equiv_parciales.fecha,
						vw_equiv_parciales.nota,
						sga_escalas_notas.nombre as escala_nota_nombre,
						vw_equiv_parciales.resultado,
						vw_equiv_parciales.resultado_descripcion,
						sga_planes.codigo as plan_codigo,
						vw_equiv_parciales.fecha_vigencia,
						vw_equiv_parciales.temas_a_rendir
					FROM sga_alumnos
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN vw_equiv_parciales ON vw_equiv_parciales.alumno = sga_alumnos.alumno
					JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_equiv_parciales.plan_version
					JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
					LEFT JOIN sga_escalas_notas ON vw_equiv_parciales.escala_nota = sga_escalas_notas.escala_nota
					-- Nombre dela actividad segun plan actual del alumno
					LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.elemento_revision = vw_equiv_parciales.elemento_revision AND
											                   ep_alu.plan_version = sga_alumnos.plan_version)
                $where
				ORDER BY	propuesta_nombre,
							fecha
		";
		return guarani_db::consultar($sql);
	}

	function get_inscripciones_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$si = comunes::si;
		$no = comunes::no;
		
		$tipo_insc_cursada = 'C';
		$tipo_insc_examen = 'E';
		
		$estado_rechazada = 'Rechazada';
		
		$sql = "-- Insc a cursadas aceptadas y pendientes
				SELECT 	sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_codigo_y_nombre,
						'(' || vw_elementos_plan.codigo || ') ' || COALESCE(ep_alu.nombre, vw_elementos_plan.nombre) as actividad_codigo_y_nombre,
						vw_insc_cursada.fecha_inscripcion,
						sga_comisiones.nombre as comision_o_mesa,
						sga_periodos.nombre as periodo_o_turno_nombre,
						f_instancias_insc_cursada(vw_insc_cursada.inscripcion) as instancia_nombre,
						sga_catedras.nombre as catedra_nombre,
						sga_inscripciones_estados.nombre as estado_nombre,
						CASE
							WHEN CURRENT_DATE <= sga_periodos_lectivos.fecha_fin_dictado THEN '$si'
							ELSE '$no'
						END as vigente,
						'$tipo_insc_cursada' as tipo_inscripcion,
						vw_insc_cursada.exceptuado as exceptuado,
						(CASE WHEN vw_insc_cursada.exceptuado = 'S' THEN 1 ELSE 0 END) as mostrar_lupa_requisitos,
						vw_insc_cursada.nro_transaccion
				FROM 	sga_alumnos
				JOIN	vw_insc_cursada ON sga_alumnos.alumno = vw_insc_cursada.alumno
				JOIN	sga_comisiones ON sga_comisiones.comision = vw_insc_cursada.comision
				JOIN	sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN	sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				JOIN	sga_inscripciones_estados ON sga_inscripciones_estados.estado = vw_insc_cursada.estado
				JOIN	vw_elementos_plan ON (vw_elementos_plan.elemento = sga_comisiones.elemento AND vw_elementos_plan.plan_version = vw_insc_cursada.plan_version)
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
				-- Nombre dela actividad segun plan actual del alumno
				LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.elemento_revision = vw_elementos_plan.elemento_revision AND
										                   ep_alu.plan_version = sga_alumnos.plan_version)
				
				WHERE  	sga_alumnos.persona = $persona

				UNION ALL

				-- Insc a cursadas rechazadas
				SELECT 	sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_codigo_y_nombre,
						'(' || vw_elementos_plan.codigo || ') ' || COALESCE(ep_alu.nombre, vw_elementos_plan.nombre) as actividad_codigo_y_nombre,
						vw_insc_cursada_log.fecha_inscripcion,
						sga_comisiones.nombre as comision_o_mesa,
						sga_periodos.nombre as periodo_o_turno_nombre,
						f_instancias_insc_cursada_log(vw_insc_cursada_log.inscripcion) as instancia_nombre,
						sga_catedras.nombre as catedra_nombre,
						'$estado_rechazada' as estado_nombre,
						CASE
							WHEN CURRENT_DATE <= sga_periodos_lectivos.fecha_fin_dictado THEN '$si'
							ELSE '$no'
						END as vigente,
						'$tipo_insc_cursada' as tipo_inscripcion,
						vw_insc_cursada_log.exceptuado as exceptuado,
						(CASE WHEN vw_insc_cursada_log.operacion = '".inscripcion_cursada::operacion_rechazo."' THEN 1 ELSE 0 END) as mostrar_lupa_requisitos,
						vw_insc_cursada_log.nro_transaccion				
				FROM 	sga_alumnos
				JOIN	vw_insc_cursada_log ON sga_alumnos.alumno = vw_insc_cursada_log.alumno
				JOIN	sga_comisiones ON sga_comisiones.comision = vw_insc_cursada_log.comision
				JOIN	sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN	sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				JOIN	vw_elementos_plan ON (vw_elementos_plan.elemento = sga_comisiones.elemento AND vw_elementos_plan.plan_version = vw_insc_cursada_log.plan_version)
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
				-- Nombre dela actividad segun plan actual del alumno
				LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.elemento_revision = vw_elementos_plan.elemento_revision AND
										                   ep_alu.plan_version = sga_alumnos.plan_version)
				WHERE 	sga_alumnos.persona = $persona
				  AND vw_insc_cursada_log.operacion = '".inscripcion_cursada::operacion_rechazo."'

				UNION ALL

				-- Insc a examenes aceptadas y pendientes
				SELECT 	sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_codigo_y_nombre,
						'(' || vw_elementos_plan.codigo || ') ' || COALESCE(ep_alu.nombre, vw_elementos_plan.nombre) as actividad_codigo_y_nombre,
						vw_insc_examen.fecha_inscripcion,
						vw_mesas_examen.mesa_examen_nombre || ' - ' || to_char(vw_mesas_examen.mesa_examen_fecha,'".comunes::formato_fecha_visual."') as comision_o_mesa,
						vw_mesas_examen.turno_examen_nombre as periodo_o_turno_nombre,
						sga_instancias.nombre as instancia_nombre,
						sga_catedras.nombre as catedra_nombre,
						sga_inscripciones_estados.nombre as estado_nombre,
						CASE
							WHEN CURRENT_DATE <= vw_mesas_examen.mesa_examen_fecha THEN '$si'
							ELSE '$no'
						END as vigente,
						'$tipo_insc_examen' as tipo_inscripcion,
						vw_insc_examen.exceptuado as exceptuado,
						(CASE WHEN vw_insc_examen.exceptuado = 'S' THEN 1 ELSE 0 END) as mostrar_lupa_requisitos,
						vw_insc_examen.nro_transaccion
				FROM 	sga_alumnos
				JOIN	vw_insc_examen ON sga_alumnos.alumno = vw_insc_examen.alumno
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN	vw_mesas_examen ON vw_mesas_examen.llamado_mesa = vw_insc_examen.llamado_mesa
				JOIN	sga_inscripciones_estados ON sga_inscripciones_estados.estado = vw_insc_examen.estado
				JOIN	vw_elementos_plan ON (vw_elementos_plan.elemento = vw_mesas_examen.mesa_examen_elemento AND vw_elementos_plan.plan_version = vw_insc_examen.plan_version)
				JOIN	sga_instancias ON sga_instancias.instancia = vw_insc_examen.instancia
				LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
				-- Nombre dela actividad segun plan actual del alumno
				LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.elemento_revision = vw_elementos_plan.elemento_revision AND
										                   ep_alu.plan_version = sga_alumnos.plan_version)
				WHERE 	sga_alumnos.persona = $persona

				UNION ALL

				-- Insc a examenes rechazados
				SELECT 	sga_propuestas.propuesta,
						'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_codigo_y_nombre,
						'(' || vw_elementos_plan.codigo || ') ' || COALESCE(ep_alu.nombre, vw_elementos_plan.nombre) as actividad_codigo_y_nombre,
						vw_insc_examen_log.fecha_inscripcion,
						vw_mesas_examen.mesa_examen_nombre || ' - ' || to_char(vw_mesas_examen.mesa_examen_fecha,'".comunes::formato_fecha_visual."') as comision_o_mesa,
						vw_mesas_examen.turno_examen_nombre as periodo_o_turno_nombre,
						sga_instancias.nombre as instancia_nombre,
						sga_catedras.nombre as catedra_nombre,
						'$estado_rechazada' as estado_nombre,
						CASE
							WHEN CURRENT_DATE <= vw_mesas_examen.mesa_examen_fecha THEN '$si'
							ELSE '$no'
						END as vigente,
						'$tipo_insc_examen' as tipo_inscripcion,
						vw_insc_examen_log.exceptuado as exceptuado,
						(CASE WHEN vw_insc_examen_log.operacion = '".inscripcion_examen::operacion_rechazo."' THEN 1 ELSE 0 END) as mostrar_lupa_requisitos,
						vw_insc_examen_log.nro_transaccion				
				FROM 	sga_alumnos
				JOIN	vw_insc_examen_log ON sga_alumnos.alumno = vw_insc_examen_log.alumno
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN	vw_mesas_examen ON vw_mesas_examen.llamado_mesa = vw_insc_examen_log.llamado_mesa
				JOIN	vw_elementos_plan ON (vw_elementos_plan.elemento = vw_mesas_examen.mesa_examen_elemento AND vw_elementos_plan.plan_version = vw_insc_examen_log.plan_version)
				JOIN	sga_instancias ON sga_instancias.instancia = vw_insc_examen_log.instancia
				LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
				-- Nombre dela actividad segun plan actual del alumno
				LEFT JOIN sga_elementos_plan as ep_alu ON (ep_alu.elemento_revision = vw_elementos_plan.elemento_revision AND
										                   ep_alu.plan_version = sga_alumnos.plan_version)
				WHERE 	sga_alumnos.persona = $persona
				  AND vw_insc_examen_log.operacion = '".inscripcion_examen::operacion_rechazo."'

				ORDER BY 
						propuesta_codigo_y_nombre,
						fecha_inscripcion
				";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de actividades en las cuales un alumno dado se encuentra inscripto a cursar. 
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_inscripciones_cursada_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_comisiones.elemento
				FROM	vw_insc_cursada,
						sga_comisiones
				WHERE	vw_insc_cursada.comision = sga_comisiones.comision AND
						vw_insc_cursada.alumno = $alumno AND
						vw_insc_cursada.estado = '".inscripcion_cursada::estado_aceptada."'
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el último cambio de calidad de un alumno.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_ultimo_cambio_calidad($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_alumnos_hist_calidad.calidad,
							sga_alumnos_hist_calidad.fecha
				FROM		sga_alumnos_hist_calidad
				WHERE		sga_alumnos_hist_calidad.alumno = $alumno
				ORDER BY	sga_alumnos_hist_calidad.fecha DESC, sga_alumnos_hist_calidad.cambio_calidad DESC
				LIMIT		1
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el último cambio de plan de un alumno.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_ultimo_cambio_plan($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_alumnos_hist_planes.plan_version,
							sga_alumnos_hist_planes.fecha
				FROM		sga_alumnos_hist_planes
				WHERE		sga_alumnos_hist_planes.alumno = $alumno
				ORDER BY	sga_alumnos_hist_planes.fecha DESC, sga_alumnos_hist_planes.cambio_plan DESC
				LIMIT		1
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el último cambio de modalidad de un alumno.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_ultimo_cambio_modalidad($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_alumnos_hist_modalidad.modalidad,
							sga_alumnos_hist_modalidad.fecha
				FROM		sga_alumnos_hist_modalidad
				WHERE		sga_alumnos_hist_modalidad.alumno = $alumno
				ORDER BY	sga_alumnos_hist_modalidad.fecha DESC, sga_alumnos_hist_modalidad.cambio_modalidad DESC
				LIMIT		1
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el último cambio de ubicación de un alumno.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_ultimo_cambio_ubicacion($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_alumnos_hist_ubicacion.ubicacion,
							sga_alumnos_hist_ubicacion.fecha
				FROM		sga_alumnos_hist_ubicacion
				WHERE		sga_alumnos_hist_ubicacion.alumno = $alumno
				ORDER BY	sga_alumnos_hist_ubicacion.fecha DESC, sga_alumnos_hist_ubicacion.cambio_ubicacion DESC
				LIMIT		1
		";
		return guarani_db::consultar_fila($sql);
	}

	//------------------------------------------------------------------------
	//-- Verificación de la historia académica del alumno.
	//------------------------------------------------------------------------

	/**
	 * Determina si un alumno tiene historia académica válida.
	 * Si el alumno es null, recupera la historia académica existente según el filtro dado
	 * (se utiliza para eliminar elementos de Planes Activos si no poseen hist. académica asociada).
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function tiene_historia_academica($alumno=null, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$where .= " AND vw_hist_academica_basica.alumno = $alumno";
		}
		
		$sql = "SELECT	1
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.origen_otra_propuesta = false
						$where
				LIMIT 1
		";
		$rs = guarani_db::consultar($sql);
		return !empty($rs);
	}

	/**
	 * Si el Alumno no es null, determina si tiene en su historia académica a la Actividad dada, 
	 * ya sea aprobada o desaprobada. Se utiliza para determinar si la misma puede eliminarse o no
	 * del Plan Personalizado creado para dicho Alumno.
	 * Para el caso de un plan de tipo Curso (alumno = null) determina si existe historia académica
	 * registrada para la actividad en el plan versión, para cualquier alumno. 
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @return 	boolean
	 */
	function tiene_actividad_en_historia_academica_plan_version($plan_version, $actividad, $alumno=null, $where='true')
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad = toba::db()->quote($actividad);
		$filtro = "$where AND vw_hist_academica_basica.elemento = $actividad AND vw_hist_academica_basica.plan_version = $plan_version";
		return $this->tiene_historia_academica($alumno, $filtro);
	}
	
	/**
	 * Determina si un alumno tiene regularidades vigentes.
	 * Si el alumno es null, recupera las regularidades existentes según el filtro dado
	 * (se utiliza para eliminar elementos de Planes Activos si no poseen regularidades asociadas).
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function tiene_regularidades($alumno=null, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$where .= " AND vw_regularidades_basica.alumno = $alumno";
		}
		
		$sql = "SELECT	1
				FROM	vw_regularidades_basica
				WHERE	vw_regularidades_basica.origen_otra_propuesta = false
						$where
				LIMIT 1
		";
		$rs = guarani_db::consultar($sql);
		return !empty($rs);
	}

	/**
	 * Determina si un Alumno tiene entre sus regularidades a la Actividad dada, 
	 * ya sea aprobada o desaprobada. Se utiliza para determinar si la misma puede eliminarse o no
	 * del Plan Personalizado creado para dicho Alumno.
	 * Para el caso de un plan de tipo Curso (alumno = null) determina si existen regularidades
	 * registradas para la actividad en el plan versión, para cualquier alumno. 
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @return 	boolean
	 */
	function tiene_actividad_en_regularidades_plan_version($plan_version, $actividad, $alumno=null, $where='true')
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad = toba::db()->quote($actividad);
		$filtro = "$where AND vw_regularidades_basica.elemento = $actividad AND vw_regularidades_basica.plan_version = $plan_version";
		return $this->tiene_regularidades($alumno, $filtro);
	}

	/*
	 * Verifica si el alumno tiene inscripciones a actividades o examen vigentes sin estar en acta cerrada
	 * Utilizado en el cambio de calidad del alumno, cuando se cambia a Pasivo
	 * @param 	integer $alumno
	 * @return 	array
	*/

	/**
	 * Retorna la cantidad de inscripciones a cursada o examen vigentes (sin estar en acta cerrada) para un alumno dado.
	 * Utilizado cuando se cambia la calidad de un alumno a "Pasivo".
	 * 
	 * @param int $alumno
	 * @return int
	 */
	function get_cant_inscripciones_cursada_examen_sin_acta_cerrada($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	COUNT(1) as cant_inscripciones
				FROM (	SELECT 	1
						FROM	vw_insc_cursada
						WHERE	vw_insc_cursada.alumno = $alumno AND
								NOT EXISTS (SELECT	1 
											FROM	sga_actas
													JOIN sga_actas_detalle ON (sga_actas_detalle.id_acta = sga_actas.id_acta AND sga_actas_detalle.alumno = $alumno)
											WHERE	sga_actas.comision = vw_insc_cursada.comision AND
													sga_actas.estado = '".acta::estado_cerrada."') 
						UNION ALL 
						-- Insc a examenes que no estan en acta cerrada.
						SELECT	1
						FROM	vw_insc_examen
						WHERE	vw_insc_examen.alumno = $alumno AND
								NOT EXISTS (SELECT	1 
											FROM	sga_actas
													JOIN sga_actas_detalle ON (sga_actas_detalle.id_acta = sga_actas.id_acta AND sga_actas_detalle.alumno = $alumno)
											WHERE	sga_actas.llamado_mesa = vw_insc_examen.llamado_mesa AND
													sga_actas.estado = '".acta::estado_cerrada."')) as inscripciones 
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant_inscripciones'];
	}

	/**
	 * Determina si un alumno tiene inscripciones a Cursada.
	 * Si el alumno es null, recupera las Inscripciones a Cursada existentes según el filtro dado
	 * (se utiliza para eliminar elementos de Planes Activos si no poseen inscripciones asociadas).
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function tiene_inscripciones_actividad($alumno=null, $where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$filtro_alumno = "vw_insc_cursada.alumno = $alumno";
			$where = !empty($where) ? $where . ' AND ' . $filtro_alumno :  ' WHERE ' . $filtro_alumno;
		}
		
		$sql = "SELECT	1
				FROM	vw_insc_cursada
				$where
				LIMIT 1
		";
		$rs = guarani_db::consultar($sql);
		return !empty($rs);
	}

	/**
	 * Determina si un Alumno tiene Inscripciones a Cursada para la Actividad dada en el plan versión dado. 
	 * Se utiliza para determinar si la misma puede eliminarse o no del Plan Personalizado creado para dicho Alumno.
	 * Para el caso de un plan de tipo Curso (alumno = null) determina si existen Inscripciones a Cursada
	 * registradas para la actividad en el plan versión, para cualquier alumno. 
	 */
	function tiene_inscripcion_a_cursada_actividad_plan_version($plan_version, $actividad, $alumno=null)
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad = toba::db()->quote($actividad);
		$filtro = "vw_insc_cursada.comision IN (SELECT	sga_comisiones.comision
												FROM	sga_comisiones
												WHERE	sga_comisiones.elemento = $actividad)
					AND vw_insc_cursada.plan_version = $plan_version";
		return $this->tiene_inscripciones_actividad($alumno, $filtro);
	}

	/**
	 * Determina si un alumno tiene una inscripción rechazada en la comisión (incluyendo otras propuestas de la misma persona).
	 * 
	 * @param integer $alumno
	 * @param integer $comision
	 * @return boolean
	 */
	function tiene_inscripcion_rechazada_comision($alumno, $comision)
	{
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);
        $sql = "SELECT	COUNT(sga_insc_cursada_log.inscripcion) as cant
				FROM	sga_alumnos
						JOIN sga_insc_cursada_log ON sga_alumnos.alumno = sga_insc_cursada_log.alumno
				WHERE	sga_alumnos.persona IN (SELECT persona FROM sga_alumnos WHERE alumno = $alumno) AND
						sga_insc_cursada_log.comision = $comision AND
						sga_insc_cursada_log.operacion = '".inscripcion_cursada::operacion_rechazo."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	/**
	 * Determina si un alumno tiene inscripciones a examen.
	 * Si el alumno es null, recupera las Inscripciones a Examen existentes según el filtro dado
	 * (se utiliza para eliminar elementos de Planes Activos si no poseen inscripciones asociadas).
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function tiene_inscripciones_examen($alumno=null, $where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$filtro_alumno = "vw_insc_examen.alumno = $alumno";
			$where = !empty($where) ? $where . ' AND ' . $filtro_alumno : ' WHERE ' . $filtro_alumno;
		}
		
		$sql = "SELECT	vw_insc_examen.inscripcion
				FROM	vw_insc_examen
				$where
				LIMIT 1
		";
		$rs = guarani_db::consultar($sql);
		return !empty($rs);
	}

	/**
	 * Determina si un Alumno tiene Inscripciones a Examen para la Actividad dada en el plan versión dado. 
	 * Se utiliza para determinar si la misma puede eliminarse o no del Plan Personalizado creado para dicho Alumno.
	 * Para el caso de un plan de tipo Curso (alumno = null) determina si existen Inscripciones a Examen
	 * registradas para la actividad en el plan versión, para cualquier alumno. 
	 */
	function tiene_inscripcion_a_examen_actividad_plan_version($plan_version, $actividad, $alumno)
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad = toba::db()->quote($actividad);
		$filtro = "vw_insc_examen.llamado_mesa IN ( SELECT	vw_mesas_examen.llamado_mesa
													FROM	vw_mesas_examen
													WHERE	Vw_mesas_examen.mesa_examen_elemento = $actividad)
					AND vw_insc_examen.plan_version = $plan_version";
		return $this->tiene_inscripciones_examen($alumno, $filtro);
	}

	/**
	 * Determina si un alumno está en algún Acta abierta (de Cursada o Examen) de la actividad dada.
	 * Se utiliza para determinar si la misma puede eliminarse o no del Plan Personalizado creado para dicho alumno.
	 * Para el caso de un plan de tipo Curso (alumno = null) determina si existen Inscripciones a Examen
	 * registradas para la actividad en el plan versión, para cualquier alumno. 
	 *
	 * @param 	integer $plan_version
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @return 	boolean
	 */
	function tiene_actividad_en_evaluaciones_plan_version($plan_version, $actividad, $alumno=null)
	{
		$plan_version = toba::db()->quote($plan_version);
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$where_cursada = " AND sga_eval_detalle_cursadas.alumno = $alumno";
			$where_examen = " AND sga_eval_detalle_examenes.alumno = $alumno";
		} else {
			$where_cursada = '';
			$where_examen = '';
		}
		$actividad = toba::db()->quote($actividad);
		$sql = "SELECT	1
				FROM	sga_eval_detalle_cursadas,
						sga_evaluaciones,
						sga_comisiones
				WHERE	sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion AND
						sga_evaluaciones.entidad = sga_comisiones.entidad AND
						sga_eval_detalle_cursadas.plan_version = $plan_version AND
						sga_comisiones.elemento = $actividad
						$where_cursada
				UNION
				SELECT	1
				FROM	sga_eval_detalle_examenes,
						sga_evaluaciones,
						sga_llamados_mesa,
						sga_mesas_examen
				WHERE	sga_eval_detalle_examenes.evaluacion = sga_evaluaciones.evaluacion AND
						sga_evaluaciones.entidad = sga_llamados_mesa.entidad AND
						sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen AND
						sga_eval_detalle_examenes.plan_version = $plan_version AND
						sga_mesas_examen.elemento = $actividad
						$where_examen
				LIMIT 1
		";
		$rs = guarani_db::consultar($sql);
		return !empty($rs);
	}

	/**
	 * Determina si un alumno tiene trámites de solicitud de certificado iniciados.
	 * Si el alumno es null, determina si existe algún trámite de solicitud de certificado iniciado,
	 * según el filtro indicado.
	 *
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function tiene_certificados_otorgados($alumno=null, $where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$filtro_alumno = "sga_certificados_otorg.alumno = $alumno";
			$where = !empty($where) ? $where . ' AND ' . $filtro_alumno : ' WHERE ' . $filtro_alumno;
		}
		
		$sql = "SELECT	sga_certificados_otorg.nro_solicitud
				FROM	sga_certificados_otorg
				$where
		";
		$rs = guarani_db::consultar($sql);
		return !empty($rs);
	}

	/**
	 * Si el alumno no es null, determina si un Alumno tiene trámites de solicitud iniciados para un 
	 * Certificado en un Plan Versión dados. Si no está seteado, verifica si existe al menos un Alumno
	 * del Plan Versión dado, que haya iniciado una solicitud del Certificado dado.
	 */
	
	function tiene_certificado_otorgado_en_plan_version($certificado, $plan_version, $alumno=null)
	{
		$certificado = toba::db()->quote($certificado);
		$plan_version = toba::db()->quote($plan_version);
		$filtro = "	sga_certificados_otorg.certificado = $certificado
					AND sga_certificados_otorg.plan_version = $plan_version
					AND sga_certificados_otorg.anulado = 0";
		return $this->tiene_certificados_otorgados($alumno, $filtro);
	}	
	
	/**
	 * Determina si un alumno es regular.
	 *
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function es_alumno_regular($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT  sga_alumnos.regular
				FROM 	sga_alumnos
				WHERE	sga_alumnos.alumno = $alumno
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['regular'] == comunes::si);
	}

	/**
	 * Verifica si el alumno ha sido sancionado en la institución en una fecha dada.
	 * Si tiene fecha de cancelacion, considera que no ya no esta sancionado en la fecha de cancelación
	 *
	 * @param integer $alumno
	 * @param date $fecha
	 * @return boolean
	 */
	function es_alumno_sancionado($alumno, $fecha)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha = toba::db()->quote($fecha);
		$sql = "SELECT COUNT(sga_sanciones.sancion) as cant
				  FROM sga_alumnos 
				  JOIN sga_sanciones ON sga_sanciones.persona = sga_alumnos.persona
				WHERE sga_alumnos.alumno = $alumno
				  AND (sga_sanciones.propuesta = sga_alumnos.propuesta OR sga_sanciones.propuesta IS NULL)
				  AND (sga_sanciones.fecha_cancelacion IS NULL AND sga_sanciones.fecha_hasta IS NULL AND sga_sanciones.fecha_desde <= $fecha OR
		 			   sga_sanciones.fecha_cancelacion IS NULL AND sga_sanciones.fecha_hasta IS NOT NULL AND $fecha BETWEEN sga_sanciones.fecha_desde AND sga_sanciones.fecha_hasta OR
					   sga_sanciones.fecha_cancelacion IS NOT NULL AND $fecha BETWEEN sga_sanciones.fecha_desde AND sga_sanciones.fecha_cancelacion - interval '1 day'
					   )
		";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}

	/**
	 * Verifica si el alumno tiene calidad "Activo" en una fecha dada.
	 *
	 * @param integer $alumno
	 * @param date $fecha por defecto toma la fecha actual
	 * @return boolean
	 */
	function es_alumno_activo($alumno, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		if (is_null($fecha)) {
			// Recupero la calidad actual del alumno
			$sql = "SELECT	sga_alumnos.calidad	
					FROM	sga_alumnos
					WHERE	sga_alumnos.alumno = $alumno
					";
		} else {
			$fecha = toba::db()->quote($fecha);			
			
			// Se recupera el ultimo registro del historico de cambio de calidad con fecha igual o menor a la fecha a buscar
			// Se ordena por fecha y el dato de cambio_calidad por casos donde hay cambios de calidad el mismo dia y horario.
			// Que tome el ultimo dato registrado.
			$sql = "SELECT	sga_alumnos_hist_calidad.calidad
					FROM	sga_alumnos_hist_calidad
					WHERE	sga_alumnos_hist_calidad.alumno = $alumno
					  AND	CAST(sga_alumnos_hist_calidad.fecha as DATE) <= $fecha
					ORDER BY sga_alumnos_hist_calidad.fecha DESC, 
							sga_alumnos_hist_calidad.cambio_calidad DESC
					LIMIT 1
					";
		}
		
		// Recupero la calidad del alumno
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['calidad'] == alumno::calidad_activo);
	}
	
	/**
	 *	Retorna los requisitos documentales restrictivos que el alumno no presentó a la menor fecha de: 
			- fecha actual (momento en que se corre el proceso)
		y mayor entre:
			- fecha de prórroga otorgada al alumno (sga_requisito_aspirante.fecha_prorroga)
			- fecha de excepción de presentación en el período de inscripción (sga_requisitos_ingreso_excep.fecha_tope), si existe, o fecha de cierre definida en el período de inscripción (sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos).
	 *
	 * @param integer $alumno
	 * @param date $fecha
	 * @return array
	 */
	function cumple_requisitos_ingreso_documentales_restrictivos($alumno, $fecha)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha = toba::db()->quote($fecha);
		$sql = "SELECT	sga_requisitos_aspirante.requisito,
						sga_requisitos.nombre as requisito_nombre
				FROM	sga_propuestas_aspira
						JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
						JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
						JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
						JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
						LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos.requisito)
				WHERE	sga_alumnos.alumno = $alumno AND
						sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_pendiente."', '".propuesta::inscripcion_aceptada."') AND
						sga_requisitos.requisito_tipo = ".requisito::tipo_documentales." AND
						sga_requisitos_aspirante.restrictivo = '".comunes::si."' AND
						sga_requisitos_aspirante.exceptuado = '".comunes::no."' AND
						NOT f_requisito_presentado(sga_propuestas_aspira.persona, 
				                             sga_requisitos_aspirante.requisito, 
				                             $fecha, 
				                             sga_propuestas_aspira.propuesta_aspira, 
											 LEAST($fecha, GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos))))
		";
		return guarani_db::consultar($sql);
	}


	/**
	 * Retorna el conjunto de requisitos de ingreso de tipo proceso restrictivos para un alumno a una fecha dada.
	 * 
	 * @param integer $alumno
	 * @param date $fecha
	 * @return array
	 */
	function get_requisitos_ingreso_tipo_proceso_restrictivos($alumno, $fecha)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha = toba::db()->quote($fecha);
		// Requisitos de tipo proceso que no cumple el alumno
		$sql = "SELECT	sga_propuestas_aspira.persona,
						sga_propuestas_aspira.propuesta,
						sga_requisitos.regla,
						sga_requisitos_aspirante.parametros,
						sga_requisitos.requisito,
						sga_requisitos.nombre as requisito_nombre
				FROM	sga_propuestas_aspira
						JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
						JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
						JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
						JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
						LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos.requisito)
				WHERE	sga_alumnos.alumno = $alumno AND
						sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_pendiente."', '".propuesta::inscripcion_aceptada."') AND
						sga_requisitos.requisito_tipo = ".requisito::tipo_procesos." AND
						sga_requisitos_aspirante.restrictivo = '".comunes::si."' AND
						sga_requisitos_aspirante.exceptuado = '".comunes::no."' AND
						$fecha > GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos))
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la suma de créditos en mano por regularidades vigentes para un alumno a una fecha dada.
	 * Se excluyen los créditos de la actividad en particular a la que el alumno se inscribe.
	 *
	 * @param integer $alumno
	 * @param date $fecha
	 * @param integer $actividad
	 * @return boolean
	 */
	function get_creditos_regularidades_actividad($alumno, $fecha, $actividad)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha = toba::db()->quote($fecha);
		$actividad = toba::db()->quote($actividad);
		$sql = "SELECT	SUM(COALESCE(vw_regularidades_basica.creditos, 0)) AS creditos
				FROM	vw_regularidades_basica
				WHERE	vw_regularidades_basica.alumno = $alumno AND
						vw_regularidades_basica.origen_otra_propuesta = false AND
						vw_regularidades_basica.elemento <> $actividad AND
						vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
						(vw_regularidades_basica.fecha_vigencia IS NULL OR vw_regularidades_basica.fecha_vigencia >= $fecha) AND
						vw_regularidades_basica.elemento NOT IN (	SELECT	vw_hist_academica_basica.elemento
																	FROM	vw_hist_academica_basica
																	WHERE	vw_hist_academica_basica.alumno = $alumno AND
																			vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
																			vw_hist_academica_basica.origen_otra_propuesta = false)
		";
		$rs = guarani_db::consultar_fila($sql);
		return (is_null($rs['creditos']) ? 0 : $rs['creditos']);
	}

	/**
	 * Retorna la suma de créditos por inscripciones vigentes para un alumno a una fecha dada.
	 * Se excluyen los créditos de la actividad en particular a la que el alumno se inscribe.
	 *
	 * @param integer $alumno
	 * @param date $fecha
	 * @param integer $actividad
	 * @return boolean
	 */
	function get_creditos_inscripciones($alumno, $fecha, $actividad)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha = toba::db()->quote($fecha);
		$actividad = toba::db()->quote($actividad);
		$sql = "SELECT	SUM(COALESCE(sga_elementos_plan.creditos, 0)) as creditos
				FROM	vw_insc_cursada,
						sga_comisiones,
						sga_periodos_lectivos,
						sga_periodos,
						sga_elementos_revision,
						sga_elementos_plan
				WHERE	vw_insc_cursada.comision = sga_comisiones.comision AND
						sga_comisiones.elemento = sga_elementos_revision.elemento AND
						vw_insc_cursada.plan_version = sga_elementos_plan.plan_version AND
						sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision AND
						vw_insc_cursada.alumno = $alumno AND
						sga_comisiones.elemento <> $actividad AND
						vw_insc_cursada.estado = '".inscripcion_cursada::estado_aceptada."' AND

						-- Períodos que no finalizaron.
						sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
						sga_periodos_lectivos.periodo = sga_periodos.periodo AND
						sga_periodos.fecha_fin >= CURRENT_DATE AND

						-- Actividades no regularizadas.
						sga_comisiones.comision NOT IN (SELECT	vw_regularidades_basica.comision
														FROM	vw_regularidades_basica
														WHERE	vw_regularidades_basica.alumno = $alumno AND
																vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
																vw_regularidades_basica.origen_otra_propuesta = false) AND

						-- Actividades no aprobadas.
						sga_comisiones.elemento NOT IN (SELECT	vw_hist_academica_basica.elemento
														FROM	vw_hist_academica_basica
														WHERE	vw_hist_academica_basica.alumno = $alumno AND
																vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
																vw_hist_academica_basica.origen_otra_propuesta = false)
		";
		$rs = guarani_db::consultar_fila($sql);
		return (is_null($rs['creditos']) ? 0 : $rs['creditos']);
	}
	
	/**
	 * Determina si un alumno tiene legajo generado para una propuesta
	 *
	 * @param 	integer $alumno
	 * @return 	boolean
	 */
	function tiene_legajo_generado($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT	COUNT(1) AS cant
				FROM	sga_alumnos
				WHERE	sga_alumnos.alumno = $alumno
				AND 	sga_alumnos.legajo IS NOT NULL
				";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'] == 1;
	}
	
	/**
	 * Determina si un alumno realizó la reinscripción para el año académico y fecha dados como parámetro.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $anio_academico
	 * @param 	date 	$fecha
	 * @return 	boolean
	 */
	 
	function tiene_reinscripcion_anual($alumno, $anio_academico, $fecha)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		$fecha = toba::db()->quote($fecha);
		
		$sql = "SELECT  COUNT(*) AS cant
				FROM 	sga_reinscripciones
				WHERE	sga_reinscripciones.alumno = $alumno
				AND		sga_reinscripciones.anio_academico = $anio_academico
				AND		CAST(sga_reinscripciones.fecha_reinscripcion AS DATE) <= $fecha
		";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'] > 0;
	}

	// Obtiene el listado de aspirantes para un conjunto de propuestas utilizado en el proceso de inscripcion automatica de ingresantes
	function get_aspirantes($select='', $where=' 1 = 1 ', $orden=' vw_personas.apellido ')
	{
		$where .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."' ";
		$sql = "SELECT
                        vw_personas.apellido,
                        vw_personas.nombres,
                        sga_propuestas_aspira.propuesta,
                        sga_propuestas_aspira.modalidad,
                        sga_alumnos.alumno,
                        sga_alumnos.persona,
                        sga_alumnos.legajo,
                        sga_alumnos.plan_version,
                        sga_planes_versiones.plan,
                        mdp_datos_personales.turno_preferido,
                        vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						'('  || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_nombre,
                        u1.nombre AS ubicacion_alumno,
                        u2.nombre AS ubicacion_propuesta
                        $select
                 FROM sga_propuestas_aspira
                 JOIN sga_propuestas ON sga_propuestas.propuesta = sga_propuestas_aspira.propuesta
                 JOIN sga_alumnos ON sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta
                 JOIN vw_personas ON vw_personas.persona = sga_propuestas_aspira.persona
                 JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc
                 JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
                 JOIN sga_ubicaciones as u1 ON sga_alumnos.ubicacion = u1.ubicacion
                 JOIN sga_ubicaciones as u2 ON sga_propuestas_aspira.ubicacion = u2.ubicacion
                 LEFT JOIN mdp_datos_censales ON mdp_datos_censales.persona = sga_propuestas_aspira.persona AND
                        		                 mdp_datos_censales.fecha_relevamiento = (SELECT Max(fecha_relevamiento) FROM mdp_datos_censales WHERE persona = sga_propuestas_aspira.persona) 
												 
                 LEFT JOIN  mdp_datos_personales ON mdp_datos_personales.dato_censal = mdp_datos_censales.dato_censal 
				 
                 WHERE $where
                        
                 ORDER BY $orden
				";
		
		return guarani_db::consultar($sql);
	}	
	
	/**
	 * Determina si un alumno esta incluído en otra comisión de la actividad y período lectivo de la comisión dada.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @param 	integer	$comision
	 * @return 	boolean
	 */
	function tiene_inscripciones_actividad_periodo_lectivo($alumno, $actividad, $comision)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$comision = toba::db()->quote($comision);

		$sql = "-- Verifica si el alumno se encuentra en otras comisiones de la misma actividad y periodo lectivo.
				SELECT	sga_eval_detalle_cursadas.alumno
				FROM	sga_alumnos
						JOIN sga_alumnos as otro_alumno ON sga_alumnos.persona = otro_alumno.persona
						JOIN sga_comisiones ON sga_comisiones.comision = $comision
						JOIN sga_comisiones as otra_comision ON sga_comisiones.periodo_lectivo = otra_comision.periodo_lectivo
						JOIN sga_evaluaciones ON otra_comision.entidad = sga_evaluaciones.entidad
						JOIN sga_eval_detalle_cursadas ON (sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion AND otro_alumno.alumno = sga_eval_detalle_cursadas.alumno)
						LEFT JOIN sga_insc_cursada ON (sga_insc_cursada.alumno = sga_eval_detalle_cursadas.alumno AND sga_insc_cursada.comision = otra_comision.comision)
				WHERE	sga_alumnos.alumno = $alumno AND
						otra_comision.elemento = $actividad AND
						otra_comision.comision <> $comision AND
						(sga_insc_cursada.estado IS NULL OR NOT (sga_insc_cursada.tipo = '".inscripcion_cursada::tipo_preinscripcion."' AND sga_insc_cursada.estado_preinscripcion = '".inscripcion_cursada::preinsc_pendiente."'))
				UNION
				-- Verifica si el alumno se encuentra en actas de regular/promoción de otras comisiones para la misma actividad y período lectivo.
				-- Para el caso de actas de promocion, verifica solo resultado Promocionado (porque las No Promociones no se pueden invalidar)
				SELECT	sga_actas_detalle.alumno
				FROM	sga_alumnos
						JOIN sga_alumnos as otro_alumno ON sga_alumnos.persona = otro_alumno.persona
						JOIN sga_comisiones ON sga_comisiones.comision = $comision
						JOIN sga_comisiones as otra_comision ON sga_comisiones.periodo_lectivo = otra_comision.periodo_lectivo
						JOIN sga_actas ON otra_comision.comision = sga_actas.comision
						JOIN sga_actas_detalle ON (sga_actas.id_acta = sga_actas_detalle.id_acta AND otro_alumno.alumno = sga_actas_detalle.alumno)
				WHERE	sga_alumnos.alumno = $alumno AND
						otra_comision.elemento = $actividad AND
						otra_comision.comision <> $comision AND
						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND 
						(sga_actas.origen = '".acta::origen_regulares."' AND sga_actas_detalle.resultado IN ('".escalas_notas::resultado_aprobado."', '".escalas_notas::resultado_reprobado."') OR sga_actas.origen = '".acta::origen_promociones."' AND sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."')
		";
		$rs = guarani_db::consultar($sql, null, false);
		return !empty($rs);
	}
	
	/**
	 * Determina si un alumno, en cualquiera de las propuestas que cursa, esta incluído
	 * en otra mesa de examen de la actividad y llamado mesa dados como parámetro.
	 * No considero inscripciones a mesas de examen donde la instancia no esta considerada en la historia academica.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @param 	integer $llamado_mesa
	 * @return 	boolean
	 */
	function tiene_inscripciones_actividad_llamado($alumno, $actividad, $llamado_mesa)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		$sql = "-- Inscripciones en otras mesas de la misma actividad y llamado.
				SELECT	sga_eval_detalle_examenes.alumno
				FROM	sga_alumnos
						JOIN sga_alumnos as otro_alumno ON sga_alumnos.persona = otro_alumno.persona
						JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = $llamado_mesa
						JOIN sga_llamados_mesa as otro_llamado_mesa ON sga_llamados_mesa.llamado = otro_llamado_mesa.llamado
						JOIN sga_mesas_examen ON otro_llamado_mesa.mesa_examen = sga_mesas_examen.mesa_examen
						JOIN sga_evaluaciones ON otro_llamado_mesa.entidad = sga_evaluaciones.entidad
						JOIN sga_eval_detalle_examenes ON (sga_evaluaciones.evaluacion = sga_eval_detalle_examenes.evaluacion AND otro_alumno.alumno = sga_eval_detalle_examenes.alumno)
						JOIN sga_instancias ON sga_instancias.instancia = sga_eval_detalle_examenes.instancia
				WHERE	sga_alumnos.alumno = $alumno AND
						sga_mesas_examen.elemento = $actividad AND
						otro_llamado_mesa.llamado_mesa <> $llamado_mesa AND 
						sga_instancias.visible_hist_academica = 'S'
				UNION
				-- Actas de examen del mismo llamado pero para otras mesas de la misma actividad.
				SELECT	sga_actas_detalle.alumno
				FROM	sga_alumnos
						JOIN sga_alumnos as otro_alumno ON sga_alumnos.persona = otro_alumno.persona
						JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = $llamado_mesa
						JOIN sga_llamados_mesa as otro_llamado_mesa ON sga_llamados_mesa.llamado = otro_llamado_mesa.llamado
						JOIN sga_mesas_examen ON otro_llamado_mesa.mesa_examen = sga_mesas_examen.mesa_examen
						JOIN sga_actas ON otro_llamado_mesa.llamado_mesa = sga_actas.llamado_mesa
						JOIN sga_actas_detalle ON (sga_actas.id_acta = sga_actas_detalle.id_acta AND otro_alumno.alumno = sga_actas_detalle.alumno)
						JOIN sga_instancias ON sga_instancias.instancia = sga_actas_detalle.instancia
				WHERE	sga_alumnos.alumno = $alumno AND
						sga_mesas_examen.elemento = $actividad AND
						otro_llamado_mesa.llamado_mesa <> $llamado_mesa AND
						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND 
						sga_instancias.visible_hist_academica = 'S'
		";

		$rs = guarani_db::consultar($sql, null, false);
		return !empty($rs);
	}

	/**
	 * Si el alumno, en cualquiera de las propuestas que cursa, está incluido
	 * en otra mesa de examen de la misma actividad que no se encuentra en un acta 
	 * o que se encuentra en un acta de examen abierta.
	 * No considera inscripciones a examen de instancias que no son visibles en la historia academica (Ej reválidas de cursadas).
	 * Si lo encuentra retorna la fecha del examen, caso contrario retorna el valor nulo.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @param 	integer $llamado_mesa
	 * @return 	date
	 */
	function get_fecha_examen_alumno_actividad($alumno, $actividad, $llamado_mesa)
	{
		$alumno       = toba::db()->quote($alumno);
		$actividad    = toba::db()->quote($actividad);
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		$sql = "SELECT	to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') as fecha_formato_visual
				FROM sga_eval_detalle_examenes
				JOIN sga_alumnos ON sga_eval_detalle_examenes.alumno = sga_alumnos.alumno 
				JOIN sga_evaluaciones ON sga_eval_detalle_examenes.evaluacion = sga_evaluaciones.evaluacion
				JOIN sga_llamados_mesa ON sga_evaluaciones.entidad = sga_llamados_mesa.entidad
				JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN sga_instancias ON sga_instancias.instancia = sga_eval_detalle_examenes.instancia
				LEFT JOIN sga_actas ON sga_eval_detalle_examenes.id_acta = sga_actas.id_acta			
				WHERE sga_mesas_examen.elemento = $actividad 
				  AND sga_alumnos.persona = (SELECT	alumnos.persona	FROM sga_alumnos as alumnos	WHERE alumnos.alumno = $alumno) 
				  -- Se encuentra en otro llamado de la misma u otra mesa de examen.
				  AND ($llamado_mesa IS NULL OR 
				       ($llamado_mesa IS NOT NULL AND sga_llamados_mesa.llamado_mesa <> $llamado_mesa)
				      ) 
				-- Si el alumno está en un acta abierta esta no puede ser rectificativa.
				AND	(sga_eval_detalle_examenes.id_acta IS NULL OR   -- No esta en acta abierta
				     (sga_eval_detalle_examenes.id_acta IS NOT NULL AND 
					  sga_actas.estado = 'A' AND
					  sga_actas.tipo_acta <> '".acta::tipo_rectificativa."'
					  )
				    ) 
				AND sga_instancias.visible_hist_academica = 'S'	
				ORDER BY sga_llamados_mesa.fecha
				LIMIT	1
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['fecha_formato_visual'] ?? null);
	}

	/**
	 * Valida si una actividad determinada está dentro de la Propuesta - Plan de un alumno dado.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @return 	boolean
	 */
	function validar_actividad_en_propuesta_plan_alumno($alumno, $actividad)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		
		$sql = "SELECT	COUNT(*) AS cant
				FROM	sga_alumnos,
						vw_actividades_plan
				WHERE	sga_alumnos.alumno = $alumno
				AND		vw_actividades_plan.elemento = $actividad
				AND		vw_actividades_plan.propuesta = sga_alumnos.propuesta
				AND		vw_actividades_plan.plan_version = sga_alumnos.plan_version
				";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0) ? true : false;
	}
	
	/**
	 * Verifica si un alumno dado estuvo ausente en los exámenes para un llamado y una actividad.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $llamado
	 * @param 	integer	$actividad
	 * @return 	boolean
	 */
	function alumno_ausente_en_examenes_de_llamado($alumno, $llamado, $actividad=null)
	{		
		$alumno = toba::db()->quote($alumno);
		$llamado = toba::db()->quote($llamado);

		$filtro_actividad = '';
		if (! empty($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$filtro_actividad = " AND sga_mesas_examen.elemento = $actividad";
		}

		$sql = "SELECT	count(*) as cant

				FROM	sga_actas,
						sga_actas_detalle,
						sga_llamados_mesa,
						sga_mesas_examen

				WHERE 	sga_actas.id_acta = sga_actas_detalle.id_acta AND
						sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen AND

						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_ausente."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						sga_actas_detalle.alumno = $alumno AND
						sga_llamados_mesa.llamado = $llamado AND

						sga_actas.id_acta NOT IN (	SELECT 	sga_ausencias_examen.id_acta
													FROM 	sga_ausencias_examen
													WHERE 	sga_ausencias_examen.alumno = $alumno AND
															sga_ausencias_examen.estado = '".comunes::estado_aceptado_desc."')
						$filtro_actividad
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}
	
	/**
	 * Verifica si un alumno dado estuvo ausente en los exámenes para un turno de examen.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $turno_examen
	 * @return 	boolean
	 */
	function alumno_ausente_en_examenes_de_turno($alumno, $turno_examen)
	{
		$alumno = toba::db()->quote($alumno);
		$turno_examen = toba::db()->quote($turno_examen);

		$sql = "SELECT	count(*) as cant

				FROM	sga_actas,
						sga_actas_detalle,
						sga_llamados_mesa,
						sga_llamados_turno

				WHERE 	sga_actas.id_acta = sga_actas_detalle.id_acta AND
						sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND

						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_ausente."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						sga_actas_detalle.alumno = $alumno AND
						sga_llamados_turno.turno_examen = $turno_examen AND

						sga_actas.id_acta NOT IN (	SELECT 	sga_ausencias_examen.id_acta
													FROM 	sga_ausencias_examen
													WHERE 	sga_ausencias_examen.alumno = $alumno AND
															sga_ausencias_examen.estado = '".comunes::estado_aceptado_desc."')
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}
	
	function alumno_ausente_en_examenes_de_llamado_vinculado($alumno, $llamado, $actividad=null)
	{
		$alumno = toba::db()->quote($alumno);
		$llamado = toba::db()->quote($llamado);

		$filtro_actividad = '';
		if (! empty($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$filtro_actividad = " AND sga_mesas_examen.elemento = $actividad";
		}
		$sql = "SELECT	count(*) as cant
				FROM	sga_actas,
						sga_actas_detalle,
						sga_llamados_mesa,
						sga_llamados_turno_relacion,
						sga_mesas_examen
				WHERE 	sga_actas.id_acta = sga_actas_detalle.id_acta AND
						sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen AND
						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_ausente."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						sga_actas_detalle.alumno = $alumno AND						
						sga_llamados_mesa.llamado = sga_llamados_turno_relacion.llamado AND
						sga_llamados_turno_relacion.llamado_vinculado = (SELECT llamado FROM sga_llamados_mesa WHERE llamado_mesa = $llamado) AND
						sga_actas.id_acta NOT IN (	SELECT 	sga_ausencias_examen.id_acta
													FROM 	sga_ausencias_examen
													WHERE 	sga_ausencias_examen.alumno = $alumno AND
															sga_ausencias_examen.estado = '".comunes::estado_aceptado_desc."')
						$filtro_actividad
		";

		$rs = guarani_db::consultar_fila($sql);		
		return ($rs['cant'] > 0);
	}
	
	/**
	 * Verifica si un alumno dado fue reprobado en los exámenes para un llamado y una actividad.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $llamado
	 * @param 	integer	$actividad
	 * @return 	array
	 */
	function alumno_reprobado_en_examenes_de_llamado($alumno, $llamado, $actividad=null)
	{
		$alumno = toba::db()->quote($alumno);
		$llamado = toba::db()->quote($llamado);

		$filtro_actividad = '';
		if (! empty($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$filtro_actividad = " AND sga_mesas_examen.elemento = $actividad";
		}

		$sql = "SELECT	
						sga_mesas_examen.nombre as nombre
				FROM	sga_actas,
						sga_actas_detalle,
						sga_llamados_mesa,
						sga_mesas_examen

				WHERE 	sga_actas.id_acta = sga_actas_detalle.id_acta AND
						sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen AND

						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_reprobado."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						sga_actas_detalle.alumno = $alumno AND
						sga_llamados_mesa.llamado = $llamado
						$filtro_actividad
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Verifica si un alumno fue reprobado en los exámenes para un turno de examen.
	 *
	 * @param 	integer $alumno
	 * @param 	integer $turno_examen
	 * @return 	boolean
	 */
	function alumno_reprobado_en_examenes_de_turno($alumno, $turno_examen)
	{
		$alumno = toba::db()->quote($alumno);
		$turno_examen = toba::db()->quote($turno_examen);

		$sql = "SELECT
						sga_periodos.nombre as nombre
				FROM	sga_actas,
						sga_actas_detalle,
						sga_llamados_mesa,
						sga_llamados_turno,
						sga_turnos_examen,
						sga_periodos
				WHERE 	sga_actas.id_acta = sga_actas_detalle.id_acta AND
						sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
						sga_turnos_examen.periodo = sga_periodos.periodo AND
						sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen AND
						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_reprobado."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						sga_actas_detalle.alumno = $alumno AND
						sga_llamados_turno.turno_examen = $turno_examen
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Verifica si un alumno fue reprobado en alguno de los llamados vinculados
	 *
	 * @param 	integer $alumno
	 * @param 	integer $llamado
	 * @param 	integer	$actividad
	 * @return 	boolean
	 */
	
	function alumno_reprobado_en_examenes_de_llamado_vinculado($alumno, $llamado, $actividad=null)
	{
		$alumno = toba::db()->quote($alumno);
		$llamado = toba::db()->quote($llamado);

		$filtro_actividad = '';
		if (! empty($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$filtro_actividad = " AND sga_mesas_examen.elemento = $actividad";
		}
		$sql = "SELECT	
						sga_mesas_examen.nombre as nombre
				FROM	sga_actas,
						sga_actas_detalle,
						sga_llamados_mesa,
						sga_llamados_turno_relacion,
						sga_mesas_examen
				WHERE 	sga_actas.id_acta = sga_actas_detalle.id_acta AND
						sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen AND
						sga_actas.estado = '".acta::estado_cerrada."' AND
						sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
						sga_actas_detalle.resultado = '".escalas_notas::resultado_reprobado."' AND
						sga_actas_detalle.rectificado = '".comunes::no."' AND
						sga_actas_detalle.alumno = $alumno AND						
						sga_llamados_mesa.llamado = sga_llamados_turno_relacion.llamado AND
						sga_llamados_turno_relacion.llamado_vinculado = (SELECT llamado FROM sga_llamados_mesa WHERE llamado_mesa = $llamado)
						$filtro_actividad
		";

		return guarani_db::consultar_fila($sql);
	}
	
	

	/**
	 * Retrona el turno de cursada preferido para un alumno dado, teniendo en cuenta el último dato censal.
	 *
	 * @param integer $alumno
	 * @return integer
	 */
	function get_turno_preferido($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	mdp_datos_personales.turno_preferido
				FROM sga_alumnos
				JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
				LEFT JOIN mdp_datos_censales ON (mdp_personas.persona = mdp_datos_censales.persona AND
							mdp_datos_censales.fecha_relevamiento = (	SELECT	MAX(fecha_relevamiento) 
																		FROM	mdp_datos_censales 
				                										WHERE	persona = mdp_personas.persona))
				LEFT JOIN mdp_datos_personales ON mdp_datos_personales.dato_censal = mdp_datos_censales.dato_censal
				WHERE	sga_alumnos.alumno = $alumno 
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['turno_preferido'];
	}
	
	function get_cant_act_aprobadas_ctrl_regularidad($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		// Se hace el DISTINCT para el caso que se cuente actividades aprobadas en todas las propuestas del alumno 
		// Por si hay alguna actividad comun que este registrada como aprobada en 2 o mas propuestas del alumno.
		$sql = "SELECT	COUNT(DISTINCT vw_hist_academica_basica.elemento) AS cant
				FROM vw_hist_academica_basica
				JOIN sga_alumnos ON sga_alumnos.alumno = vw_hist_academica_basica.alumno
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
				WHERE vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."'
				$where
				";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	/**
	 * Retorna la regularidad vigente a una fecha para un alumno y una actividad dados. La regularidad mas reciente.
	 *
	 * @param integer $alumno
	 * @param integer $elemento
	 * @return array
	 */
	function get_regularidad_vigente_actividad($alumno, $elemento, $fecha=null)
	{
		$alumno = toba::db()->quote($alumno);
		$elemento = toba::db()->quote($elemento);
		$resultado_aprobado = toba::db()->quote(comunes::resultado_nota_aprobado);
		$fecha = is_null($fecha) ? 'CURRENT_DATE' : toba::db()->quote($fecha);
		
		$sql = "SELECT	vw_regularidades_basica.comision,
						vw_regularidades_basica.equivalencia_tramite,
						vw_regularidades_basica.origen
				FROM	vw_regularidades_basica
				WHERE	vw_regularidades_basica.alumno = $alumno
				  AND	vw_regularidades_basica.elemento = $elemento
				  AND	vw_regularidades_basica.resultado = $resultado_aprobado
				  AND	(vw_regularidades_basica.fecha_vigencia IS NULL OR vw_regularidades_basica.fecha_vigencia >= $fecha)
				ORDER BY vw_regularidades_basica.fecha DESC
				LIMIT 1
				";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Recupera el id de acta de cursada o equivalencia de regularidad interna de la ultima regularidad aprobada y vencida con fecha de vencimiento menor a la fecha
	   pasada por parametro. Si no existe retorna null.
	   Utilizada en el cierre de actas de examen de reválidas de cursadas y requisito de regularidad vencida.
	 * @param integer $alumno       # Alumno
	 * @param integer $elemento     # Actividad 
	 * @param date $fecha           # Se buscan regularidades con fecha anterior a esta fecha
	 * @param boolean $solo_cursada # Indica si solo se buscan cursadas o cursadas y equivalencias de regularidad internas. Por defecto se busca en los dos.
	 * @return array
	 */
	function get_id_ultima_regularidad_vencida($alumno, $elemento, $fecha, $solo_cursada = false)
	{
		$alumno   = toba::db()->quote($alumno);
		$elemento = toba::db()->quote($elemento);
		$fecha    = toba::db()->quote($fecha);
		
		$where_origen = '';
		if ($solo_cursada) {
			// Solo actas de regularidades.
			$where_origen = " AND vw_regularidades_basica.origen = 'R' ";
		}
			
		// Obtiene ultima acta de cursada o equivalencia de regularidad interna aprobada y vencida.
		// La cursada o equiv. de regularidad puede ser en la misma propuesta u otra propuesta del alumno.
		// origen: R = Acta de Cursada / C = Equivalencia de Regularidad
		// En "alumno2" esta el id de alumno por el cual tiene registrada la regularidad.
		$sql = "SELECT vw_regularidades_basica.alumno2, 
					   vw_regularidades_basica.id_acta, 
					   vw_regularidades_basica.fecha_vigencia, 
					   vw_regularidades_basica.equivalencia, 
					   vw_regularidades_basica.origen
				  FROM vw_regularidades_basica
				  LEFT JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = vw_regularidades_basica.equivalencia_tramite
				  LEFT JOIN sga_equiv_tramite_origen ON sga_equiv_tramite_origen.origen = sga_equiv_tramite.origen 
				 WHERE vw_regularidades_basica.alumno   = $alumno
				   AND vw_regularidades_basica.elemento = $elemento
				   $where_origen
				   AND vw_regularidades_basica.fecha     < $fecha 
				   AND vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."'
				   AND vw_regularidades_basica.fecha_vigencia IS NOT NULL 
				   AND vw_regularidades_basica.fecha_vigencia < $fecha
				   AND COALESCE(sga_equiv_tramite_origen.tipo,'Interna') = 'Interna'  -- Solo equivalencia interna
				ORDER BY vw_regularidades_basica.fecha_vigencia DESC
				LIMIT 1
		";
		$resultado = guarani_db::consultar_fila($sql);
		
		// Retorna ultima acta de cursada o equivalencia de regularidad aprobada y vencida, fecha de fin de vigencia o array vacio si no encontro datos
		return $resultado;
	}

	
	//------------------------------------------------------------------------
	//-- Cambios de Plan/Plan-Versión
	//------------------------------------------------------------------------

	function get_tipos_cambio_plan()
	{
		return array(	array('valor' => alumno::tipo_cambio_solicitado, 'descr' => 'Solicitado'),
						array('valor' => alumno::tipo_cambio_forzado, 'descr' => 'Forzado'));
	}
	
	function get_destinos_cambio_plan($motivo_plan)
	{
		if ($motivo_plan == alumno::motivo_plan_cambio) {
			return array(	array('valor' => alumno::cambio_plan_destino_activos_vigentes, 'descr' => 'Planes activos vigentes'),
							array('valor' => alumno::cambio_plan_destino_activos_no_vigentes, 'descr' => 'Planes activos no vigentes'),
							array('valor' => alumno::cambio_plan_destino_anteriores, 'descr' => 'Planes anteriores del alumno'));
		} elseif ($motivo_plan == alumno::motivo_plan_cambio_version) {
			return array(	array('valor' => alumno::cambio_plan_version_destino_activas_vigentes, 'descr' => 'Versiones activas vigentes'),
							array('valor' => alumno::cambio_plan_version_destino_activas_no_vigentes, 'descr' => 'Versiones activas no vigentes'),
							array('valor' => alumno::cambio_plan_version_destino_anteriores, 'descr' => 'Versiones anteriores del alumno'));
		}
	}

	/**
	 * Retorna el listado de alumnos activos vigentes mostrando solo una vez a la persona.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_alumnos_una_persona($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT  DISTINCT  	vw_alumnos.persona,
									vw_personas.apellido,
									vw_personas.nombres,
									vw_alumnos.apellido_nombres as apellido_y_nombre,
									vw_alumnos.identificacion  as documento_principal
				FROM        vw_alumnos
				JOIN 		vw_personas ON vw_alumnos.persona = vw_personas.persona
				WHERE 		vw_alumnos.calidad = '".alumno::calidad_activo."'
				$where
				ORDER BY 	vw_alumnos.persona,
							vw_alumnos.apellido_nombres
							";
		return guarani_db::consultar($sql);
	}


	/**
	 * Retorna el listado de planes activos vigentes en los cuales NO estuvo un alumno dado (junto a su última versión activa).
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_planes_activos_vigentes($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_planes_versiones.plan_version,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.version as plan_version_codigo,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes_versiones.estado,
						sga_planes_estados.descripcion as plan_version_estado
				FROM	sga_alumnos
				JOIN	sga_planes ON sga_planes.propuesta = sga_alumnos.propuesta
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_planes.version_actual
				JOIN	sga_planes_estados ON sga_planes_estados.estado = sga_planes_versiones.estado
				WHERE	sga_alumnos.alumno = $alumno	
				  AND	sga_planes.estado = '".planes::estado_activo_vigente."'
				  -- Se excluyen los planes anteriores del alumno.
				  AND	sga_planes.plan NOT IN (SELECT	sga_planes_versiones.plan
												FROM	sga_alumnos_hist_planes
												JOIN	sga_planes_versiones ON sga_alumnos_hist_planes.plan_version = sga_planes_versiones.plan_version
												WHERE	sga_alumnos_hist_planes.alumno = $alumno)
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de planes activos no vigentes en los cuales NO estuvo un alumno dado (junto a su última versión activa).
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_planes_activos_no_vigentes($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_planes_versiones.plan_version,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.version as plan_version_codigo,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes_versiones.estado,
						sga_planes_estados.descripcion as plan_version_estado
				FROM	sga_alumnos 
				JOIN	sga_planes ON sga_planes.propuesta = sga_alumnos.propuesta
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_planes.version_actual
				JOIN	sga_planes_estados ON sga_planes_estados.estado = sga_planes_versiones.estado
				WHERE	sga_alumnos.alumno = $alumno 
				  AND	sga_planes.estado = '".planes::estado_activo_no_vigente."'
				  -- Se excluyen los planes anteriores del alumno.
				  AND	sga_planes.plan NOT IN (SELECT	sga_planes_versiones.plan
												FROM	sga_alumnos_hist_planes
												JOIN	sga_planes_versiones ON sga_alumnos_hist_planes.plan_version = sga_planes_versiones.plan_version
												WHERE	sga_alumnos_hist_planes.alumno = $alumno)
		";
		return guarani_db::consultar($sql);
	}


	/**
	 * Retorna el listado de planes en los cuales estuvo un alumno dado (junto a su última versión activa). No se listan planes dados de baja.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_planes_anteriores_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_planes_versiones.plan_version,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.version as plan_version_codigo,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes_versiones.estado,
						sga_planes_estados.descripcion as plan_version_estado
				FROM	sga_alumnos_hist_planes
				JOIN	sga_alumnos ON sga_alumnos.alumno = sga_alumnos_hist_planes.alumno
				JOIN	sga_planes_versiones as hist_planes_versiones ON hist_planes_versiones.plan_version = sga_alumnos_hist_planes.plan_version
				JOIN	sga_planes ON hist_planes_versiones.plan = sga_planes.plan
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_planes.version_actual
				JOIN	sga_planes_estados ON sga_planes_estados.estado = sga_planes_versiones.estado
				WHERE	sga_alumnos_hist_planes.alumno = $alumno 
				  AND	sga_planes.estado <> '".planes::estado_baja."'
				  AND	sga_planes_versiones.estado <> '".planes::estado_baja."'
				  -- Se excluye el plan actual del alumno.
				  AND	sga_planes_versiones.plan_version <> sga_alumnos.plan_version
				GROUP BY	sga_planes_versiones.plan_version,
							sga_planes.codigo,
							sga_planes_versiones.version,
							sga_planes_versiones.fecha_entrada_vigencia,
							sga_planes_versiones.estado,
							sga_planes_estados.descripcion
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de planes-versiones activas vigentes en las cuales NO estuvo un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_versiones_activas_vigentes($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_planes_versiones.plan_version,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.version as plan_version_codigo,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes_versiones.estado,
						sga_planes_estados.descripcion as plan_version_estado
				FROM	sga_alumnos
				JOIN	sga_planes_versiones as plan_version_alumno ON sga_alumnos.plan_version = plan_version_alumno.plan_version
				JOIN	sga_planes ON plan_version_alumno.plan = sga_planes.plan
				JOIN	sga_planes_versiones ON sga_planes.plan = sga_planes_versiones.plan
				JOIN	sga_planes_estados ON sga_planes_versiones.estado = sga_planes_estados.estado
				WHERE	sga_alumnos.alumno = $alumno 
				  AND	sga_planes_versiones.estado = '".planes::estado_activo_vigente."' 
				  -- Se excluyen las versiones anteriores del alumno.
				  AND	sga_planes_versiones.plan_version NOT IN (SELECT	sga_alumnos_hist_planes.plan_version
																	FROM	sga_alumnos_hist_planes
																	WHERE	sga_alumnos_hist_planes.alumno = $alumno)
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de versiones de planes activos NO vigentes en las cuales NO estuvo un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_versiones_activas_no_vigentes($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_planes_versiones.plan_version,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.version as plan_version_codigo,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes_versiones.estado,
						sga_planes_estados.descripcion as plan_version_estado
				FROM sga_alumnos
				JOIN sga_planes_versiones as plan_version_alumno ON sga_alumnos.plan_version = plan_version_alumno.plan_version
				JOIN sga_planes ON plan_version_alumno.plan = sga_planes.plan
				JOIN sga_planes_versiones ON sga_planes.plan = sga_planes_versiones.plan
				JOIN sga_planes_estados ON sga_planes_versiones.estado = sga_planes_estados.estado
				WHERE sga_alumnos.alumno = $alumno
				  AND sga_planes_versiones.estado = '".planes::estado_activo_no_vigente."' 
				  -- Se excluyen las versiones anteriores del alumno.
				  AND sga_planes_versiones.plan_version NOT IN (SELECT	sga_alumnos_hist_planes.plan_version
																FROM	sga_alumnos_hist_planes
																WHERE	sga_alumnos_hist_planes.alumno = $alumno)
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de planes-versiones en los cuales estuvo un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_versiones_anteriores_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	sga_planes_versiones.plan_version,
						sga_planes.codigo as plan_codigo,
						sga_planes_versiones.version as plan_version_codigo,
						sga_planes_versiones.fecha_entrada_vigencia,
						sga_planes_versiones.estado,
						sga_planes_estados.descripcion as plan_version_estado
				FROM	sga_alumnos_hist_planes 
				JOIN	sga_alumnos ON sga_alumnos_hist_planes.alumno = sga_alumnos.alumno
				JOIN	sga_planes_versiones ON sga_alumnos_hist_planes.plan_version = sga_planes_versiones.plan_version
				JOIN	sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN	sga_planes_estados ON sga_planes_estados.estado = sga_planes_versiones.estado
				WHERE	sga_alumnos_hist_planes.alumno = $alumno
				  AND	sga_planes.estado <> '".planes::estado_baja."'
				  AND	sga_planes_versiones.estado <> '".planes::estado_baja."'
				  -- Se excluye el plan actual del alumno.
				  AND	sga_planes_versiones.plan_version <> sga_alumnos.plan_version
				GROUP BY	sga_planes_versiones.plan_version,
							sga_planes.codigo,
							sga_planes_versiones.version,
							sga_planes_versiones.fecha_entrada_vigencia,
							sga_planes_versiones.estado,
							sga_planes_estados.descripcion
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado histórico de cambios de plan/plan-versión para un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_listado_historico_cambio_planes($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT 		'(' || sga_planes.codigo || ') ' || sga_planes.nombre AS plan_descr,        
							'(' || sga_planes_versiones.version || ') ' || sga_planes_versiones.nombre AS plan_version_descr,
							sga_alumnos_hist_planes.fecha,
							sga_alumnos_motivos_plan.nombre AS motivo_nombre,        
							sga_documentos.documento_numero
				FROM 		sga_alumnos_hist_planes
							LEFT JOIN sga_documentos ON
				            	sga_documentos.documento = sga_alumnos_hist_planes.documento,
							sga_alumnos_motivos_plan,
				            sga_planes_versiones,
				            sga_planes
				WHERE		sga_alumnos_hist_planes.alumno = $alumno
							AND sga_alumnos_motivos_plan.motivo_plan = sga_alumnos_hist_planes.motivo_plan
				            AND sga_planes_versiones.plan_version = sga_alumnos_hist_planes.plan_version
				            AND sga_planes.plan = sga_planes_versiones.plan
				ORDER BY 	sga_alumnos_hist_planes.fecha DESC
        		";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado histórico de cambios de plan/plan-versión para un alumno dado.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_listado_historico_cambio_planes_propuestas_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 		'(' || sga_planes.codigo || ') ' || sga_planes.nombre AS plan_descr,        
							'(' || sga_planes_versiones.version || ') ' || sga_planes_versiones.nombre AS plan_version_descr,
							sga_propuestas.propuesta,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_descr,
							sga_alumnos_hist_planes.fecha,
							sga_alumnos_motivos_plan.nombre AS motivo_nombre,        
							sga_documentos.documento_numero
				FROM 		sga_alumnos_hist_planes
							LEFT JOIN sga_documentos ON
				            	sga_documentos.documento = sga_alumnos_hist_planes.documento,
							sga_alumnos,
							sga_alumnos_motivos_plan,
				            sga_planes_versiones,
				            sga_planes, 
							sga_propuestas
				WHERE		sga_alumnos_hist_planes.alumno = sga_alumnos.alumno 
							AND sga_alumnos.persona = $persona
							AND sga_alumnos_motivos_plan.motivo_plan = sga_alumnos_hist_planes.motivo_plan
				            AND sga_planes_versiones.plan_version = sga_alumnos_hist_planes.plan_version
				            AND sga_planes.plan = sga_planes_versiones.plan
							AND sga_planes.propuesta = sga_propuestas.propuesta
				ORDER BY 	sga_alumnos_hist_planes.fecha DESC
        		";
		
		return guarani_db::consultar($sql);
	}
	
	function get_cant_readmisiones($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT 	sga_alumnos.cnt_readmisiones
				FROM 	sga_alumnos
				WHERE	sga_alumnos.alumno = $alumno
        		";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cnt_readmisiones'];
	}
	
	function get_cant_perdidas_regularidad($alumno, $anio_academico)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		
		$sql = "SELECT 	COUNT(*) AS cantidad_perdidas
				FROM 	sga_perdida_regularidad
				WHERE	sga_perdida_regularidad.alumno = $alumno
				AND		sga_perdida_regularidad.anio_academico = $anio_academico
        		";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cantidad_perdidas'];
	}
	
	function get_alumnos_planes_versiones_ubicaciones($propuestas, $ubicaciones, $anio_academico, $mat_regularidad_alumno, $descartar_ingresantes, $where_alumnos_a_verificar)
	{
		$cond_in_propuestas = guarani_sql::armar_condicion_in($propuestas, 'sga_alumnos', 'propuesta', 'propuesta');
		$cond_in_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'sga_alumnos', 'ubicacion', 'ubicacion');
		$anio_academico = toba::db()->quote($anio_academico);
		if ($descartar_ingresantes) {
			$where_anio = "sga_propuestas_aspira.anio_academico < $anio_academico";
		} else {
			$where_anio = "sga_propuestas_aspira.anio_academico <= $anio_academico";
		}
		$distinct = ($mat_regularidad_alumno == 'Persona') ? 'DISTINCT' : '';

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
						$where_alumnos_a_verificar
		";

		return guarani_db::consultar($sql);
	}

	function get_alumnos_con_readmisiones_vencidas()
	{
		$sql = "SELECT	sga_alumnos.persona,
						sga_alumnos.alumno,
						sga_alumnos.regular,
						sga_alumnos.propuesta,
						sga_alumnos.ubicacion,
						sga_perdida_regularidad.fecha_control_desde,
						sga_perdida_regularidad.anio_academico,
						sga_readmisiones.readmision,
						sga_readmisiones.fecha_vencimiento as fecha_vencimiento_readmision
				FROM 	sga_alumnos
						JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
						JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
						JOIN sga_perdida_regularidad ON sga_alumnos.alumno = sga_perdida_regularidad.alumno
						JOIN sga_readmisiones ON sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
				WHERE	-- Alumnos activos y regulares.
						sga_alumnos.calidad = '".alumno::calidad_activo."' AND
						sga_alumnos.regular = '".comunes::si."' AND
						-- Alumnos de propuestas en las que se controla regularidad a los alumnos.
						sga_propuestas_tipos.control_regularidad = '".comunes::si."' AND
						-- Última pérdida de regularidad para el alumno.
						sga_perdida_regularidad.fecha = (	SELECT	MAX(perdida.fecha)
															FROM	sga_perdida_regularidad AS perdida
															WHERE	perdida.alumno = sga_alumnos.alumno) AND
						-- Alumnos con una readmisión vencida y en estado activo.
						sga_readmisiones.fecha_vencimiento < CURRENT_DATE AND
						sga_readmisiones.estado = '".comunes::estado_activo."' AND
						-- Última readmisión para la pérdida de regularidad.
						sga_readmisiones.fecha = (	SELECT	MAX(readmisiones.fecha)
													FROM	sga_readmisiones AS readmisiones
                 									WHERE	readmisiones.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad)
        ";
		return guarani_db::consultar($sql);
	}

	function get_readmision_vigente($alumno, $anio_academico)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		
		$sql = "SELECT	sga_readmisiones.readmision
				FROM	sga_perdida_regularidad,
						sga_readmisiones
				WHERE	sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
						AND	sga_perdida_regularidad.alumno = $alumno
						AND	sga_readmisiones.anio_academico = $anio_academico
						AND	CURRENT_DATE <= sga_readmisiones.fecha_vencimiento 
        		";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Retorna la readmisión de mayor fecha de vencimiento correspondiente al alumno y año académico dados.
	 * @param int $alumno Alumno para el cual se busca la última readmisión
	 * @param int $anio_academico Año académico a evaluar 
	 * @return array
	 */
	function get_ultima_readmision($alumno, $anio_academico)
	{
		$alumno = toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
		$sql = "SELECT		sga_readmisiones.readmision,
							sga_readmisiones.fecha,
							sga_readmisiones.fecha_vencimiento,
							sga_perdida_regularidad.fecha_control_desde
				FROM		sga_perdida_regularidad
							JOIN sga_readmisiones ON sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
				WHERE		sga_perdida_regularidad.alumno = $alumno AND
							sga_readmisiones.anio_academico = $anio_academico
				ORDER BY	sga_readmisiones.fecha_vencimiento
				LIMIT		1
        ";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Retorna los alumnos que no estan relacionados a ninguna división, según el filtro recibido.
	 * Además, cada alumno se retorna con el turno de cursada preferido teniendo en cuenta el último dato censal.
	 */
	function get_alumnos_no_asignados_a_division($where='', $where_turno_preferido='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$sql = "SELECT 		sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.division,
							vw_personas.persona,
							sga_propuestas.nombre			AS propuesta_nombre,
							vw_personas.apellido_nombres 	AS apellido_y_nombre,
							vw_personas.tipo_nro_documento 	AS documento_principal,
							(	SELECT	sga_turnos_cursadas.nombre
								FROM	sga_alumnos AS alumnos,
								 		sga_turnos_cursadas,
										mdp_personas LEFT JOIN mdp_datos_censales ON (
											mdp_personas.persona = mdp_datos_censales.persona AND
											mdp_datos_censales.fecha_relevamiento = (	SELECT	MAX(fecha_relevamiento)
																						FROM	mdp_datos_censales
																						WHERE	persona = mdp_personas.persona))
												 	LEFT JOIN mdp_datos_personales ON
											mdp_datos_personales.dato_censal = mdp_datos_censales.dato_censal
								WHERE	alumnos.alumno = sga_alumnos.alumno
								AND		alumnos.persona = mdp_personas.persona
								AND		mdp_datos_personales.turno_preferido = sga_turnos_cursadas.turno) AS turno_preferido
								
				FROM 		sga_alumnos,
							vw_personas,
							sga_propuestas,
							sga_propuestas_aspira,
							sga_situacion_aspirante
				
				WHERE  		sga_alumnos.persona = vw_personas.persona
				AND			sga_alumnos.propuesta = sga_propuestas.propuesta
				AND			sga_alumnos.persona = sga_propuestas_aspira.persona
				AND			sga_alumnos.propuesta = sga_propuestas_aspira.propuesta
				AND			sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				AND			sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_pendiente."', '".propuesta::inscripcion_aceptada."')
				AND 		sga_alumnos.calidad = '".alumno::calidad_activo."'
				AND			sga_alumnos.division IS NULL
							$where
		
				ORDER BY	vw_personas.apellido, 
							vw_personas.nombres,
							sga_propuestas.nombre
				";
		
		if ($where_turno_preferido) {
			$sql = "SELECT	*
					FROM	($sql) AS alumnos
					WHERE	$where_turno_preferido
					";
		}
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los alumnos relacionados a divisiones, según el filtro recibido.
	 */
	function get_reporte_alumnos_relacionados_a_divisiones($where='', $where_turno_preferido='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$sql = "SELECT 		sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.division,
							vw_personas.persona,
							sga_propuestas_aspira.anio_academico,
							sga_propuestas_aspira.anio_academico as anio_academico_desc,
							sga_propuestas.nombre			AS propuesta_nombre,
							vw_personas.apellido_nombres 	AS apellido_y_nombre,
							vw_personas.tipo_nro_documento 	AS documento_principal,
							sga_turnos_cursadas.turno,
							CASE 
							   WHEN sga_alumnos.division IS NULL THEN 'Sin división asociada'
							   ELSE 'División: ' || sga_divisiones.nombre || ' - ' || 'Turno: ' || sga_turnos_cursadas.nombre
							END AS division_turno_nombres,
							CASE 
							   WHEN sga_alumnos.division IS NULL THEN 'Sin división asociada'
							   ELSE 'División: ' || sga_divisiones.nombre || ' - ' || 'Turno: ' || sga_turnos_cursadas.nombre
							END AS division_turno_nombres_desc,
							(SELECT sga_turnos_cursadas.nombre
							   FROM mdp_datos_censales 
							   JOIN mdp_datos_personales ON mdp_datos_personales.dato_censal = mdp_datos_censales.dato_censal
							   JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = mdp_datos_personales.turno_preferido
							   WHERE mdp_datos_censales.persona = sga_alumnos.persona
							) AS turno_preferido
								
				FROM sga_alumnos 
				JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_propuestas_aspira ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta =  sga_alumnos.propuesta
				JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				LEFT JOIN sga_divisiones ON sga_alumnos.division = sga_divisiones.division
				LEFT JOIN sga_turnos_cursadas ON sga_divisiones.turno = sga_turnos_cursadas.turno
				
				WHERE  	sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_pendiente."', '".propuesta::inscripcion_aceptada."')
				AND 	sga_alumnos.calidad = '".alumno::calidad_activo."'
				$where
		
				ORDER BY	sga_propuestas_aspira.anio_academico,
							division_turno_nombres,
							vw_personas.apellido, 
							vw_personas.nombres,
							sga_propuestas.nombre
				";
		
		if ($where_turno_preferido) {
			$sql = "SELECT	*
					FROM	($sql) AS alumnos
					WHERE	$where_turno_preferido
					";
		}
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si una actividad de la propuesta de un alumno se encuentra en alguna otra propuesta asociada al mismo, de tipo no voacacional
	 *
	 * @param 	integer $alumno
	 * @param 	integer $actividad
	 * @param 	integer	$persona
	 * @return 	boolean
	 */
	function tiene_actividad_en_propuesta_novocacional($alumno, $persona, $actividad)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$persona = toba::db()->quote($persona);

		$sql = "SELECT	COUNT(1) AS cant
				FROM
						sga_alumnos,
						sga_propuestas,
						vw_actividades_plan
				WHERE	
						sga_alumnos.persona = $persona 
						AND sga_alumnos.alumno <> $alumno  
						AND sga_alumnos.calidad = 'A'       
						AND sga_propuestas.propuesta = sga_alumnos.propuesta
						AND sga_propuestas.propuesta_tipo <> " . g3entidad::subtipo_vocacional . "
						AND vw_actividades_plan.plan_version = sga_alumnos.plan_version
						AND vw_actividades_plan.elemento = $actividad;
		";

		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	function get_plan_version_anterior_alumno($alumno, $descr)
	{
		$alumno = toba::db()->quote($alumno);
		$descr = toba::db()->quote($descr);

		$sql = "SELECT * FROM get_plan_version_anterior_alumno($alumno, $descr);";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna la fecha de última actividad aprobada del plan de estudios del alumno.
	 * Tambien recupera fecha de modulos a los que se le reconocen créditos por resolución. 
	 *  
	 * @param integer $alumno
	 * @param date $fecha
	 * @param integer $certificado
	 * @param string $formato
	 * @return date
	 */
	function get_fecha_egreso($alumno, $fecha=null, $certificado=null, $formato=null)
	{
		if (is_null($fecha)) {
			$fecha = guarani_fecha::get_hoy();
		}
		$plan_version_alumno = $this->get_plan_version($alumno, $fecha);

		$alumno = toba::db()->quote($alumno);
		$fecha = toba::db()->quote($fecha);

		$where = '';
		// Fecha de egreso en las actividades relacionadas con un certificado. 
		// Tambien recupero modulos, por si tiene actividades reconocidas por resolucion que suman creditos al modulo.
		
		if (! is_null($certificado)) {
			$where_actividades = " AND vw_hist_academica_basica.elemento IN (SELECT * FROM get_actividades_certificado_alumno($alumno, $certificado, $plan_version_alumno, true, false))";
			$where_modulos_creditos = " AND sga_reconocimiento_act.elemento IN (SELECT * FROM get_actividades_certificado_alumno($alumno, $certificado, $plan_version_alumno, true, true))";
		}

		$select_principal = is_null($formato) ? 'MAX(fecha_egreso) as fecha_egreso' : 'to_char(MAX(fecha_egreso), '.toba::db()->quote($formato).') as fecha_egreso';
		$select_actividades = 'MAX(vw_hist_academica_basica.fecha) as fecha_egreso';
		$select_modulos_creditos = 'MAX(sga_reconocimiento.fecha) as fecha_egreso';

		$sql = "SELECT $select_principal
				FROM 
		        (-- Actividades
				SELECT	$select_actividades
				FROM	vw_hist_academica_basica
				WHERE	vw_hist_academica_basica.alumno = $alumno AND
						vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
						vw_hist_academica_basica.fecha <= $fecha AND
						vw_hist_academica_basica.elemento IN (	SELECT	vw_elementos_plan.elemento
																FROM	vw_elementos_plan,
																		sga_elementos_plan
																WHERE	vw_elementos_plan.elemento_plan = sga_elementos_plan.elemento_plan AND
																		vw_elementos_plan.plan_version = $plan_version_alumno) 
						-- Si hay actividades excluidas de la H.A. para el certificado, no se las considera para la fecha de egreso
						AND vw_hist_academica_basica.elemento NOT IN (
							SELECT 	sga_certificados_ha_no_elementos.elemento
							FROM 	sga_certificados_ha_no_elementos, 
									sga_certificados_configurar_ha,
									sga_certificados_otorg
							WHERE 	sga_certificados_ha_no_elementos.certificado_ha = sga_certificados_configurar_ha.certificado_ha
									AND sga_certificados_otorg.nro_solicitud = sga_certificados_configurar_ha.nro_solicitud
									AND sga_certificados_otorg.certificado = $certificado
									AND sga_certificados_otorg.alumno = $alumno
						)	
						$where_actividades
				
				UNION ALL
				
				-- Módulos a los que se le reconocen créditos.
				-- Actividades extracurriculares que reconocen creditos a modulos del plan. Reconocidos por resolucion
				SELECT $select_modulos_creditos
				  FROM sga_reconocimiento 
				  JOIN sga_reconocimiento_act ON sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite
				  JOIN sga_elementos ON sga_elementos.elemento = sga_reconocimiento_act.elemento
				  JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo				  
				  --JOIN sga_reconocimiento_doc ON sga_reconocimiento_doc.nro_tramite = sga_reconocimiento.nro_tramite
				 WHERE sga_reconocimiento.alumno = $alumno 
				   AND sga_reconocimiento.estado = 'C' 
				   AND sga_reconocimiento_act.rectificado = 'N'
				   AND sga_reconocimiento_act.estado = 'A'
				   AND sga_reconocimiento_act.creditos > 0	-- Se reconocen creditos al módulo. 
				   AND sga_g3entidades_subtipos.entidad_tipo = 1 -- Modulo				   
				   -- AND sga_elementos.entidad_subtipo = 6	-- Modulo de actividades extracurriculares (comentado porque podrian tener modulos genericos de subtipo 1)
				   AND sga_reconocimiento.fecha <= $fecha 
				   -- AND sga_reconocimiento_doc.creditos IS NOT NULL	-- Se reconocen creditos de actividades extracurriculares
				   $where_modulos_creditos
				) as temp		
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['fecha_egreso'];
	}

	/**
	 * Retorna la duración (en meses) del rango de fechas comprendido entre
	 * la fecha de ingreso a la propuesta para un alumno dado y la fecha de egreso recibida como parámetro.
	 * 
	 * @param int $alumno
	 * @param date $fecha_egreso
	 * @return int
	 */
	function get_duracion_real_en_meses($alumno, $fecha_egreso)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha_egreso = toba::db()->quote($fecha_egreso);
		$sql = "SELECT extract(year FROM age(TIMESTAMP $fecha_egreso, get_fecha_ingreso_alumno($alumno)::timestamp))*12 + extract(month FROM age(TIMESTAMP $fecha_egreso, get_fecha_ingreso_alumno($alumno)::timestamp)) as duracion_real_en_meses";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['duracion_real_en_meses'];
	}

	/**
	 * Retorna el promedio de las actividades realizadas por el alumno en la propuesta/certificado.
	 * 
	 * @param integer $alumno
	 * @param date $fecha
	 * @param integer $certificado
	 * @param char $con_aplazos
	 * @return decimal
	 */
	function get_promedio($alumno, $fecha_hasta=null, $certificado=null, $con_aplazos=comunes::si, $fecha_desde=null)
	{
		$alumno = toba::db()->quote($alumno);
		$fecha_hasta = is_null($fecha_hasta) ? 'null' : toba::db()->quote($fecha_hasta);
		$certificado = is_null($certificado) ? 'null' : toba::db()->quote($certificado);
		$con_aplazos = toba::db()->quote($con_aplazos);
		$fecha_desde = is_null($fecha_desde) ? 'null' : toba::db()->quote($fecha_desde);
		$sql = "SELECT f_promedio($alumno, $fecha_hasta, $certificado, $con_aplazos, $fecha_desde) as promedio";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['promedio'];
	}

	/**
	 * Retorna los datos de la inscripción a propuesta para una persona.
	 * 
	 * @param integer $persona
	 * @param integer $propuesta
	 * @return array
	 */
	function get_datos_inscripcion($persona, $propuesta)
	{
		$persona = toba::db()->quote($persona);
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT	sga_propuestas_aspira.anio_academico,
						sga_propuestas_aspira.plan_version
				FROM	sga_propuestas_aspira
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				WHERE	sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."') AND
						sga_propuestas_aspira.persona = $persona AND
						sga_propuestas_aspira.propuesta = $propuesta
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna los datos de pérdidas de regularidad en las propuestas y readmisiones de una persona
	 * Usado en la ficha del alumno
	 * @param integer $persona
	 * @return array
	 */
	function get_perdidas_regularidad($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 
					-- Corte de control
					sga_alumnos.propuesta,
					'(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre as propuesta_nombre,
					-- Pérdidas de regularidad
					sga_perdida_regularidad.anio_academico AS per_reg_anio_academico,
					sga_perdida_regularidad.fecha_control_desde as per_reg_fecha_control_desde,
					sga_perdida_regularidad.fecha_control_hasta as per_reg_fecha_control_hasta,
					sga_perdida_regularidad.fecha AS per_reg_fecha,
					documentos_per_reg.documento_numero AS per_reg_documento,
					-- Readmisiones
					sga_readmisiones.anio_academico AS readmision_anio_academico,
					sga_readmisiones.fecha AS readmision_fecha,
					sga_readmisiones.fecha_vencimiento AS readmision_fecha_vencimiento,
					sga_readmisiones.observaciones AS readmision_observaciones,
					documentos_readmision.documento_numero AS readmision_documento
				FROM 
					sga_perdida_regularidad
					LEFT JOIN sga_readmisiones ON sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
					LEFT JOIN sga_documentos as documentos_per_reg ON documentos_per_reg.documento = sga_perdida_regularidad.documento
					LEFT JOIN sga_documentos as documentos_readmision ON documentos_readmision.documento = sga_readmisiones.documento,
					sga_alumnos,
					sga_propuestas
				WHERE
					sga_perdida_regularidad.alumno = sga_alumnos.alumno
					AND sga_alumnos.propuesta = sga_propuestas.propuesta
					AND sga_alumnos.persona = $persona
				ORDER BY
					sga_alumnos.propuesta,
					sga_perdida_regularidad.fecha,
					sga_readmisiones.fecha
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos básicos del alumno a partir de los IDs de Persona y Propuesta.
	 * 
	 * @param int $persona
	 * @param int $propuesta
	 * 
	 * @return array
	 */
	function get_datos_alumno_por_persona_propuesta($persona, $propuesta)
	{
		$persona = toba::db()->quote($persona);
		$propuesta = toba::db()->quote($propuesta);
		
		$sql = "SELECT 
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.persona,
					sga_alumnos.propuesta,
					sga_alumnos.plan_version,
					sga_alumnos.ubicacion,
					sga_alumnos.modalidad,
					sga_alumnos.division,
					sga_alumnos.anio_cursada,
					sga_alumnos.cnt_readmisiones,
					sga_alumnos.regular,
					sga_alumnos.calidad
				FROM 
					sga_alumnos
				WHERE
					sga_alumnos.persona = $persona
					AND sga_alumnos.propuesta = $propuesta
				";
		
		return guarani_db::consultar_fila($sql);
	}
    
    /**
	 * Determina si un alumno tiene aceptada su inscripción a propuesta
	 * 
	 * @param int $alumno
	 * @return boolean
	 */
	function inscripcion_propuesta_aceptada($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT 
					COUNT(1) AS cant
				FROM 
					sga_alumnos as a, 
					sga_propuestas_aspira as pa, 
					sga_situacion_aspirante as sa
				WHERE 
					a.alumno = $alumno
					AND pa.propuesta = a.propuesta
					AND pa.persona = a.persona
					AND sa.situacion_asp = pa.situacion_asp
					AND sa.resultado_asp = 'A'";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] > 0;
	}
	
	/**
	 * Obtiene las orientaciones elegidas por un alumno en el plan actual de la propuesta
	 * 
	 * @param	int $alumno
	 * @return	array
	 */
	function get_orientaciones_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT	sga_alumnos_orient.orientacion
				FROM	sga_alumnos
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
				JOIN    sga_alumnos_orient ON sga_alumnos_orient.alumno = sga_alumnos.alumno AND sga_alumnos_orient.plan = sga_planes_versiones.plan
				WHERE	sga_alumnos.alumno = $alumno 
				  ";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de módulos que el alumno tiene reconocidos por resolución (total o parcialmente).
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_modulos_reconocidos_por_resolucion_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_reconocimiento_act.elemento,
							sga_reconocimiento_act.reconocimiento_total,
							sga_reconocimiento_act.creditos
				FROM		sga_reconocimiento_act,
							sga_reconocimiento,
							sga_elementos,
							sga_g3entidades_subtipos
				WHERE		sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite AND
							sga_reconocimiento_act.elemento = sga_elementos.elemento AND
							sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo AND
							sga_reconocimiento.alumno = $alumno AND
							sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."' AND
							sga_g3entidades_subtipos.entidad_tipo = ".g3entidad::tipo_modulo." AND
							sga_reconocimiento_act.reconocimiento_total = '".comunes::si."'
				UNION
				SELECT		sga_reconocimiento_act.elemento,
							sga_reconocimiento_act.reconocimiento_total,
							SUM(sga_reconocimiento_act.creditos) as creditos
				FROM		sga_reconocimiento_act,
							sga_reconocimiento,
							sga_elementos,
							sga_g3entidades_subtipos
				WHERE		sga_reconocimiento_act.nro_tramite = sga_reconocimiento.nro_tramite AND
							sga_reconocimiento_act.elemento = sga_elementos.elemento AND
							sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo AND
							sga_reconocimiento.alumno = $alumno AND
							sga_reconocimiento.estado = '".aprobacion_por_resolucion::estado_cerrado."' AND
							sga_g3entidades_subtipos.entidad_tipo = ".g3entidad::tipo_modulo." AND
							sga_reconocimiento_act.reconocimiento_total = '".comunes::no."'
				GROUP BY	sga_reconocimiento_act.elemento,
							sga_reconocimiento_act.reconocimiento_total
		";
		return guarani_db::consultar($sql);
	}
	
	/* Listado de cursadas, equivalencias de regularidad y equivalencias parciales aprobadas (vigentes o no)
	   Para poder prorrogar el vencimiento.
	*/
	function get_regularidades_equiv_parciales_a_prorrogar_vencimiento($alumno)
	{
		// Se utiliza la vista vw_equiv_parciales_basica_basica porque trae regularidades de actividades en otros planes/versiones 
		// y que no estan en la version del plan actual del alumno. Se puso el alias vw_equiv_parciales_basica por los filtros que se arman en las 
		// operaciones desde donde se llama.		
		$sql = "-- Cursadas y Equivalencias de Regularidad
				SELECT	
						sga_alumnos.legajo,
						vw_personas.tipo_nro_documento as documento_principal,
						vw_personas.apellido_nombres as apellido_y_nombre,
						sga_turnos_cursadas.nombre as turno_nombre,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						vw_regularidades.elemento as actividad,
						COALESCE(CAST(vw_regularidades.id_acta AS varchar), '') || ':' || COALESCE(CAST(vw_regularidades.equivalencia AS varchar), '') AS clave_acta_equivalencia,
						'(' || vw_elementos.codigo || ') ' || COALESCE(vw_actividades_plan.nombre, vw_elementos.nombre) AS actividad_codigo_y_nombre,
						vw_regularidades.nota,
						vw_regularidades.nota || ' (' || sga_escalas_notas_det.descripcion || ')' AS nota_descr,
						sga_instancias_resultado.descripcion as resultado_descripcion,
						to_char(vw_regularidades.fecha,'".comunes::formato_fecha_visual."') AS fecha_formato,
						vw_regularidades.fecha,
						vw_regularidades.fecha_vigencia,
						COALESCE(to_char(vw_regularidades.fecha_vigencia,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') AS fecha_vigencia_formato,
						vw_regularidades.es_vigente,
						cast(CASE vw_regularidades.origen
						       WHEN 'R' THEN 'Regularidad'
						       WHEN 'P' THEN 'Promoción'
						       WHEN 'C' THEN 'Equivalencia de Regularidad'
						     END as varchar(30)) as tipo,
						vw_regularidades.id_acta,
						vw_regularidades.alumno,
						vw_regularidades.equivalencia,
						vw_comisiones.comision_nombre AS comision_nombre,
						CASE 
						   WHEN vw_regularidades.origen = 'R' THEN vw_comisiones.anio_academico
						   ELSE cast(null as integer)
						END as anio_academico,
						vw_comisiones.periodo_lectivo_nombre AS periodo_lectivo,
						sga_catedras.nombre AS catedra_nombre,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						sga_planes.codigo AS codigo_plan,
						CASE vw_regularidades.origen
						  WHEN 'R' THEN sga_actas.nro_acta
						  WHEN 'P' THEN sga_actas.nro_acta
						  WHEN 'C' THEN doc_equiv.documento_numero
						END as acta_o_resolucion,
						sga_escalas_notas_concepto.nombre as concepto_descripcion,
						cast(null as integer) as comision
				FROM	
						vw_regularidades_basica as vw_regularidades
						JOIN sga_alumnos ON sga_alumnos.alumno = vw_regularidades.alumno
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_regularidades.plan_version
						JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						JOIN vw_elementos ON vw_elementos.elemento = vw_regularidades.elemento
						JOIN vw_actividades_plan ON (vw_actividades_plan.elemento_revision = vw_elementos.elemento_revision AND
 						                             vw_actividades_plan.plan_version = vw_regularidades.plan_version)
						LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = vw_regularidades.instancia AND 
						                                       sga_instancias_resultado.resultado = vw_regularidades.resultado)
						LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = vw_regularidades.escala_nota AND sga_escalas_notas_det.nota = vw_regularidades.nota)
						LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_concepto.concepto = sga_escalas_notas_det.concepto
						LEFT JOIN vw_comisiones ON vw_comisiones.comision = vw_regularidades.comision
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_comisiones.catedra
						LEFT JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comisiones.ubicacion
						LEFT JOIN sga_actas ON sga_actas.id_acta = vw_regularidades.id_acta
						LEFT JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = vw_regularidades.equivalencia_tramite
						LEFT JOIN sga_documentos as doc_equiv ON doc_equiv.documento = sga_equiv_tramite.documento
						LEFT JOIN sga_turnos_cursadas ON vw_comisiones.turno_cursada =  sga_turnos_cursadas.turno
				WHERE vw_regularidades.origen IN ('R','C')				  
				  AND vw_regularidades.resultado = 'A'
				  AND vw_regularidades.alumno = $alumno
				  AND vw_regularidades.origen_otra_propuesta = false
                  AND vw_regularidades.elemento NOT IN (SELECT elemento 
				                                          FROM vw_hist_academica_basica
				                                         WHERE alumno = $alumno
														   AND resultado = 'A'
														   AND origen_otra_propuesta = false)
			     /*  Se saca este filtro. Ticket 33417 
				  -- Solo muestro la ultima regularidad de cada actividad
				  AND vw_regularidades.fecha = (	SELECT 	MAX(r.fecha) 
													FROM 	vw_regularidades_basica AS r 
													WHERE 	r.alumno = vw_regularidades.alumno AND
															r.origen_otra_propuesta = false AND
															r.elemento = vw_regularidades.elemento)
				*/											
				UNION ALL 
				-- Equivalencias parciales.
				SELECT	
						sga_alumnos.legajo,
						vw_personas.tipo_nro_documento as documento_principal,
						vw_personas.apellido_nombres as apellido_y_nombre,
						cast(NULL as varchar(100)) as turno_nombre,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						vw_equiv_parciales_basica.elemento as actividad,
						'' || ':' || COALESCE(CAST(vw_equiv_parciales_basica.equivalencia AS varchar), '') AS clave_acta_equivalencia,
						'(' || vw_elementos.codigo || ') ' || COALESCE(vw_actividades_plan.nombre, vw_elementos.nombre) AS actividad_codigo_y_nombre,
						vw_equiv_parciales_basica.nota,
						vw_equiv_parciales_basica.nota || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descr,
						sga_escalas_notas_resultado.descripcion as resultado_descripcion,
						to_char(vw_equiv_parciales_basica.fecha,'".comunes::formato_fecha_visual."') AS fecha_formato,
						vw_equiv_parciales_basica.fecha,
						vw_equiv_parciales_basica.fecha_vigencia,
						COALESCE(to_char(vw_equiv_parciales_basica.fecha_vigencia,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') AS fecha_vigencia_formato,
						vw_equiv_parciales_basica.es_vigente,
						'Equivalencia Parcial' as tipo,
						cast(NULL as integer) as id_acta,
						vw_equiv_parciales_basica.alumno,
						vw_equiv_parciales_basica.equivalencia,
						cast(NULL as varchar(100)) as comision_nombre,
						cast(NULL as integer) as anio_academico,
						cast(NULL as varchar(100)) as periodo_lectivo,
						cast(NULL as varchar(100)) as catedra_nombre,
						cast(NULL as varchar(100)) as ubicacion_nombre,
						sga_planes.codigo AS codigo_plan,
						doc_equiv.documento_numero as acta_o_resolucion,
						sga_escalas_notas_concepto.nombre as concepto_descripcion,
						cast(NULL as integer) as comision
				FROM	
						vw_equiv_parciales_basica
						JOIN sga_alumnos ON sga_alumnos.alumno = vw_equiv_parciales_basica.alumno
						JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
						JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_equiv_parciales_basica.plan_version
						JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
						JOIN vw_elementos ON vw_elementos.elemento = vw_equiv_parciales_basica.elemento
						JOIN vw_actividades_plan ON (vw_actividades_plan.elemento_revision = vw_elementos.elemento_revision AND
 						                             vw_actividades_plan.plan_version = vw_equiv_parciales_basica.plan_version)
						LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = vw_equiv_parciales_basica.escala_nota AND sga_escalas_notas_det.nota = vw_equiv_parciales_basica.nota)
						LEFT JOIN sga_escalas_notas_resultado ON (sga_escalas_notas_resultado.resultado = vw_equiv_parciales_basica.resultado)
						LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_concepto.concepto = sga_escalas_notas_det.concepto
						LEFT JOIN sga_equiv_tramite ON sga_equiv_tramite.equivalencia_tramite = vw_equiv_parciales_basica.equivalencia_tramite
						LEFT JOIN sga_documentos as doc_equiv ON doc_equiv.documento = sga_equiv_tramite.documento
				WHERE vw_equiv_parciales_basica.resultado = 'A'
				  AND vw_equiv_parciales_basica.alumno = $alumno
                  AND vw_equiv_parciales_basica.elemento NOT IN (SELECT elemento 
				                                                   FROM vw_hist_academica_basica
				                                                  WHERE alumno = $alumno
																    AND resultado = 'A'
																	AND origen_otra_propuesta = false)
				ORDER BY propuesta_nombre, fecha
		";
		$sql = toba::perfil_de_datos()->filtrar($sql, null, null, array('sga_propuestas'));
		return guarani_db::consultar($sql, null, false);
	}
	
	function existe_alumno($alumno)
	{
		$sql = "
				SELECT
					1
				FROM
					sga_alumnos
				WHERE
					alumno = $alumno;
				";
		return toba::db()->hay_datos($sql);
	}
	
	function get_cambios_calidad($persona)
	{
		$sql = "
				SELECT
					sga_alumnos_hist_calidad.calidad,
					sga_alumnos_calidad.nombre as calidad_desc,
					sga_alumnos_hist_calidad.motivo_calidad,
					sga_alumnos_motivos_calidad.nombre as motivo_desc,
					to_char(sga_alumnos_hist_calidad.fecha,'".comunes::formato_fecha_visual."') as fecha,
					sga_alumnos_hist_calidad.documento,
					sga_alumnos_hist_calidad.observaciones,	
					sga_propuestas.propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_documentos.documento_numero
				FROM 
					sga_alumnos,
					sga_alumnos_hist_calidad
					LEFT JOIN sga_documentos ON sga_documentos.documento = sga_alumnos_hist_calidad.documento,
					sga_propuestas,
					sga_alumnos_calidad,
					sga_alumnos_motivos_calidad                    
				WHERE
					sga_alumnos.alumno = sga_alumnos_hist_calidad.alumno	
					AND sga_alumnos.propuesta = sga_propuestas.propuesta
					AND sga_alumnos_calidad.calidad = sga_alumnos_hist_calidad.calidad
					AND sga_alumnos_motivos_calidad.motivo_calidad = sga_alumnos_hist_calidad.motivo_calidad                        
					AND sga_alumnos.alumno IN (SELECT alumno FROM sga_alumnos WHERE persona = $persona)
				ORDER BY
					sga_propuestas.nombre, sga_alumnos_hist_calidad.fecha DESC
				";
		return guarani_db::consultar($sql);
	}
	
	function get_alumnos_para_insc_cursada_masiva($param_ingresantes_oferta_actividades, $param_filtra_rango_apellido_comision,
			$param_recursa_regularizadas, $param_cursa_en_cualquier_ubicacion, $elemento, $comision, $plan_version, $ubicacion, $where = '')
	{
		/*
		  Se recuperan alumnos activos de una version de plan de estudios que no tengan la actividad aprobada y no esten inscriptos en la comision.
		  Ademas se aplican otros filtros según los parámetros de la función.
		*/
		
		if ($where) {
			$where = ' AND ' . $where;
		}

		// Defino variables
		$insc_propuesta_aceptada  = toba::db()->quote(propuesta::inscripcion_aceptada);
		$insc_propuesta_pendiente = toba::db()->quote(propuesta::inscripcion_pendiente);
		$calidad_activo           = toba::db()->quote(comunes::estado_activo);
		$resultado_aprobado       = toba::db()->quote(comunes::resultado_nota_aprobado);

		
		// Si el parámetro del sistema cur_ingresantes_oferta_actividades = PRIMER_AÑO
		// y la actividad NO es de primer año, se excluyen a los ingresantes de la lista
		$filtro_ingresantes = '';
		$datos_actividad = toba::consulta_php('co_elementos')->get_info_elemento_plan($plan_version, $elemento);
		if ((!empty($datos_actividad['anio_de_cursada'])) && ($datos_actividad['anio_de_cursada'] > 1) && ($param_ingresantes_oferta_actividades == 'PRIMER_AÑO')) {
			$filtro_ingresantes = ' AND f_es_ingresante_periodos_insc_cursada(sga_alumnos.alumno) = 0';
		}
		
		$where_rango_apellido_comision = '';
		if ($param_filtra_rango_apellido_comision == comunes::si) {
			$datos_comision = toba::consulta_php('co_comisiones')->get_datos_basicos_comision($comision);
			$letra_desde = $datos_comision['letra_desde'];
			$letra_hasta = $datos_comision['letra_hasta'];
			if (empty($letra_desde)) {
				if (empty($letra_hasta)) {
					$where_rango_apellido_comision = '';
				} else {
					$where_rango_apellido_comision = "AND substring(vw_personas.apellido from 1 for 1) <= '$letra_hasta'";
				}
			} else {
				if (empty($letra_hasta)) {
					$where_rango_apellido_comision = "AND substring(vw_personas.apellido from 1 for 1) >= '$letra_desde'";
				} else {
					$where_rango_apellido_comision = "AND substring(vw_personas.apellido from 1 for 1) BETWEEN '$letra_desde' AND '$letra_hasta'";
				}
			}
		}
		
		// Si el parámetro cur_recursar_regularizadas = 'N', excluir alumnos con actividades regularizadas vigentes.
		$filtro_ya_regularizaron = '';
		if ($param_recursa_regularizadas == comunes::no) {
			$filtro_ya_regularizaron = " AND NOT EXISTS (	
				SELECT	1
				  FROM vw_regularidades_basica
				 WHERE alumno = sga_alumnos.alumno
				   AND elemento = $elemento
				   AND resultado = $resultado_aprobado
				   AND es_vigente = 1
				)";
		}

		$filtro_ubicacion = '';
		if ($param_cursa_en_cualquier_ubicacion == comunes::no) {
			$filtro_ubicacion = "AND sga_alumnos.ubicacion = ".toba::db()->quote($ubicacion);
		}

		$sql = "SELECT 		
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.persona,
					sga_alumnos.propuesta,
					vw_personas.apellido,
					vw_personas.nombres,
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS documento_principal,
					vw_personas.documento,
					vw_personas.nro_documento,
					vw_personas.tipo_documento,
					vw_personas.desc_tipo_documento,
					sga_propuestas_aspira.anio_academico
				FROM 		
					sga_alumnos
					JOIN vw_personas ON	sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND
					                               sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
					JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp AND
					                                 sga_situacion_aspirante.resultado_asp IN ($insc_propuesta_aceptada, $insc_propuesta_pendiente))
				
				WHERE sga_alumnos.plan_version = $plan_version
				  AND sga_alumnos.calidad = $calidad_activo 
				  -- Se excluyen alumnos que han aprobado la actividad
				  AND NOT EXISTS ( SELECT 1
									   FROM vw_hist_academica_basica
									  WHERE alumno    = sga_alumnos.alumno
									    AND elemento  = $elemento
									    AND	resultado = $resultado_aprobado
									)
				  -- Si corresponde, se excluyen ingresantes
				  $filtro_ingresantes
				  -- Se excluyen alumnos ya inscriptos a la comisión 
				  AND sga_alumnos.alumno NOT IN (SELECT alumno FROM sga_insc_cursada WHERE comision = $comision)
				  -- Si corresponde, se filtra por apellido dentro del rango de letras de la comisión
				  $where_rango_apellido_comision
				  -- Si corresponde, se excluyen alumnos que ya regularizaron la actividad
				  $filtro_ya_regularizaron
				  -- Si corresponde, se filtra por ubicación.
				  $filtro_ubicacion
				  -- Otros filtros de usuario
				  $where
				  
				ORDER BY vw_personas.apellido, vw_personas.nombres
				";
		
		toba::logger()->notice($sql, 'guarani');
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si una actividad dada es optativa compartida entre materias genéricas de la versión del plan
	 * y el alumno eligió para cuál de ellas se cumple.
	 * 
	 * @param int $optativa
	 * @param int $plan_version
	 * @param int $alumno
	 * @param int $generica
	 * @return int
	 */
	function optativa_esta_compartida($optativa, $plan_version, $alumno=null, $generica=null)
	{
		$optativa = toba::db()->quote($optativa);
		$plan_version = toba::db()->quote($plan_version);
		$alumno = is_null($alumno) ? 'null' : toba::db()->quote($alumno);
		$generica = is_null($generica) ? 'null' : toba::db()->quote($generica);
		$sql = "SELECT f_optativa_es_compartida($optativa, $plan_version, $alumno, $generica) as optativa_compartida";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['optativa_compartida'];
	}
		
	function get_cantidad_actividades_aprobadas_anio($filtro, $alumno, $plan_version) 
	{
		$sql = "
				SELECT	count(1) as cantidad
				FROM	vw_actividades_plan
				JOIN	sga_elementos_plan	ON (vw_actividades_plan.elemento_revision	= sga_elementos_plan.elemento_revision
											AND vw_actividades_plan.plan_version		= sga_elementos_plan.plan_version)
				WHERE	vw_actividades_plan.plan_version = $plan_version 
						AND vw_actividades_plan.elemento NOT IN (SELECT elemento FROM vw_hist_academica_basica WHERE alumno = $alumno AND resultado = '".comunes::resultado_nota_aprobado."')
						AND vw_actividades_plan.elemento NOT IN (SELECT elemento FROM vw_optativas_plan WHERE plan_version = $plan_version)
						$filtro
				";
		return guarani_db::consultar_fila($sql, null, false);
	}
	
	/*
	 * Recupera la actuacion provisoria en examenes de la persona en todas las propuestas
	 * Son datos de examenes que aun no estan en acta cerrada pero tienen cargado un resultado.
	 * Si esta en un acta de examen, que sea un acta de tipo normal (no rectificativo) y abierta.	
	 */	
	function get_actuacion_provisoria_examen($persona)
	{
		
		$formato_fecha_visual = comunes::formato_fecha_visual;
		$acta_tipo_normal = toba::db()->quote(acta::tipo_normal);
		$acta_estado_abierta = toba::db()->quote(acta::estado_abierta);
		$si = toba::db()->quote(comunes::si);
	
		$sql = "SELECT  
					sga_alumnos.propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					vw_actividades_plan.codigo as actividad_codigo,
					vw_actividades_plan.nombre as actividad_nombre,
					vw_actividades_plan.nombre || ' (' || vw_actividades_plan.codigo || ')' as actividad_nombre_codigo,
					to_char(vw_mesas_examen.mesa_examen_fecha, '$formato_fecha_visual') as fecha_examen_visual,
					sga_eval_detalle_examenes.nota as nota,
					sga_eval_detalle_examenes.nota || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descripcion,
					sga_eval_detalle_examenes.resultado as resultado,
					sga_instancias_resultado.descripcion as resultado_desc,
					sga_actas.nro_acta as nro_acta,
					vw_mesas_examen.anio_academico,
					vw_mesas_examen.turno_examen_nombre as turno_nombre,
					vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
					vw_mesas_examen.mesa_examen_elemento,
					vw_mesas_examen.llamado_mesa,
					vw_mesas_examen.mesa_examen
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_eval_detalle_examenes ON sga_eval_detalle_examenes.alumno = sga_alumnos.alumno
				JOIN sga_evaluaciones ON sga_evaluaciones.evaluacion = sga_eval_detalle_examenes.evaluacion
				JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa_entidad = sga_evaluaciones.entidad
				JOIN sga_evaluaciones_tipos ON (sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo AND
				                                sga_evaluaciones_tipos.automatica = $si)
				JOIN vw_actividades_plan ON (vw_actividades_plan.plan_version = sga_eval_detalle_examenes.plan_version AND 
				                             vw_actividades_plan.elemento     = vw_mesas_examen.mesa_examen_elemento )
				JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = sga_eval_detalle_examenes.instancia AND 
				                                  sga_instancias_resultado.resultado = sga_eval_detalle_examenes.resultado)												
				LEFT JOIN sga_actas ON (sga_actas.id_acta = sga_eval_detalle_examenes.id_acta AND sga_actas.tipo_acta = $acta_tipo_normal AND sga_actas.estado = $acta_estado_abierta)
				LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle_examenes.escala_nota AND 
				                                    sga_escalas_notas_det.nota = sga_eval_detalle_examenes.nota)
				
				WHERE sga_alumnos.persona = $persona
				  AND sga_eval_detalle_examenes.resultado IS NOT NULL
			ORDER BY sga_propuestas.nombre, vw_actividades_plan.nombre
				";

		return guarani_db::consultar($sql);
	}
	
	/*
	 * Recupera la actuacion provisoria en cursadas de la persona en todas las propuestas
	 * Son datos de cursadas que aun no estan en acta cerrada pero tienen cargado un resultado.
	 * Si esta en un acta de cursada, que sea un acta de tipo normal (no rectificativo) y abierta.
	 */
	function get_actuacion_provisoria_cursada($persona)
	{
		
		$formato_fecha_visual = comunes::formato_fecha_visual;		
		$acta_origen_regulares = toba::db()->quote(acta::origen_regulares);
		$acta_tipo_normal = toba::db()->quote(acta::tipo_normal);
		$acta_estado_abierta = toba::db()->quote(acta::estado_abierta);
		$si = toba::db()->quote(comunes::si);
	
		$sql = "SELECT  
					sga_alumnos.propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					vw_actividades_plan.codigo as actividad_codigo,
					vw_actividades_plan.nombre as actividad_nombre,
					vw_actividades_plan.nombre || ' (' || vw_actividades_plan.codigo || ')' as actividad_nombre_codigo,
					to_char(sga_eval_detalle_cursadas.fecha_regular, '$formato_fecha_visual') as fecha_visual,
					sga_eval_detalle_cursadas.nota_cursada as nota,
					sga_eval_detalle_cursadas.nota_cursada || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descripcion,
					sga_eval_detalle_cursadas.resultado_cursada as resultado,
					sga_instancias_resultado.descripcion as resultado_desc,
					sga_actas.nro_acta as nro_acta,
					vw_comisiones.anio_academico,
					vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
					vw_comisiones.comision_nombre as comision_nombre,
					vw_comisiones.elemento,
					vw_comisiones.comision
				
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_eval_detalle_cursadas ON sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
				JOIN sga_evaluaciones ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
				JOIN vw_comisiones ON vw_comisiones.entidad = sga_evaluaciones.entidad
				JOIN sga_evaluaciones_tipos ON (sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo AND
				                                sga_evaluaciones_tipos.automatica = $si)
				JOIN vw_actividades_plan ON (vw_actividades_plan.plan_version = sga_eval_detalle_cursadas.plan_version AND 
				                             vw_actividades_plan.elemento     = vw_comisiones.elemento )
				JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = 1 AND 
				                                  sga_instancias_resultado.resultado = sga_eval_detalle_cursadas.resultado_cursada)												
				LEFT JOIN sga_actas ON (sga_actas.id_acta = sga_eval_detalle_cursadas.id_acta_cursada AND 
				                        sga_actas.origen = $acta_origen_regulares AND  
				                        sga_actas.tipo_acta = $acta_tipo_normal AND 
										sga_actas.estado = $acta_estado_abierta)
				LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle_cursadas.escala_nota_cursada AND 
				                                    sga_escalas_notas_det.nota = sga_eval_detalle_cursadas.nota_cursada)
				
				WHERE sga_alumnos.persona = $persona
				  AND sga_eval_detalle_cursadas.instancia_cursada = 1
				  AND sga_eval_detalle_cursadas.resultado_cursada IS NOT NULL
				  AND sga_eval_detalle_cursadas.acta_cursada_cerrada = 0
			ORDER BY sga_propuestas.nombre, vw_actividades_plan.nombre				";

		return guarani_db::consultar($sql);
	}

	/*
	 * Recupera la actuacion provisoria en actas de promocion de la persona en todas las propuestas
	 * Son datos de promociones que aun no estan en acta cerrada pero tienen cargado un resultado.
	 * Si esta en un acta de promocion, que sea un acta de tipo normal (no rectificativo) y abierta.
	 */ 
	function get_actuacion_provisoria_promocion($persona)
	{		
		$formato_fecha_visual = comunes::formato_fecha_visual;
		$acta_origen_promociones = toba::db()->quote(acta::origen_promociones);
		$acta_tipo_normal = toba::db()->quote(acta::tipo_normal);
		$acta_estado_abierta = toba::db()->quote(acta::estado_abierta);
		$si = toba::db()->quote(comunes::si);
	
		$sql = "SELECT  
					sga_alumnos.propuesta,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					vw_actividades_plan.codigo as actividad_codigo,
					vw_actividades_plan.nombre as actividad_nombre,
					vw_actividades_plan.nombre || ' (' || vw_actividades_plan.codigo || ')' as actividad_nombre_codigo,
					to_char(sga_eval_detalle_cursadas.fecha_promocion, '$formato_fecha_visual') as fecha_visual,
					sga_eval_detalle_cursadas.nota_promocion as nota,
					sga_eval_detalle_cursadas.nota_promocion || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descripcion,
					sga_eval_detalle_cursadas.resultado_promocion as resultado,
					sga_instancias_resultado.descripcion as resultado_desc,
					sga_actas.nro_acta as nro_acta,
					vw_comisiones.anio_academico,
					vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
					vw_comisiones.comision_nombre as comision_nombre,
					vw_comisiones.elemento,
					vw_comisiones.comision
				
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_eval_detalle_cursadas ON sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
				JOIN sga_evaluaciones ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
				JOIN vw_comisiones ON vw_comisiones.entidad = sga_evaluaciones.entidad
				JOIN sga_evaluaciones_tipos ON (sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo AND
				                                sga_evaluaciones_tipos.automatica = $si)
				JOIN vw_actividades_plan ON (vw_actividades_plan.plan_version = sga_eval_detalle_cursadas.plan_version AND 
				                             vw_actividades_plan.elemento     = vw_comisiones.elemento )
				JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = 2 AND 
				                                  sga_instancias_resultado.resultado = sga_eval_detalle_cursadas.resultado_promocion)												
				LEFT JOIN sga_actas ON (sga_actas.id_acta = sga_eval_detalle_cursadas.id_acta_promocion AND 
				                        sga_actas.origen = $acta_origen_promociones AND  
				                        sga_actas.tipo_acta = $acta_tipo_normal AND 
										sga_actas.estado = $acta_estado_abierta)
				LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle_cursadas.escala_nota_promocion AND 
				                                    sga_escalas_notas_det.nota = sga_eval_detalle_cursadas.nota_promocion)
				
				WHERE sga_alumnos.persona = $persona
				  AND sga_eval_detalle_cursadas.instancia_promocion = 1
				  AND sga_eval_detalle_cursadas.resultado_promocion IS NOT NULL
				  AND sga_eval_detalle_cursadas.acta_promocion_cerrada = 0
			ORDER BY sga_propuestas.nombre, vw_actividades_plan.nombre
			";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de reinscripciones a propuesta de una persona dada.
	 * 
	 * @param int $persona
	 * @return array
	 */
	function reinscripciones_reporte($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT		'(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre as propuesta_codigo_nombre,
							sga_propuestas.propuesta,
							sga_reinscripciones.anio_academico,
							sga_reinscripciones.fecha_reinscripcion,
							sga_reinscripciones.nro_transaccion
				FROM		sga_reinscripciones
							JOIN sga_alumnos ON sga_alumnos.alumno = sga_reinscripciones.alumno
							JOIN sga_propuestas ON sga_propuestas.propuesta =  sga_alumnos.propuesta
				WHERE		sga_alumnos.persona = $persona
                ORDER BY	sga_propuestas.codigo,
							sga_propuestas.nombre,
							sga_reinscripciones.anio_academico
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos necesarios para el reporte de transacciones según la condición pasada como parámetro 
	 * 
	 * @param string $where
	 * @return type
	 */
	function get_listado_reporte_transacciones($where='')
	{
		$formato_fecha_hora_visual = toba::db()->quote(comunes::formato_fecha_hora_visual);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 
					vw_comprobantes.nro_transaccion as nro_transaccion, 
					vw_comprobantes.alumno_nombre as apellido_nombres,
					to_char(vw_comprobantes.fecha, $formato_fecha_hora_visual) as fecha,
					vw_comprobantes.interfaz_descripcion as interfaz,
					vw_comprobantes.descripcion as nombre_operacion

				FROM vw_comprobantes
				WHERE true
				$where
				ORDER BY vw_comprobantes.fecha
			";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos necesarios para el reporte de inscripciones según la condición pasada como parámetro 
	 * 
	 * @param string $filtro
	 * @return type
	 */
	function get_listado_reporte_inscripciones_alumno($filtro = '')
	{
		/*
		  IE = Inscripciones a Examenes tipo_comprobante_insc_exa
		  BIE = Bajas de Inscripciones a Examenes tipo_comprobante_baja_insc_exa
		  RIE = Rechazos de Inscripciones a Examenes tipo_comprobante_rec_insc_exa
		  IC = Inscripciones a Cursadas tipo_comprobante_insc_act
		  BIC = Bajas de Inscripciones a Cursadas tipo_comprobante_baja_insc_act
		  RIC = Rechaos de de Inscripciones a Cursadas tipo_comprobante_rec_insc_act
		  Condición:
		  WHERE vw_comprobantes.tipo IN ('IE','BIE','RIE','IC','BIC','RIC') */
		$where = " WHERE vw_comprobantes.tipo IN (" . toba::db()->quote(comprobantes::tipo_comprobante_insc_act) . ","
				. toba::db()->quote(comprobantes::tipo_comprobante_baja_insc_act) . ","
				. toba::db()->quote(comprobantes::tipo_comprobante_rec_insc_act) . ","
				. toba::db()->quote(comprobantes::tipo_comprobante_insc_exa) . ","
				. toba::db()->quote(comprobantes::tipo_comprobante_baja_insc_exa) . ","
				. toba::db()->quote(comprobantes::tipo_comprobante_rec_insc_exa) . ")";
		$formato_fecha_hora_visual = toba::db()->quote(comunes::formato_fecha_hora_visual);
		if ($filtro) {
			$filtro = ' AND ' . $filtro;
		}
		$sql = "SELECT 
					vw_comprobantes.nro_transaccion as nro_transaccion, 
					vw_comprobantes.alumno_nombre as apellido_nombres,
					to_char(vw_comprobantes.fecha, $formato_fecha_hora_visual) as fecha,
					vw_comprobantes.interfaz_descripcion as interfaz_descripcion,
					vw_comprobantes.estado_descripcion as accion,
					vw_comprobantes.actividad_nombre || ' (' || vw_comprobantes.actividad_codigo || ')'  as actividad,
					vw_comprobantes.descripcion as tipo_descripcion

				FROM vw_comprobantes
				$where
				$filtro
				ORDER BY vw_comprobantes.fecha
			";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos para el reporte de optativas de ficha de la persona
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_reporte_optativas($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		/* 	Recupera propuestas del alumno y las actividades genericas con sus optativas.
		    optativa_compartida = 
				Valores: 0 = No esta compartida o la actividad (pOptativa) NO es una actividad optativa.
					 	 1 = Es optativa compartida y el alumno la eligió para la generica consultada.
					 	 2 = Esta compartida y el alumno la eligió para otra generica 
						-1 = El alumno aun no eligio para gue generica se cumple la optativa
		*/
		$sql = "SELECT 
					sga_alumnos.alumno,
					sga_alumnos.plan_version,
					sga_alumnos.persona,
					sga_propuestas.propuesta,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descripcion,
					sga_propuestas.nombre as propuesta_nombre,
					sga_alumnos.legajo as legajo,
					'(' || vw_optativas_plan.codigo_generica || ') ' || vw_optativas_plan.nombre_generica as generica_descripcion,
					vw_optativas_plan.elemento_generica as generica_elemento,
					vw_optativas_plan.codigo_generica 	as generica_codigo,
					vw_optativas_plan.nombre_generica 	as generica_nombre,
					get_regla_mensaje(sga_elementos_revision.regla, sga_elementos_revision.parametros) as forma_cumplimiento,
					'(' || vw_optativas_plan.codigo || ') ' || vw_optativas_plan.nombre as actividad_optativa,
					vw_optativas_plan.nombre as optativa_nombre,
					vw_optativas_plan.creditos,
					vw_optativas_plan.puntaje,
					vw_optativas_plan.horas,
					f_optativa_es_compartida(vw_optativas_plan.elemento, sga_alumnos.plan_version, sga_alumnos.alumno, vw_optativas_plan.elemento_generica) as optativa_compartida,
					vw_optativas_plan.elemento as optativa,
					sga_elementos_revision.regla as regla,
					sga_elementos_revision.parametros as parametros_regla
				FROM sga_alumnos 
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN vw_optativas_plan ON vw_optativas_plan.plan_version = sga_alumnos.plan_version
				JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = vw_optativas_plan.elemento_revision_generica
				JOIN sga_reglas ON sga_reglas.regla = sga_elementos_revision.regla
				
				$where
				
				ORDER BY 
					sga_propuestas.nombre, 
					sga_propuestas.propuesta, 
					generica_nombre, 
					generica_elemento,
					optativa_nombre,
					optativa
				";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos activos de un conjunto (no vacío) de tuplas propuesta-ubicación.
	 * 
	 * @param array $propuestas_ubicaciones
	 * @return array
	 */
	function get_alumnos_activos_propuestas_ubicaciones($propuestas_ubicaciones)
	{
		$where = 'ROW(sga_alumnos.propuesta, sga_alumnos.ubicacion) IN (';
		$primera = true;
		foreach ($propuestas_ubicaciones as $propuesta_ubicacion) {
			$where .= $primera ? "({$propuesta_ubicacion['propuesta']}, {$propuesta_ubicacion['ubicacion']})" : ", ({$propuesta_ubicacion['propuesta']}, {$propuesta_ubicacion['ubicacion']})";
			$primera = false;
		}
		$where .= ')';
		$sql = "SELECT	sga_alumnos.alumno
				FROM 	sga_alumnos						
				WHERE 	sga_alumnos.calidad = '".alumno::calidad_activo."' AND
						sga_alumnos.regular = '".comunes::si."' AND
						$where
        ";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de actividades aprobadas, regularizadas y cursando actualmente de una persona en todas sus propuestas.
	 * Usada en la ficha del alumno, en el reporte de Optativas.
	 *	
	 * @param integer $persona  # Id de la persona que se consulta su historia academica.
	 * @return array # Recupera lista de actividades aprobadas, cursadas o en curso, en orden de prioridad por cada actividad.
	 */
	function get_hist_academica_basica ($persona)
	{
		$sql = "-- Aprobadas
				SELECT elemento,
					   'Aprobada' as tipo,
					   1 as orden
				FROM vw_hist_academica_basica
				WHERE persona = $persona
				  AND resultado = '".escalas_notas::resultado_aprobado."'
					
				UNION 
				-- Cursadas aprobadas vigentes
				SELECT elemento,
					   'Regularizada' as tipo,
					   2 as orden
				FROM vw_regularidades_basica 
				WHERE persona = $persona
				  AND es_vigente = 1
				  AND resultado = '".escalas_notas::resultado_aprobado."'
							
				UNION 
				-- Cursandose actualmente.
				SELECT c.elemento,
					   'En curso' as tipo,
					   3 as orden
					FROM sga_insc_cursada as i
					  JOIN sga_alumnos ON sga_alumnos.alumno = i.alumno
					  JOIN sga_comisiones as c ON c.comision = i.comision
					  JOIN vw_periodos_lectivos as pl ON pl.periodo_lectivo = c.periodo_lectivo
					WHERE sga_alumnos.persona = $persona
					AND pl.fecha_fin >= CURRENT_DATE
				ORDER BY 1, 3	
				";
		return guarani_db::consultar($sql, null, false);
	}

	
	function get_generica_certificado($alumno)
	{
		//agrego que el certificado no este anulado
		$sql = "SELECT 
					get_actividades_certificado_alumno.elemento as generica,
					sga_elementos.elemento,
					sga_certificados_otorg.alumno,
					sga_elementos.entidad_subtipo
				FROM 
					sga_certificados_otorg as sga_certificados_otorg
				JOIN LATERAL 
					get_actividades_certificado_alumno(sga_certificados_otorg.alumno, sga_certificados_otorg.certificado, sga_certificados_otorg.plan_version, true,  true ) as get_actividades_certificado_alumno ON true
				JOIN 
					sga_elementos as sga_elementos ON sga_elementos.elemento = get_actividades_certificado_alumno.elemento
				WHERE sga_certificados_otorg.alumno = $alumno
				AND sga_elementos.entidad_subtipo = 2 
				AND sga_certificados_otorg.anulado = 0 ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Consulta los datos del sistema para obtener información que conformará
	 * el suplemento al título de tipo "Reconocimientos obtenidos", para su posterior procesamiento.
	 */
	function get_suplemento_reconocimiento($alumno)
	{
		$alumno = toba::db()->quote($alumno);

		$sql = "
			SELECT		
				sga_bitacora.titulo,
				sga_bitacora.observaciones
			FROM		
				sga_bitacora
				JOIN sga_alumnos ON sga_alumnos.persona = sga_bitacora.persona
			WHERE
				sga_bitacora.id_seguimiento = '" . persona::bitacora_seguimiento_actividades . "' AND
				sga_alumnos.alumno = $alumno
			ORDER BY fecha DESC
		";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Consulta los datos del sistema para obtener información que conformará
	 * el suplemento al título de tipo "Actividad Docente", para su posterior procesamiento.
	 */
	function get_suplemento_docencia($alumno)
	{
		$alumno = toba::db()->quote($alumno);

		$sql = "
			SELECT
				r.nombre									AS responsabilidad,
				vw_comisiones.elemento_nombre				AS actividad,
				COALESCE(sga_docentes_comision.fecha_desde,vw_comisiones.fecha_inicio_dictado)	AS inicio_dictado,
				COALESCE(sga_docentes_comision.fecha_hasta,vw_comisiones.fecha_fin_dictado)		AS fin_dictado,
				vw_comisiones.periodo_lectivo_nombre,
				vw_comisiones.anio_academico				
			FROM 
				sga_alumnos 
				JOIN sga_docentes		ON sga_docentes.persona = sga_alumnos.persona
				JOIN sga_docentes_comision	ON sga_docentes_comision.docente = sga_docentes.docente
				JOIN vw_comisiones		ON vw_comisiones.comision = sga_docentes_comision.comision
				JOIN sga_docentes_responsabilidades as r	ON r.responsabilidad = sga_docentes_comision.responsabilidad
			WHERE
				sga_alumnos.alumno = $alumno
			ORDER BY vw_comisiones.fecha_inicio_dictado
		";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Recupera alumnos a los que se van a procesar matrices de equivalencias por cambio de plan de estudios
	 */
	function get_alumnos_cambio_plan_aplicar_matrices($ubicaciones, $planes)
	{
		$filtro_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'sga_alumnos', 'ubicacion');
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan');

		$sql = "-- Matrices definidas con otras propuestas del alumno que esta cursando actualmente.
				SELECT	0 as id_acta,
						'CURSADA_SIMULTANEA' as accion,
						sga_alumnos.alumno as alumno,
						p_origen.propuesta as propuesta_origen,
						p_origen.plan as plan_origen,
						p_origen.plan_version as plan_version_origen,
						null as elemento
				FROM sga_alumnos
				JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
				JOIN sga_alumnos as alu_origen ON alu_origen.persona = sga_alumnos.persona
				JOIN vw_planes as p_origen ON p_origen.plan_version = alu_origen.plan_version
				WHERE sga_alumnos.calidad = '".alumno::calidad_activo."'
				 AND alu_origen.alumno <> sga_alumnos.alumno 
				 AND alu_origen.calidad = '".alumno::calidad_activo."'
				 AND $filtro_ubicaciones 
				 AND $filtro_planes

				UNION ALL

				-- Matrices definidas con otros planes de la propuesta.
				SELECT	0 as id_acta,
						'CAMBIO_DE_PLAN' as accion,
						sga_alumnos.alumno,
						sga_alumnos.propuesta as propuesta_origen,
						null as plan_origen,
						null as plan_version_origen,
						null as elemento
				FROM	sga_alumnos 
				JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				WHERE	sga_alumnos.calidad = '".alumno::calidad_activo."'
				  AND	$filtro_ubicaciones
				  AND	$filtro_planes
				  -- Tuvo un cambio de plan 
				  -- Luego del cambio de plan pudo haber tenido cambios de version de plan, por eso se busca por plan en el historico.
				  AND	EXISTS (SELECT	1
								  FROM	sga_alumnos_hist_planes
								  JOIN	sga_planes_versiones as pv_hist ON pv_hist.plan_version = sga_alumnos_hist_planes.plan_version
								 WHERE	sga_alumnos_hist_planes.alumno = sga_alumnos.alumno 
								   AND	sga_alumnos_hist_planes.motivo_plan = ".alumno::motivo_plan_cambio."
								   AND	pv_hist.plan = sga_planes_versiones.plan
								)
				ORDER BY 3, 2
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Recupera alumnos a los que se van a procesar matrices de equivalencias por cambio de version de plan de estudios
	 */
	function get_alumnos_cambio_version_aplicar_matrices($ubicaciones, $versiones)
	{
		$filtro_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'sga_alumnos', 'ubicacion');
		$filtro_versiones = guarani_sql::armar_condicion_in($versiones, 'sga_planes_versiones', 'plan_version');

		$sql = "-- Matrices definidas con otras propuestas del alumno.
				SELECT	0 as id_acta,
						'CURSADA_SIMULTANEA' as accion,
						sga_alumnos.alumno as alumno,
						p_origen.propuesta as propuesta_origen,
						p_origen.plan as plan_origen,
						p_origen.plan_version as plan_version_origen,
						null as elemento
				FROM sga_alumnos
				JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
				JOIN sga_alumnos as alu_origen ON alu_origen.persona = sga_alumnos.persona
				JOIN vw_planes as p_origen ON p_origen.plan_version = alu_origen.plan_version
				WHERE sga_alumnos.calidad = '".alumno::calidad_activo."'
				 AND alu_origen.alumno <> sga_alumnos.alumno 
				 AND alu_origen.calidad = '".alumno::calidad_activo."'
				 AND $filtro_ubicaciones 
				 AND $filtro_versiones
				 
				UNION ALL
				
				-- Matrices definidas con otras versiones del plan de estudios.
				SELECT	0 as id_acta,
						'CAMBIO_DE_VERSION' as accion,
						sga_alumnos.alumno,
						sga_alumnos.propuesta as propuesta_origen,
						sga_planes_versiones.plan as plan_origen,
						null as plan_version_origen,
						null as elemento
				FROM	sga_alumnos 
				JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				WHERE	sga_alumnos.calidad = '".alumno::calidad_activo."' 
				  AND	$filtro_ubicaciones 
				  AND	$filtro_versiones 
				  
				  -- Tuvo un cambio de version de plan.
				  AND EXISTS (SELECT	1
								FROM	sga_alumnos_hist_planes
								WHERE	sga_alumnos_hist_planes.alumno = sga_alumnos.alumno 
								  AND	sga_alumnos_hist_planes.plan_version = sga_alumnos.plan_version 
								  AND	sga_alumnos_hist_planes.motivo_plan = ".alumno::motivo_plan_cambio_version."
							)
				ORDER BY 3,2
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el conjunto de alumnos a los que se van a procesar matrices de equivalencia por cursada simultánea.
	 * 
	 * @param array $ubicaciones
	 * @param array $planes
	 * @return array
	 */
	function get_alumnos_cursada_simultanea_aplicar_matrices($ubicaciones, $planes)
	{
		$filtro_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'sga_alumnos', 'ubicacion');
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan');
		$sql = "-- Matrices definidas con otras propuestas del alumno que esta cursando actualmente.
				SELECT	DISTINCT sga_alumnos.alumno,
						0 as id_acta,
						'CURSADA_SIMULTANEA' as accion,
						null as propuesta_origen,
						null as plan_origen,
						null as plan_version_origen,
						null as elemento
				FROM	sga_alumnos
						JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
						JOIN sga_alumnos as alu_origen ON sga_alumnos.persona = alu_origen.persona
				WHERE	sga_alumnos.calidad = '".alumno::calidad_activo."' AND
						alu_origen.alumno <> sga_alumnos.alumno AND
						alu_origen.calidad = '".alumno::calidad_activo."' AND
						$filtro_ubicaciones AND
						$filtro_planes
		";
		return guarani_db::consultar($sql);
	}

    function get_materias_genericas_alumno($plan_version)
	{		
		$genericas = array();
		$sql = "SELECT	
		                gen.elemento as generica_elemento,
		                gen.codigo as generica_codigo,
		                gen.nombre as generica_nombres,
		                opt.elemento as optativa_elemento,
		                opt.codigo as optativa_codigo,
		                opt.nombre as optativa_nombres,
		                opt.puntaje as optativa_puntaje
				FROM vw_actividades_genericas_plan as gen
				JOIN vw_optativas as opt ON opt.elemento_generica = gen.elemento
				WHERE gen.plan_version = $plan_version
				ORDER BY 6;
				";

		$genericas = guarani_db::consultar($sql, null, false);
		return $genericas;
	}
      
	function get_actividad_academica($alumno)
	{
		// Historia académica del Alumno: Examenes, Promociones, Regularidades, Equivalencias Totales y Equivalencias de Regularidad.
		// equivalencia_instancia = 10 - Total / 11 - Regularidad / 12 = Parcial
		// tipo = Aprobacion / Regularidades / Eq. Parcial / En Curso
		// Las equivalencias parciales se pueden recuperar con la vista vw_equiv_parciales. 
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$formato_fecha_orden = toba::db()->quote(comunes::formato_fecha_orden);

		$sql = "SELECT	
						cast('Aprobacion' as varchar(11)) as tipo, -- Historia Academica - Aprobaciones
						sga_elementos_plan.anio_de_cursada, -- Año de cursada definido en el plan
						sga_elementos_plan.periodo_de_cursada, -- Periodo lectivo de Cursada definido en el plan 
						EXTRACT(YEAR FROM vw_hist_academica.fecha) as anio,
						vw_hist_academica.elemento as materia,
						vw_hist_academica.actividad_codigo as materia_codigo,
						vw_hist_academica.actividad_nombre as materia_nombre,
						vw_hist_academica.actividad_nombre_abreviado as materia_nombre_red,
						to_char(vw_hist_academica.fecha, $formato_fecha_visual) as fecha_display,
						to_char(vw_hist_academica.fecha, $formato_fecha_orden) as fecha_orden,
						cast(null as integer) as comision, -- comision en cursadas
						vw_hist_academica.escala_nota,
						vw_hist_academica.nota,
						vw_hist_academica.nota_descripcion as nota_descripcion,
						-- sga_escalas_notas_concepto.descripcion as nota_concepto,
						vw_hist_academica.resultado as resultado,
						vw_hist_academica.resultado_descripcion as resultado_descripcion,
						CASE vw_hist_academica.origen  
						   WHEN 'P' THEN 'Promocion' 
						   WHEN 'A' THEN 'AprobRes' 
						   WHEN 'E' THEN (CASE vw_hist_academica.instancia
						                    WHEN 7 THEN 'Examen'
						                    ELSE vw_hist_academica.tipo
						                  END)  
						   ELSE vw_hist_academica.tipo 
						END	as origen,
						CASE vw_hist_academica.origen  
						   WHEN 'E' THEN (CASE vw_hist_academica.instancia
						                    WHEN 7 THEN 'Examen'
						                    ELSE vw_hist_academica.tipo
						                  END)  
						   ELSE vw_hist_academica.tipo 
						END	as origen_descripcion,
						CASE vw_hist_academica.origen 
						   WHEN 'B' THEN " . equivalencia::equiv_instancia_total . "
						   ELSE cast(null as integer) 
						END as equivalencia_instancia,
						1 as vigente, 	   -- vigente en cursadas
						cast(null as integer) as cond_regularidad, -- condicion de regularidad en cursadas
						vw_hist_academica.creditos as credito,
						CASE vw_hist_academica.origen
							WHEN 'E' THEN vw_hist_academica.nro_acta  -- Examen
							WHEN 'P' THEN vw_hist_academica.nro_acta  -- Promocion
							WHEN 'A' THEN vw_hist_academica.nro_resolucion_descripcion  -- Aprobacion x Resolución
							WHEN 'B' THEN vw_hist_academica.nro_resolucion_descripcion  -- Equivalncia total.
							ELSE '' 
						END as acta_resolucion,
						vw_hist_academica.plan_version,
						vw_hist_academica.id_acta as id_acta,
						vw_hist_academica.equivalencia,
						to_char(cast(null as date) , $formato_fecha_visual) as fin_vigencia_regul,
						-- Datos Inscripcion a cursada.
						cast(null as varchar(100))	as comision_nombre, 
						vw_hist_academica.anio_academico	as anio_academico, 
						cast(null as varchar(100))	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM vw_hist_academica
					LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = vw_hist_academica.elemento_revision
												AND sga_elementos_plan.plan_version = vw_hist_academica.plan_version
					WHERE vw_hist_academica.alumno =  $alumno
			
			UNION ALL
			
				SELECT		
						cast('Regularidad' as varchar(11)) as tipo, -- Regularidades 
						sga_elementos_plan.anio_de_cursada, -- Año de cursada definido en el plan
						sga_elementos_plan.periodo_de_cursada, -- Periodo lectivo de Cursada definido en el plan 
						EXTRACT(YEAR FROM vw_regularidades.fecha) as anio,
						vw_regularidades.elemento as materia,
						vw_regularidades.actividad_codigo as materia_codigo,
						vw_regularidades.actividad_nombre as materia_nombre,
						vw_regularidades.actividad_nombre_abreviado as materia_nombre_red,
						to_char(vw_regularidades.fecha, $formato_fecha_visual) as fecha_display,
						to_char(vw_regularidades.fecha, $formato_fecha_orden) as fecha_orden,
						vw_regularidades.comision,
						vw_regularidades.escala_nota,
						vw_regularidades.nota,
						vw_regularidades.nota_descripcion as nota_descripcion,
						-- sga_escala_notas_conceptos.descripcion as nota_concepto,
						vw_regularidades.resultado as resultado,
						vw_regularidades.resultado_descripcion as resultado_descripcion,
						CASE vw_regularidades.tipo 
						  WHEN 'Equivalencia de Regularidad' THEN 'Equivalencia'  
						  ELSE vw_regularidades.tipo
						END as origen,   
						vw_regularidades.tipo as origen_descripcion,   
						CASE vw_regularidades.tipo 
						   WHEN 'Equivalencia de Regularidad' THEN " . equivalencia::equiv_instancia_regularidad . "  
						   ELSE cast(null as integer) 
						END as equivalencia_instancia,
						vw_regularidades.es_vigente as vigente,
						vw_regularidades.cond_regularidad,
						vw_regularidades.creditos as credito,
						CASE vw_regularidades.origen
						  WHEN 'P' THEN cast(vw_regularidades.nro_acta as varchar(20)) -- Acta de Promocion
						  WHEN 'R' THEN cast(vw_regularidades.nro_acta as varchar(20)) -- Acta de Regulares
						  WHEN 'C' THEN vw_regularidades.nro_resolucion_descripcion  -- Equiv de Regularidad
						  ELSE '' 
						END as acta_resolucion,
						vw_regularidades.plan_version,
						vw_regularidades.id_acta as id_acta,
						vw_regularidades.equivalencia,
						to_char(vw_regularidades.fecha_vigencia, $formato_fecha_visual) as fin_vigencia_regul,
			
						cast(null as varchar(100))	as comision_nombre, 
						vw_regularidades.anio_academico as anio_academico, 
						vw_regularidades.periodo_lectivo_nombre	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM vw_regularidades
					LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = vw_regularidades.elemento_revision
												AND sga_elementos_plan.plan_version = vw_regularidades.plan_version
					WHERE vw_regularidades.alumno = $alumno
					  AND vw_regularidades.origen <> 'P' -- Las promociones las obtengo por la vista vw-hist_academica
								
			UNION ALL
			
				SELECT		
						cast('Eq. Parcial' as varchar(11)) as tipo, -- Equivalencias Parciales
						sga_elementos_plan.anio_de_cursada,         
						sga_elementos_plan.periodo_de_cursada,      
						EXTRACT(YEAR FROM vw_equiv_parciales.fecha) as anio,
						vw_equiv_parciales.elemento as materia,
						vw_equiv_parciales.actividad_codigo as materia_codigo,
						vw_equiv_parciales.actividad_nombre as materia_nombre,
						vw_equiv_parciales.actividad_nombre_abreviado as materia_nombre_red,
						to_char(vw_equiv_parciales.fecha, $formato_fecha_visual) as fecha_display,
						to_char(vw_equiv_parciales.fecha, $formato_fecha_orden) as fecha_orden,
						cast(null as integer) as comision,
						vw_equiv_parciales.escala_nota,
						vw_equiv_parciales.nota,
						vw_equiv_parciales.nota_descripcion as nota_descripcion,
						-- sga_escala_notas_conceptos.descripcion as nota_concepto,
						vw_equiv_parciales.resultado as resultado,
						vw_equiv_parciales.resultado_descripcion as resultado_descripcion,
						'Equivalencia' as origen,   
						vw_equiv_parciales.tipo as origen_descripcion,   
						" . equivalencia::equiv_instancia_parcial . " as equivalencia_instancia,
						vw_equiv_parciales.es_vigente as vigente,
						cast(null as integer) as cond_regularidad,
						sga_elementos_plan.creditos as credito,
						vw_equiv_parciales.nro_resolucion_descripcion as acta_resolucion,
						vw_equiv_parciales.plan_version,
						cast(null as integer) as id_acta,
						vw_equiv_parciales.equivalencia,
						to_char(vw_equiv_parciales.fecha_vigencia, $formato_fecha_visual) as fin_vigencia_regul,
			 
						cast(null as varchar(100))	as comision_nombre, 
						cast(null as integer) as  anio_academico, 
						cast(null as varchar(100))	as periodo_lectivo,
						cast(null as varchar(50))	as turno_cursada, 
						cast(null as varchar(100))	as catedra, 
						cast(null as varchar(50))	as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM vw_equiv_parciales
					LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = vw_equiv_parciales.elemento_revision
												AND sga_elementos_plan.plan_version = vw_equiv_parciales.plan_version
					WHERE vw_equiv_parciales.alumno = $alumno

				UNION ALL
				
				SELECT
						cast('En Curso' as varchar(11)) as tipo, -- En Curso
						sga_elementos_plan.anio_de_cursada,    -- Año de cursada definido en el plan
						sga_elementos_plan.periodo_de_cursada, -- Periodo lectivo de Cursada definido en el plan 
						sga_periodos.anio_academico as anio, 
						sga_comisiones.elemento as materia,
						sga_elementos.codigo as materia_codigo,
						sga_elementos_plan.nombre as materia_nombre,
						sga_elementos_plan.nombre_abreviado as materia_nombre_red,
						to_char(sga_periodos_lectivos.fecha_inicio_dictado, $formato_fecha_visual) as fecha_display,
						to_char(sga_periodos_lectivos.fecha_inicio_dictado, $formato_fecha_orden) as fecha_orden,
						sga_insc_cursada.comision,
						cast(null as int) as escala_notas, 
						cast(null as varchar(10)) as nota, 
						cast(null as varchar(100)) as nota_descripcion, 
						-- cast(null as varchar(15)) as nota_concepto, 
						cast(null as char(1)) as resultado, 
						cast(null as varchar(50)) as resultado_descripcion, 
						'EnCurso' as origen,
						'En Curso' as origen_descripcion,
						cast(null as integer) as equivalencia_instancia,
						cast(null as int) as vigente, 
						cast(null as int) as cond_regularidad,
						cast(null as decimal(8,2)) as credito, 
						cast(null as varchar(30)) as acta_resolucion,
						sga_insc_cursada.plan_version as plan_version,
						cast(null as int) as id_acta, 
						cast(null as int) as equivalencia,			
						to_char(cast(null as date) , $formato_fecha_visual) as fin_vigencia_regul,	
						sga_comisiones.nombre as comision_nombre,
						sga_periodos.anio_academico,
						sga_periodos.nombre as periodo_lectivo,
						-- sga_comisiones.turno as turno,
						sga_turnos_cursadas.descripcion as turno_cursada,
						sga_catedras.nombre as catedra,
						f_instancias_insc_cursada(sga_insc_cursada.inscripcion) as calidad_insc,
						'' as tipo_reconocimiento,
						cast(null as integer) as nro_tramite
					FROM sga_insc_cursada
					JOIN sga_comisiones ON sga_comisiones.comision = sga_insc_cursada.comision
					JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento
					JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
					                            sga_elementos_plan.plan_version      = sga_insc_cursada.plan_version)
					JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_cursada.estado
					JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
					JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
					LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = sga_comisiones.turno
					LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra
				WHERE	sga_insc_cursada.alumno  = $alumno
				AND sga_periodos.fecha_fin >= CURRENT_DATE  -- Comisiones actuales o futuras
			ORDER BY fecha_orden DESC
		";
		
		return guarani_db::consultar($sql, null, false);
	}
	
	
	/**
	 * Consulta si un alumno puede solicitar una readmisión por la perdida de regularidad en la propuesta.
	 * Solo puede solicitar en el siguiente caso:
	 *   - Esta como no regular y tienen una perdida de regularidad registrada (Año actual o algun año anterior)
	 *   - No tiene ninguna solicitud de readmision en el año actual (en cualquier estado: Pendiente/Aceptada/Rechazada)
	 *   - Que la pérdida de regularidad no tenga una readmisión vigente
	 *
	 * @param integer $alumno	# Id de Alumno
	 * @param integer $anio_academico	# año academico actual
	 * @return array			# Datos de la perdida de regularidad y alumno. Si devuelve datos significa que puede solicitar readmision.	
	 */
	
	function puede_solicitar_readmision($alumno, $anio_academico)
	{
		
		// Consulto si puede solicitar la readmision
		$sql = "SELECT	sga_alumnos.persona,
						sga_alumnos.alumno,
						sga_alumnos.regular,
						sga_alumnos.propuesta,
						sga_perdida_regularidad.perdida_regularidad,  -- Perdida de regularidad para solicitar readmision
						sga_perdida_regularidad.anio_academico as anio_academico_perdida_regularidad,
						$anio_academico as anio_academico_solicitud_readmision
				
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_propuestas_tipos ON sga_propuestas_tipos.propuesta_tipo = sga_propuestas.propuesta_tipo
				JOIN sga_perdida_regularidad ON sga_alumnos.alumno = sga_perdida_regularidad.alumno 
				
				WHERE 
				  -- Alumnos activos y regulares.
					  sga_alumnos.alumno = $alumno 
				  AND sga_alumnos.calidad = '".alumno::calidad_activo."' 
				  AND sga_alumnos.regular = '".comunes::no."' 
				
				  -- Propuesta donde se controla regularidad
				  AND sga_propuestas_tipos.control_regularidad = '".comunes::si."' 
				  
				  -- Última pérdida de regularidad en la propuesta.
				  AND sga_perdida_regularidad.fecha = (SELECT MAX(perdida.fecha)
														 FROM sga_perdida_regularidad as perdida
														WHERE perdida.alumno = sga_alumnos.alumno) 
				  
				  -- Que no tenga una solicitud de readmisión relacionada con esa pérdida de regularidad. No importa el estado (pendiente, rechazada)
				  AND NOT EXISTS (SELECT 1 FROM sga_readmisiones_solicitud 
				                   WHERE sga_readmisiones_solicitud.alumno = sga_alumnos.alumno 
								     AND sga_readmisiones_solicitud.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad
									 AND sga_readmisiones_solicitud.anio_academico = $anio_academico
								  )
				  -- Que la pérdida de regularidad no tenga una readmisión vigente (Se supone que si tiene readmisión esta como regular).
				  AND NOT EXISTS (SELECT 1 FROM sga_readmisiones 
				                   WHERE sga_readmisiones.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad
								     AND COALESCE(sga_readmisiones.fecha_vencimiento,CURRENT_DATE) >= CURRENT_DATE
						             AND COALESCE(sga_readmisiones.estado,'A') = 'A'
								  )
								  
        ";
		return guarani_db::consultar($sql);
	}
    
    function get_correlativas($elemento, $plan_version)
    {
        $sql = "
                SELECT
                    v.condicion_tipo as tipo_correlativa,
                    v.condicion_tipo_nombre as tipo_correlativa_nombre,
                    v.grupo_condicion as grupo_condicion,
                    v.grupo_orden as grupo_orden,
                    v.orden as requisito_orden,
                    CASE v.tipo
                      WHEN 'E' THEN v.elemento_codigo
                      WHEN 'R' THEN cast(v.requisito as varchar(255))
                    END as requisito_codigo,
                    CASE v.tipo
                      WHEN 'E' THEN v.elemento_nombre
                      WHEN 'R' THEN v.requisito_nombre
                    END as requisito_nombre,
                    tipo,
                    ec.entidad_subtipo,
                    operador_not,
                    CASE
                      WHEN parametros IS NULL THEN COALESCE(estado_nombre, requisito_nombre, get_regla_mensaje(regla, parametros))
                      ELSE COALESCE(estado_nombre, get_regla_mensaje(regla, parametros), requisito_nombre)
                    END as condicion
				FROM sga_elementos as e
				JOIN vw_condiciones as v ON v.condicion_entidad = e.entidad
				LEFT JOIN sga_elementos as ec ON (ec.entidad = v.entidad)
				WHERE e.elemento = $elemento
				  AND v.plan_version = $plan_version
		          AND v.condicion_tipo IN ('1', '2') -- Correlativas para cursar y rendir
				ORDER BY tipo_correlativa, grupo_orden, grupo_condicion, requisito_orden
            ";
        return guarani_db::consultar($sql);
    }

	/**
	 * Retorna las actividaes de las versiones de plan en las que estuvo o esta el alumno
	 * 
	 * @param integer $alumno
	 * @param integer $persona
	 * @param integer $propuesta
	 * @return array  // Listado de actividades
	*/
	function get_actividades_planes_versiones_alumno($alumno = null, $persona = null, $propuesta = null)
	{
		// Que no recupere nada por defecto.
		$where = " WHERE false "; 
		
		// Filtros de busqueda
		if (!is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$where = " WHERE sga_alumnos.alumno = $alumno";
		}
		if (!is_null($persona) && !is_null($propuesta)) {
			$persona   = toba::db()->quote($persona);
			$propuesta = toba::db()->quote($propuesta);
			$where = " WHERE sga_alumnos.persona = $persona AND sga_alumnos.propuesta = $propuesta ";
		}
		
		// Evaluaciones parciales.
		$sql = "SELECT DISTINCT elemento, nombre, codigo
				  FROM vw_actividades_plan
				 WHERE plan_version IN (SELECT sga_alumnos_hist_planes.plan_version 
										FROM sga_alumnos
										JOIN sga_alumnos_hist_planes ON sga_alumnos_hist_planes.alumno = sga_alumnos.alumno
										$where
									  )
				ORDER BY nombre";
		
		return guarani_db::consultar($sql);
	}

	
	/**
	 * Retorna las evaluaciones parciales del alumno. 
	 * Reporte de Evaluaciones parciales de la Ficha del Alumno
	 * 
	 * @param integer $persona
	 * @param integer $propuesta
	 * @param integer $actividad 
	 * @return array  // Datos de las evaluaciones parciales
	*/
	function get_evaluaciones_parciales($persona, $propuesta, $actividad = null, $anio_academico = null)
	{
		$persona = toba::db()->quote($persona);

		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$no = toba::db()->quote(comunes::no);

                $where_actividad = "";
		// Filtros de busqueda
		if (!is_null($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$where_actividad = " AND sga_comisiones.elemento = $actividad";
		}

                $where_anio_academico = "";
		if (!is_null($anio_academico)) {
			$anio_academico = toba::db()->quote($anio_academico);
			$where_anio_academico = " AND sga_periodos.anio_academico = $anio_academico";
		}
		
		// Evaluaciones parciales.
		$sql = "SELECT
						sga_propuestas.propuesta as propuesta, 
						sga_propuestas.nombre as propuesta_nombre, 
						sga_propuestas.codigo as propuesta_codigo, 
						COALESCE(vw_actividades_plan.nombre, sga_elementos.nombre) as actividad_nombre,
						sga_elementos.codigo as actividad_codigo,
						sga_comisiones.comision as comision,
						sga_comisiones.nombre as comision_nombre,
						sga_periodos.anio_academico as anio_academico,
						sga_periodos.nombre as periodo_lectivo_nombre,
						to_char(sga_evaluaciones.fecha, $formato_fecha_visual) as evaluacion_fecha_display,
						sga_evaluaciones.fecha as evaluacion_fecha,
						sga_evaluaciones.evaluacion as evaluacion,
						sga_evaluaciones.nombre as evaluacion_nombre,
						sga_evaluaciones.evaluacion_tipo as evaluacion_tipo,
						sga_evaluaciones_tipos.nombre as evaluacion_tipo_desc,
						sga_evaluaciones.promediable as promediable,
						CASE sga_evaluaciones.promediable WHEN 'S' THEN 'SI' WHEN 'N' THEN 'NO' END as promediable_desc,
						sga_evaluaciones.estado as evaluacion_estado,
						sga_actas_estados.nombre as evaluacion_estado_desc,
						sga_eval_detalle.nota as nota,
						sga_escalas_notas_det.descripcion as nota_desc,
						sga_eval_detalle.resultado as resultado,
						sga_escalas_notas_resultado.descripcion as resultado_desc,
						sga_eval_detalle.corregido_por as corregido_por,
						sga_eval_detalle.observaciones as observaciones
		
				FROM	sga_eval_detalle 
				JOIN	sga_propuestas ON sga_propuestas.propuesta = $propuesta
				JOIN	sga_evaluaciones ON sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion 
				JOIN	sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN	sga_comisiones ON sga_comisiones.entidad = sga_evaluaciones.entidad
				JOIN	sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
				JOIN	sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				JOIN	sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				JOIN	sga_actas_estados ON sga_actas_estados.estado = sga_evaluaciones.estado
				JOIN	sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle.resultado
				LEFT JOIN sga_insc_cursada ON (sga_insc_cursada.comision = sga_comisiones.comision AND sga_insc_cursada.alumno = sga_eval_detalle.alumno)
				LEFT JOIN vw_actividades_plan ON (vw_actividades_plan.elemento = sga_comisiones.elemento AND vw_actividades_plan.plan_version = sga_insc_cursada.plan_version)
				LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota AND sga_escalas_notas_det.nota = sga_eval_detalle.nota)
				
				WHERE sga_eval_detalle.alumno = (SELECT a.alumno FROM sga_alumnos as a WHERE a.persona = $persona AND a.propuesta = $propuesta)
				  AND sga_eval_detalle.rectificado = $no 
				  AND sga_evaluaciones_tipos.automatica = $no
				  $where_actividad
				  $where_anio_academico
				  
				ORDER BY propuesta_nombre, anio_academico, actividad_nombre, evaluacion_fecha		
		";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 *  Retorna las Responsables Académicas asociadas a un alumno.
	 *	@param integer $alumno Id del alumno
	 */
	function get_ras_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT 	sga_propuestas_ra.responsable_academica
				FROM 	sga_propuestas_ra 
				JOIN 	sga_alumnos ON (sga_alumnos.propuesta = sga_propuestas_ra.propuesta)
				WHERE 	sga_alumnos.alumno = $alumno
                        ;";
		$datos = guarani_db::consultar($sql);
		return array_column($datos, 'responsable_academica');
	}
	
	
	
	/**
	 * Verifica si el alumno tiene una licencia estudiantil en una fecha dada
	 *
	 * @param integer	$alumno
	 * @param date		$fecha_examen
	 * @param integer	$anio_academico_examen
	 * @return string	# Retorna un texto con los datos de la licencia para la fecha del examen.
	 */
	function tiene_licencia_estudiantil_examen($alumno, $fecha_examen, $anio_academico_examen)
	{
		$fecha_examen = toba::db()->quote($fecha_examen);
		
		$sql = "SELECT CASE sga_licencias.alcance 
		                WHEN 1 THEN 'desde ' || to_char(sga_licencias.fecha_desde,'DD/MM/YYYY') || ' hasta ' || to_char(sga_licencias.fecha_hasta,'DD/MM/YYYY')
		                WHEN 2 THEN 'desde el período lectivo ' || pl.nombre || ' de ' || pl.anio_academico::text || ' y por ' || sga_licencias.cant_periodos_lectivos::text || ' períodos lectivos consecutivos'
                       END as licencia_desde_hasta						
		          FROM sga_alumnos 
				  JOIN sga_licencias ON sga_licencias.persona = sga_alumnos.persona
				  JOIN sga_licencias_propuestas ON (sga_licencias_propuestas.licencia = sga_licencias.licencia AND 
				                                    sga_licencias_propuestas.propuesta = sga_alumnos.propuesta)
				  LEFT JOIN vw_periodos_lectivos as pl ON pl.periodo_lectivo = sga_licencias.periodo_lectivo_desde
				  WHERE sga_alumnos.alumno = $alumno
				    AND sga_licencias.estado = 'Aceptado'
				    AND sga_licencias.puede_rendir_examen = 'N'
				    AND ((alcance = 1 AND $fecha_examen BETWEEN sga_licencias.fecha_desde AND sga_licencias.fecha_hasta) OR	-- Rango de fechas
				         (alcance = 2 AND $fecha_examen BETWEEN pl.fecha_inicio AND get_fecha_fin_licencia_pl(sga_licencias.periodo_lectivo_desde, sga_licencias.cant_periodos_lectivos))
				        )	
				LIMIT 1
				";  
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Controla que el alumno no tenga una licencia estudiantil que se solape con el periodo lectivo de la comision pasada por parámetro.
	 * Si lo encuentra falla el control y devuelve el dato del tiempo de la licencia..
	 *
	 * @param integer	$alumno
	 * @param integer	$comision
	 * @return string	# Retorna un texto con los datos de la licencia que se solapa con el periodo lectivo de la comision.
	 */
	function tiene_licencia_estudiantil_cursada($alumno, $comision)
	{
		$sql = "SELECT CASE sga_licencias.alcance 
		                WHEN 1 THEN 'desde ' || to_char(sga_licencias.fecha_desde,'DD/MM/YYYY') || ' hasta ' || to_char(sga_licencias.fecha_hasta,'DD/MM/YYYY')
		                WHEN 2 THEN 'desde el período lectivo ' || pl_lic.nombre || ' de ' || pl_lic.anio_academico::text || ' y por ' || sga_licencias.cant_periodos_lectivos::text || ' períodos lectivos consecutivos'
                       END as licencia_desde_hasta						
		          FROM sga_alumnos 
				  JOIN sga_licencias ON sga_licencias.persona = sga_alumnos.persona
				  JOIN sga_licencias_propuestas ON (sga_licencias_propuestas.licencia = sga_licencias.licencia AND sga_licencias_propuestas.propuesta = sga_alumnos.propuesta)
				  -- Comision en la que se inscribe											
				  JOIN sga_comisiones ON sga_comisiones.comision = $comision
				  JOIN sga_periodos_lectivos as pl_com ON pl_com.periodo_lectivo = sga_comisiones.periodo_lectivo 
				  JOIN sga_periodos as p_com ON p_com.periodo = pl_com.periodo
				  -- Periodo lectivo de la licencia
				  LEFT JOIN vw_periodos_lectivos as pl_lic ON pl_lic.periodo_lectivo = sga_licencias.periodo_lectivo_desde
				  
				  WHERE sga_alumnos.alumno = $alumno
				    AND sga_licencias.estado = 'Aceptado'
				    AND ((alcance = 1 AND -- Rango de Fechas
					       (p_com.fecha_inicio BETWEEN sga_licencias.fecha_desde AND sga_licencias.fecha_hasta OR 
					        p_com.fecha_fin BETWEEN sga_licencias.fecha_desde AND sga_licencias.fecha_hasta OR 
					        (p_com.fecha_inicio < sga_licencias.fecha_desde AND p_com.fecha_fin > sga_licencias.fecha_hasta) OR 
					        (sga_licencias.fecha_desde < p_com.fecha_inicio AND sga_licencias.fecha_hasta > p_com.fecha_fin)
						   ) 
						  ) OR
				         (alcance = 2 AND -- Licencia por n periodos lectivos
						  (sga_comisiones.periodo_lectivo = sga_licencias.periodo_lectivo_desde OR 
						   p_com.fecha_inicio BETWEEN pl_lic.fecha_inicio AND get_fecha_fin_licencia_pl(sga_licencias.periodo_lectivo_desde, sga_licencias.cant_periodos_lectivos) OR 
						   p_com.fecha_fin BETWEEN pl_lic.fecha_inicio AND get_fecha_fin_licencia_pl(sga_licencias.periodo_lectivo_desde, sga_licencias.cant_periodos_lectivos) OR 
						   pl_lic.fecha_inicio BETWEEN p_com.fecha_inicio AND p_com.fecha_fin OR 
						   get_fecha_fin_licencia_pl(sga_licencias.periodo_lectivo_desde, sga_licencias.cant_periodos_lectivos) BETWEEN p_com.fecha_inicio AND p_com.fecha_fin 
						   )
						 )	
				        )	
				LIMIT 1
				";  

		return guarani_db::consultar_fila($sql);
	}
	
	function get_alumnos_por_persona($persona)
    {
        $sql = "
                SELECT 
                    vw_alumnos.alumno,
                    vw_alumnos.propuesta,
                    vw_alumnos.propuesta_codigo,
                    vw_alumnos.propuesta_nombre,
                    '(' || vw_alumnos.propuesta_codigo || ') ' || vw_alumnos.propuesta_nombre as propuesta_desc                    
                FROM 
                    vw_alumnos
                JOIN sga_propuestas ON sga_propuestas.propuesta = vw_alumnos.propuesta
                    
                WHERe 
                    vw_alumnos.persona = $persona
                    AND sga_propuestas.propuesta_tipo <> ".propuesta::tipo_curso." AND sga_propuestas.propuesta_tipo <> ".propuesta::tipo_vocacional
                ;
        return guarani_db::consultar($sql);
    }
    
    function get_porcentaje_avance_propuesta($alumno)
    {
        $sql = "
                SELECT
                    certificado,
                    certificado_nombre,
                    certificado_nivel,
                    orientacion,
                    orientacion_nombre,
                    orientacion_elegida,
                    porcentaje_avance,
                    cant_aprobadas
                FROM
                    get_porcentaje_avance_propuesta($alumno, 'TODOS')
                ";
        return guarani_db::consultar($sql);
    }
    
    /**
     * La operacion recibe dos parametros, el filtro= where y el columnas opcionales, que son aquellos filtros seleccionados en la operacion
     * dependiendo de lo que trae este arreglo va armando el select para mostrar, ademas si no requiere saber informacion de promedio se agiliza
     * la consulta quitando el JOIN Lateral.
     * TICKET #42918
     * @param type $where
     * @param type $columnas_opcionales['valor'=>'fecha_ingreso','metodo'=>'get_columna_dinamica_fecha_ingreso()']
     * @param type $filtros Variable que almacena los filtros que requieren promedio
     * @return type
     */
	function get_reporte_alumnos($where='', $columnas_opcionales = null, $calcular_promedio = false)
	{
		//Columnas opcionales que requieren del calculo del promedio para ser vistas en el reporte
		$requieren_promedio = ['fecha_ultimo_examen','promedio','promedio_sin_aplazos','cant_actividades_aprobadas','cant_actividades_plan'];
		
		$col_optativas_dinamicas_SELECT = '1';
		$col_optativas_dinamicas_FROM = '';
		$col_optativas_dinamicas_WHERE = '';
        //Variable que se usa para incorporar el JOIN LATERAL con la funcion de calcular el promedio
		$join_lateral = '';
                
        foreach ($columnas_opcionales as $col) {
			//pregunto si las columnas opcionales que estan seleccionadas en la operación requieren del calculo del promedio
			if($calcular_promedio == false && in_array($col['valor'],$requieren_promedio)){
				$calcular_promedio = true;
			}
			//De esta forma invoco al metodo que trae la variable columnas_opcionales ejemplo: get_columna_dinamica_fecha_ingreso()
			$metodo = $col['metodo'];		
			$this->$metodo($col_optativas_dinamicas_SELECT,$col_optativas_dinamicas_FROM,$col_optativas_dinamicas_WHERE);
			
		}
        
        if($calcular_promedio){
            $sql = "
				-- Actividades realizadas por el alumno
				CREATE TEMP TABLE _Tactividades_aux (elemento integer,id_acta integer,origen char(1), equivalencia_tramite integer, equivalencia integer, fecha date, escala_nota integer, nota varchar(10), resultado char(1), promediable char(1));
				CREATE TEMP TABLE _Tactividades (elemento integer,id_acta integer,origen char(1), equivalencia_tramite integer, equivalencia integer, fecha date, escala_nota integer, nota varchar(10), resultado char(1), promediable char(1));
  
				-- Propuesta del alumno (Incluye propuestas vinculadas)
				CREATE TEMP TABLE _temp_prop_alumno (alumno integer, propuesta integer, plan integer, plan_version integer);
				CREATE TEMP TABLE _temp_relacion (relacion integer); -- Tabla de propuestas relacionadas";
		
            guarani_db::ejecutar($sql);
        
            $join_lateral = " JOIN LATERAL get_promedios_actividades_alumno(sga_alumnos.alumno, sga_alumnos.plan_version, false) as f ON true ";
            
        }
        
        
		if ($where) {
			$where = " WHERE $where";
		}		
		
        $order_by = " ORDER BY	
					vw_planes.propuesta_nombre,
					sga_ubicaciones.nombre,
					vw_personas.apellido,
					vw_personas.nombres ";
		
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
					vw_planes.propuesta_nombre as propuesta_nombre,
					vw_planes.propuesta_codigo as propuesta_codigo,
					'(' || vw_planes.propuesta_codigo || ') ' || vw_planes.propuesta_nombre AS propuesta_descr,
					vw_planes.plan_nombre    as  plan_nombre,
					vw_planes.plan_codigo    as plan_codigo,
					vw_planes.version_nombre as plan_version_nombre,
					vw_planes.version_codigo as plan_version_codigo,
					vw_planes.plan_version_actual as plan_version_actual,
					sga_alumnos.ubicacion,
					sga_propuestas_aspira.anio_academico as anio_academico_ingreso,
					sga_situacion_aspirante.nombre as nombre_estado_inscripcion,
					sga_situacion_aspirante.situacion_asp,					
					$col_optativas_dinamicas_SELECT
                    
				FROM		
					sga_alumnos
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN mdp_personas ON mdp_personas.persona = vw_personas.persona
					JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
					JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp AND sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."', '".propuesta::inscripcion_pendiente."', '".propuesta::inscripcion_rechazada."'))
					JOIN vw_planes ON vw_planes.plan_version = sga_alumnos.plan_version
					JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion 
					$col_optativas_dinamicas_FROM
					$join_lateral
				$where
				$col_optativas_dinamicas_WHERE
				$order_by
		";
        
		$datos = guarani_db::consultar($sql);
		
        //Elimino las tablas temporales
        if($calcular_promedio){
            $sql = "
				-- Borro tablas temporales.
				DROP TABLE _Tactividades;
				DROP TABLE _Tactividades_aux;
				DROP TABLE _temp_prop_alumno;
				DROP TABLE _temp_relacion;
				";
            guarani_db::ejecutar($sql);
        }
		
		return $datos;
		
	}
		
	/*
	 * El siguiente metodo va agregando logica a la consulta.
	 * Recibe por referencia las variables SELECT, FROM y WHERE
	 * Select: concatena un string donde almacena los select necesarios de las columnas opcionales seleccionadas por el usuario en el reporte
	 * From: Si precisamos de una tabla que aparezca en el reporte, podemos concatenarla como el ejemplo dentro de este metodo
	 * Where: Podemos hacer uso de esta variable para condicionar la Query
	 * Los siguientes metodos tienen la misma logica :
	 * - get_columna_dinamica_ultima_reinscripcion
	 * - get_columna_dinamica_fecha_ultimo_examen
	 * - get_columna_dinamica_nro_telefono
	 * - get_columna_dinamica_email
	 * - get_columna_dinamica_promedio
	 * - get_columna_dinamica_promedio_sin_aplazos
	 * - get_columna_dinamica_cant_actividades_aprobadas
	 * - get_columna_dinamica_fecha_nacimiento
	 * - get_columna_dinamica_cant_actividades_plan
	 * - get_columna_dinamica_pais_origen
	 * - get_columna_dinamica_coeficiente
	 */
	function get_columna_dinamica_fecha_ingreso(&$select, &$from,&$where)
	{
		$select_metodo = " TO_CHAR(get_fecha_ingreso_alumno(sga_alumnos.alumno),'DD/MM/YYYY') as fecha_ingreso ";
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
		
		/* Dejo comentado las siguientes asignaciones por si se quiere usar.
			$from_metodo ='';
			$from	= ($from == '') ? $from_metodo : $from . $from_metodo;
		
			$where_metodo ='';
			$where	= ($where == '') ? $where_metodo : $where . $where_metodo;
		 */
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_ultima_reinscripcion(&$select, &$from,&$where)
	{
		$select_metodo = " (SELECT r.anio_academico FROM sga_reinscripciones as r 
					  WHERE r.alumno = sga_alumnos.alumno ORDER BY r.anio_academico DESC   LIMIT 1
					 ) as ultima_reinscripcion ";
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;	
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_fecha_ultimo_examen(&$select, &$from,&$where)
	{
		$select_metodo = " TO_CHAR(f.fecha_ultimo_examen,'DD/MM/YYYY') as fecha_ultimo_examen " ;
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_nro_telefono(&$select, &$from,&$where)
	{
		$tel_cel =  toba::db()->quote(persona::tel_celular);
		$tel_fam =  toba::db()->quote(persona::tel_familiar);
		$tel_fijo =  toba::db()->quote(persona::tel_fijo);
		$tel_lab =  toba::db()->quote(persona::tel_laboral);
		$otro =  toba::db()->quote(persona::otro_contacto);
		
		$select_metodo = " 
						COALESCE((
								SELECT (mdp_personas_contactos.telefono_codigo_area || '|' ||mdp_personas_contactos.telefono_numero)
								FROM mdp_personas_contactos
								WHERE mdp_personas_contactos.persona = vw_personas.persona
								AND contacto_tipo IN ($tel_cel, $tel_fam, $tel_fijo, $tel_lab)
								ORDER BY contacto_tipo = $tel_cel DESC, contacto_tipo = $tel_fam DESC, contacto_tipo = $tel_fijo DESC, contacto_tipo = $tel_lab DESC , contacto_tipo = $otro DESC
								LIMIT 1)) AS nro_telefono " ;
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_email(&$select, &$from,&$where)
	{
		$retorna = [];
		
		$mail_prim =  toba::db()->quote(persona::mail_primario);
		$mail_sec  =  toba::db()->quote(persona::mail_secundario);
		$mail_inst =  toba::db()->quote(persona::mail_institucional);
		
		$select_metodo = " COALESCE((
									SELECT mdp_personas_contactos.email
									FROM mdp_personas_contactos
									WHERE mdp_personas_contactos.persona = vw_personas.persona
									AND contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
									ORDER BY contacto_tipo = $mail_prim DESC, contacto_tipo = $mail_sec DESC, contacto_tipo = $mail_inst DESC
									LIMIT 1)) AS email " ;
		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;		
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_promedio(&$select, &$from,&$where)
	{
		$select_metodo = " f.promedio " ;		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_promedio_sin_aplazos(&$select, &$from,&$where)
	{
		$select_metodo = " f.promedio_sin_aplazos " ;		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_cant_actividades_aprobadas(&$select, &$from,&$where)
	{
		$select_metodo = " f.cant_actividades_aprobadas ";		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_fecha_nacimiento(&$select, &$from,&$where)
	{
		$select_metodo = " mdp_personas.fecha_nacimiento ";
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_cant_actividades_plan(&$select, &$from,&$where)
	{
		$select_metodo = " f.cant_actividades_plan ";
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;		
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_pais_origen(&$select, &$from,&$where)
	{
		$select_metodo = " (SELECT p.nombre FROM mug_paises as p 
					  WHERE p.pais = mdp_personas.pais_origen LIMIT 1
					 ) as nombre_pais ";		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_fecha_egreso(&$select, &$from,&$where)
	{
		$select_metodo = " (SELECT to_char(fecha_egreso, 'DD/MM/YYYY')
							FROM sga_certificados_otorg
							WHERE sga_certificados_otorg.alumno = sga_alumnos.alumno
							AND sga_certificados_otorg.certificado = certificado --Este campo viene del filtro titulo_obtenido
							AND sga_certificados_otorg.anulado = 0
							) as fecha_egreso";		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}
	
	/*
	 * Este metodo es utilizado exclusivamente en el Reporte de alumnos
	 */
	function get_columna_dinamica_coeficiente(&$select, &$from,&$where)
	{
		$select_metodo = " cast(sga_alumnos.coeficiente as DECIMAL(8,3)) ";		
		$select = ($select == '1') ? $select_metodo : $select . ' , ' . $select_metodo;
	}

	function get_campos_con_legajo_sin_legajo()
	{
		return [
			['valor' => comunes::si, 'descr' => legajos::combo_con_legajo_descr],
			['valor' => comunes::no, 'descr' => legajos::combo_sin_legajo_descr]
		];
	}
	
	function get_certificados_obtenidos($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$formato_fecha_visual = toba::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT
					sga_certificados.certificado as certificado,
					sga_certificados.codigo,
					sga_certificados.nombre,
					tn.descripcion as nivel_titulo,
					to_char(sga_certificados_otorg.fecha_inicio_tramite, $formato_fecha_visual) as fecha_inicio_tramite,
					to_char(sga_certificados_otorg.fecha_egreso, $formato_fecha_visual) as fecha_egreso,
					sga_certificados_otorg.promedio as promedio_con_aplazos,
					sga_certificados_otorg.promedio_sin_aplazos as promedio_sin_aplazos,
					mce_estados.descripcion as estado_actual_tramite
				FROM sga_certificados_otorg
				JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg.estado
				JOIN sga_certificados ON sga_certificados.certificado = sga_certificados_otorg.certificado
				LEFT JOIN sga_titulos_niveles as tn ON tn.titulo_nivel = sga_certificados.titulo_nivel
					
				WHERE sga_certificados_otorg.alumno = {$alumno}
				  AND sga_certificados_otorg.anulado = 0
				ORDER BY  sga_certificados.titulo_nivel 
				";

		$datos_titulos = guarani_db::consultar($sql, PDO::FETCH_ASSOC);
		
		return $datos_titulos;
	}
	
	/**
	 * Retorna los datos necesarios para el reporte de transacciones según la condición pasada como parámetro 
	 * 
	 * @param string $where
	 * @return type
	 */
	function get_listado_reporte_ingresos_autogestion($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 
					COALESCE( p.apellido_elegido, p.apellido ) || ', ' || COALESCE( p.nombres_elegido, p.nombres ) as apellido_nombres,
					p.persona,
					l.*,
                    l.ingreso as fecha_desde,
                    l.egreso as fecha_egreso,
					l.ip,
					l.login_por,
					CASE
						WHEN l.ingreso IS NULL THEN '-'
                        WHEN l.egreso IS NULL THEN '-'
                        WHEN l.ingreso > l.egreso THEN '-'
						ELSE round(EXTRACT(Epoch FROM ((l.egreso::TIMESTAMP - l.ingreso::TIMESTAMP)))/60) || ' minutos'
					END as duracion
				FROM aud_login_autogestion as l
				JOIN mdp_personas as p ON p.persona = l.persona
				WHERE true
				$where
				ORDER BY l.ingreso	
			";
		return guarani_db::consultar($sql);
	}
    
    /**
     * Listado de alumnos de una propuesta que estan en condiciones de cursar una actividad o de rendir examen una actividad.
     * Usado en el reporte "Alumnos en condiciones de cursar/rendir una actividad"
     *
     * @param string $where
     */
	function get_listado_alumnos_en_condiciones_de_cursar_rendir_una_actividad($elemento, $where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$elemento = toba::db()->quote($elemento);

		$mail_prim = toba::db()->quote(persona::mail_primario);
		$mail_sec = toba::db()->quote(persona::mail_secundario);
		$mail_inst = toba::db()->quote(persona::mail_institucional);

		$tel_cel = toba::db()->quote(persona::tel_celular);
		$tel_fam = toba::db()->quote(persona::tel_familiar);
		$tel_fijo = toba::db()->quote(persona::tel_fijo);
		$tel_lab = toba::db()->quote(persona::tel_laboral);
		$otro = toba::db()->quote(persona::otro_contacto);

		$orden_tipo_email = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();

		$sql = "SELECT		vw_personas.apellido_nombres as apellido_nombres,
					vw_personas.tipo_nro_documento as nro_identificacion,
					sga_alumnos.legajo as legajo,
					sga_alumnos.alumno as alumno,
					sga_alumnos.persona as persona,
					sga_alumnos.plan_version,
					sga_alumnos.ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					(SELECT	mdp_personas_contactos.email
					FROM	mdp_personas_contactos
					WHERE	mdp_personas_contactos.persona = sga_alumnos.persona AND
						mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst) AND
						mdp_personas_contactos.email IS NOT NULL
					ORDER BY $orden_tipo_email
					LIMIT	1
					) as email,
					(SELECT	mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero
					FROM	mdp_personas_contactos
					WHERE	mdp_personas_contactos.persona = sga_alumnos.persona AND
						mdp_personas_contactos.contacto_tipo IN ($tel_cel, $tel_fam, $tel_fijo, $tel_lab, $otro)
					ORDER BY $orden_tipo_telefono
					LIMIT	1
					) as telefono 
				FROM	sga_alumnos
				JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN	sga_propuestas_aspira ON (sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta)
				JOIN	sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				JOIN	sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
				WHERE	sga_alumnos.calidad = '".alumno::calidad_activo."'
				  AND	sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."') 
				  AND	1 NOT IN (SELECT	1
							FROM	vw_hist_academica_basica
							WHERE	vw_hist_academica_basica.alumno = sga_alumnos.alumno AND
								vw_hist_academica_basica.elemento = $elemento AND
								vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'
						)
				$where
				ORDER BY	apellido_nombres
		";

		return guarani_db::consultar($sql);
	}

    //Carga el compo utilizado en la operacion Alomnos condiciones de cursar/rendir
    function get_opciones_para() {
        return array(array('valor' => alumno::cursar_rendir_cursar_valor, 'descr' => alumno::cursar_rendir_cursar_descr),
            array('valor' => alumno::cursar_rendir_rendir_valor, 'descr' => alumno::cursar_rendir_rendir_descr)
        );
    }

    //Carga el compo utilizado en la operacion Alomnos condiciones de cursar/rendir
    //Recibe la clave del combo PARA donde trae el valor => cursar o rendir
    function get_opciones_alumno_cond($para = null) {

        if ($para == 'c') {
            return array(array('valor' => alumno::cursar_rendir_todos_valor, 'descr' => alumno::cursar_rendir_todos_descr),
                array('valor' => alumno::cursar_rendir_c_primera_vez_valor, 'descr' => alumno::cursar_rendir_c_primera_vez_descr),
                array('valor' => alumno::cursar_rendir_recursante_valor, 'descr' => alumno::cursar_rendir_recursante_descr)
            );
        } else {
            return array(array('valor' => alumno::cursar_rendir_todos_valor, 'descr' => alumno::cursar_rendir_todos_descr),
                array('valor' => alumno::cursar_rendir_r_primera_vez_valor, 'descr' => alumno::cursar_rendir_r_primera_vez_descr)
            );
        }
    }

    /**
     * Devuelve una lista de alumnos pasivos en un período de tiempo con historial de cambios de calidad para el ENDPOINT GET /alumnos-pasivos.
     *
     * @param string $fecha_desde Obligatorio.
     * @param string $fecha_hasta Obligatorio.
     * @param string $persona Opcional, por defecto vacío.
     * @param string $propuesta Opcional, por defecto vacío.
     * @param string $plan Opcional, por defecto vacío.
     * @param string $plan_cobrable Opcional, por defecto vacío.
     * @param string $nro_transaccion Opcional, por defecto vacío.
     * @param string $motivo_calidad Opcional, por defecto vacío.
     *
     * @return array Alumnos pasivos en un período de tiempo con historial de cambios de calidad para el ENDPOINT GET /alumnos-pasivos.
     */
    function get_listado_alumnos_pasivos_periodo_rest(
        $fecha_desde,
        $fecha_hasta,
        $persona = '',
        $propuesta = '',
        $plan = '',
        $plan_cobrable = '',
        $nro_transaccion = '',
        $motivo_calidad = ''
    )
    {

        $where_persona = "";
        // Si filtra por persona
        if (!empty($persona)) {
            $where_persona = "AND sga_alumnos.persona = $persona";
        }

        $where_propuesta = "";
        // Si filtra por propuesta
        if (!empty($propuesta)) {
            $where_propuesta = "AND sga_propuestas.propuesta = {$propuesta}";
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

        $where_nro_transaccion = "";
        // Si filtra por nro_transaccion
        if (!empty($nro_transaccion)) {
            $where_nro_transaccion = "AND sga_propuestas_aspira.nro_transaccion = {$nro_transaccion}";
        }

        $where_motivo_calidad = "";
        // Si filtra por motivo_calidad
        if (!empty($motivo_calidad)) {
            $where_motivo_calidad = "AND sga_alumnos_motivos_calidad.motivo_calidad = {$motivo_calidad}";
        }

        $formato_fecha_bd = comunes::formato_fecha_bd;
        $inscripcion_aceptada = propuesta::inscripcion_aceptada;
        $inscripcion_pendiente = propuesta::inscripcion_pendiente;
        $calidad_activo = alumno::calidad_activo;
        $calidad_pasivo = alumno::calidad_pasivo;

        $sql = "
            SELECT
                sga_alumnos.persona,
                sga_propuestas.propuesta,
                sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
                sga_planes.plan,
                sga_planes.cobrable AS plan_cobrable,
                sga_propuestas_aspira.nro_transaccion,
                sga_alumnos_hist_calidad.calidad,
                -- Recupero el siguiente cambio a activo si existe
                sga_alumnos_hist_calidad.fecha AS fecha_pasivo,
                (
                    SELECT hist_calidad_subquery2.fecha
                    FROM sga_alumnos_hist_calidad AS hist_calidad_subquery2
                    WHERE hist_calidad_subquery2.calidad = '{$calidad_activo}'
                    AND hist_calidad_subquery2.alumno = sga_alumnos_hist_calidad.alumno
                    AND hist_calidad_subquery2.fecha > sga_alumnos_hist_calidad.fecha
                    ORDER BY hist_calidad_subquery2.fecha
                    LIMIT 1
                ) AS fecha_activo,
                sga_alumnos_motivos_calidad.motivo_calidad,
                sga_alumnos_motivos_calidad.nombre AS motivo_calidad_nombre,
                sga_alumnos_motivos_calidad.descripcion AS motivo_calidad_descripcion
            FROM
                sga_alumnos
                JOIN sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
                JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
                JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp)
                JOIN sga_planes_versiones ON (sga_planes_versiones.plan_version = sga_alumnos.plan_version)
                JOIN sga_planes ON (sga_planes.plan = sga_planes_versiones.plan)
                JOIN sga_alumnos_hist_calidad ON (sga_alumnos_hist_calidad.alumno = sga_alumnos.alumno)
                JOIN sga_alumnos_motivos_calidad ON (sga_alumnos_hist_calidad.motivo_calidad = sga_alumnos_motivos_calidad.motivo_calidad)

            WHERE
                sga_situacion_aspirante.resultado_asp IN ('{$inscripcion_aceptada}', '{$inscripcion_pendiente}')
                AND sga_alumnos_hist_calidad.calidad = '{$calidad_pasivo}'
                AND DATE(sga_alumnos_hist_calidad.fecha) <= TO_DATE('{$fecha_hasta}', '{$formato_fecha_bd}')  -- Menor o igual a la fecha hasta
                -- No se informan los cambios de calidad pasivos si existe un cambio de calidad activo antes de la fecha desde
                AND NOT EXISTS (
                    SELECT 1
                    FROM sga_alumnos_hist_calidad AS hist_calidad_subquery
                    WHERE hist_calidad_subquery.calidad = '{$calidad_activo}'
                    AND hist_calidad_subquery.alumno = sga_alumnos_hist_calidad.alumno
                    AND hist_calidad_subquery.fecha >= sga_alumnos_hist_calidad.fecha
                    AND DATE(hist_calidad_subquery.fecha) <= TO_DATE('{$fecha_desde}', '{$formato_fecha_bd}')
                )
                $where_persona
                $where_propuesta
                $where_plan
                $where_plan_cobrable
                $where_nro_transaccion
                $where_motivo_calidad
            ORDER BY nro_transaccion, fecha_pasivo;
        ";

        $alumnos_pasivos = guarani_db::consultar($sql);

        $rs = [];
        // Hago un corte de control por 'nro_transaccion'
        foreach ($alumnos_pasivos as $alumno) {
            $rs[$alumno['nro_transaccion']]['persona'] = $alumno['persona'];
            $rs[$alumno['nro_transaccion']]['propuesta'] = $alumno['propuesta'];
            $rs[$alumno['nro_transaccion']]['propuesta_nombre_abreviado'] = $alumno['propuesta_nombre_abreviado'];
            $rs[$alumno['nro_transaccion']]['plan'] = $alumno['plan'];
            $rs[$alumno['nro_transaccion']]['plan_cobrable'] = $alumno['plan_cobrable'];
            $rs[$alumno['nro_transaccion']]['nro_transaccion'] = $alumno['nro_transaccion'];

            $rs[$alumno['nro_transaccion']]['historial'][] = [
                'fecha_pasivo' => $alumno['fecha_pasivo'],
                'fecha_activo' => $alumno['fecha_activo'],
                'motivo_calidad' => $alumno['motivo_calidad'],
                'motivo_calidad_nombre' => $alumno['motivo_calidad_nombre'],
                'motivo_calidad_descripcion' => $alumno['motivo_calidad_descripcion'],
            ];
        }

        return $rs;
    }

	/**
     * Devuelve una lista de alumnos con cambios de calidades en un período de tiempo para el ENDPOINT GET /alumnos-cambios-calidades.
     *
     * @param string $fecha_desde Obligatorio.
     * @param string $fecha_hasta Obligatorio.
     * @param string $persona Opcional, por defecto vacío.
     * @param string $propuesta Opcional, por defecto vacío.
     * @param string $plan Opcional, por defecto vacío.
     * @param string $plan_cobrable Opcional, por defecto vacío.
     * @param string $nro_transaccion Opcional, por defecto vacío.
     * @param string $motivo_calidad Opcional, por defecto vacío.
     *
     * @return array Alumnos con cambios de calidades en un período de tiempo para el ENDPOINT GET /alumnos-cambios-calidades.
     */
    function get_listado_alumnos_cambios_calidades_rest(
        $fecha_desde,
        $fecha_hasta,
        $persona = '',
        $propuesta = '',
        $plan = '',
        $plan_cobrable = '',
        $nro_transaccion = '',
        $motivo_calidad = ''
    )
    {

        $where_persona = "";
        // Si filtra por persona
        if (!empty($persona)) {
            $where_persona = "AND sga_alumnos.persona = $persona";
        }

        $where_propuesta = "";
        // Si filtra por propuesta
        if (!empty($propuesta)) {
            $where_propuesta = "AND sga_propuestas.propuesta = {$propuesta}";
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

        $where_nro_transaccion = "";
        // Si filtra por nro_transaccion
        if (!empty($nro_transaccion)) {
            $where_nro_transaccion = "AND sga_propuestas_aspira.nro_transaccion = {$nro_transaccion}";
        }

        $where_motivo_calidad = "";
        // Si filtra por motivo_calidad
        if (!empty($motivo_calidad)) {
            $where_motivo_calidad = "AND sga_alumnos_motivos_calidad.motivo_calidad = {$motivo_calidad}";
        }

        $formato_fecha_bd = comunes::formato_fecha_bd;
        $inscripcion_aceptada = propuesta::inscripcion_aceptada;
        $inscripcion_pendiente = propuesta::inscripcion_pendiente;

        $sql = "
            SELECT
                sga_alumnos.persona,
                sga_propuestas.propuesta,
                sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
                sga_planes.plan,
                sga_planes.cobrable AS plan_cobrable,
                sga_propuestas_aspira.nro_transaccion,
                sga_alumnos_hist_calidad.calidad,
                sga_alumnos_hist_calidad.fecha,
                sga_alumnos_motivos_calidad.motivo_calidad,
                sga_alumnos_motivos_calidad.nombre AS motivo_calidad_nombre,
                sga_alumnos_motivos_calidad.descripcion AS motivo_calidad_descripcion
            FROM
                sga_alumnos
                JOIN sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
                JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
                JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp)
                JOIN sga_planes_versiones ON (sga_planes_versiones.plan_version = sga_alumnos.plan_version)
                JOIN sga_planes ON (sga_planes.plan = sga_planes_versiones.plan)
                JOIN sga_alumnos_hist_calidad ON (sga_alumnos_hist_calidad.alumno = sga_alumnos.alumno)
                JOIN sga_alumnos_motivos_calidad ON (sga_alumnos_hist_calidad.motivo_calidad = sga_alumnos_motivos_calidad.motivo_calidad)

            WHERE
                sga_situacion_aspirante.resultado_asp IN ('{$inscripcion_aceptada}', '{$inscripcion_pendiente}')
                AND DATE(sga_alumnos_hist_calidad.fecha) BETWEEN TO_DATE('{$fecha_desde}', '{$formato_fecha_bd}') AND TO_DATE('{$fecha_hasta}', '{$formato_fecha_bd}')
                $where_persona
                $where_propuesta
                $where_plan
                $where_plan_cobrable
                $where_nro_transaccion
                $where_motivo_calidad
            ORDER BY sga_propuestas_aspira.nro_transaccion, sga_alumnos_hist_calidad.fecha;
        ";
				
        $alumnos_cambios_calidades = guarani_db::consultar($sql);

        $rs = [];
        // Hago un corte de control por 'nro_transaccion'
        foreach ($alumnos_cambios_calidades as $alumno) {
            $rs[$alumno['nro_transaccion']]['persona'] = $alumno['persona'];
            $rs[$alumno['nro_transaccion']]['propuesta'] = $alumno['propuesta'];
            $rs[$alumno['nro_transaccion']]['propuesta_nombre_abreviado'] = $alumno['propuesta_nombre_abreviado'];
            $rs[$alumno['nro_transaccion']]['plan'] = $alumno['plan'];
            $rs[$alumno['nro_transaccion']]['plan_cobrable'] = $alumno['plan_cobrable'];
            $rs[$alumno['nro_transaccion']]['nro_transaccion'] = $alumno['nro_transaccion'];

            $rs[$alumno['nro_transaccion']]['historial'][] = [
                'fecha' => $alumno['fecha'],
                'calidad' => $alumno['calidad'],
                'motivo_calidad' => $alumno['motivo_calidad'],
                'motivo_calidad_nombre' => $alumno['motivo_calidad_nombre'],
                'motivo_calidad_descripcion' => $alumno['motivo_calidad_descripcion'],
            ];
        }

        return $rs;
    }
        
}