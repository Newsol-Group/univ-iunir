<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Personas
 */
class co_personas
{
	/**
        Obtiene una lista de personas según el filtro recibido
		Si la persona es un docente, recupera solo el legajo activo.
    */
	function get_listado($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
			$where = ' AND '.$where.' ';			        
		}

		$subquery_documentos = $this->subquery_documentos();

		$sql = "SELECT 		mdp_personas.persona,
							COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
							COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido_elegido,
 	 	                    COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres_elegido,	
							COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) || ' ' || COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as apellido_nombres_elegido,
							mdp_personas.apellido || ' ' ||mdp_personas.nombres as apellido_nombres_oficial,
        					 COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
        					mdp_personas.sexo as sexo_id,
        					mdp_personas_sexo.descripcion as sexo_valor,
							mdp_personas.identidad_genero,
        					mdp_identidad_genero.nombre as identidad_genero_nombre,
							mdp_personas.identidad_genero_otro,
        					mdp_personas.fecha_nacimiento,
				        	mdp_personas.localidad_nacimiento as localidad_id,
				        	mug_localidades.nombre as localidad_valor, 
				        	mdp_personas.nacionalidad as nacionalidad_id,
				        	mdp_nacionalidades.descripcion as nacionalidad_valor,
				        	mdp_personas.fecha_ingreso_pais,
				        	mdp_personas.usuario,
				        	mdp_personas_documentos.nro_documento,
				        	COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as tipo_documento,
						COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) || ' ' || mdp_personas_documentos.nro_documento as identificacion,
				        	COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) || ', ' || COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombre_completo,
						COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) || ', ' || COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombre_completo_elegido,
        					COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) || ' ' || mdp_personas_documentos.nro_documento as documento,
        					to_char(mdp_personas.fecha_nacimiento,'".comunes::formato_fecha_visual."') as fecha_nacimiento_formato_visual,
        					to_char(mdp_personas.fecha_nacimiento,'".comunes::formato_fecha_visual."')  || ' - ' || mug_localidades.nombre_abreviado || ', ' || mug_provincias.nombre || ', ' || mug_paises.nombre	as fecha_lugar_nacimiento,
        					mdp_personas_documentos.tipo_documento as tipo_doc,
        					mdp_personas_documentos.pais_documento as pais_doc,
							mdp_personas_documentos.validado_con_renaper as validado_con_renaper,
        					mdp_personas_extranjeros.tipo_visa,
        					mdp_personas_extranjeros.vencimiento_visa,
                            mdp_personas_extranjeros.otorgamiento_visa,
                            mdp_personas_extranjeros.otorgamiento_residencia,
                            mdp_personas_extranjeros.vencimiento_residencia,
                            mdp_personas_extranjeros.residencia_cupo,
        					sga_docentes.legajo,
							mdp_tipo_visa.descripcion as tipo_visa_descripcion,
							mdp_tipo_residencia.descripcion as tipo_residencia_descripcion,
							{$subquery_documentos}
				FROM 		mdp_personas
					 		JOIN mdp_personas_sexo ON mdp_personas.sexo = mdp_personas_sexo.sexo 
					 		JOIN mdp_nacionalidades ON mdp_personas.nacionalidad = mdp_nacionalidades.nacionalidad
							LEFT JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
							LEFT JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
							LEFT JOIN mug_localidades ON mdp_personas.localidad_nacimiento = mug_localidades.localidad						
							LEFT JOIN mug_dptos_partidos ON mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
							LEFT JOIN mug_provincias ON mug_dptos_partidos.provincia = mug_provincias.provincia
							LEFT JOIN mug_paises ON mug_provincias.pais = mug_paises.pais
							LEFT JOIN mdp_personas_extranjeros ON mdp_personas.persona = mdp_personas_extranjeros.persona
							LEFT JOIN mdp_tipo_visa ON mdp_personas_extranjeros.tipo_visa = mdp_tipo_visa.tipo_visa
							LEFT JOIN mdp_tipo_residencia ON mdp_personas_extranjeros.tipo_residencia = mdp_tipo_residencia.tipo_residencia
							LEFT JOIN sga_docentes ON (sga_docentes.persona = mdp_personas.persona AND sga_docentes.estado = '".comunes::estado_activo."')
							LEFT JOIN mdp_identidad_genero ON mdp_personas.identidad_genero = mdp_identidad_genero.identidad_genero
							LEFT JOIN mdp_tipo_documento_pais on mdp_personas_documentos.tipo_documento = mdp_tipo_documento_pais.tipo_documento AND mdp_personas_documentos.pais_documento = mdp_tipo_documento_pais.pais
				WHERE		true
							$where
				ORDER BY	 COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
				;";

		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
		
	}

	// https://stackoverflow.com/questions/15847173/concatenate-multiple-result-rows-of-one-column-into-one-group-by-another-column
	// https://www.sisense.com/blog/listagg-group-concat-and-string-agg/
	// https://www.geeksforgeeks.org/how-to-use-string_agg-to-concatenate-strings-in-postgresql/
	function subquery_documentos($columna_persona = 'mdp_personas.persona', $alias = 'documentos')
	{
		return "
			(
				SELECT
					STRING_AGG(COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) || ' (' || mug_paises.nombre || ') ' || mdp_personas_documentos.nro_documento, ' - ' ORDER BY mdp_tipo_documento.orden_principal) AS documentos
				FROM mdp_personas_documentos
				JOIN mdp_tipo_documento ON (mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento)
				JOIN mdp_tipo_documento_pais ON (mdp_personas_documentos.tipo_documento = mdp_tipo_documento_pais.tipo_documento AND mdp_personas_documentos.pais_documento = mdp_tipo_documento_pais.pais)
				JOIN mug_paises ON (mdp_tipo_documento_pais.pais = mug_paises.pais)
				WHERE mdp_personas_documentos.persona = {$columna_persona}
				GROUP BY mdp_personas_documentos.persona
			) AS {$alias}
		";
	}

	/**
	 * Retorna el listado de datos básicos de las personas usando una vista que puede ser filtrada por perfil de datos.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_personas_perfil_de_datos($where='')
	{
		if ($where) {
			$where = "AND $where";
		}
		$where_gatillo = 'true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'vw_perfil_de_datos_personas', 'personas');
		}

		$subquery_documentos = $this->subquery_documentos('personas.persona');

		$sql = "SELECT		personas.persona,
							personas.apellido,
							personas.nombres,
							personas.tipo_nro_documento as documento,
							{$subquery_documentos}
				FROM		vw_perfil_de_datos_personas as personas
				WHERE		$where_gatillo
							$where
		";
		/* Cuando el usuario logueado tiene perfil de datos hay que agregar a lo anterior (alumnos y docentes de la UG) los administrativos (no alumnos ni docentes) tales que:
		 * - no tienen perfil de datos
		 * - tienen perfil de datos y su UG coincide con la del usuario
		 */
		if (! empty($perfil_datos)) {
			$schema_toba = toba::instancia()->get_db()->get_schema();
			$filtro_ugs_perfil_datos = guarani_sql::armar_condicion_in(toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos), "$schema_toba.apex_usuario_perfil_datos_dims", 'clave', 'clave');
			$sql .= "	UNION
						SELECT		personas.persona,
									COALESCE(personas.apellido_elegido, personas.apellido) as apellido,
									COALESCE(personas.nombres_elegido, personas.nombres) as nombres,
									mdp_tipo_documento.desc_abreviada || ' ' || mdp_personas_documentos.nro_documento as documento,
									{$subquery_documentos}
						FROM		mdp_personas as personas
									JOIN mdp_personas_documentos ON personas.documento_principal = mdp_personas_documentos.documento
									JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
									LEFT JOIN mdp_personas_perfil_datos ON personas.persona = mdp_personas_perfil_datos.persona
									LEFT JOIN $schema_toba.apex_usuario_perfil_datos_dims ON mdp_personas_perfil_datos.usuario_perfil_datos = $schema_toba.apex_usuario_perfil_datos_dims.usuario_perfil_datos
						WHERE		NOT EXISTS (SELECT	1
												FROM	sga_alumnos
												WHERE	sga_alumnos.persona = personas.persona 
												UNION
												SELECT	1
												FROM	sga_docentes
												WHERE	sga_docentes.persona = personas.persona) AND
									(mdp_personas_perfil_datos.usuario_perfil_datos IS NULL OR $filtro_ugs_perfil_datos)
									$where
						GROUP BY	personas.persona,
									mdp_tipo_documento.desc_abreviada,
									mdp_personas_documentos.nro_documento
			";
		}
		$sql .=	'ORDER BY	apellido,
							nombres';
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna las Personas que son Alumnos
	 *
	 */
	function get_personas_alumnos($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "SELECT 		DISTINCT
							vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							vw_personas.apellido_nombres_elegido,
							vw_personas.documento,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							vw_personas.tipo_nro_documento AS documento_desc
				FROM 		vw_personas,
							sga_alumnos
				WHERE  		sga_alumnos.persona = vw_personas.persona
					   		$where
				ORDER BY	vw_personas.apellido;";

		return guarani_db::consultar($sql);

	}

	/**
	 * Retorna las Personas que son Alumnos y/o Docentes según los filtros recibidos
	 *
	 */
	function get_personas_alumnos_o_docentes($where='', $where_docentes='')
	{
		if ($where) {
			$where = " AND $where ";
		}
		
		if ($where_docentes) {
			$where_docentes = " AND $where_docentes ";
			
			$sql = "SELECT	DISTINCT
							vw_personas.persona,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento
					FROM 	vw_personas,
							sga_docentes
					WHERE  	sga_docentes.persona = vw_personas.persona
							$where
							$where_docentes
					
					ORDER BY apellido_nombres
					";
		} else {
		
			$sql = "SELECT	DISTINCT
							vw_personas.persona,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento
					FROM 	vw_personas,
							sga_alumnos
					WHERE  	sga_alumnos.persona = vw_personas.persona
							$where
			
					UNION
					
					SELECT	DISTINCT
							vw_personas.persona,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento
					FROM 	vw_personas,
							sga_docentes
					WHERE  	sga_docentes.persona = vw_personas.persona
							$where
					
					ORDER BY apellido_nombres
					";
		}
		
		return guarani_db::consultar($sql);

	}

	/**
	 * Retorna las Personas que son Administradores
	 * @see https://redmine.siu.edu.ar/redmine/issues/32755 Ticket #32755
	 *
	 */
	function get_personas_admin($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$id_perfil_admin = toba::db()->quote(usuario::id_perfil_admin);

		$sql = "SELECT 		DISTINCT
							vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							vw_personas.apellido_nombres_elegido,
							vw_personas.documento,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							vw_personas.tipo_nro_documento AS documento_desc
				FROM 		vw_personas
				JOIN 		mdp_personas_grupo_acc ON (vw_personas.persona = mdp_personas_grupo_acc.persona)
				WHERE  		mdp_personas_grupo_acc.usuario_grupo_acc = $id_perfil_admin
					   		$where
				ORDER BY	vw_personas.apellido;";

		return guarani_db::consultar($sql);

	}
	
	/**
        Obtiene los datos relacionados a la persona correspondiente al id recibido.
    */
	function get_datos_persona($id=null)
	{
		if (!is_null($id)) {
	   		$id = toba::db()->quote($id);
	   		$result = $this->get_listado("mdp_personas.persona = $id");
			if (!empty($result)) {
	   			return $result[0];	
			}
		} else {
			return array();
		}
	}
	
	/**
 	  *  Retorna los datos de una Persona en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los datos de las Personas según la condición del array (para la carga masiva).
      *	 @param integer/array	persona	id o arreglo de ids de la/las Personas para los cuales se consultan los datos.
    */
	function get_datos_personas($persona)
	{
		if (is_array($persona)) {
			$filtro = guarani_sql::armar_condicion_in($persona, 'mdp_personas', 'persona');
			$datos = $this->get_listado($filtro);
			return $datos;
		} 
		return $this->get_datos_persona($persona);
	}

	/**
	 * Retorna el apellido y nombres de una persona dada.
	 *
	 * @param integer $persona
	 * @return string
	 */
	function get_nombre_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT	COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) || ' ' || COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as apellido_nombres
				FROM	mdp_personas
				WHERE	mdp_personas.persona = $persona
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['apellido_nombres'];
	}

	/**
        Obtiene nombres de personas, según el filtro ingresado.
    */
	function get_descr_persona($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT	mdp_personas.persona,
						    COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as persona_nombre
  				FROM 	mdp_personas
  				$where
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de una persona en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de las personas según la condición del array (para la carga masiva).
      *	 @param integer/array	id o arreglo de ids de la/s persona/s para las cuales se consultan los nombres.
    */
	function get_nombre_apellido_persona($persona)
	{
		if (is_array($persona)) {
			$filtro = guarani_sql::armar_condicion_in($persona, 'mdp_personas', 'persona');
			$datos = $this->get_descr_persona($filtro);
			return $datos;
		} 
		$persona = toba::db()->quote($persona);
		$datos = $this->get_descr_persona("mdp_personas.persona = $persona");
		return $datos[0];
	}
	
	/**
	 * Retorna el país de origen de una persona dada.
	 *
	 * @param integer $persona
	 * @return string
	 */
	function get_pais_origen_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$nac_extranjero = toba::db()->quote(persona::nac_extranjero);
		$pais_argentina = toba::db()->quote(persona::pais_argentina);
		$sql = "SELECT	
				CASE WHEN mdp_personas.nacionalidad = $nac_extranjero
						THEN 
							(SELECT nombre FROM mug_paises WHERE mug_paises.pais = mdp_personas.pais_origen)
						ELSE
							(SELECT nombre FROM mug_paises WHERE mug_paises.pais = $pais_argentina)
					END AS pais_origen
				FROM	mdp_personas
				WHERE	mdp_personas.persona = $persona
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['pais_origen'];
	}
	
	/**
 	 * Verifica si una tupla <pais, tipo, nro_documento> es el documento principal 
	 * de una persona del sistema.
	 * 
     * @param int pais_doc 
     * @param int tipo_doc
     * @param int nro_doc
     * @return boolean 
     */
	function existe_persona($pais_doc, $tipo_doc, $nro_doc, $persona = null)
	{
		$pais_doc = toba::db()->quote($pais_doc);
		$tipo_doc = toba::db()->quote($tipo_doc);
		$nro_doc = toba::db()->quote($nro_doc);
        $where = '';
        if ($persona) {
            $where = ' AND mdp_personas.persona <> '.$persona;
        }
        
		$sql = "SELECT	COUNT(1) as cant
				FROM	mdp_personas
       			JOIN	mdp_personas_documentos ON mdp_personas_documentos.persona = mdp_personas.persona
                WHERE    mdp_personas_documentos.pais_documento = $pais_doc 
                AND mdp_personas_documentos.tipo_documento = $tipo_doc
                AND mdp_personas_documentos.nro_documento = $nro_doc
                $where
				;";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 1);
	}
	
	/**
 	 * Verifica si una tupla <pais, tipo, nro_documento> es el documento de una persona del sistema.
	 * 
         * @param int $pais_doc
         * @param int $tipo_doc
         * @param string $nro_doc
         * @return int|null
         */
	function get_persona_por_documento($pais_doc, $tipo_doc, $nro_doc)
	{
		$nro_doc = toba::db()->quote($nro_doc);
        
		$sql = "SELECT persona
                        FROM mdp_personas_documentos
                        WHERE pais_documento = {$pais_doc}
                        AND tipo_documento = {$tipo_doc}
                        AND nro_documento = {$nro_doc};";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['persona'] ?? null;
	}
	
	/**
        Obtiene una lista de los sexos de las personas en la base de datos
    */
	function get_sexos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
	   	$sql = "SELECT		mdp_personas_sexo.sexo as valor,
	   						mdp_personas_sexo.descripcion as descr
				FROM 		mdp_personas_sexo
				$where
				ORDER BY	mdp_personas_sexo.descripcion
		";	        
		return guarani_db::consultar($sql);
	}
    
    function get_entes_emisores_ci()
    {
        $sql = "SELECT		mdp_entes_emisores_ci.entidad as valor,
	   						mdp_entes_emisores_ci.nombre as descr
				FROM 		mdp_entes_emisores_ci
				ORDER BY	mdp_entes_emisores_ci.entidad
		";	        
		return guarani_db::consultar($sql);
    }
    
	function get_identidades_genero($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
	   	$sql = "SELECT		mdp_identidad_genero.identidad_genero as valor,
	   						mdp_identidad_genero.nombre as descr
				FROM 		mdp_identidad_genero
				$where
				ORDER BY	mdp_identidad_genero.identidad_genero
		";	        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de las nacionalidades de las personas en la base de datos
    */
	function get_nacionalidades($id=null)
	{
		$where = '';
		if ($id != null) {
			$id = toba::db()->quote($id);
			$where = " WHERE mdp_nacionalidades.nacionalidad = $id";
		}
	   	$sql = "SELECT		mdp_nacionalidades.nacionalidad as valor,
	   						mdp_nacionalidades.descripcion as descr
				FROM 		mdp_nacionalidades
				$where
	   			ORDER BY	mdp_nacionalidades.descripcion
			   ;";
			        
		return guarani_db::consultar($sql);
	}

	/**
        Obtiene una lista de los tipos de documentos de las personas en la base de datos
    */
	function get_tipos_documentos($id=null)
	{
		$where = '';
		if ($id != null) {
			$id = toba::db()->quote($id);
			$where = " WHERE mdp_tipo_documento.tipo_documento = $id";
		}
	   	$sql = "SELECT		mdp_tipo_documento.tipo_documento as valor,
	   						mdp_tipo_documento.descripcion as descr,
	   						mdp_tipo_documento.desc_abreviada as descr_ab,
							mdp_tipo_documento.exp_regular_validacion,
							mdp_tipo_documento.exp_regular_mensaje
				FROM 		mdp_tipo_documento
				$where
	   			ORDER BY 	mdp_tipo_documento.orden_principal
			   ";
			        
		return guarani_db::consultar($sql);
	}
	/**
         * Obtiene una lista de los tipos de documentos de las personas en la base de datos según país de origen
         * @param int $pais
         * @param int|null $tipo_documento
         * @param string|null $habilita_inscripcion Puede ser 'S', 'N' o null para que no filtre por la columna mdp_tipo_documento.habilita_inscripcion
         */
	function get_datos_tipos_documentos_x_pais($pais, $tipo_documento = null, $habilita_inscripcion = null)
	{
		$where = '';
		if ($pais != null) {
			if (is_array($pais)) {
				$filtro = guarani_sql::armar_condicion_in($pais, 'mdp_tipo_documento_pais', 'pais');
				$where = " AND $filtro";	
			} else {
				$pais = toba::db()->quote($pais);
				$where = " AND mdp_tipo_documento_pais.pais = $pais";
			}
		}
		if (!is_null($tipo_documento)) {
			$tipo_documento = toba::db()->quote($tipo_documento);
			$where .= " AND mdp_tipo_documento.tipo_documento = $tipo_documento";
		}

                // Si viene el parámetro $habilita_inscripcion filtro por el mismo
                if (!is_null($habilita_inscripcion)) {
                    $where .= " AND mdp_tipo_documento.habilita_inscripcion = '{$habilita_inscripcion}'";
                }

	   	$sql = "SELECT		mdp_tipo_documento.tipo_documento as valor,
							mdp_tipo_documento.orden_principal,
							mdp_tipo_documento.tipo_documento,
							COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as tipo_nombre,
	   						COALESCE(mdp_tipo_documento_pais.descripcion, mdp_tipo_documento.descripcion) as descr ,
							COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as descr_ab,
							COALESCE(mdp_tipo_documento_pais.exp_regular_validacion, mdp_tipo_documento.exp_regular_validacion) as exp_regular_validacion ,
							COALESCE(mdp_tipo_documento_pais.exp_regular_mensaje, mdp_tipo_documento.exp_regular_mensaje) as exp_regular_mensaje,
							mdp_tipo_documento_pais.pais
				FROM 		mdp_tipo_documento
							JOIN mdp_tipo_documento_pais on mdp_tipo_documento.tipo_documento = mdp_tipo_documento_pais.tipo_documento 
				WHERE		mdp_tipo_documento_pais.activo = '".comunes::si."'
                                $where
	   			ORDER BY 	mdp_tipo_documento.orden_principal
		";
		return guarani_db::consultar($sql);
	}

	/**
        Obtiene nombres de paises, según el filtro ingresado.
    */
	function get_nombre_pais($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT	mug_paises.pais as pais_documento,
	   					mug_paises.nombre as pais_nombre
				FROM 	mug_paises 
				$where
				ORDER BY mug_paises.nombre
				;";
			        
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de un País en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Países según la condición del array (para la carga masiva).
      *	 @param integer/array	pais	id o arreglo de ids del o de los Países para los cuales se consultan los nombres.
    */
	function get_nombre_pais_documento($pais)
	{
		if (is_array($pais)) {
			$filtro = guarani_sql::armar_condicion_in($pais, 'mug_paises', 'pais');
			$datos = $this->get_nombre_pais($filtro);
			return $datos;
		} 
		$pais = toba::db()->quote($pais);
		$datos = $this->get_nombre_pais("mug_paises.pais = $pais");
		return $datos[0];
	}
	
	/**
        Obtiene nombres de tipos de documentos, según el filtro ingresado.
    */
	function get_descr_tipo_documento($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT	mdp_tipo_documento.tipo_documento,
						mdp_tipo_documento.desc_abreviada as tipo_nombre,
						mdp_tipo_documento.orden_principal
				FROM	mdp_tipo_documento
				$where
		";
		return guarani_db::consultar($sql);
	}

	/**
 	  *  Retorna el nombre de un Tipo de Documento en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Tipos de Documentos según la condición del array (para la carga masiva).
      *	 @param integer/array	pais	id o arreglo de ids del o de los Tipos de Documentos para los cuales se consultan los nombres.
    */
	function get_nombre_tipo_documento($pais_documento, $tipo_documento)
	{
		if (is_array($pais_documento) && is_array($tipo_documento)) {
			$filtro = guarani_sql::armar_condicion_in_dupla($pais_documento, 'mdp_tipo_documento_pais', 'pais', '', $tipo_documento, 'mdp_tipo_documento', 'tipo_documento', '');
		} else {
			$pais_documento = toba::db()->quote($pais_documento);
			$tipo_documento = toba::db()->quote($tipo_documento);
			$filtro = "mdp_tipo_documento_pais.pais = $pais_documento AND mdp_tipo_documento.tipo_documento = $tipo_documento";
		}
		$sql = "SELECT	mdp_tipo_documento_pais.pais as pais_documento,
						mdp_tipo_documento.tipo_documento,
						mdp_tipo_documento.orden_principal,
						COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as tipo_nombre
				FROM	mdp_tipo_documento
						JOIN mdp_tipo_documento_pais ON mdp_tipo_documento.tipo_documento = mdp_tipo_documento_pais.tipo_documento
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene nombres de tipos de allegados relacionados a una persona, según el filtro recibido.
    */
	function get_descr_tipo_allegado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
	   	$sql = 'SELECT	mdp_tipo_allegado.tipo_allegado,
	   					mdp_tipo_allegado.descripcion as tipo_allegado_nombre 
				FROM 	mdp_tipo_allegado
				' . $where;
			        
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de un Tipo de Allegado en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Tipos de Allegados según la condición del array (para la carga masiva).
      *	 @param integer/array	tipo_allegado	id o arreglo de ids del o de los Tipos de Allegados para los cuales se consultan los nombres.
    */
	function get_nombre_tipo_allegado($tipo_allegado)
	{
		if (is_array($tipo_allegado)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_allegado, 'mdp_tipo_allegado', 'tipo_allegado');
			$datos = $this->get_descr_tipo_allegado($filtro);
			return $datos;
		}
		$tipo_allegado = toba::db()->quote($tipo_allegado);
		$datos = $this->get_descr_tipo_allegado("mdp_tipo_allegado.tipo_allegado = $tipo_allegado");
		return $datos[0];
	}
	
	/**
        Obtiene el nombre de un tipo de allegado dado relacionado a una persona.
    */
	function get_apellidos_y_nombres_allegado($apellido, $nombres)
	{
		return array('apellido_y_nombres'=>$apellido . ' ' . $nombres);
	}
	
	/**
        Obtiene los nombres de títulos correspondientes a los estudios de una persona, según el filtro recibido.
    */
	function get_descr_titulo($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = 'SELECT	mdp_titulos.titulo,
	   					mdp_titulos.nombre as titulo_nombre
				FROM 	mdp_titulos
				'. $where;
				        
		return guarani_db::consultar($sql); 
	}
	
	/**
 	  *  Retorna el nombre de un Título en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Títulos según la condición del array (para la carga masiva).
      *	 @param integer/array	título	id o arreglo de ids del o de los Títulos para los cuales se consultan los nombres.
    */
	function get_nombre_titulo($titulo)
	{
		if (is_array($titulo)) {
			/**
 	  		* En el caso de la carga masiva se arma otro arreglo, porque la columna título puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de titulo no nulos.
 	  		*/
			$titulos = array_diff($titulo, array(null));
			$filtro = guarani_sql::armar_condicion_in($titulos, 'mdp_titulos', 'titulo');
			$datos = $this->get_descr_titulo($filtro);
			return $datos;
		} elseif (!is_null($titulo)) {
			$titulo = toba::db()->quote($titulo);
			$datos = $this->get_descr_titulo("mdp_titulos.titulo = $titulo");
			return $datos[0];
		}
	}
	
	/**
        Obtiene los nombres de instituciones, según el filtro recibido.
    */
	function get_descr_institucion($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		  	
		$sql = 'SELECT	sga_instituciones.institucion,
	   					sga_instituciones.nombre as institucion_nombre 
				FROM 	sga_instituciones
				' . $where;
				        
		return guarani_db::consultar($sql); 
	}
	
	/**
 	  *  Retorna el nombre de una Institucion en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de las Instituciones según la condición del array (para la carga masiva).
      *	 @param integer/array	institucion	id o arreglo de ids del o de las Instituciones para las cuales se consultan los nombres.
    */
	function get_nombre_institucion($institucion)
	{
		if (is_array($institucion)) {
			/**
 	  		* En el caso de la carga masiva se hace un recorrido previo del arreglo, porque la columna institución puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de institución no nulos.
 	  		*/
			$instituciones = array_diff($institucion, array(null));
			$filtro = guarani_sql::armar_condicion_in($instituciones, 'sga_instituciones', 'institucion');
			$datos = $this->get_descr_institucion($filtro);
			return $datos;
		} elseif (!is_null($institucion)) {
			$institucion = toba::db()->quote($institucion);
			$datos = $this->get_descr_institucion("sga_instituciones.institucion = $institucion");
			return $datos[0];
		}
	}
	
	/**
        Obtiene los nombres correspondientes a diferentes niveles de estudios, según el filtro recibido.
    */
	function get_descr_nivel_estudio($where)
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
	   	$sql = 'SELECT	mdp_nivel_estudio.nivel_estudio,
	   					mdp_nivel_estudio.descripcion as nivel_estudio_descr
				FROM 	mdp_nivel_estudio
				' . $where;
			        
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de un Nivel de Estudio en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Niveles de Estudio según la condición del array (para la carga masiva).
      *	 @param integer/array	nivel	id o arreglo de ids del o de los Niveles de Estudio para las cuales se consultan los nombres.
    */
	function get_nombre_nivel_estudio($nivel)
	{
		if (is_array($nivel)) {
			$filtro = guarani_sql::armar_condicion_in($nivel, 'mdp_nivel_estudio', 'nivel_estudio');
			$datos = $this->get_descr_nivel_estudio($filtro);
			return $datos;
		} 
		$nivel = toba::db()->quote($nivel);
		$datos = $this->get_descr_nivel_estudio("mdp_nivel_estudio.nivel_estudio = $nivel");
		return $datos[0];
	}
	
	/**
        Obtiene los nombres de tipos de contactos, según el filtro recibido.
    */
	function get_descr_tipo_contacto($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = 'SELECT	mdp_contactos_tipo.contacto_tipo,
	   					mdp_contactos_tipo.descripcion as contacto_tipo_nombre
				FROM 	mdp_contactos_tipo
				'. $where;
			        
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de un Tipo de Contacto en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Tipos de Contactos según la condición del array (para la carga masiva).
      *	 @param integer/array	tipo_contacto	id o arreglo de ids del o de los Tipos de Contactos para los cuales se consultan los nombres.
    */
	function get_nombre_tipo_contacto($tipo_contacto)
	{
		if (is_array($tipo_contacto)) {
			/**
 	  		* En el caso de la carga masiva se hace un recorrido previo del arreglo, porque la columna tipo_contacto puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de tipo contacto no nulos.
 	  		*/
			$tipos_contactos = array_diff($tipo_contacto, array(null));
			$filtro = guarani_sql::armar_condicion_in($tipos_contactos, 'mdp_contactos_tipo', 'contacto_tipo');
			$datos = $this->get_descr_tipo_contacto($filtro);
			return $datos;
		} elseif (!is_null($tipo_contacto)) {
			$tipo_contacto = toba::db()->quote($tipo_contacto);
			$datos = $this->get_descr_tipo_contacto("mdp_contactos_tipo.contacto_tipo = $tipo_contacto");
			return $datos[0];
		}
	}
	
	/**
        Obtiene nombres de companías de celulares, según el filtro recibido.
    */
	function get_descr_celular_compania($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = 'SELECT	mdp_celulares_companias.celular_compania,
	   					mdp_celulares_companias.nombre as celular_compania_nombre
				FROM 	mdp_celulares_companias					
				'. $where;
				        
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de un Tipo de Contacto en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Tipos de Contactos según la condición del array (para la carga masiva).
      *	 @param integer/array	tipo_contacto	id o arreglo de ids del o de los Tipos de Contactos para los cuales se consultan los nombres.
    */
	function get_nombre_celular_compania($compania)
	{
		if (is_array($compania)) {
			/**
 	  		* En el caso de la carga masiva se hace un recorrido previo del arreglo, porque la columna compañía puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de compañía no nulos.
 	  		*/
			$companias = array_diff($compania, array(null));
			$filtro = guarani_sql::armar_condicion_in($companias, 'mdp_celulares_companias', 'celular_compania');
			$datos = $this->get_descr_celular_compania($filtro);
			return $datos;
		} elseif (!is_null($compania)) {
			$compania = toba::db()->quote($compania);
			$datos = $this->get_descr_celular_compania("mdp_celulares_companias.celular_compania = $compania");
			return $datos[0];
		}
	}
	
	/**
        Obtiene una lista de los paises de documentos habilitados (o de los datos de un pais habilitado si se pasa el id).
    */
	function get_paises_habilitados($id=null)
	{
		$where = '';

		if ($id != null) {
			$id = toba::db()->quote($id);
			$where = " AND mdp_paises_habilitados.pais = $id";
		}

	   	$sql = "SELECT	mdp_paises_habilitados.pais as valor,
	   					mug_paises.nombre as descr,
	   					mdp_paises_habilitados.permite_cedula as permite_cedula,
	   					mdp_paises_habilitados.necesita_visa as necesita_visa
				FROM 	mdp_paises_habilitados,
						mug_paises
				WHERE	mdp_paises_habilitados.pais = mug_paises.pais
						$where
	   			ORDER BY mug_paises.nombre
	   	";
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los tipos de visas de las personas en la base de datos
    */
	function get_tipos_visas()
	{
	   	$sql = 'SELECT		mdp_tipo_visa.tipo_visa as valor,
	   						mdp_tipo_visa.descripcion as descr
				FROM 		mdp_tipo_visa
				ORDER BY 	mdp_tipo_visa.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los tipos de residencias de las personas en la base de datos
    */
	function get_tipos_residencias()
	{
	   	$sql = 'SELECT		mdp_tipo_residencia.tipo_residencia as valor,
	   						mdp_tipo_residencia.descripcion as descr
				FROM 		mdp_tipo_residencia
				ORDER BY 	mdp_tipo_residencia.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los tipos de contactos de las personas en la base de datos
    */
	function get_tipos_contactos()
	{
	   	$sql = 'SELECT		mdp_contactos_tipo.contacto_tipo as valor,
	   						mdp_contactos_tipo.descripcion as descr
				FROM 		mdp_contactos_tipo
				ORDER BY 	mdp_contactos_tipo.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de las companias de celulares de las personas en la base de datos
    */
	function get_celulares_companias()
	{
	   	$sql = 'SELECT		mdp_celulares_companias.celular_compania as valor,
	   						mdp_celulares_companias.nombre as descr,
	   						mdp_celulares_companias.sigla as sigla
				FROM 		mdp_celulares_companias
				ORDER BY 	mdp_celulares_companias.nombre
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los tipos de allegados de las personas en la base de datos
    */
	function get_tipos_allegados()
	{
	   	$sql = 'SELECT		mdp_tipo_allegado.tipo_allegado as valor,
	   						mdp_tipo_allegado.descripcion as descr
				FROM 		mdp_tipo_allegado
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los posibles trabajos que existen de los allegados a las personas.
    */
	function get_trabajos_existen()
	{
	   	$sql = 'SELECT		mdp_trabajo_existe.trabajo_existe as valor,
	   						mdp_trabajo_existe.descripcion as descr
				FROM 		mdp_trabajo_existe
		';
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de las descripciones de lo que se hace en los posibles trabajos de los allegados a las personas.
    */
	function get_trabajos_hacen()
	{
	   	$sql = 'SELECT		mdp_trabajo_hace.trabajo_hace as valor,
	   						mdp_trabajo_hace.descripcion as descr
				FROM 		mdp_trabajo_hace
				ORDER BY	mdp_trabajo_hace.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de las ocupaciones de los posibles trabajos de los allegados a las personas.
    */
	function get_trabajos_ocupaciones()
	{
	   	$sql = 'SELECT		mdp_trabajo_ocupacion.trabajo_ocupacion as valor,
	   						mdp_trabajo_ocupacion.descripcion as descr
				FROM 		mdp_trabajo_ocupacion
				ORDER BY	mdp_trabajo_ocupacion.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los restantes posibles trabajos de los allegados a las personas.
    */
	function get_trabajos_otros()
	{
	   	$sql = 'SELECT		mdp_trabajo_otro.trabajo_otro as valor,
	   						mdp_trabajo_otro.descripcion as descr
				FROM 		mdp_trabajo_otro
				ORDER BY 	mdp_trabajo_otro.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de las posibles relaciones de los trabajos con las carreras de una persona.
    */
	function get_trabajos_carreras()
	{
	   	$sql = 'SELECT		mdp_trabajo_carrera.trabajo_carrera as valor,
	   						mdp_trabajo_carrera.descripcion as descr
				FROM 		mdp_trabajo_carrera
				ORDER BY	mdp_trabajo_carrera.descripcion
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los niveles de estudios posibles de las personas.
    */
	function get_niveles_de_estudio()
	{
	   	$sql = 'SELECT		mdp_nivel_estudio.nivel_estudio as valor,
	   						mdp_nivel_estudio.descripcion as descr
				FROM 		mdp_nivel_estudio
				;';
			        
		return guarani_db::consultar($sql);
	}	
	
	/**
     * Obtiene una lista de los niveles de estudios alcanzados de las personas.
     */
	function get_estudios_alcanzados()
	{
	   	$sql = 'SELECT	mdp_estudios_alcanzados.estudio_alcanzado as valor,
						mdp_estudios_alcanzados.descripcion as descr
				FROM 	mdp_estudios_alcanzados
				;';
		return guarani_db::consultar($sql);
	}	
	
	/**
	 * Obtiene los ids y descripciones de todos los títulos nombres contengan al string ingresado como
	 * filtro (mediante un combo editable).
	 *
	 * @param str $filtro porción del nombre del título ingresado por el usuario.
	 */
	function get_titulos($filtro_nombre='')
	{
		$condicion = '';
		if (isset($filtro_nombre) && trim($filtro_nombre) != '') {
			$filtro_nombre = guarani_texto::limpiar_acentos($filtro_nombre);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = ' WHERE '.$filtro_condicion->get_sql('f_limpiar_acentos(mdp_titulos.nombre::varchar)', $filtro_nombre);
		}
		$sql = "SELECT		mdp_titulos.titulo as valor,
							mdp_titulos.nombre as descr
				FROM		mdp_titulos
				$condicion
				ORDER BY	mdp_titulos.nombre
		";
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los nombres de todos los títulos de personas posibles.
    */
	function get_titulos_personas()
	{
	   	$sql = 'SELECT		mdp_personas_titulos.titulo_persona as valor,
	   						mdp_personas_titulos.nombre as descr
				FROM 		mdp_personas_titulos
				ORDER BY	mdp_personas_titulos.nombre
				;';
			        
		return guarani_db::consultar($sql);
	}

	function get_listado_personas_titulos($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		mdp_personas_titulos.titulo_persona,
							mdp_personas_titulos.nombre,
							mdp_personas_titulos.nombre_femenino,
							mdp_personas_titulos.nombre_no_binario
				FROM 		mdp_personas_titulos
				$where
				ORDER BY	mdp_personas_titulos.nombre
		";
		return guarani_db::consultar($sql);
	}

	function validar_nombre_persona_titulo($nombre, $titulo=null)
	{
		if (is_null($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mdp_personas_titulos.titulo_persona) as cant
                FROM 	mdp_personas_titulos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('mdp_personas_titulos.nombre', $nombre).'
		';
		if (!is_null($titulo)) {
			$titulo = toba::db()->quote($titulo);
			$sql .= " AND mdp_personas_titulos.titulo_persona <> $titulo ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de los nombres de todas las orientaciones que pueden haber recibido las personas.
    */
	function get_orientaciones_recibidas()
	{
	   	$sql = 'SELECT		mdp_orientacion_recibida.orientacion_recibida as valor,
	   						mdp_orientacion_recibida.nombre as descr
				FROM 		mdp_orientacion_recibida
				ORDER BY 	mdp_orientacion_recibida.orden
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene la descripcion de las posibles horas semanales del trabajo de una persona.
    */
	function get_trabajos_horas_semanales()
	{
	   	$sql = 'SELECT		mdp_trabajo_horas_sem.trabajo_hora_sem as valor,
	   						mdp_trabajo_horas_sem.descripcion as descr
				FROM 		mdp_trabajo_horas_sem
				;';
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene el tipo de dato de un tipo de documento dado.
    */
	function get_tipo_dato_nro_doc($tipo_doc)
	{
		$tipo_doc = toba::db()->quote($tipo_doc);
		
	   	$sql = "SELECT	mdp_tipo_documento.tipo_documento as tipo_documento,
	   					mdp_tipo_documento.tipo_de_dato as tipo_dato
				FROM 	mdp_tipo_documento
				WHERE	mdp_tipo_documento.tipo_documento = $tipo_doc
		";
			        
		$datos = guarani_db::consultar($sql);
		return ($datos[0]['tipo_dato']);
	}
    
    /**
     * Obtiene los tipos de documentos y sus expresiones regulares para poder 
     * validar el ingreso de usuario.
     */
    function get_datos_tipo_documento()
    {
        $sql = "SELECT	
                    mdp_tipo_documento.tipo_documento as tipo_documento,
                    mdp_tipo_documento.exp_regular_validacion as exp_regular,
                    mdp_tipo_documento.exp_regular_mensaje as mensaje,
                    mdp_tipo_documento.tipo_de_dato as tipo_dato
				FROM 	
                    mdp_tipo_documento				
		";			        
		return guarani_db::consultar($sql);
    }


    /**
        Obtiene una lista de los nombres y apellidos de las personas existentes en la BD del sistema (o estos datos para una persona dada si se pasa el id).
    */
	function get_nombres($id=null)
	{
		$where = '';
		
		if ($id != null) {
			$id = toba::db()->quote($id);
			$where .= " WHERE mdp_personas.persona = $id";
		}
		
	   	$sql = "SELECT	mdp_personas.persona as valor,
	   					    COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as descr 
				FROM 	mdp_personas
			   " . $where;
			        
		return guarani_db::consultar($sql);
	}
	
	 /*
     * Retorna la descripcion de una persona
     */
	static function get_persona($id=null)
	{
		if (! isset($id) || trim($id) == '') {
			return array();
		}    	
		
	    $id = toba::db()->quote($id);

				
		$sql = "SELECT 	mdp_personas.persona as valor, 
                                COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as descr
				FROM mdp_personas 
				WHERE mdp_personas.persona = $id 
				";				
			
		$result = guarani_db::consultar($sql);

		if (! empty($result)) {
			return $result[0]['descr'];
		}
	}
  
	/**
	* Retorna true/false segun si el documento esta diponible o no
	*/
	function validar_documento($tipo, $nro, $pais) 
	{
		$tipo = toba::db()->quote($tipo);
		$nro = toba::db()->quote($nro);
		$pais = is_null($pais) ? persona::pais_argentina : toba::db()->quote($pais);

		$sql = "SELECT	count(*) as cant,
						mdp_personas_documentos.persona as id
				FROM	mdp_personas_documentos
				WHERE	tipo_documento = $tipo AND
						nro_documento = $nro AND
						pais_documento = $pais
				GROUP BY mdp_personas_documentos.persona
				";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['id'] ?? null);
	}
	
	/**
 	  *  Verifica si la persona que se está ingresando o editando no ha sido expulsada de otra institución, a partir de los datos de su documento.
      *	 @param	 integer pais_doc 	id del país del documento correspondiente a la persona en cuestión
      *	 @param  integer tipo_doc	id tipo del documento de la persona.
      *  @param  integer nro_doc	número del documento de la persona.
      *  @return boolean retorna true si la persona ha sido expulsada de alguna otra institución y false en caso contrario.
    */
	function persona_expulsada_de_otra_institucion($pais_doc, $tipo_doc, $nro_doc)
	{
		$pais_doc = toba::db()->quote($pais_doc);
		$tipo_doc = toba::db()->quote($tipo_doc);
		$nro_doc = toba::db()->quote($nro_doc);
		
		$sql = "SELECT 	count(sga_alumnos_expulsados.nro_expulsion) as cant 	
                FROM 	sga_alumnos_expulsados 
				WHERE 	sga_alumnos_expulsados.pais = $pais_doc AND
						sga_alumnos_expulsados.tipo_documento = $tipo_doc AND
						sga_alumnos_expulsados.nro_documento = $nro_doc
				;";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}

	/**
 	  *  Verifica si la persona que se está ingresando o editando no ha sido sancionada.
      *	 @param	 integer pais_doc 	id del país del documento correspondiente a la persona en cuestión
      *	 @param  integer tipo_doc	id tipo del documento de la persona.
      *  @param  integer nro_doc	número del documento de la persona.
      *  @return boolean retorna true si la persona ha sido expulsada de alguna otra institución y false en caso contrario.
    */
	function persona_sancionada_por_documento($pais_doc, $tipo_doc, $nro_doc)
	{
		$pais_doc = toba::db()->quote($pais_doc);
		$tipo_doc = toba::db()->quote($tipo_doc);
		$nro_doc = toba::db()->quote($nro_doc);
		
		$sql = "SELECT	COUNT(sga_sanciones.sancion) as cant
				FROM	mdp_personas_documentos 
				JOIN	mdp_personas ON mdp_personas.persona = mdp_personas_documentos.persona
       			JOIN	sga_sanciones ON mdp_personas.persona = sga_sanciones.persona
				WHERE	mdp_personas_documentos.pais_documento = $pais_doc 
				  AND	mdp_personas_documentos.tipo_documento = $tipo_doc 
				  AND	mdp_personas_documentos.nro_documento = $nro_doc; ";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	/**
        Retorna los documentos secuendarios de una persona dada.
    */
	function get_documentos_sec_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT		mdp_tipo_documento.descripcion as tipo_documento,
							mdp_personas_documentos.nro_documento as numero_documento,
							mug_paises.nombre as pais_emisor
				FROM		mdp_personas,
							mdp_personas_documentos,
							mdp_tipo_documento,
							mug_paises
				WHERE		mdp_personas.persona = mdp_personas_documentos.persona AND
							mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento AND
							mdp_personas_documentos.pais_documento = mug_paises.pais AND
							mdp_personas.persona = $persona AND
							mdp_personas_documentos.documento <> mdp_personas.documento_principal
				ORDER BY	documento
		";
		return guarani_db::consultar($sql);		
	}
	
	/**
        Retorna los contactos de una persona dada.
    */
	function get_contactos_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 		COALESCE
								(	
								 '(' || mdp_personas_contactos.telefono_codigo_area || ') ' || mdp_personas_contactos.telefono_numero,							
								 mdp_personas_contactos.email,
								 mdp_personas_contactos.otros_contactos
								)							as descripcion,
							mdp_personas_contactos.contacto_tipo,
							mdp_contactos_tipo.descripcion	as contacto_tipo_desc
				FROM 		mdp_personas_contactos,
							mdp_contactos_tipo
				WHERE  		mdp_personas_contactos.persona 			= $persona
				AND			mdp_personas_contactos.contacto_tipo	= mdp_contactos_tipo.contacto_tipo
				
				ORDER BY	contacto_tipo_desc
				";
		
		return guarani_db::consultar($sql);		
	}

	
	/**
        Retorna los contactos de una persona dada.
    */
	function get_datos_persona_mail($persona)
	{
		$persona = toba::db()->quote($persona);
		$mail_prim =  persona::mail_primario;
		$mail_sec  =  persona::mail_secundario;
		$mail_inst =  persona::mail_institucional;
		$orden_tipo_email = persona::orden_tipo_email();
		$sql = "SELECT	mdp_personas.persona,
						COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) || ', ' || COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombre_completo,
						COALESCE((	SELECT		mdp_personas_contactos.email
									FROM		mdp_personas_contactos
									WHERE		mdp_personas_contactos.persona = mdp_personas.persona AND
												mdp_personas_contactos.contacto_tipo IN ('$mail_prim','$mail_sec','$mail_inst') AND
												mdp_personas_contactos.email IS NOT NULL
									ORDER BY	$orden_tipo_email
									LIMIT 1), ' ') as email,
									mdp_personas.usuario
				FROM	mdp_personas
				WHERE	mdp_personas.persona = $persona
		";
		return guarani_db::consultar($sql);
	}

	/**
        Retorna los allegados de una persona dada.
    */
	function get_allegados_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 		mdp_personas_allegados.calle 
							|| 
				            CASE
				            	WHEN (mdp_personas_allegados.numero IS NOT NULL) 
				                	THEN ' - ' || mdp_personas_allegados.numero 
				                    ELSE ''
							END
									|| 
				            CASE
				            	WHEN (mdp_personas_allegados.barrio IS NOT NULL) 
				                	THEN ' - ' || mdp_personas_allegados.barrio 
				                    ELSE ''
							END
									|| 
				            CASE
				            	WHEN (mug_localidades.nombre_abreviado IS NOT NULL) 
				                	THEN ' - ' || mug_localidades.nombre_abreviado 
				                    ELSE ''
							END
				            || 
				            CASE
				            	WHEN (mdp_personas_allegados.codigo_postal IS NOT NULL) 
				                	THEN ' - C.P.: ' || mdp_personas_allegados.codigo_postal
				                    ELSE ''
				
							END as domicilio,
							mdp_personas_allegados.apellido || ', ' || mdp_personas_allegados.nombres 	as persona_nombre,
							mdp_tipo_allegado.descripcion 												as allegado_tipo_desc,
							mdp_personas_allegados.telefono												as allegado_telefono
				FROM 		mdp_datos_censales
				JOIN		mdp_personas_allegados ON mdp_personas_allegados.dato_censal = mdp_datos_censales.dato_censal 
				JOIN		mdp_tipo_allegado ON mdp_tipo_allegado.tipo_allegado = mdp_personas_allegados.tipo_allegado
				LEFT JOIN 	mug_localidades ON mug_localidades.localidad = mdp_personas_allegados.localidad
				WHERE  		mdp_datos_censales.persona = $persona
				
				ORDER BY	allegado_tipo_desc
				";
		
		return guarani_db::consultar($sql);
	}
    
    function get_allegados_tipo($persona, $tipo, $tipoString)
    {        
        $sql = "
                SELECT 
                    apellido as apellido_$tipoString,
                    nombres as nombres_$tipoString,
                    estudio_alcanzado as ult_est_cur_$tipoString,
                    trabajo_existe as sit_laboral_$tipoString,
                    trabajo_hace as {$tipoString}_trab_hace,
                    trabajo_ocupacion as {$tipoString}_trab_ocup,
                    trabajo_tarea as {$tipoString}_trab_tarea,
                    trabajo_otro as {$tipoString}_trab_otro,
                    calle as calle_{$tipoString}, 
                    numero as nro_{$tipoString}, 
                    piso as piso_{$tipoString}, 
                    departamento as dpto_{$tipoString}, 
                    unidad as unidad_{$tipoString},
                    localidad_nombre as loc_{$tipoString}, 
                    codigo_postal as cp_{$tipoString}, 
                    barrio as barrio_{$tipoString}, 
                    telefono as te_{$tipoString}
                FROM
                    mdp_datos_censales
                INNER JOIN
                    mdp_personas_allegados ON mdp_personas_allegados.dato_censal = mdp_datos_censales.dato_censal 
                LEFT JOIN
                    vw_localidades ON vw_localidades.localidad = mdp_personas_allegados.localidad
                WHERe 
                    mdp_datos_censales.persona = $persona
                    AND mdp_personas_allegados.tipo_allegado = '$tipo'
                ";
        return guarani_db::consultar_fila($sql);
    }
	
	/**
        Retorna los estudios realizados por una persona dada.
    */
	function get_estudios_persona($persona, $where='')
	{
		$persona = toba::db()->quote($persona);
		
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "SELECT		COALESCE(sga_colegios_secundarios.nombre, mdp_datos_estudios.colegio_otro, sga_instituciones.nombre_abreviado, mdp_datos_estudios.institucion_otra) as lugar_desc,
							COALESCE(mdp_titulos.nombre, mdp_datos_estudios.titulo_otro) as titulo_desc,
							mdp_orientacion_recibida.nombre as orientacion_recibida_desc,
							mdp_datos_estudios.fecha_ingreso as fecha_ingreso,
							mdp_datos_estudios.fecha_egreso as fecha_egreso,
							mdp_titulos_tipos.nombre as titulo_nivel
				FROM 		mdp_datos_estudios
								LEFT JOIN mdp_titulos 				ON mdp_datos_estudios.titulo 				= mdp_titulos.titulo
								LEFT JOIN mdp_titulos_tipos			ON mdp_titulos_tipos.titulo_tipo			= mdp_titulos.titulo_tipo
								LEFT JOIN sga_instituciones			ON mdp_datos_estudios.institucion 			= sga_instituciones.institucion
								LEFT JOIN sga_colegios_secundarios 	ON mdp_datos_estudios.colegio 				= sga_colegios_secundarios.colegio
								LEFT JOIN mdp_orientacion_recibida 	ON mdp_datos_estudios.orientacion_recibida 	= mdp_orientacion_recibida.orientacion_recibida
				WHERE  		mdp_datos_estudios.persona = $persona
							$where
				ORDER BY	titulo_desc
		";

		return guarani_db::consultar($sql);
	}

	function get_datos_estudios_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT	mdp_datos_estudios.dato_estudio as valor,
						COALESCE(mdp_titulos.nombre, mdp_datos_estudios.titulo_otro, '<Sin título>') as descr
				FROM	mdp_datos_estudios
				LEFT JOIN mdp_titulos ON mdp_datos_estudios.titulo = mdp_titulos.titulo
				WHERE	mdp_datos_estudios.persona = $persona
				ORDER BY descr
		";
		return guarani_db::consultar($sql);
	}

	function get_institucion_dato_estudio($dato_estudio)
	{
		$dato_estudio = toba::db()->quote($dato_estudio);
		$sql = "SELECT	COALESCE(sga_colegios_secundarios.nombre, mdp_datos_estudios.colegio_otro, sga_instituciones.nombre, mdp_datos_estudios.institucion_otra) as colegio_institucion_nombre
				FROM	mdp_datos_estudios
				LEFT JOIN sga_colegios_secundarios ON mdp_datos_estudios.colegio = sga_colegios_secundarios.colegio
				LEFT JOIN sga_instituciones ON mdp_datos_estudios.institucion = sga_instituciones.institucion
				WHERE  	mdp_datos_estudios.dato_estudio = $dato_estudio
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['colegio_institucion_nombre'];
	}

	// Listado de tipos de convalidación de títulos extranjeros.
	function get_tipos_convalidacion_titulos_extranjeros()
	{
		return array(array('valor' => persona::tit_convalidado, 'desc' => persona::tit_convalidado_desc),
					 array('valor' => persona::tit_revalidado, 'desc' => persona::tit_revalidado_desc),
					 array('valor' => persona::tit_no_conv_rev, 'desc' => persona::tit_no_conv_rev_desc)
					);
	}
	
	/**
	 * Listado de razones de abandono de estudios
	 */
	function get_razones_abandono_estudios()
	{
		$sql = "SELECT 
					razon_abandono AS valor,
					nombre AS descr,
					orden
				FROM 
					mdp_estudio_abandono
				ORDER BY 
					orden
					";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna los antecedentes de una persona dada.
    */
	function get_antecedentes_persona($persona)
	{
		$persona = toba::db()->quote($persona);

		$sql = "SELECT 	COALESCE(sga_instituciones.nombre_abreviado, mdp_antecedentes_docentes.institucion_otra) as institucion_desc,
						COALESCE(sga_colegios_secundarios.nombre, mdp_antecedentes_docentes.colegio_otro) as colegio_desc,
						mdp_antecedentes_docentes.materia,
						mdp_antecedentes_docentes.fecha_desde as fecha_desde,
						mdp_antecedentes_docentes.fecha_hasta as fecha_hasta,
						mdp_antecedentes_docentes.cargo
				FROM 	mdp_antecedentes_docentes
				LEFT JOIN sga_instituciones			ON mdp_antecedentes_docentes.institucion = sga_instituciones.institucion
				LEFT JOIN sga_colegios_secundarios 	ON mdp_antecedentes_docentes.colegio 	 = sga_colegios_secundarios.colegio
				WHERE  	mdp_antecedentes_docentes.persona = $persona
				ORDER BY	institucion_desc,
							colegio_desc
		";

		$datos = guarani_db::consultar($sql);
		foreach ($datos as $clave => $valor) {
			$lugar_desc = $valor['institucion_desc'].' '.$valor['colegio_desc'];
			$datos[$clave]['lugar_desc'] = $lugar_desc;
		}
		return $datos;
	}
	
	/**
	 * Obtiene los usuarios de Araí­ ya asociados.
	 */
	function get_usuarios_arai_asociados()
	{
		$sql = "SELECT	mdp_personas.arai_identificador_sso
				FROM 	mdp_personas
				WHERE mdp_personas.arai_identificador_sso IS NOT NULL
		";
		return guarani_db::consultar($sql);
	}

	/**
     * Obtiene los usuarios de Araí que no están asociados. Ticket #42013
     */
    function get_usuarios_arai_no_asociados()
    {
        $sql = "SELECT	mdp_personas.usuario
				FROM 	mdp_personas
                WHERE mdp_personas.arai_identificador_sso IS NULL
		";
        return guarani_db::consultar($sql);
    }

    /**
	 * Obtiene los datos básicos de la persona a partir de un nombre de usuario.
	 */
	function get_persona_x_usuario($usuario)
	{
		$usuario = toba::db()->quote($usuario);
		
		$sql = "SELECT	mdp_personas.persona,
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
						mdp_personas.arai_identificador_sso,
						mdp_personas.arai_uuid
				FROM 	mdp_personas
				WHERE	mdp_personas.usuario = $usuario
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene los datos básicos de la persona a partir de arai_identificador_sso (usuario sso).
	 */
	function get_persona_x_arai_identificador_sso($arai_identificador_sso)
	{
		$arai_identificador_sso = toba::db()->quote($arai_identificador_sso);
		
		$sql = "SELECT	mdp_personas.persona,
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido_elegido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres_elegido,
						mdp_personas.arai_identificador_sso,
						mdp_personas.arai_uuid
				FROM 	mdp_personas
				WHERE	mdp_personas.arai_identificador_sso = $arai_identificador_sso
		";
		return guarani_db::consultar_fila($sql);
	}

    /**
     * Obtiene los datos básicos de la persona a partir del id de persona.
     */
    function get_usuario_x_persona($persona)
    {
        $persona = toba::db()->quote($persona);

        $sql = "SELECT	mdp_personas.usuario,
				COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
                                COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres
				FROM 	mdp_personas
				WHERE	mdp_personas.persona = $persona
		";
        return guarani_db::consultar_fila($sql);
    }

    /**
     * Obtiene los datos de SIU-Araí a partir del id de persona.
     */
    function get_datos_arai_x_persona($persona)
    {
        $persona = toba::db()->quote($persona);

        $sql = "SELECT	mdp_personas.usuario,
                        mdp_personas.arai_identificador_sso,
                        mdp_personas.arai_uuid
                FROM 	mdp_personas
                WHERE	mdp_personas.persona = $persona
		";
        return guarani_db::consultar_fila($sql);
    }

    /**
     * Obtiene los datos básicos de la persona a partir del token.
     */
    function get_persona_x_token($token)
    {
        $token = toba::db()->quote($token);
        
        // Si se trata de Autogestión
        if (\comunes::es_interfaz_autogestion()) {
            $interval = toba::db()->quote(\SIU\Chulupi\kernel::proyecto()->get_ttl_token());
        } else {// Si se trata de Gestión
            $interval = toba::db()->quote(guarani_instalacion_conf::get_parametro_instalacion('ttl_token', false, '1 day'));
        }

        $sql = "SELECT mdp_personas.usuario,
                       mdp_personas.persona,
                       COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
                       COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
                       token_alta, 
                       token_alta + interval $interval AS token_expiracion,
                       COALESCE((token_alta + interval $interval) < CURRENT_TIMESTAMP, true) AS token_expirado
                FROM mdp_personas
                WHERE token = $token
                ";
        return guarani_db::consultar_fila($sql);
    }
	
	function get_datos_cuadro_req_aspirante($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';			        
		}
		
		$sql = "SELECT 		sga_propuestas.nombre			AS propuesta_nombre,
							sga_planes.nombre 				AS plan_nombre,
							sga_planes_versiones.nombre 	AS version_nombre,
							vw_personas.apellido			AS persona_apellido,
							vw_personas.nombres				AS persona_nombres,
							vw_personas.tipo_nro_documento	AS persona_documento
				
				FROM 		sga_requisitos_aspirante,
					 		sga_propuestas_aspira,
							sga_propuestas,
							sga_situacion_aspirante,
							sga_planes_versiones,
							sga_planes,
							vw_personas
							
				WHERE		sga_propuestas_aspira.propuesta_aspira	= sga_requisitos_aspirante.propuesta_aspira
				AND			sga_propuestas.propuesta 				= sga_propuestas_aspira.propuesta
				AND			sga_situacion_aspirante.situacion_asp 	= sga_propuestas_aspira.situacion_asp
				AND			sga_propuestas_aspira.plan_version		= sga_planes_versiones.plan_version
				AND			sga_planes_versiones.plan				= sga_planes.plan
				AND			vw_personas.persona						= sga_propuestas_aspira.persona
					   		$where
		
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres,
							vw_personas.desc_tipo_documento,
							vw_personas.nro_documento
				";
		
		return guarani_db::consultar($sql);
	}

	function tiene_visa_vencida($persona, $fecha)
	{
		$persona = toba::db()->quote($persona);
		$fecha = toba::db()->quote($fecha);
		$sql = "SELECT	COUNT(1) as cant
				FROM	mdp_personas
						JOIN mdp_personas_extranjeros ON mdp_personas.persona = mdp_personas_extranjeros.persona
				WHERE	mdp_personas.persona = $persona AND
						mdp_personas.nacionalidad = ".persona::nac_extranjero." AND
						mdp_personas_extranjeros.tipo_visa = ".persona::visa_temporal." AND
						mdp_personas_extranjeros.vencimiento_visa <= $fecha
		";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}

	/*
	  Verifica si una persona es extranjera y tiene la residencia Precaria/Temporal vencida
	*/
	function tiene_residencia_vencida($persona, $fecha=null)
	{
		$persona = toba::db()->quote($persona);
		if (!$fecha) {
			$fecha = guarani_fecha::get_hoy();
		}
		$fecha = toba::db()->quote($fecha);
	
		$sql = "SELECT	COUNT(1) as cant
				FROM	mdp_personas
				JOIN	mdp_personas_extranjeros ON mdp_personas.persona = mdp_personas_extranjeros.persona
				WHERE	mdp_personas.persona = $persona 
				  AND	mdp_personas.nacionalidad = ".persona::nac_extranjero." 
				  AND	mdp_personas_extranjeros.tipo_residencia IN (".persona::residencia_precaria.",".persona::residencia_temporal.")  
				  AND	COALESCE(mdp_personas_extranjeros.vencimiento_residencia, CURRENT_DATE) < $fecha
		";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}
	
	function get_persona_foto($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 
					mdp_personas_foto.foto_persona,
					mdp_personas_foto.persona,
					mdp_personas_foto.imagen,
					mdp_personas_foto.fecha,
					mdp_personas_foto.nombre_imagen
				FROM 
					mdp_personas_foto
				WHERE
					mdp_personas_foto.persona = $persona
				";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Verifica si una persona dada como parámetro ha sido expulsada en otra institución
	 *
	 */
	function es_persona_expulsada($persona)
	{
		$persona = toba::db()->quote($persona);

		$sql = "SELECT 	COUNT(sga_alumnos_expulsados.nro_expulsion) AS cant
				FROM 	mdp_personas,
						sga_alumnos_expulsados,
						mdp_personas_documentos
				WHERE	mdp_personas.persona = $persona
				AND		mdp_personas.persona = mdp_personas_documentos.persona
				AND		mdp_personas_documentos.pais_documento	= sga_alumnos_expulsados.pais
				AND		mdp_personas_documentos.tipo_documento	= sga_alumnos_expulsados.tipo_documento
				AND		mdp_personas_documentos.nro_documento	= sga_alumnos_expulsados.nro_documento
				";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	/**
	 * Obtiene el nombre de un Título cuyo id se da como parámetro,
	 * retornando la string para el método descripción de un combo editable.
	 *
	 * @param integer $titulo id del título para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_titulo($titulo=null)
	{
		if (!isset($titulo)) {
			return '';
		}
		
		$result = toba::consulta_php('co_personas')->get_nombre_titulo($titulo);
		return empty($result) ? '' : $result['titulo_nombre'];
	}
	
	/**
	 * Verifica si una persona dada es alumno.
	 *
	 */
	function es_alumno($persona, $where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$persona = toba::db()->quote($persona);

		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	sga_alumnos
				WHERE	sga_alumnos.persona = $persona
						$where
				";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}
	
	/**
	 * Verifica si una persona dada es docente.
	 *
	 */
	function es_docente($persona, $where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$persona = toba::db()->quote($persona);

		$sql = "SELECT 	COUNT(*) AS cant
				FROM 	sga_docentes
				WHERE	sga_docentes.persona = $persona
						$where
				";

		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}

        /**
	 * Verifica si una persona tiene un perfil dado.
	 *
	 */
	function tiene_perfil($persona, $perfil)
	{
		$persona = toba::db()->quote($persona);
		$perfil = toba::db()->quote($perfil);

		$sql = "SELECT COUNT(*) AS cant
                        FROM mdp_personas_grupo_acc
                        WHERE persona = $persona
                        AND tipo_usuario = $perfil";

		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] != 0;
	}

	/**
	 * Verifica si una persona dada es docente y en ese caso devuelve su legajo.
	 *
	 */
	function get_legajo_persona_docente($persona)
	{
		$persona = toba::db()->quote($persona);

		$sql = "SELECT 	sga_docentes.legajo
				FROM 	sga_docentes
				WHERE	sga_docentes.persona = $persona
				";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene un listado de propuestas a la que se encuentra inscripta una persona. Inscripciones aceptadas o pendientes.
	 *
	 */
	function get_propuestas_persona($persona)
	{
		$persona = toba::db()->quote($persona);

		$sql = "SELECT 	sga_propuestas.propuesta,
						sga_propuestas.nombre_abreviado,
						sga_propuestas.nombre
				FROM 	sga_alumnos
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN	sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND 
				                                  sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
				JOIN	sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				WHERE	sga_alumnos.persona = $persona
				  AND	sga_alumnos.calidad = '".comunes::estado_activo."'
				  AND	sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')
				ORDER BY
						sga_propuestas.nombre_abreviado
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de propuestas aspira en estado PENDIENTE de la persona recibida como parámetro
	 */
	function get_propuestas_aspira_pendiente_persona($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT 	sga_propuestas_aspira.propuesta_aspira
				FROM 	sga_propuestas_aspira
				JOIN	sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				WHERE	sga_propuestas_aspira.persona = $persona
				  AND	sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."'
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de propuestas aspira en estado PENDIENTE o ACEPTADA 
	 * de la persona recibida como parámetro
	 * 
	 *  + ACEPTADAS de propuestas cuyo cierre de requisitos del período de inscripción no haya finalizado
	 *  + PENDIENTES 
	 */
	function get_propuestas_aspira_control_requisitos($persona)
	{
		$persona = toba::db()->quote($persona);
		$hoy = guarani_fecha::get_hoy();
		// El JOIN con 'sga_propuestas' permite la aplicación del perfil de datos.
		$sql = "SELECT 	sga_propuestas_aspira.propuesta_aspira
				FROM 	sga_propuestas_aspira
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
						JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
				WHERE	sga_propuestas_aspira.persona = $persona AND
						(	-- PENDIENTES
							sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_pendiente . "'
							OR ( -- ACEPTADAS de propuestas cuyo cierre de requisitos del período de inscripción no haya finalizado
								sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_aceptada . "' AND
								'$hoy' > (	SELECT	sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos
											FROM	sga_periodos_inscripcion_fechas
													JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion
											WHERE	sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc))
						)
		";
		return guarani_db::consultar($sql);
	}
	
	function get_listado_personas_con_insc_propuesta($where)
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT DISTINCT	vw_personas.persona,
				                vw_personas.apellido,
				                vw_personas.nombres,
				                vw_personas.tipo_nro_documento	AS documento,
				                mdp_nacionalidades.descripcion	AS nacionalidad_valor,
				                vw_personas.apellido_nombres 	AS nombre_completo
				FROM 			vw_personas,
				                sga_alumnos,
				                mdp_nacionalidades,
								sga_propuestas
				WHERE  			sga_alumnos.persona = vw_personas.persona
				AND				sga_alumnos.propuesta = sga_propuestas.propuesta
				AND 			vw_personas.nacionalidad = mdp_nacionalidades.nacionalidad
								$where
				ORDER BY		vw_personas.apellido,
				                vw_personas.nombres
				";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si la persona tiene los datos censales actualizados.
	 *
	 * @param 	integer $persona
	 * @param 	date 	$fecha
	 * @param 	int 	$cant_meses
	 * @return 	boolean
	 */
	function tiene_datos_censales_actualizados($persona, $fecha, $cant_meses)
	{
		$persona = toba::db()->quote($persona);
		$fecha = toba::db()->quote($fecha);		
		$sql = "SELECT  COUNT(1) AS cant
				FROM 	mdp_datos_censales
				WHERE	mdp_datos_censales.persona = $persona
				AND     mdp_datos_censales.fecha_actualizacion >= ($fecha::date - interval '$cant_meses month')::date
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'] > 0;
	}
	
	//--------------------------------------------------------------------------
	//---- BITÁCORA ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
     * Obtiene una lista de tipos de seguimientos para la bitácora
     */
	function get_tipos_seguimientos_bitacora($id=null)
	{
		$where = '';
		if ($id != null) {
			$id = toba::db()->quote($id);
			$where = " WHERE sga_bitacora_seguimiento.id_seguimiento = $id";
		}
		
		$sql = "SELECT	sga_bitacora_seguimiento.id_seguimiento,
						sga_bitacora_seguimiento.nombre
				FROM	sga_bitacora_seguimiento
				$where
				ORDER BY sga_bitacora_seguimiento.nombre;
				";
		return guarani_db::consultar($sql);
	}

	/**
        Obtiene nombres de tipos de seguimientos, según el filtro ingresado.
    */
	function get_descr_tipo_seguimiento($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
	   	$sql = 'SELECT	sga_bitacora_seguimiento.id_seguimiento,
	   					sga_bitacora_seguimiento.nombre as seguimiento_nombre
				FROM 	sga_bitacora_seguimiento
				'. $where;
			      
		return guarani_db::consultar($sql);
	}
	
	/**
 	  *  Retorna el nombre de un Tipo de Seguimiento en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los nombres de los Tipos de Seguimientos según la condición del array (para la carga masiva).
      *	 @param integer/array	id o arreglo de ids del o de los Tipos de Seguimientos para los cuales se consultan los nombres.
    */
	function get_nombre_tipo_seguimiento($id_seguimiento)
	{
		if (is_array($id_seguimiento)) {
			$filtro = guarani_sql::armar_condicion_in($id_seguimiento, 'sga_bitacora_seguimiento', 'id_seguimiento');
			$datos = $this->get_descr_tipo_seguimiento($filtro);
			return $datos;
		} 
		$id_seguimiento = toba::db()->quote($id_seguimiento);
		$datos = $this->get_descr_tipo_seguimiento("sga_bitacora_seguimiento.id_seguimiento = $id_seguimiento");
		return $datos[0];
	}
	
	/**
     * Obtiene una lista de tipos de seguimientos (sga_bitacora_seguimiento_tipo) para la bitácora, pertenecientes a un seguimiento dado (sga_bitacora_seguimiento)
     */
	function get_seguimientos_tipos_bitacora($id_seguimiento = null)
	{
		$where = '';
		if (!is_null($id_seguimiento)) {
			$id_seguimiento = toba::db()->quote($id_seguimiento);
			$where = " WHERE sga_bitacora_seguimiento_tipo.id_seguimiento = $id_seguimiento";
		}
		
		$sql = "SELECT	sga_bitacora_seguimiento_tipo.seguimiento_tipo,
						sga_bitacora_seguimiento_tipo.nombre
				FROM	sga_bitacora_seguimiento_tipo
				$where
				ORDER BY sga_bitacora_seguimiento_tipo.nombre;
				";
		return guarani_db::consultar($sql);
	}
	
	/**
     *   Retorna los niveles de visibilidad de la bitácora
     */
    function get_niveles_visibilidad_bitacora()
    {
    	return array(	
			array('valor' => persona::bitacora_visibilidad_gestion, 'descr' => persona::bitacora_visibilidad_gestion),
			array('valor' => persona::bitacora_visibilidad_todos, 'descr' => persona::bitacora_visibilidad_todos)
		);
    }
	
	/**
     *   Retorna los niveles de visibilidad de la bitácora para autogestión
     */
    function get_niveles_visibilidad_bitacora_autogestion()
    {
    	return array(	
			array('valor' => persona::bitacora_visibilidad_todos, 'descr' => persona::bitacora_visibilidad_todos),
			array('valor' => persona::bitacora_visibilidad_docente, 'descr' => persona::bitacora_visibilidad_docente),
		);
    }

	/**
	 * Retorna los comentarios de la bitácora según el filtro recibido.
	 * No filtra docentes por perfil de datos.
	 * 
	 * @param string $filtro
	 * @return array
	 */
	function get_comentarios_bitacora($filtro = '')
	{
		$where = '';
    	if ($filtro != '') {
    		$where .= " WHERE $filtro";
    	}
    	
		$sql = "SELECT		
					sga_bitacora.bitacora,
					sga_bitacora.persona,
					sga_bitacora.fecha,
					to_char(sga_bitacora.fecha,'".comunes::formato_fecha_visual."') as fecha_formato_visual,
					sga_bitacora.id_seguimiento,
					sga_bitacora_seguimiento.nombre as seguimiento_nombre,
					sga_bitacora.observaciones,
					sga_bitacora.usuario_alta,
					usuario_alta.nombres || ' ' || usuario_alta.apellido as persona_nombre,
					sga_bitacora.visibilidad,
					sga_bitacora.docente,
					CASE WHEN sga_bitacora.docente IS NOT NULL
						THEN docente_referencia.nombres || ' ' || docente_referencia.apellido 
						ELSE null
					END as docente_nombre,
					sga_bitacora.interfaz,
					sga_bitacora.titulo
				FROM sga_bitacora
				JOIN vw_personas as usuario_alta ON (sga_bitacora.usuario_alta = usuario_alta.persona)
				JOIN sga_bitacora_seguimiento ON (sga_bitacora.id_seguimiento = sga_bitacora_seguimiento.id_seguimiento)
				LEFT JOIN sga_docentes ON (sga_bitacora.docente = sga_docentes.docente)
				LEFT JOIN vw_personas as docente_referencia ON (sga_docentes.persona = docente_referencia.persona)
				$where
				ORDER BY	
					fecha DESC
				";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Verifica si un alumno tiene presentado y vigente el requisito documental a la fecha dada.
	 *
	 * @param integer $alumno
	 * @param integer $requisito
	 * @param date $fecha
	 * @return boolean
	 */
	function cumple_requisito_documental($alumno, $requisito, $fecha)
	{
		if ($fecha == '') {
            $fecha = 'CURRENT_DATE';
        } else {
            $fecha = toba::db()->quote($fecha);
        }        
		
		// Esta presentado/exceptuado o depende de otro que esta presentado/exceptuado.
		$sql = "SELECT COUNT(1) as cant
		  		  FROM sga_alumnos 
				  JOIN sga_propuestas_aspira ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
				  JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp 
				 WHERE sga_alumnos.alumno = $alumno
				   AND sga_situacion_aspirante.resultado_asp IN ('P','A') 
				   AND f_requisito_presentado(sga_alumnos.persona, $requisito, $fecha, sga_propuestas_aspira.propuesta_aspira, $fecha)
				";
		$rs = guarani_db::consultar_fila($sql);
		if ($rs['cant'] > 0) {
			// Esta Presentado(no vencido) o Exceptuado en la propuesta
			return true;
		}
		
		// No lo tiene cumplido (o esta presentado y vencido)
		return false;
	}

    /**
     * Retorna los datos de contacto de una persona dada.
	 * Función deprecated para próxima versión
     */
    function get_contactos_persona_rest($tipo_documento, $nro_documento)
    {
        $mail_prim =  toba::db()->quote(persona::mail_primario);
        $mail_sec  =  toba::db()->quote(persona::mail_secundario);
        $mail_inst =  toba::db()->quote(persona::mail_institucional);

        $tel_cel =  toba::db()->quote(persona::tel_celular);
        $tel_fam =  toba::db()->quote(persona::tel_familiar);
        $tel_fijo =  toba::db()->quote(persona::tel_fijo);
        $tel_lab =  toba::db()->quote(persona::tel_laboral);
        $otro =  toba::db()->quote(persona::otro_contacto);

		$orden_tipo_email    = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();

        $tipo_documento =  toba::db()->quote($tipo_documento);
        $nro_documento =  toba::db()->quote($nro_documento);

        $sql = "
            SELECT mdp_personas.persona,
            COALESCE((
                SELECT mdp_personas_contactos.email
                FROM mdp_personas_contactos
                WHERE mdp_personas_contactos.persona = mdp_personas.persona
                  AND mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
				  AND mdp_personas_contactos.email IS NOT NULL
                ORDER BY  $orden_tipo_email
                LIMIT 1)) AS email,
            COALESCE((
                SELECT (mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero)
                  FROM mdp_personas_contactos
                 WHERE mdp_personas_contactos.persona = mdp_personas.persona
                   AND mdp_personas_contactos.contacto_tipo IN ($tel_cel, $tel_fam, $tel_fijo, $tel_lab)
                ORDER BY $orden_tipo_telefono
                LIMIT 1)) AS telefono
            FROM mdp_personas_documentos
            JOIN mdp_personas ON (mdp_personas.persona = mdp_personas_documentos.persona)
            WHERE mdp_personas_documentos.tipo_documento = $tipo_documento
            AND   mdp_personas_documentos.nro_documento = $nro_documento;
        ";

        return guarani_db::consultar_fila($sql);
    }
	
	function get_contactos_persona_rest_persona($usuario = null, $pais_documento = null, $tipo_documento = null, $nro_documento = null)
	{
		$mail_prim =  toba::db()->quote(persona::mail_primario);
        $mail_sec  =  toba::db()->quote(persona::mail_secundario);
        $mail_inst =  toba::db()->quote(persona::mail_institucional);

        $tel_cel =  toba::db()->quote(persona::tel_celular);
        $tel_fam =  toba::db()->quote(persona::tel_familiar);
        $tel_fijo =  toba::db()->quote(persona::tel_fijo);
        $tel_lab =  toba::db()->quote(persona::tel_laboral);
        $otro =  toba::db()->quote(persona::otro_contacto);

		$orden_tipo_email    = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();

        $where = '';        
        if ($usuario) {
            $usuario = toba::db()->quote($usuario);
            $where .= " AND mdp_personas.usuario = $usuario";
        }
        
        if ($tipo_documento) {
            $tipo_documento =  toba::db()->quote($tipo_documento);
            $where .= " AND mdp_personas_documentos.tipo_documento = $tipo_documento";
        }
        
        if ($nro_documento) {
            $nro_documento =  toba::db()->quote($nro_documento); 
            $where .= " AND mdp_personas_documentos.nro_documento = $nro_documento";
        }
        
        if ($pais_documento) {
            $pais_documento =  toba::db()->quote($pais_documento);
            $where .= " AND mdp_personas_documentos.pais_documento = $pais_documento";
        }


        $sql = "
            SELECT mdp_personas.persona,
            COALESCE((
                SELECT mdp_personas_contactos.email
                FROM mdp_personas_contactos
                WHERE mdp_personas_contactos.persona = mdp_personas.persona
                  AND mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
				  AND mdp_personas_contactos.email IS NOT NULL
                ORDER BY $orden_tipo_email
                LIMIT 1)) AS email,
            COALESCE((
                SELECT (mdp_personas_contactos.telefono_codigo_area || '|' ||mdp_personas_contactos.telefono_numero)
                  FROM mdp_personas_contactos
                 WHERE mdp_personas_contactos.persona = mdp_personas.persona
                   AND mdp_personas_contactos.contacto_tipo IN ($tel_cel, $tel_fam, $tel_fijo, $tel_lab)
                ORDER BY $orden_tipo_telefono
                LIMIT 1)) AS telefono
            FROM mdp_personas_documentos
            JOIN mdp_personas ON (mdp_personas.persona = mdp_personas_documentos.persona)
            WHERE TRUE 
            $where
            ";
        return guarani_db::consultar_fila($sql);
	}
	
	function get_promedios($persona)
	{
		$persona = toba::db()->quote($persona);
		// Si el alumno obtuvo la certificacion, visualiza los promedios registrados en la certificación, sino lo calcula.
		$sql = "-- Promedio general. De todas las actividades del plan actual del alumno.	
				SELECT
					sga_propuestas.propuesta as propuesta,
					sga_propuestas.codigo as propuesta_codigo,
					sga_propuestas.nombre as propuesta_nombre,
					'General' as certificado_nombre,	
					f_promedio(sga_alumnos.alumno, CURRENT_DATE, NULL, 'S') as promedio_con_aplazos,   
					f_promedio(sga_alumnos.alumno, CURRENT_DATE, NULL, 'N') as promedio_sin_aplazos,
					(SELECT count(*) 
					   FROM vw_hist_academica_basica as ha 
					   JOIN sga_elementos_revision as er ON er.elemento = ha.elemento 
					   JOIN sga_elementos_plan as ep ON ep.plan_version = sga_alumnos.plan_version AND ep.elemento_revision = er.elemento_revision
					  WHERE ha.alumno = sga_alumnos.alumno 
					    AND ha.resultado = '".escalas_notas::resultado_aprobado."') as aprobadas,
					(SELECT count(*) 
					   FROM vw_hist_academica_basica as ha 
					   JOIN sga_elementos_revision as er ON er.elemento = ha.elemento 
					   JOIN sga_elementos_plan as ep ON ep.plan_version = sga_alumnos.plan_version AND ep.elemento_revision = er.elemento_revision
					  WHERE ha.alumno = sga_alumnos.alumno 
					    AND ha.resultado = '".escalas_notas::resultado_reprobado."') as desaprobadas
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				WHERE sga_alumnos.persona = $persona
				  AND sga_propuestas.propuesta_tipo <> ".propuesta::tipo_curso."

				UNION
				
				-- Promedios de cada certificación del plan actual del alumno
				SELECT
					sga_propuestas.propuesta as propuesta,
					sga_propuestas.codigo as propuesta_codigo,
					sga_propuestas.nombre as propuesta_nombre,
					sga_certificados.nombre as certificado_nombre,
					COALESCE(sga_certificados_otorg.promedio, f_promedio(sga_alumnos.alumno, CURRENT_DATE, sga_certificados.certificado, 'S')) as promedio_con_aplazos,   
					COALESCE(sga_certificados_otorg.promedio_sin_aplazos, f_promedio(sga_alumnos.alumno, CURRENT_DATE, sga_certificados.certificado, 'N')) as promedio_sin_aplazos,
					(SELECT count(*) FROM vw_hist_academica_basica 
					  WHERE alumno = sga_alumnos.alumno 
					     AND resultado = '".escalas_notas::resultado_aprobado."'
						 AND elemento IN (SELECT * FROM get_actividades_certificado(sga_certificados.certificado, sga_alumnos.plan_version))
					) as aprobadas,
					(SELECT count(*) FROM vw_hist_academica_basica 
					  WHERE alumno = sga_alumnos.alumno 
					    AND resultado = '".escalas_notas::resultado_reprobado."'
						AND elemento IN (SELECT * FROM get_actividades_certificado(sga_certificados.certificado, sga_alumnos.plan_version))
					)as desaprobadas
				FROM sga_alumnos
				JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				JOIN sga_planes_certificados ON sga_planes_certificados.plan_version = sga_alumnos.plan_version
				JOIN sga_certificados ON sga_certificados.certificado = sga_planes_certificados.certificado
				JOIN sga_certificados_tipos ON sga_certificados_tipos.certificado_tipo = sga_certificados.certificado_tipo
				LEFT JOIN sga_certificados_otorg ON sga_certificados_otorg.alumno = sga_alumnos.alumno AND
				                                    sga_certificados_otorg.certificado = sga_certificados.certificado  AND
													sga_certificados_otorg.anulado = 0
				WHERE sga_alumnos.persona = $persona
				  AND sga_propuestas.propuesta_tipo <> ".propuesta::tipo_curso."
				
				ORDER BY 3,4
				";
		return guarani_db::consultar($sql);
	}
	
	function get_opciones_pueblos_originarios()
	{
		$sql = "SELECT 
                            mdp_pueblos_originarios.pueblo_originario AS valor,
                            mdp_pueblos_originarios.nombre AS descr
			FROM 
                            mdp_pueblos_originarios
                        ORDER BY
                        -- 'Otros Especifique' va a lo último
                        CASE mdp_pueblos_originarios.pueblo_originario
                            WHEN ".\comunes::pueblo_originario_otros." THEN 1
                            ELSE 0
                        END,
                        -- Luego se ordena por nombre alfabéticamente
                        mdp_pueblos_originarios.nombre";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los emails de un conjuto de personas dada.
	 * @param int[] $personas Array de personas.
	 * @param boolean $solo_reciben_mail Si trae solo las personas que quieren recibir email o todas.
	 * @return string[] Array de emails.
	 */
	function get_emails_personas($personas, $solo_reciben_mail = true)
	{

		$where  = "";
                $join   = "";
		$emails = [];

		$cond_in_persona = guarani_sql::armar_condicion_in($personas, 'mdp_personas', 'persona');

		if($solo_reciben_mail){
			$join   = "JOIN mdp_configuracion ON (mdp_personas.persona = mdp_configuracion.persona)";
			$where  = "mdp_configuracion.recibe_mail_mensajes = 1 AND ";
		}

		$mail_prim =  toba::db()->quote(persona::mail_primario);
		$mail_sec  =  toba::db()->quote(persona::mail_secundario);
		$mail_inst =  toba::db()->quote(persona::mail_institucional);
		$orden_tipo_email = persona::orden_tipo_email();

		$sql = "SELECT  mdp_personas.persona,
                                (   SELECT mdp_personas_contactos.email
                                    FROM mdp_personas_contactos
                                    WHERE mdp_personas_contactos.persona = mdp_personas.persona
                                    AND mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
                                    AND mdp_personas_contactos.email IS NOT NULL
                                    ORDER BY $orden_tipo_email
                                    LIMIT 1
                                ) as email
                                FROM    mdp_personas
                                $join
                                WHERE
                                $where
                                $cond_in_persona
                                AND EXISTS (SELECT 1
                                            FROM mdp_personas_contactos
                                            WHERE mdp_personas_contactos.persona = mdp_personas.persona
                                            AND mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
                                            AND mdp_personas_contactos.email IS NOT NULL
                                            )
		";

		$results = guarani_db::consultar($sql);

		foreach ($results as $result){
			$emails[$result['persona']]['email'] = $result['email'];
		}

		return $emails;
	}


	/**
	 * Retorna los apellidos y nombres (considera los 'elegidos') de un conjuto de personas dada.
	 * @param int[] $personas Array de personas.
	 * @return string[] Array de apellidos y nombres.
	 */
	function get_apellido_nombres_personas($personas)
	{

		$datos = array();

		$personas = implode(", ", $personas);

		$sql = "SELECT 	persona,
						COALESCE(apellido_elegido, apellido) AS apellido,
						COALESCE(nombres_elegido, nombres) AS nombres
				FROM mdp_personas
				WHERE persona IN ($personas)
		";

		$results = guarani_db::consultar($sql);

		foreach ($results as $result){
			$datos[$result['persona']] = array(
				'apellido' => $result['apellido'],
				'nombres' => $result['nombres']
			);
		}

		return $datos;
	}
	
	function get_alumnos($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "
				SELECT
					DISTINCT(vw_personas.persona),
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS documento_principal	
				FROM 		
					sga_alumnos,
					vw_personas,
					sga_alumnos_calidad,
					sga_modalidad_cursada,
					sga_ubicaciones,
					sga_propuestas,
					sga_planes_versiones,
					sga_planes	
				WHERE  		
					sga_alumnos.persona = vw_personas.persona AND
					sga_alumnos.calidad = sga_alumnos_calidad.calidad AND
					sga_alumnos.modalidad = sga_modalidad_cursada.modalidad AND
					sga_alumnos.ubicacion = sga_ubicaciones.ubicacion AND
					sga_alumnos.propuesta = sga_propuestas.propuesta AND
					sga_alumnos.plan_version = sga_planes_versiones.plan_version AND
					sga_planes_versiones.plan = sga_planes.plan
					$where
				ORDER BY	
					vw_personas.apellido_nombres
				";
		return guarani_db::consultar($sql); 
	}

	function get_datos_personales_rest($persona)
	{
		$mail_prim = toba::db()->quote(persona::mail_primario);
        $mail_sec  = toba::db()->quote(persona::mail_secundario);
        $mail_inst = toba::db()->quote(persona::mail_institucional);

        $tel_cel = toba::db()->quote(persona::tel_celular);
        $tel_fijo = toba::db()->quote(persona::tel_fijo);

		$orden_tipo_email    = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();

        $persona = toba::db()->quote($persona);

		$sql = "SELECT		mdp_personas.persona,
							mdp_personas.apellido,
							mdp_personas.nombres,
							mdp_personas.nombres_elegido, 
							vw_personas.apellido_nombres_ley, 
							mdp_personas_sexo.descripcion as genero,
							mdp_personas.pais_origen as pais_origen,
							to_char(mdp_personas.fecha_nacimiento,'".comunes::formato_fecha_visual."') as fecha_nacimiento,
							mdp_nacionalidades.descripcion as nacionalidad,
							COALESCE(sga_docentes.legajo, '') as legajo,
							vw_localidades.pais as codigo_pais_procedencia,
							vw_localidades.pais_nombre as nombre_pais_procedencia,
							vw_localidades.provincia as codigo_provincia_procedencia,
							vw_localidades.provincia_nombre as nombre_provincia_procedencia,
							vw_localidades.localidad as codigo_localidad_procedencia,
							vw_localidades.localidad_nombre as nombre_localidad_procedencia,
							mdp_datos_personales.procedencia_calle || ' ' || procedencia_numero as direccion_procedencia,
							mdp_tipo_documento.desc_abreviada || ' ' || mdp_personas_documentos.nro_documento as documento,
							COALESCE((SELECT mdp_personas_contactos.email
										FROM mdp_personas_contactos
									   WHERE mdp_personas_contactos.persona = mdp_personas.persona 
										 AND mdp_personas_contactos.contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
										 AND mdp_personas_contactos.email IS NOT NULL
									ORDER BY $orden_tipo_email
									LIMIT 1),'') AS mail,
							COALESCE((SELECT '(' || mdp_personas_contactos.telefono_codigo_area || ') ' || mdp_personas_contactos.telefono_numero
										FROM mdp_personas_contactos
									   WHERE mdp_personas_contactos.persona = mdp_personas.persona 
										 AND mdp_personas_contactos.contacto_tipo = $tel_cel
									  LIMIT 1),'') AS telefono_celular,
							COALESCE((SELECT '(' || mdp_personas_contactos.telefono_codigo_area || ') ' || mdp_personas_contactos.telefono_numero
										FROM mdp_personas_contactos
									   WHERE mdp_personas_contactos.persona = mdp_personas.persona 
										 AND mdp_personas_contactos.contacto_tipo = $tel_fijo
									  LIMIT 1),'') AS telefono_fijo
				FROM		mdp_personas
							JOIN mdp_personas_sexo ON mdp_personas.sexo = mdp_personas_sexo.sexo
							JOIN mdp_nacionalidades ON mdp_personas.nacionalidad = mdp_nacionalidades.nacionalidad
							JOIN vw_personas ON mdp_personas.persona = vw_personas.persona
							LEFT JOIN mdp_datos_censales ON mdp_datos_censales.persona = mdp_personas.persona
							LEFT JOIN mdp_datos_personales ON mdp_datos_personales.dato_censal =  mdp_datos_censales.dato_censal
							LEFT JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
							LEFT JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
							LEFT JOIN vw_localidades ON vw_localidades.localidad = mdp_datos_personales.procedencia_localidad	
							LEFT JOIN sga_docentes ON (sga_docentes.persona = mdp_personas.persona AND sga_docentes.estado = 'A')
				WHERE		mdp_personas.persona = $persona
				ORDER BY	mdp_personas.apellido,
							mdp_personas.nombres
		";
		return guarani::db()->consultar_fila($sql);
	}

	function get_datos_analitico_rest($persona, $codigo_titulo_araucano)
	{
		$persona = toba::db()->quote($persona);
		$codigo_titulo_araucano = is_null($codigo_titulo_araucano) ? 'null' : toba::db()->quote($codigo_titulo_araucano);
		$sql = "SELECT	titulo_araucano,
						titulo_nombre,
						responsable_academica,
						propuesta,
						propuesta_nombre,
						plan_codigo AS plan_alumno,
						titulo_esta_cumplido,
						nro_resolucion_ministerial,
						nro_resolucion_coneau,
						nro_resolucion_spu,
						nro_disposicion_spu,
						nro_resolucion_institucion,
						fecha_ingreso,
						fecha_egreso,
						tiene_sanciones,
						titulo_anterior_nivel,
						titulo_anterior_origen,
						titulo_anterior_nacionalidad,
						titulo_anterior_institucion,
						titulo_anterior_denominacion,
						titulo_anterior_revalidado,
						titulo_anterior_nro_resolucion,
						titulo_apto_ejercicio,
						plan_vigente,
						tipo,
						actividad_nombre,
						actividad_codigo,
						creditos,
						fecha,
						nota,
						resultado,
						folio_fisico,
						acta_resolucion,
						promedio,
						promedio_sin_aplazos,
						forma_aprobacion,
						es_optativa,
						fecha_inicio_tramite,
						nro_expediente
				FROM	ws_datos_analitico ($persona, $codigo_titulo_araucano, true)
		";
		return guarani::db()->consultar($sql);
	}
	
	function get_constancias_solicitadas($persona)
	{
		$sql = "
				SELECT 
					COUNT(*) as cantidad_solicitudes,
					(SELECT COUNT(*) FROM sga_constancias_solicitud as cs WHERE cs.constancia = sga_constancias_solicitud.constancia AND cs.alumno = sga_constancias_solicitud.alumno 
						AND estado IN ('Impreso', 'Entregado', 'Online')) as cantidad_impresiones,
					sga_constancias.nombre as constancia_nombre,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.propuesta as propuesta
				FROM 
					sga_constancias_solicitud,
					sga_constancias,
					sga_alumnos,
					sga_propuestas
				WHERE
					sga_constancias_solicitud.constancia = sga_constancias.constancia
					AND sga_constancias_solicitud.alumno = sga_alumnos.alumno
					AND sga_propuestas.propuesta = sga_alumnos.propuesta
					AND sga_alumnos.persona = $persona
				GROUP BY 2, 3, 4, 5
				";
		return guarani::db()->consultar($sql);
	}
    
    function get_agenda_del_dia($persona, $fecha, $modalidad)
    {
        if ($fecha == '') {
            $fecha = 'CURRENT_DATE';
        } else {
            $fecha = toba::db()->quote($fecha);
        }        
        if($modalidad == '') {
            $modalidad = 'false';
        }
        $sql = "
                SELECT 
                    tipo_actividad as tipo_actividad,
                    tipo_persona as tipo_persona,
                    legajo as legajo,
                    actividad_nombre as actividad,
                    horario as horario,
                    ubicacion_nombre as ubicacion,
                    modalidad as modalidad
                FROM
                    ws_agenda_del_dia($persona, null, $fecha, $modalidad);
                ";
        return guarani::db()->consultar($sql);
    }
    
    function get_colegio_secundario($colegio)
    {
        $sql = "
                SELECT
                    colegio as valor,
                    nombre as descr
                FROM
                    sga_colegios_secundarios
                WHERE
                    colegio = $colegio
                ";
        return guarani::db()->consultar_fila($sql);
                
    }
    
    /*
     * Para preinscripcion voy a buscar por persona los tipos de documentos que necesito
     */
    function get_tipo_documento_persona($persona, $tipo_documento)
    {
        $sql = "
                SELECT
                    p.nombre as pais,
                    pd.nro_documento as nro_documento
                FROM 
                    mdp_personas_documentos as pd
                INNER JOIN 
                    mug_paises as p On p.pais = pd.pais_documento
                WHERE 
                    persona = $persona
                    AND tipo_documento = $tipo_documento
                ";
        return guarani::db()->consultar_fila($sql);
    }
}