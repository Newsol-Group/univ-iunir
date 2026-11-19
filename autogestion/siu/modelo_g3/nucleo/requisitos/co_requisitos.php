<?php
class co_requisitos	
{
    function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	sga_requisitos.requisito as id,
						sga_requisitos.requisito,
						sga_requisitos.nombre,
						sga_requisitos.requisito_tipo,
						sga_requisitos.descripcion,
						sga_requisitos_tipos.nombre as tipo_nombre,
						sga_requisitos.tipo_documento_digital,
						sga_requisitos_documentales_tipos.nombre as tipo_documento_nombre,
						sga_requisitos.requisito_subordinado_de,
						sga_requisitos2.nombre as subordinado_de_nombre,
						sga_requisitos.regla,
						sga_reglas.nombre as nombre_regla,
						sga_requisitos_tipos.regla as regla_requisito_subordinado,
						sga_requisitos_tipos.icono,
						sga_reglas2.nombre as nombre_regla2
				FROM 	sga_requisitos 
				JOIN	sga_requisitos_tipos ON sga_requisitos_tipos.requisito_tipo = sga_requisitos.requisito_tipo
				LEFT JOIN sga_requisitos as sga_requisitos2 ON sga_requisitos2.requisito = sga_requisitos.requisito_subordinado_de  
				LEFT JOIN sga_reglas ON sga_reglas.regla = sga_requisitos.regla
				LEFT JOIN sga_requisitos_documentales_tipos ON sga_requisitos_documentales_tipos.tipo_documento_digital = sga_requisitos.tipo_documento_digital
				LEFT JOIN sga_reglas as sga_reglas2 ON sga_reglas2.regla = sga_requisitos_tipos.regla
  				$where
  				ORDER BY sga_requisitos.nombre";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna Los requisitos requeridos por inscripciones a Propuestas de una Persona y otros requisitos
	 * presentados por la misma, devuelve el ESTADO de cada uno de ellos de acuerdo a su presentación
	 * con respecto a la fecha actual
	 *
	 * @param <type> $persona
	 * @return <type>
	 */
	function get_requisitos_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT	sga_requisitos.requisito,
						sga_requisitos.nombre,
						sga_requisitos.tipo_documento_digital,
						f_estado_requisito($persona, sga_requisitos.requisito) as estado,
						f_requisito_solicitado_para($persona, sga_requisitos.requisito) as solicitado_para,
						CASE
				        	WHEN requisito IN (	SELECT 	sga_requisitos_presentados.requisito
												FROM	sga_requisitos_presentados
												WHERE 	sga_requisitos_presentados.persona =  $persona) THEN 1
							ELSE 0
						END	as presentado,
						sga_requisitos.requisito_tipo,
						sga_requisitos.regla
				FROM	sga_requisitos
				WHERE	sga_requisitos.requisito_tipo = ".requisito::tipo_documentales." AND
						sga_requisitos.requisito IN (	SELECT	sga_requisitos_aspirante.requisito
														FROM 	sga_requisitos_aspirante
																JOIN sga_propuestas_aspira ON sga_requisitos_aspirante.propuesta_aspira = sga_propuestas_aspira.propuesta_aspira
														WHERE 	sga_propuestas_aspira.persona = $persona
														UNION
														SELECT	sga_requisitos_certificado.requisito
														FROM 	sga_requisitos_certificado
																JOIN sga_certificados_otorg ON sga_requisitos_certificado.nro_solicitud = sga_certificados_otorg.nro_solicitud
														WHERE 	sga_certificados_otorg.persona = $persona
														UNION
														SELECT 	sga_requisitos_presentados.requisito
														FROM	sga_requisitos_presentados
														WHERE  	sga_requisitos_presentados.persona = $persona)
  				ORDER BY sga_requisitos.requisito
		";
		$datos = guarani_db::consultar($sql);
		
		foreach ($datos as $key => $dato) {
			// Estado de presentación sincronizado en la base
			$datos[$key]['presentado_bd'] = $dato['presentado'];
		}
		
