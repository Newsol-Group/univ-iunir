<?php
/**
 *
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class co_docentes
{
	/**
        Obtiene una lista de docentes según el filtro recibido
    */
	function get_listado($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
	    	$where = ' AND '.$where;		
		}
		
		$sql = "SELECT 		sga_docentes.docente,
				  			sga_docentes.legajo,
				  			sga_docentes.persona,"
				  			.guarani_sql::get_case('sga_docentes', 'estado', 'estado_docente').",
				  			vw_personas.apellido 			AS apellido,
				  			vw_personas.nombres 			AS nombres,
				  			vw_personas.apellido_nombres 	AS nombre_persona,
							vw_personas.nombres || ' ' || vw_personas.apellido AS nombre_apellido_persona,
							vw_personas.tipo_nro_documento 	AS documento,
							mdp_personas_sexo.descripcion 	AS sexo,
				   			vw_personas.usuario 			AS usuario,
				   			mdp_nacionalidades.descripcion 	AS nacionalidad,
							sga_docentes.es_docente_externo			  			
				FROM 		vw_personas,
				     		sga_docentes,
							mdp_personas_sexo,
							mdp_nacionalidades
				WHERE 		sga_docentes.persona = vw_personas.persona AND
							mdp_personas_sexo.sexo 	= vw_personas.sexo AND
							mdp_nacionalidades.nacionalidad = vw_personas.nacionalidad
				$where
				ORDER BY 	vw_personas.apellido,
							vw_personas.nombres
				";
		
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}
	
	/**
	 * Retorna el listado de todos los docentes con calidad de activos con sus datos asociados, según el filtro ingresado.
	 *
	 * @param str $where filtro sobre los diferentes atributos de los docentes a recuperar.
	 */
	function get_docentes_activos($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$filtro = " sga_docentes.estado = '" . comunes::estado_activo . "'" . $where;
		return $this->get_listado($filtro);
	}

	function get_docentes($filtro = '')
	{
		$condicion = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = $filtro_condicion->get_sql('f_limpiar_acentos(vw_personas.apellido_nombres::varchar)', $filtro);
		}
		return $this->get_docentes_activos($condicion);	
	}
	
	static function get_descripcion_docente($docente = null)
	{
		if (!isset($docente)) {
			return '';
		}
		$docente = toba::db()->quote($docente);
		$result = guarani::co('co_docentes')->get_listado("sga_docentes.docente = $docente");
		return empty($result) ? '' : $result[0]['nombre_persona'];
	}
	
	/**
	 * Obtiene los datos principales de los docentes activos según el filtro dado.
	 * Si se busca aquellos docentes pertenecientes a una cátedra determinada, se obtiene además la responsabilidad que
	 * tienen los mismos asociada.
	 *
	 * @param integer	$catedra	id de la catedra para la cual se quieren obtener los docentes asociados, puede ser nula.
	 * @param str		$where		filtro sobre los docentes a recuperar.
	 */
	function get_docentes_activos_catedras($catedra=null, $where='')
	{
		$filtro = '';
		$select = '';
		$from	= '';
		
		if (!is_null($catedra)) {
			$catedra	= toba::db()->quote($catedra);
			$select		= "	, sga_catedras_docentes.responsabilidad
							, sga_docentes_responsabilidades.firma";
			$from		= " LEFT JOIN sga_catedras_docentes ON sga_catedras_docentes.docente = sga_docentes.docente
							LEFT JOIN sga_docentes_responsabilidades ON sga_catedras_docentes.responsabilidad = sga_docentes_responsabilidades.responsabilidad";
			$filtro		= " AND sga_catedras_docentes.catedra = $catedra";
		}
		if ($where) {
	    	$filtro .= ' AND ' . $where;
		}
		
		$sql = "SELECT	vw_personas.nombres,
				        vw_personas.apellido,
				        vw_personas.apellido_nombres AS nombre_persona,
				        sga_docentes.docente,
				        sga_docentes.legajo
				        $select
				FROM    vw_personas,
				        sga_docentes
				        $from
				WHERE   vw_personas.persona = sga_docentes.persona AND
				        sga_docentes.estado = '" . comunes::estado_activo . "'
				   		$filtro
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
 	  * Retorna los datos correspondientes a un docente en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de los docentes correspondientes según la condición del array (para la carga masiva).
      *	@param integer/array	docente	id o arreglo de ids del o de los docentes para los cuales se consultan los datos.
    */
	function get_datos_docente($docente)
	{
		if (is_array($docente)) {
			$filtro = guarani_sql::armar_condicion_in($docente, 'sga_docentes', 'docente');
			$datos = $this->get_listado($filtro);
			return $datos;
		} 
		$docente = toba::db()->quote($docente);
		$datos = $this->get_listado(" sga_docentes.docente = $docente ");
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	// Recupera los datos del docente dado y su responsabilidad en la comisión dada como parámetro.
	function get_datos_docente_comision($docente, $comision)
	{
		$comision = toba::db()->quote($comision);
		$docente = toba::db()->quote($docente);
		
		$sql = "SELECT	 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as docente_nombre, 
						sga_docentes.legajo, 
						sga_docentes.estado,
						sga_docentes_responsabilidades.nombre as responsabilidad_nombre
				FROM	sga_docentes
						JOIN mdp_personas ON sga_docentes.persona = mdp_personas.persona
						JOIN sga_docentes_comision ON sga_docentes.docente = sga_docentes_comision.docente
						LEFT JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
				WHERE	sga_docentes_comision.comision = $comision AND
						sga_docentes.docente = $docente
		";

		return guarani_db::consultar_fila($sql);
	}
	
	/**
        Retorna el legajo de un docente.
    */
	function get_docente_legajo($docente)
	{
		$docente = toba::db()->quote($docente);
		$sql = "SELECT	sga_docentes.legajo as legajo_docente
  				FROM 	sga_docentes
  				WHERE 	sga_docentes.docente = $docente
				;";

		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna el nombre de un docente.
    */
	function get_docente_nombre($docente)
	{
		$docente = toba::db()->quote($docente);
		$sql = "SELECT	COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) || ' ' || mdp_personas.apellido as nombre_docente
  				FROM 	sga_docentes,
  						mdp_personas
  				WHERE 	sga_docentes.docente = $docente AND   						
  						sga_docentes.persona = mdp_personas.persona
				;";

		return guarani_db::consultar($sql);
	}

	/**
        Retorna el estado de un docente.
    */
	function get_docente_estado($docente)
	{
		$docente = toba::db()->quote($docente);
		$sql = 'SELECT	'.guarani_sql::get_case('sga_docentes', 'estado', 'estado_docente').' 
  				FROM 	sga_docentes
  				WHERE 	sga_docentes.docente = $docente
				;';

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el conjunto de responsabilidades asociadas a un docente dado.
	 *
	 * @param integer $docente
	 * @return array
	 */
	function get_docente_responsabilidades($docente, $comision)
	{
		$docente = toba::db()->quote($docente);
		$sql = "";
		if ($comision != '') {
			/*
			 * Si viene la comisión busco si ya tenía responsabilidad asignada para ese docente
			 */			
			$sql = "(	SELECT	sga_docentes_responsabilidades.responsabilidad as valor,
								sga_docentes_responsabilidades.nombre as descr
						FROM	sga_docentes_comision
								JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
						WHERE	sga_docentes_comision.docente = $docente AND
								sga_docentes_comision.comision = $comision)
					UNION";
		}		
		$sql .= " (	SELECT		sga_docentes_resp.responsabilidad as valor,
								sga_docentes_responsabilidades.nombre as descr
					FROM		sga_docentes_resp
								JOIN sga_docentes_responsabilidades ON sga_docentes_resp.responsabilidad = sga_docentes_responsabilidades.responsabilidad
					WHERE		sga_docentes_resp.docente = $docente)
					ORDER BY	2
		";
		return guarani_db::consultar($sql);
	}

	function get_datos_docente_responsabilidad($responsabilidad)
	{
		$responsabilidad = toba::db()->quote($responsabilidad);
		$sql = "SELECT	sga_docentes_responsabilidades.firma
				FROM	sga_docentes_responsabilidades
				WHERE	sga_docentes_responsabilidades.responsabilidad = $responsabilidad
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Obtiene los ids y descripciones de los diferentes Tipos de Dedicaciones docentes, para la carga de un combo.
	 *
	 * @param str $where filtro sobre los tipos de dedicaciones docentes a retornar.
	 */
	function get_tipos_dedicaciones_docentes($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT		sga_docentes_tipo_dedicacion.tipo_dedicacion as valor,
							sga_docentes_tipo_dedicacion.nombre as descr,
							sga_docentes_tipo_dedicacion.descripcion
				FROM		sga_docentes_tipo_dedicacion
				$where
				ORDER BY	sga_docentes_tipo_dedicacion.nombre
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Verifica si un docente dado tiene alguna cátedra asociada.
	 *
	 */
	function tiene_catedras_asociadas($docente)
	{
		$docente = toba::db()->quote($docente);

		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	sga_catedras_docentes
				WHERE	sga_catedras_docentes.docente = $docente
				";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}
	
	/**
	 * Verifica si un docente dado tiene algún tribunal de examen asociado.
	 *
	 */
	function tiene_tribunales_examen_asociados($docente)
	{
		$docente = toba::db()->quote($docente);

		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	sga_tribunal_docentes
				WHERE	sga_tribunal_docentes.docente = $docente
				";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}
	
	/**
	 * Verifica si un docente dado tiene alguna comisión asociada.
	 *
	 */
	function tiene_comisiones_asociadas($docente)
	{
		$docente = toba::db()->quote($docente);

		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	sga_docentes_comision
				WHERE	sga_docentes_comision.docente = $docente
				";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}
	
	/**
	 * Verifica si un docente dado tiene algún llamado de mesa de examen asociado.
	 *
	 */
	function tiene_llamados_mesas_examen_asociados($docente)
	{
		$docente = toba::db()->quote($docente);

		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	sga_docentes_mesa_llamado
				WHERE	sga_docentes_mesa_llamado.docente = $docente
				";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}

	/**
	 * Verifica si un docente dado tiene algún ítem de encuesta asociado.
	 *
	 */
	function tiene_items_asociados($docente)
	{
		$docente = toba::db()->quote($docente);
		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	gde_items
				WHERE	gde_items.docente = $docente
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'] != 0;
	}

	/**
	 * Valida si un legajo de Docente se puede usar o no.
	 */
	function validar_legajo($legajo, $docente)
	{
		if (!isset($legajo)) {
			return false;
		}
		$legajo = toba::db()->quote($legajo);
		$sql = 'SELECT	COUNT(sga_docentes.docente) AS cant
                FROM 	sga_docentes
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_docentes.legajo', $legajo).'
		';
		if (!is_null($docente)) {
			$docente = toba::db()->quote($docente);
			$sql .= " AND sga_docentes.docente <> $docente ";
		}
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	function get_listado_rest($where='', $order='', $limit='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$orden_tipo_email = persona::orden_tipo_email();
		$sql = "SELECT	sga_docentes.docente,
				  		sga_docentes.legajo,
				  		sga_docentes.persona,
						vw_personas.pais_documento,
						vw_personas.tipo_documento,
						vw_personas.nro_documento,
						vw_personas.apellido,
						vw_personas.nombres,
						(SELECT		mdp_personas_contactos.email
						FROM		mdp_personas_contactos
						WHERE		mdp_personas_contactos.persona = vw_personas.persona AND
									mdp_personas_contactos.contacto_tipo IN ('".persona::mail_primario."','".persona::mail_secundario."','".persona::mail_institucional."') AND
									mdp_personas_contactos.email IS NOT NULL
						ORDER BY	$orden_tipo_email
						LIMIT		1) as email
				FROM	sga_docentes
						JOIN vw_personas ON sga_docentes.persona = vw_personas.persona
				$where
				$order
				$limit
		";
		return guarani::db()->consultar($sql);
	}

	function get_mesas_docente_rest($docente, $solo_vigentes = false, $where='')
	{
		$docente = toba::db()->quote($docente);
		if ($where) {
			$where = "AND $where";
		}
		if ($solo_vigentes) {
			$where .= 'AND CURRENT_DATE BETWEEN sga_periodos.fecha_inicio AND sga_periodos.fecha_fin';
		}
		// Solo recupera mesas de examen de turnos de examen. No se recuperan mesas de examen fuera de calendario.
		$sql = "SELECT  sga_docentes_mesa_llamado.llamado_mesa,
						sga_llamados_mesa.fecha,
						sga_llamados_mesa.hora_inicio,
						sga_llamados_mesa.hora_finalizacion,
						sga_llamados_mesa.cupo,
						sga_tribunal_roles.nombre as rol,
						sga_llamados_mesa.mesa_examen,
						sga_mesas_examen.nombre,
						sga_catedras.nombre as nombre_catedra,
						f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) as nombre_modalidades,
						f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) as nombre_instancias,
						sga_elementos.codigo as codigo_actividad,
						get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as nombre_actividad,
						sga_mesas_examen.ubicacion,
						sga_ubicaciones.nombre as nombre_ubicacion
				FROM	sga_docentes_mesa_llamado
						JOIN sga_docentes ON sga_docentes_mesa_llamado.docente = sga_docentes.docente
						JOIN sga_tribunal_roles ON sga_docentes_mesa_llamado.rol = sga_tribunal_roles.rol
						JOIN sga_llamados_mesa ON sga_docentes_mesa_llamado.llamado_mesa = sga_llamados_mesa.llamado_mesa
						JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
						JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
						JOIN sga_periodos ON sga_turnos_examen.periodo = sga_periodos.periodo
						JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
						JOIN sga_elementos ON sga_mesas_examen.elemento = sga_elementos.elemento
						JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
						LEFT JOIN sga_catedras ON sga_mesas_examen.catedra = sga_catedras.catedra
				WHERE	sga_docentes_mesa_llamado.docente = $docente
						$where
				ORDER BY sga_tribunal_roles.orden_acta
		";
		$datos = guarani_db::consultar($sql);
		$mesas = array();
		foreach ($datos as $dato) {
			$ubicacion = array('ubicacion' => $dato['ubicacion'], 'nombre' => $dato['nombre_ubicacion']);
			$actividad = array('codigo' => $dato['codigo_actividad'], 'nombre' => $dato['nombre_actividad']);
			$mesa_examen = array(	'mesa_examen' => $dato['mesa_examen'],
									'nombre' => $dato['nombre'],
									'nombre_catedra' => $dato['nombre_catedra'],
									'nombre_modalidades' => $dato['nombre_modalidades'],
									'nombre_instancias' => $dato['nombre_instancias'],
									'ubicacion' => $ubicacion,
									'actividad' => $actividad);
			$llamado_mesa = array(	'llamado_mesa' => $dato['llamado_mesa'],
									'fecha' => $dato['fecha'],
									'hora_inicio' => $dato['hora_inicio'],
									'hora_finalizacion' => $dato['hora_finalizacion'],
									'cupo' => $dato['cupo'],
									'mesa_examen' => $mesa_examen);
			$mesas[] = array('rol' => $dato['rol'], 'llamado_mesa' => $llamado_mesa);
		}
		return $mesas;
	}

	/**
	 * Retorna identificador y nombre de docente sin aplicar filtro por perfil de datos.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_docente_nombre_sin_perfil_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_docentes.docente,
						COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) || ' ' ||  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as docente_nombre
  				FROM 	sga_docentes,
  						mdp_personas
  				WHERE 	sga_docentes.persona = mdp_personas.persona
						$where
		";
		return guarani_db::consultar($sql, null, false);
	}
	
	/**
 	  *  Retorna el nombre de un Docente en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Docentes según la condición del array (para la carga masiva).
      *	 @param integer/array	id o arreglo de ids del o de los docentes para los cuales se consultan los nombres.
    */
	function get_nombre_docente($docente)
	{
		if (is_array($docente)) {
			$filtro = guarani_sql::armar_condicion_in($docente, 'sga_docentes', 'docente');
			$datos = $this->get_docente_nombre_sin_perfil_datos($filtro);
			return $datos;
		} 
		$docente = toba::db()->quote($docente);
		$datos = $this->get_docente_nombre_sin_perfil_datos("sga_docentes.docente = $docente");
		return $datos[0];
	}
	
	/**
 	  * Retorna el nombre de un docente
    */
	static function get_nombre_sin_id_docente($docente)
	{
		if (! isset($docente) || trim($docente) == '') {
			return '';
		}    	
		
		$datos = toba::consulta_php('co_docentes')->get_docente_nombre_sin_perfil_datos("sga_docentes.docente = $docente");
		return $datos[0]['docente_nombre'];
	}

	/**
	 * Retorna los datos del docente correspondiente a una persona dada.
	 * 
	 * @param int $persona
	 * @return array
	 */
	function get_docente_activo_por_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT 	sga_docentes.docente,
						sga_docentes.legajo,
						sga_docentes.estado
				FROM 	sga_docentes
				WHERE	sga_docentes.persona = $persona AND
						sga_docentes.estado = '".comunes::estado_activo."'
		";
		return guarani_db::consultar_fila($sql);
	}

	function get_docente_por_legajo($legajo)
	{
		$legajo = toba::db()->quote($legajo);
		$sql = "SELECT 	sga_docentes.docente
				FROM 	sga_docentes
				WHERE	sga_docentes.legajo = $legajo
		";
		return guarani_db::consultar_fila($sql);
	}
	
	function existe_docente($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT 	sga_docentes.docente
				FROM 	sga_docentes
				WHERE	sga_docentes.persona = $persona
		";
		$resultado = guarani_db::consultar($sql, null, false);
		
		return guarani_arreglos::count($resultado) > 0;
	}

	/**
	 * Retorna el conjunto de docentes de comisiones y mesas vigentes donde se encuentran
	 * los alumnos vinculados a la persona, y opcionalmente la propuesta, recibidos como parámetro.
	 * 
	 * @param int $persona
	 * @param int $propuesta
	 * @return array
	 */
	function get_docentes_comision_mesas($persona, $propuesta=null)
	{
		$persona = toba::db()->quote($persona);
		$filtro = " AND sga_alumnos.persona = $persona";
		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$filtro .= " AND sga_alumnos.propuesta = $propuesta";
		}

		$orden_tipo_email = persona::orden_tipo_email();
		$mail_prim =  toba::db()->quote(persona::mail_primario);
        $mail_sec  =  toba::db()->quote(persona::mail_secundario);
        $mail_inst =  toba::db()->quote(persona::mail_institucional);

		$sql = "SELECT	DISTINCT(sga_docentes.docente),
						sga_docentes.persona,
						vw_personas.apellido_nombres_elegido,				
						COALESCE((	SELECT		mdp_personas_contactos.email
									FROM		mdp_personas_contactos
									WHERE		mdp_personas_contactos.persona = sga_docentes.persona AND
												mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst) AND
												mdp_personas_contactos.email IS NOT NULL
									ORDER BY	$orden_tipo_email
									LIMIT		1), '') as email
				FROM	sga_docentes
						JOIN vw_personas ON sga_docentes.persona = vw_personas.persona
				WHERE	sga_docentes.docente IN (	-- Docentes de las comisiones donde se encuentra el alumno (cursando o futuras).
													SELECT	DISTINCT sga_docentes_comision.docente
													FROM	sga_insc_cursada
															JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
															JOIN vw_comisiones ON sga_insc_cursada.comision = vw_comisiones.comision
															JOIN sga_docentes_comision ON vw_comisiones.comision = sga_docentes_comision.comision
													WHERE	vw_comisiones.fecha_fin >= CURRENT_DATE
															$filtro
													UNION ALL
													-- Docentes de las mesas.
													SELECT	DISTINCT sga_docentes_mesa_llamado.docente
													FROM	sga_insc_examen
															JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
															JOIN vw_mesas_examen ON sga_insc_examen.llamado_mesa = vw_mesas_examen.llamado_mesa
															JOIN sga_docentes_mesa_llamado ON vw_mesas_examen.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
													WHERE	vw_mesas_examen.mesa_examen_fecha >= CURRENT_DATE
															$filtro)
		";
		return guarani_db::consultar($sql, null, false);
	}
}