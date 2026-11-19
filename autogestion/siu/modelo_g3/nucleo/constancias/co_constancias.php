<?php
/**
 *
 * 
 * @package 	Constancias y Certificados
 * 
 */
class co_constancias
{
	/**
	 * Obtiene un listado de constancias, según el filtro recibidos
	 */
	function get_listado_constancias($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT		sga_constancias.constancia,
							sga_constancias.nombre,
							sga_constancias.descripcion,
							sga_constancias.max_cant_solicitudes,
							sga_constancias.max_cant_copias,
							sga_constancias.objeto,
							sga_constancias_formularios.nombre AS formulario_nombre,
							sga_constancias.plantilla,
							mce_plantillas.nombre AS plantilla_nombre,
							CASE sga_constancias.permite_descargar
								WHEN true THEN 'Sí'
								WHEN false THEN 'No'
							END AS permite_descargar_descr,
							sga_constancias.permite_descargar,
							sga_constancias.vigencia,
							sga_constancias.activo,
							CASE sga_constancias.activo
								WHEN '" . comunes::si . "' THEN 'Activo'
								WHEN '" . comunes::no . "' THEN 'No Activo'
							END AS activo_descr,
							sga_constancias.exige_aprobacion,
                            CASE sga_constancias.disponible_autogestion
								WHEN '" . comunes::si . "' THEN '". comunes::si_desc ."'
								WHEN '" . comunes::no . "' THEN '". comunes::no_desc ."'
							END AS disponible_autogestion_descr
                            
				FROM		sga_constancias
							JOIN mce_plantillas ON sga_constancias.plantilla = mce_plantillas.plantilla
							LEFT JOIN sga_constancias_formularios ON sga_constancias.objeto = sga_constancias_formularios.objeto
				$where
				ORDER BY	sga_constancias.nombre
		";

		return guarani_db::consultar($sql);
	}
	
	function get_datos_constancia($id_constancia)
	{
		$id_constancia = toba::db()->quote($id_constancia);
		$filtro = "sga_constancias.constancia = $id_constancia";
		$datos = $this->get_listado_constancias($filtro);
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	function get_se_puede_descargar_constancia($id_constancia)
	{
		$datos = $this->get_datos_constancia($id_constancia);
		return $datos['permite_descargar'];
	}
	
	function get_constancias($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT 
					sga_constancias.constancia as valor,
					sga_constancias.nombre as descr
				FROM
					sga_constancias
				$where
				ORDER BY
					sga_constancias.nombre
				";

		return guarani_db::consultar($sql);
	}
	
	// Obtiene un listado de las constancias activas.
	function get_constancias_activas()
	{
		$filtro = "sga_constancias.activo = '" . comunes::si . "'";
		$datos = $this->get_constancias($filtro);
		return $datos;
	}
    
    // Obtiene un listado de las constancias activas por propuesta, y que se puedan descargar desde 3W.
	function get_constancias_activas_autogestion($propuesta = null)
	{
		$si = toba::db()->quote(comunes::si);
		$filtro = "sga_constancias.activo = $si";
        $filtro .= " AND sga_constancias.disponible_autogestion = $si";
		
		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$filtro .= " AND constancia IN (SELECT DISTINCT sga_constancias_ra.constancia
											FROM sga_constancias_ra
											JOIN sga_propuestas_ra ON (sga_constancias_ra.responsable_academica = sga_propuestas_ra.responsable_academica)
											WHERE sga_propuestas_ra.propuesta = $propuesta)";
		}
		