		return $datos;
	}


	function get_estado_requisito ($persona,$requisito)
	{
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		$sql = "SELECT	f_estado_requisito($persona , $requisito)";
		$datos = guarani_db::consultar_fila($sql);
		return $datos['f_estado_requisito'];
	}

	function requisito_presentado($persona, $requisito, $fecha, $propuesta_aspira, $fecha_tope)
	{
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		$fecha = toba::db()->quote($fecha);
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$fecha_tope = toba::db()->quote($fecha_tope);
		$sql = "SELECT f_requisito_presentado($persona, $requisito, $fecha, $propuesta_aspira, $fecha_tope)";
		$datos = guarani_db::consultar_fila($sql);
		return $datos['f_requisito_presentado'];
	}

	/**
	 *  Dado una persona y un requisito retorna las propuestas con fecha de inscripcion mayor
	 *  a la cuales la persona esta inscripta que requiern el requisito. ¿Debería solo mirar inscripciones aceptadas/pendientes?
	 *
	 * @param <type> $persona
	 * @param <type> $requisito
	 */
	function requisito_requerido_por($persona, $requisito)
	{
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		$sql = "SELECT	sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas_aspira.fecha_inscripcion,
						sga_propuestas_aspira.periodo_insc,
						sga_propuestas_aspira.situacion_asp,
						sga_situacion_aspirante.resultado_asp,
						sga_situacion_aspirante.nombre as situacion_nombre

				FROM	sga_propuestas,
						sga_propuestas_aspira,
						sga_requisitos_aspirante,
						sga_situacion_aspirante

				WHERE	sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
				AND		sga_requisitos_aspirante.propuesta_aspira = sga_propuestas_aspira.propuesta_aspira
				AND		sga_requisitos_aspirante.requisito  = $requisito
				AND		sga_propuestas_aspira.persona = $persona
				AND		sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				AND		sga_propuestas_aspira.fecha_inscripcion = (	SELECT	MAX(b_sga_propuestas_aspira.fecha_inscripcion)
																	FROM	sga_propuestas_aspira as b_sga_propuestas_aspira
																	WHERE	b_sga_propuestas_aspira.propuesta = sga_propuestas_aspira.propuesta
																	AND		b_sga_propuestas_aspira.persona = sga_propuestas_aspira.persona )";

		$datos = guarani_db::consultar($sql);
		return $datos;

	}

	
	/**
        Retorna los requisitos para Certificados
    */
	function get_requisitos_certificados($filtro='')
	{
		if ($filtro != '') {
			$filtro .= ' AND ';
		}
		$filtro .= " sga_requisitos.requisito_tipo IN (".requisito::tipo_documentales.",".requisito::tipo_procesos.") ";
		$filtro .= " AND sga_reglas.valido_en_certificados = '".comunes::si."'";
		return $this->get_listado($filtro);
	}
	
	/**
        Retorna los requisitos para Correlativas
    */
	function get_requisitos_correlativas($filtro='')
	{
		if ($filtro != '') {
			$filtro .= ' AND ';
		}
		$filtro .= " sga_requisitos.requisito_tipo IN (".requisito::tipo_documentales.",".requisito::tipo_procesos.") ";
		$filtro .= " AND sga_reglas.valido_en_correlativas = '".comunes::si."'";
		return $this->get_listado($filtro);
	}
		
	/**
        Retorna los tipos de requisitos
    */
	function get_requisitos_tipos($filtro='')
	{
		$filtro = toba::db()->quote("%$filtro%");
		$sql = "SELECT	sga_requisitos_tipos.requisito_tipo as valor, 
						sga_requisitos_tipos.nombre as descr 
  				FROM 	sga_requisitos_tipos
  				WHERE 	sga_requisitos_tipos.nombre ILIKE $filtro OR
						sga_requisitos_tipos.descripcion ILIKE $filtro
				ORDER BY 2
				;";				

		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna los tipos de requisitos públicos
    */
	function get_requisitos_tipos_publicos()
	{
		$sql = "SELECT	sga_requisitos_tipos.requisito_tipo as valor, 
						sga_requisitos_tipos.nombre as descr 
  				FROM 	sga_requisitos_tipos
  				WHERE 	sga_requisitos_tipos.publico = 'S'
				ORDER BY 2
				;";				

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los tipos de documentos digitales que pueden asociarse a los requisitos de 
	 * tipo documental, en caso de utilizar RDI para su almacenamiento.
	 * @param str $where filtro sobre los tipos a obtener
	 */
	function get_requisitos_documentales_tipos_digitales($where='')
	{
		if ($where) {
			$where .= ' WHERE ' . $where;
		}
		
		$sql = "SELECT	sga_requisitos_documentales_tipos.tipo_documento_digital AS valor,
						sga_requisitos_documentales_tipos.nombre AS descr
				FROM	sga_requisitos_documentales_tipos
				$where
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Recupera los parámetros formales y los datos asociados a los mismos, definidos para la regla asociada a un 
	 * requisito dado, si es que la misma posee parámetros.
	 * @param integer $requisito id del requisito para el cual se desea recuperar los parámetros de su regla asociada. 
	 * @return array arreglo de recorsets con los parámetros del requisito y sus datos.
	 */
	function get_requisito_regla_params_def($requisito)
	{
		$requisito = toba::db()->quote($requisito);
		
        $sql = "SELECT	   	sga_requisitos.requisito,
        					sga_reglas.regla,
		                    sga_reglas_parametros.parametro,
	                       	sga_reglas_parametros.tipo_de_dato,
	                       	par_tipo_dato.nombre as nombre_tipo_de_dato, 
	                       	sga_reglas_parametros.orden,
	                       	sga_reglas_parametros.descripcion,
	                       	sga_reglas_parametros.etiqueta,
	                       	sga_reglas_parametros.valor_desde,
	                       	sga_reglas_parametros.valor_hasta
                  FROM     	sga_requisitos,
                  			sga_reglas,
							sga_reglas_parametros,
							par_tipo_dato
                  WHERE     sga_requisitos.regla = sga_reglas.regla AND
                  			sga_reglas.regla = sga_reglas_parametros.regla AND
                  			sga_reglas_parametros.tipo_de_dato = par_tipo_dato.tipo_de_dato AND
                  			sga_requisitos.requisito = $requisito
                  ORDER BY 	sga_reglas_parametros.orden
  				";
       
        return guarani_db::consultar($sql);		
	}	
	
	/**
	 * Obtiene un listado de Requisitos
	 *
	 */
	function get_requisitos()
	{
		$sql = "SELECT		sga_requisitos.requisito as valor,
							sga_requisitos.nombre as descr
               	FROM 		sga_requisitos 
               	ORDER BY	sga_requisitos.nombre
				";				
			
		return guarani_db::consultar($sql);
    }
	
    /**
        Obtiene el valor y descripción de un requisito a partir de un requisito dado para el ef_popup de Requisito
    */
    static function get_requisito($requisito=null)
    {
		if (! isset($requisito) || trim($requisito) == '') {
			return array();
		}    	
		
	    $requisito = toba::db()->quote($requisito);
				
		$sql = "SELECT	sga_requisitos.requisito as valor,
						sga_requisitos.nombre as descr
               	FROM 	sga_requisitos 
               	WHERE 	sga_requisitos.requisito = $requisito
				";				
			
		$result = guarani_db::consultar($sql);
		
		if (!empty($result)) {
			return $result[0]['descr'];
		}
    }

    function get_requisitos_presentados_x_persona($persona)
    {
    	$pers = toba::db()->quote($persona);
        $sql = "SELECT	sga_requisitos_presentados.requisito_presentado,
						sga_requisitos_presentados.persona,
						sga_requisitos_presentados.requisito,
						sga_requisitos_presentados.fecha_presentacion,
						sga_requisitos_presentados.fecha_vencimiento,
						CASE 
							WHEN (sga_requisitos_presentados.fecha_vencimiento >= ".guarani_sql::fecha_actual.") THEN 'Si'
							ELSE 'No'
						END as vigente,
						sga_requisitos_presentados.fecha_alta,
						sga_requisitos_presentados.requisito_digital,
						sga_requisitos.nombre
               	FROM 	sga_requisitos_presentados,
               			sga_requisitos
               	WHERE 	sga_requisitos_presentados.persona = $pers AND
               			sga_requisitos_presentados.requisito = sga_requisitos.requisito
      			;";
               			
        return guarani_db::consultar($sql);
    }

    /**
      *	Retorna los datos de un requisito según el id dado como parámetro en formato RecordSet, 
      *	en caso de ser un array retorna los datos de el/los requisitos segun la condicion 
      * del array (para la carga masiva).
      * @param int/array $requisito id del requisito o array de ids de requisitos para los cuales se obtienen los datos.
    */
    function get_requisito_datos($requisito)
    {
   		if (is_array($requisito)) {
			/**
 	  		* En el caso de la carga masiva se arma otro arreglo, porque la columna requisito puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de requisito no nulos.
 	  		*/
			$requisitos = array_diff($requisito, array(null));
			$filtro = guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos', 'requisito');
			$datos = $this->get_listado($filtro);
			return $datos;
		} elseif (!is_null($requisito)) {
			$requisito = toba::db()->quote($requisito);
			$datos = $this->get_listado("sga_requisitos.requisito = $requisito");
			return $datos[0];
		}
    }

    function get_requisito_apl_pres($plan_version, $persona, $periodo_inscripcion)
    {
		if (!is_null($plan_version) && !is_null($persona)) {
			$plan_version = toba::db()->quote($plan_version);
			$persona = toba::db()->quote($persona);
			$periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
		} else {
			return array();
		}
		$sql = "SELECT		DISTINCT sga_requisitos_propuestas_aplanado.requisito_propuesta,
							sga_requisitos_propuestas.requisito,
							sga_requisitos_propuestas.restrictivo,
							sga_requisitos_propuestas.visible_en_preinscripcion,
							sga_requisitos_propuestas.doc_digital_obligatorio_preinscripcion,
							sga_requisitos_propuestas.parametros,
							sga_requisitos.nombre as nombre_req,
							sga_requisitos.regla,
							sga_requisitos.requisito_tipo,
							sga_requisitos_tipos.nombre as tipo_req,
							sga_requisitos.tipo_documento_digital,
							sga_requisitos_presentados.requisito_presentado,
							sga_requisitos_presentados.fecha_presentacion,
							sga_requisitos_presentados.fecha_vencimiento,
							sga_requisitos_presentados.fecha_alta,
							CASE
								WHEN (sga_requisitos_presentados.fecha_presentacion IS NULL OR sga_requisitos_presentados.fecha_vencimiento < CURRENT_DATE) THEN 0
								ELSE 1
							END as cumplido,
							COALESCE(sga_requisitos_ingreso_excep.fecha_tope, sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos) as fecha_tope,
							sga_requisitos_digitales.requisito_digital,
							sga_requisitos_digitales.id_documento	
				FROM		sga_requisitos_propuestas_aplanado
							JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas_aplanado.requisito_propuesta = sga_requisitos_propuestas.requisito_propuesta
							JOIN sga_requisitos ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito
							JOIN sga_requisitos_tipos ON sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo
							JOIN sga_periodos_inscripcion_propuesta ON true
							LEFT JOIN sga_requisitos_presentados ON sga_requisitos_presentados.requisito_presentado = (	SELECT		rp.requisito_presentado
																														FROM		sga_requisitos_presentados as rp
																														WHERE		rp.persona = $persona AND
																																	rp.requisito = sga_requisitos_propuestas.requisito
																														ORDER BY	rp.fecha_presentacion DESC, rp.requisito_presentado DESC
																														LIMIT		1)						
							LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_propuestas.requisito = sga_requisitos_ingreso_excep.requisito AND sga_requisitos_ingreso_excep.periodo_inscripcion = $periodo_inscripcion)
							LEFT JOIN sga_requisitos_digitales ON sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital
				WHERE		sga_requisitos_propuestas_aplanado.plan_version = $plan_version AND
							sga_periodos_inscripcion_propuesta.periodo_inscripcion = $periodo_inscripcion AND
							sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'
				ORDER BY	sga_requisitos_propuestas.requisito 
		";
		return guarani_db::consultar($sql);
    }

    function get_requisitos_egreso_persona_plan_version($persona, $plan_version)
    {
		$persona = toba::db()->quote($persona);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT		sga_requisitos_propuestas.requisito
				FROM		sga_requisitos_propuestas_aplanado
							JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas_aplanado.requisito_propuesta = sga_requisitos_propuestas.requisito_propuesta
							JOIN sga_requisitos ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito
							JOIN sga_requisitos_tipos ON sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo
							LEFT JOIN sga_requisitos_presentados ON sga_requisitos_presentados.requisito_presentado = (	SELECT		rp.requisito_presentado
																														FROM		sga_requisitos_presentados as rp
																														WHERE		rp.persona = $persona AND
																																	rp.requisito = sga_requisitos_propuestas.requisito
																														ORDER BY	rp.fecha_presentacion DESC, rp.requisito_presentado DESC
																														LIMIT		1)						
							LEFT JOIN sga_requisitos_digitales ON sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital
                                WHERE   sga_requisitos_propuestas_aplanado.plan_version = $plan_version
                                AND     sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_egreso."'
				ORDER BY	sga_requisitos_propuestas.requisito 
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los requisitos de ingreso a pegar al aspirante que se está inscribiendo a Propuesta
	 * para el caso de los planes de tipo Personalizado y Convenio, cuya inscripción se realiza al 
	 * momento de la creación del Plan, motivo por el cual no existe un aplanado de requisitos asociado
	 * a la versión (por ser nueva y no activa vigente).
	 * Se obtienen los requisitos de las entidades o subtipos (Institución, Ras, Propuestas, Planes, Versiones 
	 * y Tipos de Propuestas) asociadas al Plan Versión creado.
	 * @param type $plan
	 * @param type $persona
	 * @param type $periodo_inscripcion
	 * @return type
	 */
	function get_requisitos_ingreso_plan_nuevo_pres($plan_version, $persona, $periodo_inscripcion)
    {
		$plan_version = toba::db()->quote($plan_version);
		$persona = toba::db()->quote($persona);
		$periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
		$sql = "SELECT		DISTINCT sga_requisitos_propuestas.requisito,
							sga_requisitos_propuestas.restrictivo,
							sga_requisitos_propuestas.visible_en_preinscripcion,
							sga_requisitos_propuestas.doc_digital_obligatorio_preinscripcion,
							sga_requisitos_propuestas.parametros,
							sga_requisitos.nombre as nombre_req,
							sga_requisitos.regla,
							sga_requisitos_tipos.nombre as tipo_req,
							sga_requisitos_presentados.fecha_presentacion,
							sga_requisitos_presentados.fecha_vencimiento,
							CASE
								WHEN (sga_requisitos_presentados.fecha_presentacion IS NULL OR sga_requisitos_presentados.fecha_vencimiento < CURRENT_DATE) THEN 0
								ELSE 1
							END as cumplido,
							COALESCE(sga_requisitos_ingreso_excep.fecha_tope, sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos) as fecha_tope
				FROM		sga_requisitos_propuestas
							JOIN sga_requisitos ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito 
							JOIN sga_requisitos_tipos ON sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo
							JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion_propuesta.periodo_inscripcion = $periodo_inscripcion
							LEFT JOIN sga_requisitos_presentados ON	sga_requisitos_presentados.requisito_presentado = (	SELECT		rp.requisito_presentado
																														FROM		sga_requisitos_presentados as rp
																														WHERE		rp.persona = $persona AND
																																	rp.requisito = sga_requisitos_propuestas.requisito
																														ORDER BY	rp.fecha_presentacion DESC, rp.requisito_presentado DESC
																														LIMIT		1)
					
							LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_propuestas.requisito = sga_requisitos_ingreso_excep.requisito AND sga_requisitos_ingreso_excep.periodo_inscripcion = $periodo_inscripcion)
				WHERE		sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."' AND
							sga_requisitos_propuestas.requisito_propuesta IN (	-- Requisitos de la version/plan/propuesta/responsable academica
																				SELECT	sga_requisitos_propuestas.requisito_propuesta
																				FROM	sga_requisitos_propuestas_entidades,
																						sga_requisitos_propuestas
																				WHERE	sga_requisitos_propuestas_entidades.entidad IN (SELECT * FROM get_entidades_plan_version($plan_version)) AND
																						sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_entidades.requisito_propuesta
																				UNION
																				SELECT	sga_requisitos_propuestas.requisito_propuesta
																				FROM	sga_requisitos_propuestas_subtipos,
																						sga_requisitos_propuestas,
																						sga_g3entidades,
																						sga_propuestas,
																						sga_planes,
																						sga_planes_versiones
																				WHERE	sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_subtipos.requisito_propuesta AND
																						sga_requisitos_propuestas_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo AND
																						sga_g3entidades.entidad = sga_propuestas.entidad AND
																						sga_propuestas.propuesta = sga_planes.propuesta AND
																						sga_planes.plan = sga_planes_versiones.plan AND
																						sga_planes_versiones.plan_version = $plan_version)
				ORDER BY	sga_requisitos_propuestas.requisito			
		";
		return guarani_db::consultar($sql, null, false);
    }

	/**
        Valida si un nombre de requisito general se puede usar o no.
    */
	function validar_nombre_requisito($nombre, $requisito)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_requisitos.requisito) as cant
                FROM 	sga_requisitos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_requisitos.nombre', $nombre).'
				';
		
		if (!is_null($requisito)) {
			$requisito = toba::db()->quote($requisito);
			$sql .= " AND sga_requisitos.requisito <> $requisito ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna el grupo para una acción del requisito
	 *
	 * @param integer $requisito
	 */
	function get_requisito_grupo_accion($requisito)
	{
		$requisito = toba::db()->quote($requisito);
		
        $sql = "SELECT	   	sga_requisitos_grupos.grupo_requisito,
        					sga_requisitos_grupos.accion,
        					sga_requisitos_grupos.entidad_subtipo,
        					sga_requisitos_grupos.entidad
                  FROM     	sga_requisitos_x_accion,
                  			sga_requisitos_grupos
                 WHERE		sga_requisitos_x_accion.requisito = $requisito AND
                  			sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito
              ORDER BY		2
  				";
       
        return guarani_db::consultar($sql);
	}

	function get_fecha_limite_presentacion($propuesta_aspira, $requisito, $con_prorroga=false)
	{
		$sql_prorroga = 'null';
		if ($con_prorroga) {
			$sql_prorroga = "(SELECT	sga_requisitos_aspirante.fecha_prorroga
								FROM	sga_requisitos_aspirante
							   WHERE	sga_requisitos_aspirante.propuesta_aspira = $propuesta_aspira
								 AND	sga_requisitos_aspirante.requisito = $requisito)";
		}
		$sql = "SELECT  GREATEST($sql_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos)) as fecha_tope_maxima
				FROM	sga_propuestas_aspira
				JOIN	sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc
				JOIN	sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
				LEFT JOIN sga_requisitos_ingreso_excep ON sga_requisitos_ingreso_excep.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
				                                      AND sga_requisitos_ingreso_excep.requisito = $requisito 
				WHERE	sga_propuestas_aspira.propuesta_aspira	= $propuesta_aspira ";
		
		$res = guarani_db::consultar_fila($sql);

		return $res['fecha_tope_maxima'];
	}

	/**
	 *  Dado un requisito y una persona retorna las propuestas aspira en estado Aceptado o Pendiente de la Persona
	 *  que esta asociada al requisito
	 *  Retorna el Sql o los datos segun $get_sql.
	 *
	 * @return <type>
	 */
	function requisitos_aspirante($persona,$requisitos,$where='', $get_sql = false)
	{
		if (is_array($requisitos)){
			$where .= "AND " .guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos_aspirante', 'requisito') . " ";
		} else {
			$where .= "AND	sga_requisitos_aspirante.requisito	= $requisitos ";
		}

		$sql = "SELECT	sga_requisitos_aspirante.requisito,
						sga_requisitos_aspirante.requisito_aspirante,
						sga_requisitos_aspirante.propuesta_aspira,
						sga_requisitos_aspirante.fecha_prorroga,
						sga_requisitos_aspirante.documento_prorroga,
						sga_requisitos_aspirante.exceptuado,
						sga_requisitos_aspirante.fecha_excepcion,
						sga_requisitos_aspirante.documento_excepcion,
						sga_requisitos_aspirante.motivo_excepcion,
						sga_requisitos_aspirante.observaciones,
						sga_requisitos_aspirante.origen,
						sga_requisitos_aspirante.restrictivo
				FROM 	sga_requisitos_aspirante 
				JOIN	sga_propuestas_aspira ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
				JOIN	sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				WHERE 	sga_propuestas_aspira.persona = $persona
				AND     sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')
				$where
				";

		if ($get_sql) {
			return $sql;
		} else {
			return guarani_db::consultar($sql);
		}
	}

	/**
	 * Retorna los datos de los requisitos de egreso para una persona.
	 * 
	 * @param int $persona
	 * @param array $requisitos
	 * @param bool $get_sql
	 * @return str/array
	 */
	function requisitos_certificado($persona, $requisitos, $get_sql = false)
	{
		$where = " AND ".guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos_certificado', 'requisito');
		$sql = "SELECT	sga_requisitos_certificado.nro_solicitud,
						sga_requisitos_certificado.requisito,
						sga_requisitos_certificado.confirmado,
						sga_requisitos_certificado.validado,
						sga_requisitos_certificado.exceptuado,
						sga_requisitos_certificado.fecha_excepcion,
						sga_requisitos_certificado.documento_excepcion,
						sga_requisitos_certificado.motivo_excepcion,
						sga_requisitos_certificado.observaciones
				FROM	sga_requisitos_certificado
						JOIN sga_certificados_otorg ON sga_requisitos_certificado.nro_solicitud = sga_certificados_otorg.nro_solicitud
				WHERE	sga_certificados_otorg.persona = $persona AND
						sga_certificados_otorg.anulado = 0
						$where
		";
		return ($get_sql ? $sql : guarani_db::consultar($sql));
	}

	function requisitos_digitales_draft_detalle($persona, $requisitos, $get_sql = false)
	{
		$where = " AND ".guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos_digitales_draft_detalle', 'requisito');
		$sql = "SELECT	sga_requisitos_digitales_draft_detalle.persona,
						sga_requisitos_digitales_draft_detalle.requisito,
						sga_requisitos_digitales_draft_detalle.observaciones
				FROM	sga_requisitos_digitales_draft_detalle
				WHERE	sga_requisitos_digitales_draft_detalle.persona = $persona
						$where
		";
		return ($get_sql ? $sql : guarani_db::consultar($sql));
	}

	/**
	 * Obtiene todos los Requisitos que tengan por lo menos una Inscripción en estado pendiente
	 */
	function get_requisitos_con_inscripcion_pendiente($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT	DISTINCT sga_requisitos.requisito,
						sga_requisitos.nombre AS nombre_requisito
        					
                FROM	sga_requisitos_aspirante,
                  		sga_requisitos,
                  		sga_propuestas_aspira,
                  		sga_situacion_aspirante
                  		
                WHERE	sga_propuestas_aspira.propuesta_aspira	= sga_requisitos_aspirante.propuesta_aspira
                AND		sga_requisitos_aspirante.requisito		= sga_requisitos.requisito
                AND		sga_situacion_aspirante.situacion_asp	= sga_propuestas_aspira.situacion_asp
                AND		sga_situacion_aspirante.resultado_asp	= '".propuesta::inscripcion_pendiente."'
                AND		sga_requisitos_aspirante.exceptuado		= '".comunes::no."'
                 		$where
		
                ORDER BY	sga_requisitos.nombre
  				";
		
        return guarani_db::consultar($sql);
	}
	
	/**
	 * Determina si una Propuesta Aspira tiene asociado un Requisito.
    */
	function existe_requisito_de_propuesta_aspira($propuesta_aspira, $requisito)
	{
		if (!is_null($propuesta_aspira)) {
			$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		}	
		if (!is_null($requisito)) {
			$requisito = toba::db()->quote($requisito);
		}
		
		$sql = "SELECT 	count(*) as cant
                FROM 	sga_requisitos_aspirante
				WHERE 	sga_requisitos_aspirante.propuesta_aspira 	= $propuesta_aspira
				AND		sga_requisitos_aspirante.requisito			= $requisito
				";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	function existe_draft_requisito_digital($requisito, $persona)
	{
		$requisito = toba::db()->quote($requisito);
		$persona = toba::db()->quote($persona);
			
		$sql = "SELECT 	COUNT (1) AS cant
				FROM 	sga_requisitos_digitales_draft
				WHERE 	persona = $persona
						AND requisito = $requisito";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	function get_draft_requisito_digital($requisito, $persona)
	{
		$requisito = toba::db()->quote($requisito);
		$persona = toba::db()->quote($persona);
			
		$sql = "SELECT 	
					sga_requisitos_digitales_draft.requisito_digital,
					sga_requisitos_digitales_draft.persona,
					sga_requisitos_digitales_draft.requisito,
					sga_requisitos_digitales_draft.id_documento,
					sga_requisitos_digitales_draft.fecha_carga,
					sga_requisitos_digitales_draft.fecha_carga::date as fecha_carga_date
				FROM 	
					sga_requisitos_digitales_draft
				WHERE 	
					persona = $persona
					AND requisito = $requisito";
		
		return guarani_db::consultar_fila($sql);
	}
	
    /**
        Obtiene los datos de un Requisito para carga externa
    */
    function get_datos_requisito($requisito)
    {
    	if (is_array($requisito)) {
			$filtro = guarani_sql::armar_condicion_in($requisito, 'sga_requisitos', 'requisito');
		} else {
			$requisito = toba::db()->quote($requisito);
			$filtro = "sga_requisitos.requisito = $requisito";
		}
		$sql = "SELECT	sga_requisitos.requisito,
						sga_requisitos.nombre as nombre_requisito,
						sga_requisitos.descripcion as descripcion_requisito,
						sga_requisitos.requisito_tipo,
						sga_requisitos.regla,
						sga_requisitos.requisito_subordinado_de,
						sga_requisitos_tipos.nombre as tipo_requisito,
						sga_requisitos_tipos.icono 
               	FROM 	sga_requisitos,
               			sga_requisitos_tipos
               	WHERE 	sga_requisitos_tipos.requisito_tipo = sga_requisitos.requisito_tipo AND
               			$filtro
		";
			
		return guarani_db::consultar($sql);
    }
    
    function get_requisitos_aspirante($filtro='')
    {
    	$where = isset($filtro) ? "WHERE $filtro" : '';
    	
    	$sql = "SELECT	sga_requisitos_aspirante.requisito_aspirante,
    					sga_requisitos_aspirante.propuesta_aspira
               	FROM	sga_requisitos_aspirante
               	$where
				";				
			
		return guarani_db::consultar($sql);
    }
    
    /**
     * Obtiene los requisitos generales para un aspirante a propuesta.
     *
     */
    function get_requisitos_generales_propuesta_aspira($propuesta_aspira, $where='')
    {
    	$propuesta_aspira = toba::db()->quote($propuesta_aspira);
    	if ($where) {
    		$where = ' AND '.$where;
    	}
    	
    	$sql = "SELECT 
					sga_requisitos_aspirante.requisito_aspirante,
					sga_requisitos_aspirante.propuesta_aspira,
					sga_requisitos_aspirante.requisito,
					sga_requisitos_aspirante.restrictivo,
					sga_requisitos_aspirante.fecha_prorroga,
					sga_requisitos_aspirante.documento_prorroga,
					sga_requisitos_aspirante.exceptuado,
					sga_requisitos_aspirante.fecha_excepcion,
					sga_requisitos_aspirante.documento_excepcion,
					sga_requisitos_aspirante.motivo_excepcion,
					sga_requisitos_aspirante.observaciones,
					sga_requisitos_aspirante.origen,
					sga_requisitos_aspirante.parametros,
					sga_requisitos.requisito_tipo,
					sga_requisitos.regla
				FROM sga_requisitos_aspirante
				JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
				WHERE sga_requisitos_aspirante.propuesta_aspira = $propuesta_aspira
    			$where
				";
    	
    	return guarani_db::consultar($sql);
    }

    /**
     * Retorna los requisitos configurados para un plan-versión y una acción dados.
     *
     * @param integer $plan_version
     * @param integer $accion
     * @return array
     */
    function get_requisitos_plan_version_accion($plan_version, $accion)
    {
    	$plan_version = toba::db()->quote($plan_version);
    	$accion = toba::db()->quote($accion);
    	$sql = "SELECT	DISTINCT sga_requisitos_x_accion.requisito,
    					sga_requisitos.nombre
    			FROM	sga_requisitos_aplanado,
    					sga_requisitos_x_accion,
    					sga_requisitos_grupos,
    					sga_requisitos
    			WHERE	sga_requisitos_aplanado.requisito_accion = sga_requisitos_x_accion.requisito_accion AND
    					sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito AND
    					sga_requisitos_x_accion.requisito = sga_requisitos.requisito AND
    					sga_requisitos_aplanado.plan_version = $plan_version AND
    					sga_requisitos_grupos.accion = $accion
   				ORDER BY sga_requisitos.nombre
    	";
    	return guarani_db::consultar($sql);
    }

	/**
	* Retorna el listado de requisitos exceptuados de un alumno. Requisitos registrados para el ingreso
	*
	* @param integer $alumno
	* @return array
	*/
	function get_requisitos_exceptuados($alumno)
	{

		$sql = "SELECT	sga_requisitos_aspirante.requisito
			FROM 	sga_alumnos
			JOIN	sga_propuestas_aspira ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
			JOIN	sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
			JOIN	sga_requisitos_aspirante ON sga_requisitos_aspirante.propuesta_aspira = sga_propuestas_aspira.propuesta_aspira
			WHERE	sga_alumnos.alumno = $alumno
			AND	sga_requisitos_aspirante.exceptuado = '". comunes::si . "'
			AND     sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')
			";
		return guarani_db::consultar($sql);

	}



    /**
     * Retorna el listado de excepciones de requisitos.
     *
     * @param str $where
     * @return array
     */
	function get_requisitos_excepciones($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT	sga_requisitos_excepciones.excepcion,
						sga_requisitos_excepciones.tipo_excepcion,
						sga_requisitos_excepciones.accion,
						sga_requisitos_excepciones.requisito,
						sga_requisitos_excepciones.alumno,
						sga_requisitos.nombre as requisito_nombre,
						sga_acciones.nombre as accion_nombre,
						sga_elementos.elemento,
						sga_elementos.nombre as actividad_nombre, 
						sga_elementos.codigo,
						-- Si la excepción no tiene actividad asociada, se considera que aplica a todas las del Plan del Alumno.
						COALESCE(COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre, 'Todas') AS actividad_descr,
						sga_requisitos_excepciones.fecha_desde,
						sga_requisitos_excepciones.fecha_hasta,
						sga_requisitos_excepciones.fecha_alta,
						sga_requisitos_excepciones.persona,
						sga_requisitos_excepciones.observaciones
				FROM sga_requisitos_excepciones
				JOIN sga_requisitos ON sga_requisitos.requisito = sga_requisitos_excepciones.requisito
				LEFT JOIN sga_acciones ON sga_acciones.accion = sga_requisitos_excepciones.accion 
				LEFT JOIN sga_elementos ON sga_requisitos_excepciones.elemento = sga_elementos.elemento
				$where
				ORDER BY	sga_requisitos_excepciones.fecha_desde
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si una excepción de requisito está vigente a la fecha de hoy.
	 *
	 * @param integer $excepcion
	 * @return boolean
	 */
	function requisito_excepcion_vigente($excepcion)
	{
		$excepcion = toba::db()->quote($excepcion);
		$sql = "SELECT	CASE
				WHEN CURRENT_DATE BETWEEN sga_requisitos_excepciones.fecha_desde AND COALESCE(sga_requisitos_excepciones.fecha_hasta, CURRENT_DATE) THEN 1
				ELSE 0
				END as vigente	
			FROM	sga_requisitos_excepciones
			WHERE	sga_requisitos_excepciones.excepcion = $excepcion
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['vigente'] == 1);
	}
	
	function get_requisitos_regla($regla=null)
	{
		if (!is_null($regla)) {
			$regla = toba::db()->quote($regla);
			$sql = "SELECT		sga_requisitos.requisito,
								sga_requisitos.nombre
					FROM		sga_requisitos
					WHERE		sga_requisitos.regla = $regla
					ORDER BY	sga_requisitos.nombre
			";
			return guarani_db::consultar($sql);
		}
	}

	/**
	 * Obtiene los documentos cargados como requisitos digitales, asociados a la persona
	 * y los requisitos documentales dados.
	 * @param int $persona id de la persona para la que se buscan documentos digitales
	 * @param array $requisitos conjunto de requisitos documentales para los que se buscan
	 * documentos digitales asociados.
	 * @return array registros de sga_requisitos_digitales correspondientes a los documentos
	 * digitales cargados para la persona y requisitos documentales dados (pueden estar o no
	 * ya presentados, lo que se indica a partir del campo requisito_presentado con valor o null).
	 */
	function get_requisitos_digitales_persona($persona, $requisitos)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 	sga_requisitos_digitales.requisito_digital,
						sga_requisitos_digitales.persona,
						sga_requisitos_digitales.requisito,
						sga_requisitos_digitales.id_documento
				FROM 	sga_requisitos_digitales 
				WHERE	sga_requisitos_digitales.persona = $persona
						AND ".guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos_digitales', 'requisito', 'requisito')."
				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_datos_requisitos_digitales($requisitos_digitales)
	{
		if (!empty($requisitos_digitales)) {
			$where = 'WHERE sga_requisitos_digitales.requisito_digital IN (' . implode(',', $requisitos_digitales) . ')' ;
		} else {
			$where = '';
		}
		
		$sql = "SELECT 	sga_requisitos_digitales.requisito_digital,
						sga_requisitos_digitales.persona,
						sga_requisitos_digitales.requisito,
						sga_requisitos_digitales.id_documento
				FROM 	sga_requisitos_digitales 
				$where
				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_info_requisito_digital($requisito_digital)
	{
		$requisito_digital = toba::db()->quote($requisito_digital);
				
		$sql = "SELECT 
					sga_requisitos_digitales.requisito_digital,
					sga_requisitos_digitales.persona,
					sga_requisitos_digitales.id_documento,
					sga_requisitos.requisito,
					sga_requisitos.nombre,
					sga_requisitos.requisito_tipo,
					sga_requisitos.requisito_subordinado_de,
					sga_requisitos.descripcion,
					sga_requisitos.regla,
					sga_requisitos.cacheable,
					sga_requisitos.requisito_ingreso,
					sga_requisitos.activo,
					sga_requisitos.tipo_documento_digital
				FROM  sga_requisitos_digitales
				JOIN sga_requisitos ON	sga_requisitos_digitales.requisito = sga_requisitos.requisito
				WHERE sga_requisitos_digitales.requisito_digital = $requisito_digital";
		
		return guarani::db()->consultar_fila($sql);
	}
	
	function get_requisitos_presentados_vigentes_persona($persona, $requisitos)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 	sga_requisitos_presentados.requisito_presentado,
						sga_requisitos_presentados.persona,
						sga_requisitos_presentados.requisito,
						sga_requisitos_presentados.fecha_presentacion,
						sga_requisitos_presentados.fecha_vencimiento,
						sga_requisitos_presentados.fecha_alta,
						sga_requisitos_presentados.requisito_digital
				FROM 	sga_requisitos_presentados 
				WHERE sga_requisitos_presentados.persona = $persona
				  AND (sga_requisitos_presentados.fecha_vencimiento IS NULL OR sga_requisitos_presentados.fecha_vencimiento >= CURRENT_DATE)
				  -- AND f_estado_requisito(sga_requisitos_presentados.persona, sga_requisitos_presentados.requisito) = '" .requisito::estado_vigente ."'
				  AND ".guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos_presentados', 'requisito', 'requisito')."
				;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_requisitos_vencidos_persona($persona, $requisito = null)
	{
		$persona = toba::db()->quote($persona);
		if (!is_null($requisito)) {
			$where = ' AND sga_requisitos_presentados.requisito = ' . toba::db()->quote($requisito);
		} else {
			$where = '';
		}
		
		$sql = "SELECT 	sga_requisitos_presentados.requisito_presentado,
						sga_requisitos_presentados.persona,
						sga_requisitos_presentados.requisito,
						sga_requisitos.nombre AS requisito_nombre, 
						sga_requisitos_presentados.fecha_presentacion,
						sga_requisitos_presentados.fecha_vencimiento,
						sga_requisitos_presentados.fecha_alta,
						sga_requisitos_presentados.requisito_digital,
						sga_requisitos_digitales.id_documento
				FROM sga_requisitos_presentados 
				JOIN sga_requisitos ON sga_requisitos_presentados.requisito = sga_requisitos.requisito
				LEFT JOIN sga_requisitos_digitales ON sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital
				WHERE sga_requisitos_presentados.persona = $persona
				  AND sga_requisitos_presentados.fecha_vencimiento IS NOT NULL
				  AND sga_requisitos_presentados.fecha_vencimiento < CURRENT_DATE
				  $where
				ORDER BY sga_requisitos_presentados.fecha_vencimiento DESC, sga_requisitos_presentados.requisito
				;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de requisitos por acción por plan-versión según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
    function get_requisitos_propuesta($where='')
    {
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT	sga_requisitos_x_accion.tipo,
						sga_requisitos_x_accion.operador_not,
						CASE
							WHEN sga_requisitos_x_accion.tipo = '".requisito::comp_requisito."'	THEN sga_requisitos.nombre
							WHEN sga_requisitos_x_accion.tipo = '".requisito::comp_entidad_estado."' THEN ''
						END as requisito_nombre,
						sga_requisitos_x_accion.entidad,
						sga_requisitos_x_accion.parametros,
						sga_estados.nombre as estado_nombre,
						sga_acciones.nombre as accion_nombre,
						sga_propuestas.nombre as propuesta_nombre,
						sga_planes.nombre as plan_nombre,
						sga_planes_versiones.nombre as plan_version_nombre
				FROM	sga_requisitos_aplanado
				JOIN	sga_requisitos_x_accion ON sga_requisitos_aplanado.requisito_accion = sga_requisitos_x_accion.requisito_accion
				JOIN	sga_requisitos_grupos ON sga_requisitos_x_accion.grupo_requisito = sga_requisitos_grupos.grupo_requisito
				JOIN	sga_acciones ON sga_requisitos_grupos.accion = sga_acciones.accion
				JOIN	sga_planes_versiones ON sga_requisitos_aplanado.plan_version = sga_planes_versiones.plan_version
				JOIN	sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN	sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				LEFT JOIN sga_requisitos ON sga_requisitos_x_accion.requisito = sga_requisitos.requisito
				LEFT JOIN sga_estados ON sga_requisitos_x_accion.estado	= sga_estados.estado
				
				$where
				
				ORDER BY	requisito_nombre,
							sga_acciones.nombre,
							sga_propuestas.nombre,
							sga_planes.nombre,
							sga_planes_versiones.nombre
    	";
    	$datos = guarani_db::consultar($sql);
		foreach ($datos as $clave => $valor) {
			if ($valor['tipo'] == requisito::comp_entidad_estado) {
				$datos[$clave]['requisito_nombre'] = toba::consulta_php('co_g3entidades')->get_nombre_entidad($valor['entidad']);
			}
		}
		return $datos;
    }
    
    function get_requisitos_digitales_preinscripcion($persona)
    {
        $sql = "SELECT  sga_requisitos.tipo_documento_digital,
						sga_requisitos.nombre,
						sga_requisitos_digitales_draft.requisito_digital,
						sga_requisitos_digitales_draft.persona,
						sga_requisitos_digitales_draft.requisito,
						sga_requisitos_digitales_draft.id_documento
				FROM	sga_requisitos_digitales_draft
						JOIN	sga_requisitos ON sga_requisitos_digitales_draft.requisito = sga_requisitos.requisito
						WHERE	persona = $persona
                ";
        return guarani_db::consultar($sql);
    }

	function get_requisitos_ingreso_documentales()
	{
		$sql = "SELECT		DISTINCT sga_requisitos.requisito,
							sga_requisitos.nombre
				FROM		sga_requisitos
							JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito
				WHERE		sga_requisitos.requisito_tipo = ". requisito::tipo_documentales." AND
							sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'
				ORDER BY	sga_requisitos.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_requisitos_egreso()
	{
		$sql = "SELECT		DISTINCT sga_requisitos.requisito,
							sga_requisitos.nombre
				FROM		sga_requisitos
							JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito
				WHERE		sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_egreso."'
				ORDER BY	sga_requisitos.nombre
		";
		return guarani_db::consultar($sql);
	}
}