		return $this->get_constancias($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Consultas sobre sga_constancias_solicitudes y tablas relacionadas ------------
	//-----------------------------------------------------------------------------------
		
	/**
	 * Obtiene un listado de constancias solicitadas según filtros recibidos
	 */
	function get_listado_const_solicitadas($where='', $order='', $params_sistema='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if (!$order) {
			$order = 'sga_constancias.nombre';
		}
		$apellido_y_nombre = persona::get_param_nombre_persona_doc_oficiales();
		$sql = "SELECT		sga_constancias_solicitud.solicitud,
							sga_constancias_solicitud.constancia,
							sga_constancias.nombre AS constancia_nombre,
							$apellido_y_nombre	AS apellido_y_nombre,
							vw_personas.apellido_elegido 	AS apellido_elegido,
							vw_personas.nombres_elegido		AS nombres_elegido,
							vw_personas.nro_documento,
							vw_personas.tipo_nro_documento AS documento_principal,
							vw_personas.persona,
							sga_alumnos.propuesta,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_planes_versiones.plan,
							sga_planes.nombre AS plan_nombre,
							sga_alumnos.plan_version,
							sga_planes_versiones.nombre AS plan_version_nombre,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_constancias_solicitud.fecha_solicitud,
							to_char(sga_constancias_solicitud.fecha_solicitud, '".comunes::formato_fecha_visual."') as fecha_solicitud_formateada,
							sga_constancias_solicitud.cant_copias_solicitadas,
							CASE 
								WHEN sga_constancias_solicitud.estado = '".constancias::estado_solicitud_impreso."' THEN sga_constancias_solicitud.fecha_proceso
							END AS fecha_impresion,
							CASE
								WHEN sga_constancias_solicitud.estado = '".constancias::estado_solicitud_rechazado."' THEN sga_constancias_solicitud.fecha_proceso
							END AS fecha_rechazo,
							sga_constancias_solicitud.fecha_entrega,
							sga_constancias_solicitud.presentar_a,
							sga_constancias_solicitud.observaciones,
							sga_constancias_solicitud.estado,
							sga_constancias_solicitud.alumno,
							sga_constancias_solicitud.fecha_fin_vigencia,
							sga_constancias_solicitud.nro_transaccion,
							sga_constancias_solicitud.codigo_verificacion,
							acc_interfaces.interfaz,
							acc_interfaces.nombre as interfaz_nombre,
							sga_constancias.permite_descargar
				FROM		sga_constancias_solicitud
							JOIN sga_constancias ON sga_constancias.constancia = sga_constancias_solicitud.constancia
							JOIN sga_alumnos ON sga_alumnos.alumno = sga_constancias_solicitud.alumno
							JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
							JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
							JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
							JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
							JOIN acc_interfaces ON sga_constancias_solicitud.interfaz = acc_interfaces.interfaz
							JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
			    $where
				ORDER BY	$order
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos asociados a la solicitud de constancia dada.
	 */
	function get_datos_solicitud($id_solicitud)
	{
		$id_solicitud = toba::db()->quote($id_solicitud);
		$filtro = "sga_constancias_solicitud.solicitud = $id_solicitud";
		$datos = $this->get_listado_const_solicitadas($filtro);
		if (!empty($datos)) {
			return current($datos);
		}
			
	}
	
	/**
	 * Retorna el estado de la solicitud de constancia dada, en formato recordset.
	 */
	function get_solicitud_estado($id_solicitud)
	{
		$id_solicitud = toba::db()->quote($id_solicitud);
		$sql = "SELECT	sga_constancias_solicitud.estado
				FROM	sga_constancias_solicitud
				WHERE	sga_constancias_solicitud.solicitud = $id_solicitud
				;";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna los parametros de una solicitud
	 */
	function get_solicitud_params($id_solicitud)
	{
		$id_solicitud = toba::db()->quote($id_solicitud);
		$sql = "SELECT	
						sga_constancias_solicitud_param.campo,
						sga_constancias_solicitud_param.valor,
						mce_campos.tipo_de_dato
				FROM	
						sga_constancias_solicitud_param,
						mce_campos
				WHERE	
						sga_constancias_solicitud_param.solicitud = $id_solicitud
						AND sga_constancias_solicitud_param.campo = mce_campos.campo
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna la cantidad de solicitudes asociadas a la constancia dada para el alumno dado.
	 */
	function get_cant_solicitudes_constancia($id_constancia, $id_alumno)
	{
		$id_constancia = toba::db()->quote($id_constancia);
		$id_alumno = toba::db()->quote($id_alumno);
	
		$sql = "SELECT	COUNT(sga_constancias_solicitud.solicitud) as cant_solicitudes
				FROM	sga_constancias_solicitud
				WHERE	sga_constancias_solicitud.constancia = $id_constancia AND
						sga_constancias_solicitud.alumno = $id_alumno
				";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_solicitudes'];
	}
	
	/**
	 * Retorna la cantidad de solicitudes de constancias solicitadas por el alumno en el año academico actual.
	 *
     * @param	int	$constancia		Constancia solicitada por el alumno
     * @param	int	$alumno			Alumno que solicita la constancia
     * @param	int	$anio_academico	Año Académico
     * @return	int	Cantidad de solicitudes de la constancia por el alumno en el año academico actual
	 */
	function get_cant_solicitudes_constancia_anio_academico($constancia, $alumno, $anio_academico)
	{
		$constancia	= toba::db()->quote($constancia);
		$alumno		= toba::db()->quote($alumno);
		$anio_academico = toba::db()->quote($anio_academico);
	
		$sql = "SELECT	COUNT(DISTINCT sga_constancias_solicitud.solicitud) as cant_solicitudes
				FROM	sga_constancias_solicitud
				JOIN	sga_alumnos					ON sga_alumnos.alumno								= sga_constancias_solicitud.alumno
				JOIN    sga_propuestas_ra			ON sga_propuestas_ra.propuesta						= sga_alumnos.propuesta
				JOIN	sga_anios_academicos_ra		ON sga_anios_academicos_ra.responsable_academica	= sga_propuestas_ra.responsable_academica 
				JOIN	sga_anios_academicos_fechas ON sga_anios_academicos_fechas.id_fecha				= sga_anios_academicos_ra.id_fecha
				WHERE	sga_constancias_solicitud.constancia		= $constancia 
				  AND   sga_alumnos.alumno = $alumno
				  AND   sga_anios_academicos_fechas.anio_academico	= $anio_academico
                  AND   DATE(sga_constancias_solicitud.fecha_solicitud) BETWEEN sga_anios_academicos_fechas.fecha_inicio AND sga_anios_academicos_fechas.fecha_fin
				";

		$datos = guarani_db::consultar_fila($sql, null, false);
		return (isset($datos['cant_solicitudes']))? $datos['cant_solicitudes'] : 0;
	}
	
	/**
	 * Retorna los datos de los formularios definidos para constancias, según el filtro dado.
	 *
	 * @param str $where	filtro sobre los formularios a retornar.
	 */
	function get_constancias_formularios($where='')
	{
		if ($where) {
			$where = 'WHERE ' . $where;	
		}
		
		$sql = "SELECT		sga_constancias_formularios.objeto,
							sga_constancias_formularios.nombre
		 		FROM		sga_constancias_formularios
		 		$where
				ORDER BY sga_constancias_formularios.nombre
				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_estados_solicitudes()
	{
		return array(array('valor' => constancias::estado_solicitud_entregado,'descr' => constancias::estado_solicitud_entregado),
		             array('valor' => constancias::estado_solicitud_impreso,'descr' => constancias::estado_solicitud_impreso),
		             array('valor' => constancias::estado_solicitud_pendiente,'descr' => constancias::estado_solicitud_pendiente),
		             array('valor' => constancias::estado_solicitud_rechazado ,'descr' => constancias::estado_solicitud_rechazado)
		            );
	}
	
	/**
	 *  Valida si un nombre de Constancia se puede usar o no.
     * @param string	$nombre			Nombre de la Constancia a validar.
     * @param integer	$constancia		Id de la Constancia para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_constancia($nombre, $constancia=null)
	{
		if (is_null($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(sga_constancias.constancia) as cant 	
                FROM 	sga_constancias
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_constancias.nombre', $nombre).'
				';
		if (!is_null($constancia)) {
			$constancia = toba::db()->quote($constancia);
			$sql .= " AND sga_constancias.constancia <> $constancia ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 * Valida si un nombre de Formulario se puede usar o no.
     * @param string	$nombre			Nombre de la Formulario a validar.
     * @param integer	$objeto			Id del Objeto del Formulario para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_formulario($nombre, $objeto=null)
	{
		if (is_null($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(sga_constancias_formularios.objeto) as cant 	
                FROM 	sga_constancias_formularios
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_constancias_formularios.nombre', $nombre).'
				';
		if (!is_null($objeto)) {
			$objeto = toba::db()->quote($objeto);
			$sql .= " AND sga_constancias_formularios.objeto <> $objeto ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 * Chequea si hay una solicitud vigente
     * @param string	$dni				 DNI de la persona que hizo la solicitud
     * @param string	$codigo_verificacion Código de verificación de la constancia
     * @return integer	Código de la solicitud que se relaciona con el DNI y código de verificación o null si es vacío
    */
	function get_solicitud_vigente($dni, $codigo_verificacion)
	{
		$dni = toba::db()->quote($dni);
		$codigo_verificacion = toba::db()->quote($codigo_verificacion);
	
		$sql = 'SELECT	solicitud
				FROM	sga_constancias_solicitud,
						sga_alumnos,
						vw_personas
				WHERE	sga_constancias_solicitud.codigo_verificacion		= '.$codigo_verificacion.'
						AND sga_constancias_solicitud.fecha_fin_vigencia	>= CURRENT_DATE
						AND sga_constancias_solicitud.alumno				= sga_alumnos.alumno
						AND sga_alumnos.persona								= vw_personas.persona
						AND vw_personas.nro_documento						= '.$dni;
		$result = guarani_db::consultar($sql);
		
		if (empty($result)){
			return null;
		}else{
			return $result[0]['solicitud'];
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Consultas sobre mce_plantillas y tablas relacionadas -------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Obtiene los datos de las plantillas definidas, según el filtro dado.
	 *
	 */
	function get_datos_plantillas($where='')
	{
		if ($where) {
			$where = 'WHERE ' . $where;	
		}
		
		$sql = 		"SELECT		mce_plantillas.plantilla,
								mce_plantillas.nombre AS plantilla_nombre, 
								mce_plantillas.descripcion AS plantilla_descripcion,
								mce_plantillas.archivo_plantilla
		 			FROM		mce_plantillas
		 			$where
		 			ORDER BY	mce_plantillas.nombre
					;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene las Plantillas que pueden ser utilizadas para una Solicitud manual por parte del usuario, 
	 * según el filtro dado.
	 *
	 */
	function get_plantillas_solicitud_manual($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;	
		}
		
		$sql = 		"SELECT		mce_plantillas.plantilla AS valor,
								mce_plantillas.nombre AS descr
		 			 FROM		mce_plantillas
		 			 WHERE		mce_plantillas.admite_solicitud = 1	
		 						$where
		 		 	 ORDER BY	mce_plantillas.nombre
					;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los ids y descripciones de todas las Plantillas de constancias cuyos nombres contengan al string ingresado 
	 * como filtro (para cargar un combo editable).
	 *
	 * @param str $filtro porción del nombre de la/las Plantilla/s ingresada por el usuario.
	 */
	function get_descripciones_plantillas($filtro='')
	{
		$condicion = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = $filtro_condicion->get_sql('f_limpiar_acentos(mce_plantillas.nombre::varchar)', $filtro);
		}
		return $this->get_datos_plantillas($condicion);	
	}
	
	/**
	 * Obtiene el nombre de una Plantilla cuyo id se da como parámetro, retornando la string para el método
	 * descripción de un combo editable.
	 *
	 * @param integer $plantilla id de la Plantilla para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_plantilla($plantilla=null)
	{
		if (!isset($plantilla)) {
			return '';
		}
		$plantilla = toba::db()->quote($plantilla);
		$result = toba::consulta_php('co_constancias')->get_datos_plantillas("mce_plantillas.plantilla = $plantilla");
		return empty($result) ? '' : $result[0]['plantilla_nombre'];
	}

	/**
	 * Obtiene el archivo de una Plantilla cuyo id se da como parámetro
	 *
	 * @param integer $plantilla id de la Plantilla para la cual se desea recuperar la descripción.
	 */
	function get_archivo_plantilla($plantilla=null)
	{
		if (!isset($plantilla)) {
			return '';
		}
		$plantilla = toba::db()->quote($plantilla);
		$result = $this->get_datos_plantillas("mce_plantillas.plantilla = $plantilla");
		return empty($result) ? '' : $result[0]['archivo_plantilla'];
	}
	
	/**
	 * Obtiene el archivo de una Plantilla asociada a una Constancia cuyo id se da como parámetro
	 *
	 * @param integer $constancia id de la Constancia
	 */
	function get_constancia_archivo_plantilla($constancia=null)
	{
		if (!isset($constancia)) {
			return '';
		}
		$constancia = toba::db()->quote($constancia);
		$sql = 		"SELECT		
							mce_plantillas.archivo_plantilla
		 			FROM
			 				sga_constancias,		
			 				mce_plantillas
		 			WHERE
			 				sga_constancias.constancia = $constancia
							AND sga_constancias.plantilla =  mce_plantillas.plantilla
					;";
		
		$result = guarani_db::consultar($sql);
		return empty($result) ? '' : $result[0]['archivo_plantilla'];
	}
	
	/**
	 * Valida si un nombre de Plantilla se puede usar o no.
     * @param string	$nombre			Nombre de la Plantilla a validar.
     * @param integer	$plantilla		Id de la Plantilla excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_plantilla($nombre, $plantilla=null)
	{
		if (is_null($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mce_plantillas.plantilla) as cant 	
                FROM 	mce_plantillas
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('mce_plantillas.nombre', $nombre).'
				';
		if (!is_null($plantilla)) {
			$plantilla = toba::db()->quote($plantilla);
			$sql .= " AND mce_plantillas.plantilla <> $plantilla ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	function get_constancias_curso() 
	{
		$where = ' sga_constancias.aplica_a_cursos = '.quote(comunes::si);
		return $this->get_constancias($where);
	}
	
	/**
	 * Obtiene los alumnos inscriptos a un curso
	 * 
     * @param integer	$comision: ID de la comision
     * @param boolean	$solo_aprobados: si se deben recuperar sólo los alumnos aprobados
     * @param string	$where:	filtros adicionales sobre los datos a recuperar.
	 * 
	 * @return array
     */
	function get_alumnos_inscriptos_a_curso($comision, $solo_aprobados=false, $where = '') 
	{
		if ($where) {
			$where = " AND $where";
		}
		
		if ($solo_aprobados) {
			$select = '
					,vw_hist_academica.fecha
					,vw_hist_academica.nota
					,sga_escalas_notas_resultado.descripcion AS resultado_descr';
			
			$from = "
					,vw_hist_academica
					,sga_escalas_notas_resultado";
			
			$where .= "
					-- Condiciones del join de Historia Académica
					AND vw_hist_academica.alumno = vw_insc_cursada.alumno
					AND vw_hist_academica.resultado = 'A'
					AND vw_hist_academica.elemento = vw_comisiones.elemento
					AND (
						(vw_hist_academica.origen = 'P' AND vw_hist_academica.comision = vw_comisiones.comision) 
						OR -- Aprobada por Promocion en la comision
						(vw_hist_academica.origen <> 'P') -- EXAMEN, EQUIVALENCIA TOTAL , APROB X RESOL.
					)  -- cursos aprobados por promocion / examen final
					AND sga_escalas_notas_resultado.resultado = vw_hist_academica.resultado";
					
		} else {
			$select = '';
			$from = '';
		}
		
		$sql = "
				SELECT 
					vw_insc_cursada.alumno,
					sga_alumnos.legajo,
					sga_alumnos.persona,
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS documento_principal
					$select
				FROM 
					vw_insc_cursada,
					sga_alumnos,
					vw_personas,
					vw_comisiones  
					$from
				WHERE 
					vw_insc_cursada.comision = $comision 
					AND	vw_insc_cursada.estado = '".inscripcion_cursada::estado_aceptada."'
					AND vw_insc_cursada.alumno = sga_alumnos.alumno
					AND vw_personas.persona = sga_alumnos.persona
					AND vw_comisiones.comision = vw_insc_cursada.comision
					$where
				";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de la actividad de una solicitud de constancia de curso
	 *
	 */
	function get_datos_actividad_solicitud_constancia_curso($id_solicitud)
	{

		$id_solicitud = toba::db()->quote($id_solicitud);

		$sql = "SELECT sga_elementos.elemento, sga_elementos.codigo, get_nombre_actividad_comision(sga_comisiones.comision, 1) as nombre, get_nombre_actividad_comision(sga_comisiones.comision, 2) as nombre_abreviado
				FROM sga_constancias_solicitud_param
				JOIN sga_comisiones ON (sga_comisiones.comision = sga_constancias_solicitud_param.valor::int)
				JOIN sga_elementos ON (sga_elementos.elemento = sga_comisiones.elemento)
				WHERE campo = 'comision'
				AND solicitud = $id_solicitud";

		return guarani_db::consultar_fila($sql);
	}

        /**
         * Obtiene los datos para ConstanciaRegular.jrxml
         * @param integer $solicitud ID de la solicitud (sga_constancias_solicitud.solicitud)
         * @return array Datos para ConstanciaRegular.jrxml
         */
        function get_datos_constancia_regular($solicitud)
        {
            $apellido_y_nombre=  guarani::persona()->param_nombre_persona_doc_oficiales();
		
            $sql = "SELECT
                            COALESCE(sga_alumnos.legajo, ' -- ') AS legajo,
                            $apellido_y_nombre as apellido_y_nombre,
                            vw_personas.desc_tipo_documento || ' ' || vw_personas.nro_documento as documento_principal,
                            vw_planes.propuesta_nombre as propuesta_nombre,
                            '(' || vw_planes.propuesta_codigo || ') ' || vw_planes.propuesta_nombre as propuesta_nombre_completo,
                            vw_planes.propuesta_nombre_abreviado as propuesta_nombre_abreviado,
                            sga_ubicaciones.nombre as ubicacion_nombre,
                            vw_planes.plan_nombre as plan_nombre,
                            vw_planes.version_nombre as plan_version_nombre,
                            sga_constancias_solicitud.presentar_a,
                            COALESCE(sga_constancias_solicitud.observaciones, ' ') AS observaciones,
                            vw_localidades.localidad_nombre as ciudad,
                            vw_localidades.provincia_nombre as provincia,
                            sga_instituciones.nombre as nombre_institucion,
                            f_responsables_academicas_propuesta(sga_alumnos.propuesta) AS ra_nombre
                    FROM
                            sga_constancias_solicitud,
                            sga_alumnos,
                            vw_personas,
                            vw_planes,
                            sga_ubicaciones,
                            sga_instituciones,
                            vw_localidades
                    WHERE
                            sga_constancias_solicitud.solicitud = $solicitud
                            AND sga_constancias_solicitud.alumno = sga_alumnos.alumno
                            AND sga_alumnos.persona = vw_personas.persona
                            AND sga_alumnos.calidad = '".alumno::calidad_activo."'
                            AND vw_planes.plan_version = sga_alumnos.plan_version
                            AND sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
                            AND sga_instituciones.institucion = get_ra_inst_araucano_propuesta(sga_alumnos.propuesta, 'IG')
                            AND sga_ubicaciones.localidad = vw_localidades.localidad
                    LIMIT 1";

            return guarani_db::consultar_fila($sql, null, false);
        }

        /**
         * Obtiene los datos para ConstanciaPromedio.jrxml
         * @param integer $solicitud ID de la solicitud (sga_constancias_solicitud.solicitud)
         * @return array Datos para ConstanciaPromedio.jrxml
         */
        function get_datos_constancia_promedio_academico($solicitud)
        {
            $apellido_y_nombre= guarani::persona()->param_nombre_persona_doc_oficiales();
	
            $sql = "SELECT
                       $apellido_y_nombre as apellido_y_nombre,
                        vw_personas.desc_tipo_documento || ' ' || vw_personas.nro_documento as documento_principal,
                        sga_propuestas.nombre as propuesta_nombre,
                        '(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre_completo,
                        sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
                        sga_ubicaciones.nombre as ubicacion_nombre,
                        sga_constancias_solicitud.presentar_a,
                        COALESCE(sga_constancias_solicitud.observaciones, ' ') AS observaciones,
                        vw_localidades.localidad_nombre as ciudad,
                        vw_localidades.provincia_nombre as provincia,
                        sga_instituciones.nombre as nombre_institucion,
                        COALESCE(f_promedio_gral_con_aplazos(sga_alumnos.alumno, CURRENT_DATE)::VARCHAR,' -- ') AS promedio,
                        f_responsables_academicas_propuesta(sga_propuestas.propuesta) AS ra_nombre
                    FROM
                        sga_constancias_solicitud,
                        sga_alumnos,
                        vw_personas,
                        sga_propuestas,
                        sga_ubicaciones,
                        sga_instituciones,
                        vw_localidades
                    WHERE
                        sga_constancias_solicitud.solicitud = $solicitud
                        AND sga_constancias_solicitud.alumno = sga_alumnos.alumno
                        AND sga_alumnos.persona = vw_personas.persona
                        AND sga_propuestas.propuesta = sga_alumnos.propuesta
                        AND sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
                        AND sga_instituciones.institucion = get_ra_inst_araucano_propuesta(sga_alumnos.propuesta, 'IG')
                        AND sga_ubicaciones.localidad = vw_localidades.localidad
                    LIMIT 1";

            return guarani_db::consultar_fila($sql, null, false);
        }

        /**
         * Obtiene los datos para ConstanciaPromedioSinAplazos.jrxml
         * @param integer $solicitud ID de la solicitud (sga_constancias_solicitud.solicitud)
         * @return array Datos para ConstanciaPromedioSinAplazos.jrxml
         */
        function get_datos_constancia_promedio_academico_sin_aplazos($solicitud)
        {
            $apellido_y_nombre= persona::param_nombre_persona_doc_oficiales();
            
            $sql = "SELECT
                        $apellido_y_nombre as apellido_y_nombre,
                        vw_personas.desc_tipo_documento || ' ' || vw_personas.nro_documento as documento_principal,
                        sga_propuestas.nombre as propuesta_nombre,
                        '(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre_completo,
                        sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
                        sga_ubicaciones.nombre as ubicacion_nombre,
                        sga_constancias_solicitud.presentar_a,
                        COALESCE(sga_constancias_solicitud.observaciones, ' ') AS observaciones,
                        vw_localidades.localidad_nombre as ciudad,
                        vw_localidades.provincia_nombre as provincia,
                        sga_instituciones.nombre as nombre_institucion,
                        COALESCE(f_promedio_gral_sin_aplazos(sga_alumnos.alumno, CURRENT_DATE)::VARCHAR,' -- ') AS promedio,
                        f_responsables_academicas_propuesta(sga_alumnos.propuesta) AS ra_nombre
                    FROM sga_constancias_solicitud
                    JOIN sga_alumnos ON sga_constancias_solicitud.alumno = sga_alumnos.alumno
                    JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
                    JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
                    JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
                    JOIN vw_localidades ON vw_localidades.localidad = sga_ubicaciones.localidad
                    JOIN sga_instituciones ON sga_instituciones.institucion = get_ra_inst_araucano_propuesta(sga_alumnos.propuesta, 'IG')
                    WHERE sga_constancias_solicitud.solicitud = $solicitud
                    LIMIT 1";

            return guarani_db::consultar_fila($sql, null, false);
        }

        /**
         * Obtiene los datos para ConstanciaActividadesRendidas.jrxml
         * @param integer $solicitud ID de la solicitud (sga_constancias_solicitud.solicitud)
         * @return array Datos para ConstanciaActividadesRendidas.jrxml
         */
        function get_datos_constancia_actividades_rendidas($solicitud)
        {
            $apellido_y_nombre=  guarani::persona()->param_nombre_persona_doc_oficiales();
            
            $sql = "SELECT
                            sga_alumnos.alumno,
                            COALESCE(sga_alumnos.legajo, ' -- ') AS legajo,
                           $apellido_y_nombre as apellido_y_nombre,
                            vw_personas.desc_tipo_documento || ' ' || vw_personas.nro_documento as documento_principal,
                            vw_planes.propuesta_nombre as propuesta_nombre,
                            '(' || vw_planes.propuesta_codigo || ') ' || vw_planes.propuesta_nombre as propuesta_nombre_completo,
                            vw_planes.propuesta_nombre_abreviado as propuesta_nombre_abreviado,
                            sga_ubicaciones.nombre as ubicacion_nombre,
                            vw_planes.plan_nombre as plan_nombre,
                            vw_planes.version_nombre as plan_version_nombre,
                            sga_constancias_solicitud.presentar_a,
                            COALESCE(sga_constancias_solicitud.observaciones, ' ') AS observaciones,
                            vw_localidades.localidad_nombre as ciudad,
                            vw_localidades.provincia_nombre as provincia,
                            sga_instituciones.nombre as nombre_institucion,
                            f_responsables_academicas_propuesta(sga_alumnos.propuesta) AS ra_nombre
                    FROM sga_constancias_solicitud
                    JOIN sga_alumnos ON sga_alumnos.alumno = sga_constancias_solicitud.alumno
                    JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
                    JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
                    JOIN vw_planes ON vw_planes.plan_version = sga_alumnos.plan_version
                    JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
                    JOIN sga_instituciones ON sga_instituciones.institucion = get_ra_inst_araucano_propuesta(sga_alumnos.propuesta, 'IG')
                    JOIN vw_localidades ON sga_ubicaciones.localidad = vw_localidades.localidad
                    WHERE sga_constancias_solicitud.solicitud = $solicitud
                    LIMIT 1";

            $datos = guarani_db::consultar_fila($sql, null, false);

            $alumno = $datos['alumno'];

            $datos['actividades_alumno'] = toba::consulta_php('co_constancias')->get_datos_constancia_actividades_rendidas_subdataset_actividades_alumno($alumno);

            $datos['suma_examenes'] = toba::consulta_php('co_constancias')->get_datos_constancia_actividades_rendidas_subdataset_suma_examenes($alumno);

            return $datos;
        }

        /**
         * Obtiene los datos para ConstanciaActividadesRendidas.jrxml subDataset actividades_alumno
         * @param integer $alumno ID del alumno
         * @return array Datos para ConstanciaActividadesRendidas.jrxml subDataset actividades_alumno
         */
        function get_datos_constancia_actividades_rendidas_subdataset_actividades_alumno($alumno)
        {
            $sql = "SELECT  COALESCE(p.actividad_codigo || ' - ', '') || COALESCE(ep.nombre, p.actividad_nombre) as actividad_descr,
                            p.fecha,
                            p.nota || ' (' || p.nota_descripcion || ')' AS nota_completa,
                            p.nro_acta,
                            p.resultado_descripcion,
                            p.folio_fisico
                    FROM f_certificado_actividades($alumno,'T','E','T', false, true, false) as p
                    JOIN sga_alumnos ON sga_alumnos.alumno = $alumno
                    JOIN sga_elementos_revision as er ON er.elemento = p.elemento
                    LEFT JOIN sga_elementos_plan as ep ON (ep.elemento_revision = er.elemento_revision AND ep.plan_version = sga_alumnos.plan_version)";

            return guarani_db::consultar($sql, null, false);
        }

        /**
         * Obtiene los datos para ConstanciaActividadesRendidas.jrxml subDataset suma_examenes
         * @param integer $alumno ID del alumno
         * @return array Datos para ConstanciaActividadesRendidas.jrxml subDataset suma_examenes
         */
        function get_datos_constancia_actividades_rendidas_subdataset_suma_examenes($alumno)
        {
            $sql = "SELECT  COALESCE(sum(case when p.resultado = 'A' THEN 1 ELSE 0 END), 0) as examenes_aprobados,
                            COALESCE(sum(case when p.resultado = 'R' THEN 1 ELSE 0 END), 0) as examenes_desaprobados
                    FROM f_certificado_actividades($alumno, 'T', 'E', 'T', false, true, false) as p";

            return guarani_db::consultar($sql, null, false);
        }
}