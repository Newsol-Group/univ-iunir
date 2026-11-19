<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class co_preinscripciones
{
	protected $mapeos;
	
	//--------------------------------------------------------------------------
	//---- Datos desde Autogestión
	//--------------------------------------------------------------------------
	
	function get_preinscripciones_desde_autogestion($persona)
	{
		$salida = [];
		$sql = "SELECT 
					sga_preinscripcion_propuesta.preinscripcion,
					sga_preinscripcion_propuesta.persona,
					sga_preinscripcion_propuesta.propuesta,
					sga_propuestas.nombre AS propuesta_nombre,
					sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
					sga_preinscripcion_propuesta.responsable_academica,
					sga_preinscripcion_propuesta.ubicacion,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					sga_preinscripcion_propuesta.modalidad,
					sga_modalidad_cursada.nombre AS modalidad_nombre,
					sga_preinscripcion_propuesta.periodo_insc,
					sga_preinscripcion_propuesta.estado,
					sga_preinscripcion_propuesta.atiende,
					COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as atendido_por_nombre_apellido,
					sga_preinscripcion_propuesta.fecha_preinscripcion,
					sga_preinscripcion_propuesta.fecha_fin_vigencia,
					sga_preinscripcion_propuesta.tipo_ingreso
					
				FROM sga_preinscripcion_propuesta
				JOIN sga_propuestas ON sga_preinscripcion_propuesta.propuesta = sga_propuestas.propuesta
				JOIN sga_ubicaciones ON sga_preinscripcion_propuesta.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_modalidad_cursada ON sga_preinscripcion_propuesta.modalidad = sga_modalidad_cursada.modalidad
				LEFT JOIN mdp_personas ON mdp_personas.persona = sga_preinscripcion_propuesta.atiende
				WHERE sga_preinscripcion_propuesta.persona = $persona
				  -- Inscripciones no procesadas
				  AND sga_preinscripcion_propuesta.estado  <> '" . preinscripcion::insc_procesada . "'
				  -- Inscripciones vigentes
				  AND sga_preinscripcion_propuesta.fecha_fin_vigencia >= CURRENT_DATE
				";

		$datos = guarani::db()->consultar($sql);
		if(!empty($datos)){
		foreach ($datos as $dato) {
			if(!isset($dato['atendido_por_nombre_apellido'])){				
					$dato['atendido_por_nombre_apellido'] = 'Sin Asignar';
				}
				$salida[] = $dato;
			}
		}
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- Datos desde Preinscripción
	//--------------------------------------------------------------------------
	
	function get_fuentes_datos_preinscripcion($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT 		adm_bases_preinscripcion.fuente_de_datos,
							adm_bases_preinscripcion.nombre
				FROM 		adm_bases_preinscripcion
				$where
				ORDER BY	adm_bases_preinscripcion.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}
	
	/**
	 * Lista de fuentes de datos del proyecto
	 */
	function get_fuentes_datos_proyecto($proyecto)
	{
		$proyecto = toba::db()->quote($proyecto);
		
		$sql = "SELECT 
					proyecto, 
					fuente_datos, 
					descripcion as descripcion_corta, 
					descripcion, 
					schema,
					permisos_por_tabla,
					tiene_auditoria
				FROM	
					apex_fuente_datos
				WHERE	
					proyecto = $proyecto
				ORDER BY 
					fuente_datos";
		
		return toba::instancia()->get_db()->consultar($sql);
	}

	function get_nombre_fuente_datos($fuente_de_datos)
	{
		$fuente_de_datos = toba::db()->quote($fuente_de_datos);
		$where = "adm_bases_preinscripcion.fuente_de_datos = $fuente_de_datos";
		$datos = current($this->get_fuentes_datos_preinscripcion($where));
		return $datos['nombre'];
	}
	
	/**
	 * Obtiene un listado de preinscripciones según filtro especificado
	 */
	function get_listado($where='', $fuente = null)
	{
        //Si existe el dato de la fuente tomo el que me pasaron por parametro
        if (toba::memoria()->existe_dato('fuente_preinscripcion')) {            
            $fuente_preinscripcion = toba::memoria()->get_dato('fuente_preinscripcion');
        } else {
            $fuente_preinscripcion = $fuente;
        }
		if ($where) {
			$where = 'WHERE '.$where;
		}
		
		$sql = "SELECT 	*
				FROM 	sga_preinscripcion
				$where
				";
		
		return guarani_db::consultar($sql, null, false, false, '', $fuente_preinscripcion);
	}
	
	/**
	 * Obtiene los datos de una preinscripción dada
	 *
	 * @param int $id_preinscripcion
	 */
	function get_datos_preinscripcion_version_impresa($id_preinscripcion, $version_impresa)
	{
		$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		
		$id_preinscripcion = toba::db($fuente)->quote($id_preinscripcion);
		$version_impresa = toba::db($fuente)->quote($version_impresa);
		
		$where = "	sga_preinscripcion.id_preinscripcion = $id_preinscripcion
					AND sga_preinscripcion.version_impresa = $version_impresa";
		
		return current($this->get_listado($where));
	}

	function get_datos_preinscripcion($id_preinscripcion, $fuente)
	{
		$id_preinscripcion = toba::db($fuente)->quote($id_preinscripcion);
		$sql = "SELECT 	
						COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,
                                                COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,
						sga_preinscripcion.sexo,
						sga_preinscripcion.nacionalidad,
						sga_preinscripcion.celular_numero,
						sga_preinscripcion.e_mail,
						sga_preinscripcion.pais_documento,
						sga_preinscripcion.tipo_documento,
						sga_preinscripcion.nro_documento
				FROM 	sga_preinscripcion
				WHERE	sga_preinscripcion.id_preinscripcion = $id_preinscripcion
		";
		$datos = guarani_db::consultar_fila($sql, null, false, false, '', $fuente);
		$datos['sexo_descr'] = $this->buscar_descripcion('sexo', $datos['sexo']);
		$datos['nacionalidad_descr'] = $this->buscar_descripcion('nacionalidad', $datos['nacionalidad']);
		return $datos;
	}
	
	/**
	 * Obtiene datos de preinscripciones preparadas para procesar.
	 *
	 */
	function get_preinscripciones_preparadas_insc($where='', $fuente=null)
	{
		// Solo es usado este metodo para levantar preinscirpciones a propuestas desde el modulo de Preinscripcion a propuestas
		// Porque en la base de preinscripcion la tabla se llama "sga_preinscripcion_propuestas" y en la base de G3 la tabla se llama "sga_preinscripcion_propuesta"
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		if ($where) {
			$where = 'AND '.$where;
		}
		$sql = "SELECT	sga_preinscripcion_propuestas.preinscripcion_propuesta,
						sga_preinscripcion_propuestas.id_preinscripcion,
						sga_preinscripcion_propuestas.responsable_academica,
						sga_preinscripcion_propuestas.propuesta,
						sga_preinscripcion_propuestas.ubicacion,
						sga_preinscripcion_propuestas.modalidad,
						sga_preinscripcion_propuestas.periodo_insc,
						sga_preinscripcion_propuestas.fecha_preinscripcion,
						sga_preinscripcion_propuestas.fecha_fin_vigencia,
						sga_preinscripcion_propuestas.estado,
						CASE sga_preinscripcion.es_mayor_25_sin_secundario
						   WHEN '".comunes::si."' THEN 3 -- Mayor de 25 años
						   ELSE 1 -- Con titulo Secundario
						END as tipo_ingreso,
						sga_preinscripcion_propuestas.atiende as atendido_por
				FROM	sga_preinscripcion_propuestas
				JOIN	sga_preinscripcion ON sga_preinscripcion_propuestas.id_preinscripcion = sga_preinscripcion.id_preinscripcion
						WHERE	(sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."' OR sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_solicitud_informacion."' ) AND
						sga_preinscripcion_propuestas.fecha_fin_vigencia >= CURRENT_DATE
						$where
		";
		$datos = guarani_db::consultar($sql, null, false, false, '', $fuente);

		$salida = array();
		
		$mapeo_modalidades = $this->get_mapeo_nombres_modalidades_cursada();
		
		// Se obtienen las descripciones de datos a partir de tablas de conversión
		foreach ($datos as $dato) {
			$where = "vw_perfil_de_datos_propuestas.propuesta = {$dato['propuesta']}";
			$datos_propuesta = current(toba::consulta_php('co_propuestas')->get_propuestas_perfil_de_datos($where));
			if (!empty($datos_propuesta)) {
				$dato['propuesta_nombre_abreviado'] = $datos_propuesta['nombre_abreviado'];
				$dato['ubicacion_nombre'] = toba::consulta_php('co_ubicaciones')->get_ubicacion_nombre($dato['ubicacion']);
				
				// Descripción de Modalidad 
				$dato['modalidad_nombre'] = $mapeo_modalidades[$dato['modalidad']];
				
				//busco atendedor
				if(isset($dato['atendido_por'])){
					$dato['atendido_por_nombre_apellido'] = toba::consulta_php('co_personas')->get_nombre_persona($dato['atendido_por']); 
				}else{
					$dato['atendido_por_nombre_apellido'] = 'Sin Asignar';
				}
				$salida[] = $dato;
			}
		}
		
		return $salida;
	}
	
	/**
	 * Obtiene un mapeo <id modalidad cursada > -> <nombre modalidad>
	 * 
	 * @return array
	 */
	protected function get_mapeo_nombres_modalidades_cursada()
	{
		$sql = "SELECT modalidad, nombre FROM sga_modalidad_cursada";
		
		$modalidades = guarani_db::consultar($sql);
		$salida = array();
		foreach ($modalidades as $modalidad) {
			$salida[$modalidad['modalidad']] = $modalidad['nombre'];
		}
		return $salida;
	}
	
	function get_preinscripciones_preparadas_insc_por_id($id_preinscripcion, $fuente=null)
	{
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		$where = "sga_preinscripcion_propuestas.id_preinscripcion = $id_preinscripcion";
		return $this->get_preinscripciones_preparadas_insc($where, $fuente);
	}

	/**
	 * Obtiene el listado de propuestas que tienen preinscripciones vigentes sin ser procesadas - Desde Preinscripcion
	 */
	function get_listado_propuestas_insc_masiva()
	{
		$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		
		$sql = "SELECT DISTINCT sga_preinscripcion_propuestas.propuesta
				  FROM sga_preinscripcion_propuestas
				  JOIN sga_preinscripcion ON sga_preinscripcion.id_preinscripcion = sga_preinscripcion_propuestas.id_preinscripcion
				 WHERE sga_preinscripcion.estado = '".preinscripcion::estado_preparado."'
				   AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."'
				   AND sga_preinscripcion_propuestas.fecha_fin_vigencia >= CURRENT_DATE";
		
		$propuestas = guarani_db::consultar($sql, null, false, false, '', $fuente);
		
		$salida = array();
		
		foreach ($propuestas as $propuesta) {
			$where = "vw_perfil_de_datos_propuestas.propuesta = {$propuesta['propuesta']}";
			$datos_propuesta = current(toba::consulta_php('co_propuestas')->get_propuestas_perfil_de_datos($where));
			if (!empty($datos_propuesta)) {
				$propuesta['propuesta_nombre'] = $datos_propuesta['nombre'];
				$salida[] = $propuesta;
			}
		}
		
		return $salida;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Consultas de chequeo de existencia de registros
	//-----------------------------------------------------------------------------------
	
	function existe_documento_persona($persona, $tipo_documento, $pais_documento)
	{
		$persona = toba::db()->quote($persona);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$pais_documento = is_null($pais_documento) ? persona::pais_argentina : toba::db()->quote($pais_documento);

		$sql = "SELECT  count(1) as cant
				FROM 	mdp_personas_documentos
				WHERE	mdp_personas_documentos.persona = $persona
						AND mdp_personas_documentos.tipo_documento = $tipo_documento
				        AND mdp_personas_documentos.pais_documento = $pais_documento";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] != 0);
	}
	
	function existe_registro_persona_extranjera($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT  count(1) as cant
				FROM 	mdp_personas_extranjeros
				WHERE	mdp_personas_extranjeros.persona = $persona
				";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] != 0);
	}
	
	function existe_registro_contacto_persona($persona, $contacto_tipo)
	{
		$persona = toba::db()->quote($persona);
		$contacto_tipo = toba::db()->quote($contacto_tipo);
		
		$sql = "SELECT  count(1) as cant
				FROM 	mdp_personas_contactos
				WHERE	mdp_personas_contactos.persona = $persona
						AND mdp_personas_contactos.contacto_tipo = $contacto_tipo;
				";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] != 0);
	}
	
	function existe_registro_estudio_institucion($persona, $nivel_estudio, $institucion=null, $titulo=null)
	{
		$persona = toba::db()->quote($persona);
		$nivel_estudio = toba::db()->quote($nivel_estudio);
		
		$where = '';
		if (!is_null($institucion)) {
			$institucion = toba::db()->quote($institucion);
			$where .= 'AND '.guarani_sql::armar_condicion_compara_cadenas('mdp_datos_estudios.institucion_otra', $institucion);
		}
		if (!is_null($titulo)) {
			$titulo = toba::db()->quote($titulo);
			$where .= 'AND '.guarani_sql::armar_condicion_compara_cadenas('mdp_datos_estudios.titulo_otro', $titulo);
		}
		
		$sql = "SELECT 	count(1) as cant
				FROM 	mdp_datos_estudios
				WHERE	mdp_datos_estudios.persona = $persona
						AND mdp_datos_estudios.nivel_estudio = $nivel_estudio
						$where
				";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	function existe_registro_allegado($dato_censal, $tipo_allegado)
	{
		$dato_censal = toba::db()->quote($dato_censal);
		$tipo_allegado = toba::db()->quote($tipo_allegado);
		
		$sql = "SELECT count(1) as cant
				  FROM mdp_personas_allegados
				 WHERE mdp_personas_allegados.dato_censal = $dato_censal
				   AND mdp_personas_allegados.tipo_allegado = $tipo_allegado;
				";
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] != 0);
	}

	function existe_persona($pais_documento, $tipo_documento, $nro_documento, $fuente, $id_preinscripcion=null)
	{
		$pais_documento = toba::db()->quote($pais_documento);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$nro_documento = toba::db()->quote($nro_documento);
		$where = '';
		if (!empty($id_preinscripcion)) {
			$where = " AND sga_preinscripcion.id_preinscripcion <> $id_preinscripcion";
        }
		$sql = "SELECT	COUNT(1) as cant
				FROM	sga_preinscripcion
				WHERE	sga_preinscripcion.pais_documento = $pais_documento AND
						sga_preinscripcion.tipo_documento = $tipo_documento AND
						sga_preinscripcion.nro_documento = $nro_documento
						$where
		";
		$rs = guarani_db::consultar_fila($sql, null, false, false, '', $fuente);
		return ($rs['cant'] > 0);
	}

	function existe_mail($e_mail, $fuente, $id_preinscripcion=null)
	{
		if (!isset($e_mail)) {
			return true;
		}
		$e_mail = toba::db()->quote($e_mail);
		$sql = 'SELECT	count(sga_preinscripcion.id_preinscripcion) as cant
                FROM 	sga_preinscripcion
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_preinscripcion.e_mail', $e_mail).'
		';
		if (!is_null($id_preinscripcion)) {
			$id_preinscripcion = toba::db()->quote($id_preinscripcion);
			$sql .= " AND sga_preinscripcion.id_preinscripcion <> $id_preinscripcion";
		}
		$res = guarani_db::consultar_fila($sql, null, false, false, '', $fuente);
		return ($res['cant'] > 0);
	}

	//--------------------------------------------------------------------------
	//---- Antecedentes docentes -----------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_antecedentes_docentes($id_preinscripcion, $fuente=null)
	{
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		
		$sql = "SELECT 	actuacion_doc,
						id_preinscripcion,
						institucion,
						institucion_prein,
						cargo,
						materia,
						hora_sem_trab,
						fecha_desde,
						fecha_hasta
				FROM 	sga_actuacion_doc
				WHERE	id_preinscripcion = $id_preinscripcion
						";
		
		return guarani_db::consultar($sql, null, false, false, '', $fuente);
	}
	
	function existe_registro_antecedente_docente($persona, $parametros)
	{
		$persona = toba::db()->quote($persona);
		
		$where = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$where .= ' AND '.$parametro['campo'].' = '.toba::db()->quote(trim($parametro['valor']));
			}
		}
		
		$sql = "SELECT 	count(1) as cant
				FROM 	mdp_antecedentes_docentes
				WHERE	persona = $persona
						$where
				";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	//--------------------------------------------------------------------------
	//---- Antecedentes profesionales ------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_antecedentes_profesionales($id_preinscripcion, $fuente=null)
	{
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		
		$sql = "SELECT	actuacion_prof,
						id_preinscripcion,
						empresa,
						cargo,
						area,
						hora_sem_trab,
						fecha_desde,
						fecha_hasta
				FROM	sga_actuacion_prof 
				WHERE	id_preinscripcion = $id_preinscripcion
						";
		
		return guarani_db::consultar($sql, null, false, false, '', $fuente);
	}
	
	function existe_registro_antecedente_profesional($persona, $parametros)
	{
		$persona = toba::db()->quote($persona);
		
		$where = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$where .= ' AND '.$parametro['campo'].' = '.toba::db()->quote(trim($parametro['valor']));
			}
		}
		
		$sql = "SELECT 	count(1) as cant
				FROM 	mdp_antecedentes_profesionales
				WHERE	persona = $persona
						$where
				";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	//--------------------------------------------------------------------------
	//---- Formación académica -------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_formacion_academica($id_preinscripcion, $fuente=null)
	{
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		
		$sql = "SELECT	formacion_acad,
						id_preinscripcion,
						titulo_superior,
						titulo_sup_prein,
						institucion,
						institucion_prein,
						fecha_ingreso,
						fecha_egreso,
						titulo_tesis,
						director_tesis,
						categoria_coneau,
						duracion_teorica,
						nivel_estudio,
						estado
				FROM	sga_formacion_acad
				WHERE	id_preinscripcion = $id_preinscripcion
						";
		
		return guarani_db::consultar($sql, null, false, false, '', $fuente);
	}
	
	function existe_registro_formacion_academica($persona, $nivel_estudio, $parametros)
	{
		$persona = toba::db()->quote($persona);
		$nivel_estudio = toba::db()->quote($nivel_estudio);
		
		$where = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$where .= ' AND '.$parametro['campo'].' = '.toba::db()->quote(trim($parametro['valor']));
			}
		}
		
		$sql = "SELECT 	count(1) as cant
				FROM 	mdp_datos_estudios
				WHERE	persona = $persona
						AND nivel_estudio = $nivel_estudio
						$where
				";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] != 0);
	}
	
	/**
	 * Obtiene los datos de las preinscripciones a propuestas pendientes y vigentes
	 * para un ID de Preinscripción dado
	 * 
	 * @param int $id_preinscripcion
	 * @param string $fuente
	 * @return array
	 */
	function get_preinscripciones_propuestas_pendientes($id_preinscripcion, $fuente=null)
	{
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		$sql = "SELECT	sga_preinscripcion_propuestas.preinscripcion_propuesta,
						sga_preinscripcion_propuestas.id_preinscripcion,
						sga_preinscripcion_propuestas.responsable_academica,
						sga_preinscripcion_propuestas.propuesta,
						sga_preinscripcion_propuestas.ubicacion,
						sga_preinscripcion_propuestas.modalidad,
						sga_preinscripcion_propuestas.periodo_insc,
						sga_preinscripcion_propuestas.fecha_preinscripcion,
						sga_preinscripcion_propuestas.fecha_fin_vigencia,
						sga_preinscripcion_propuestas.estado,
						sga_preinscripcion_propuestas.atiende,
						CASE sga_preinscripcion.es_mayor_25_sin_secundario
							WHEN '".comunes::si."' THEN 3 -- Mayor de 25 años
							ELSE 1 -- Con titulo Secundario
						END as tipo_ingreso
				FROM	sga_preinscripcion_propuestas
				JOIN	sga_preinscripcion ON sga_preinscripcion_propuestas.id_preinscripcion = sga_preinscripcion.id_preinscripcion
				WHERE	sga_preinscripcion_propuestas.id_preinscripcion = $id_preinscripcion AND
						sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."' AND
						sga_preinscripcion_propuestas.fecha_fin_vigencia >= CURRENT_DATE
		";
		return guarani_db::consultar($sql, null, false, false, '', $fuente);
	}
	
	/**
	 * Obtiene los datos de un requisito digital presentado en Preinscripción por 
	 * un aspirante. Como puede haber más de una fuente de Preinscripción, se itera
	 * cortando en la primera aparición (que debería ser la única), entregando en la 
	 * estructura de datos resultante los datos del requisito y la fuente de dónde se
	 * obtuvo.
	 * 
	 * @param int $persona
	 * @param int $requisito
	 * 
	 * @param
	 */
	function get_requisito_digital($persona, $requisito)
	{
		$fuentes = $this->get_fuentes_datos_preinscripcion();
		
		$persona = toba::db()->quote($persona);
		$requisito = toba::db()->quote($requisito);
		
		foreach ($fuentes as $fuente) {
			$sql = "SELECT 
						sga_requisitos_digitales.requisito_digital,
						sga_requisitos_digitales.requisito,
						sga_requisitos_digitales.id_documento
					FROM sga_requisitos_digitales
					JOIN sga_preinscripcion ON sga_requisitos_digitales.id_preinscripcion = sga_preinscripcion.id_preinscripcion
					WHERE sga_preinscripcion.persona = $persona
					  AND sga_requisitos_digitales.requisito = $requisito
					  AND sga_requisitos_digitales.estado = '" . preinscripcion::est_req_dig_pendiente . "'
					";
			
			$datos = guarani_db::consultar_fila($sql, null, false, false, '', $fuente['fuente_de_datos']);
			
			if (!empty($datos)) {
				return array(
					'datos' => $datos,
					'fuente' => $fuente['fuente_de_datos'],
				);
			}
		}

		return null;
	}
	
	/**
	 * Requisitos digitales cargados por el aspirante
	 * 
	 * @param int		$id_preinscripcion
	 * @param string	$fuente
	 * @return array
	 */
	function get_requisitos_digitales($id_preinscripcion, $fuente=null)
	{
		if (is_null($fuente)) {
			$fuente = toba::memoria()->get_dato('fuente_preinscripcion');
		}
		$id_preinscripcion = toba::db()->quote($id_preinscripcion);
		
		$sql = "SELECT 
					requisito_digital,
					id_preinscripcion,
					requisito,
					id_documento,
					estado
				FROM 
					sga_requisitos_digitales
				WHERE
					id_preinscripcion = $id_preinscripcion
				";
		
		return guarani_db::consultar($sql, null, false, false, '', $fuente);
	}

	//--------------------------------------------------------------------------
	//---- Reporte de Preinscriptos --------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_datos_reporte_preinscripciones($condiciones)
	{
		$datos = array();
		foreach ($condiciones['origenes'] as $origen) {
			if ($origen == preinscripcion::origen_autogestion) {
				$datos = array_merge($datos, $this->get_datos_reporte_desde_3w($condiciones, $origen));
			} else {
				$datos = array_merge($datos, $this->get_datos_reporte_desde_preinscripcion($condiciones, $origen));
			}
		}
		// Inicio array de colegios y descirpciones
		$colegios = [];
		
		foreach ($datos as $key => $dato) {
            //Si es de preinscripcion busco el colegio por query lo almaceno en 
            //el array y luego lo paso a datos.
            if ($dato['origen'] != preinscripcion::origen_autogestion && isset($datos[$key]['colegio_secundario'])) {
				// $colegio = $this->buscar_colegio_secundario($datos[$key]['colegio_secundario']);
                // $datos[$key]['colegio'] = $colegio['descripcion'];
				
				// Si ya existe en el array, recupero el nombre, sino lo busco 
				$id_colegio = $datos[$key]['colegio_secundario'];
				if (!isset($colegios[$id_colegio])) {
					$nombre_colegio = toba::consulta_php('co_instituciones')->get_nombre_colegio_por_id($id_colegio);
					$colegios[$id_colegio]  = $nombre_colegio;
				}

				// Asigno el nombre del colegio que ya existe en el array de colegios.
				$datos[$key]['colegio'] = $colegios[$id_colegio];
            }            
			
			$datos[$key]['tipo_documento_descr'] = $this->buscar_descripcion('tipo_documento', $dato['tipo_documento']);
			$datos[$key]['sexo_descr'] = $this->buscar_descripcion('sexo', $dato['sexo']);
			$datos[$key]['nacionalidad_descr'] = $this->buscar_descripcion('nacionalidad', $dato['nacionalidad']);
			$datos[$key]['responsable_academica_descr'] = $this->buscar_descripcion('responsable_academica', $dato['responsable_academica']);
			$datos[$key]['propuesta_descr'] = $this->buscar_descripcion('propuesta', $dato['propuesta']);
			$datos[$key]['ubicacion_descr'] = $this->buscar_descripcion('ubicacion', $dato['ubicacion']);
			$datos[$key]['modalidad_descr'] = $this->buscar_descripcion('modalidad', $dato['modalidad']);
			$datos[$key]['identificacion'] = $datos[$key]['tipo_documento_descr'] . ' ' . $datos[$key]['nro_documento'];
			$datos[$key]['origen'] = $this->buscar_descripcion('origen', $dato['origen']);
			$datos[$key]['estado'] = $this->buscar_descripcion('estado', $dato['estado']);
			$datos[$key]['fuente'] = $dato['origen'];
			$datos[$key]['turno'] = $this->buscar_turno_presentacion($dato['responsable_academica'], $dato['persona'], $dato['id_preinscripcion']);
		}
		return rs_ordenar_por_columnas($datos, array('responsable_academica', 'propuesta', 'apellido', 'nombres'));
	}
	
	protected function get_datos_reporte_desde_3w($condiciones, $origen)
	{
        $mail_prim = toba::db()->quote(persona::mail_primario);
        $mail_sec  = toba::db()->quote(persona::mail_secundario);
        $mail_inst = toba::db()->quote(persona::mail_institucional);

        $tel_cel = toba::db()->quote(persona::tel_celular); 
        
		$where = ' WHERE ';
		$where .= (!empty($condiciones['where_3w'])) ? $condiciones['where_3w'] : ' 1 = 1 ';

		if (!empty($condiciones['propuestas'])) {
			$where .= ' AND ' . guarani_sql::armar_condicion_in($condiciones['propuestas'], 'sga_preinscripcion_propuesta', 'propuesta');
		}
		if (!empty($condiciones['periodos'])) {
			$where .= ' AND ' . guarani_sql::armar_condicion_in($condiciones['periodos'], 'sga_preinscripcion_propuesta', 'periodo_insc');
		}
		if (!empty($condiciones['estado'])) {
			switch ($condiciones['estado']) {
			case preinscripcion::lista_para_procesar:
				$where .= "AND sga_preinscripcion_propuesta.estado = '".preinscripcion::insc_pendiente."'";
				break;
			case preinscripcion::con_informacion_solicitada:
			case preinscripcion::sin_finalizar_por_el_aspirante:	
				$where .= "AND false";  // No se le puede solicitar información ni finalizar la preinscripciones por Autogestión
				break;
			case preinscripcion::procesada:
				$where .= "AND sga_preinscripcion_propuesta.estado = '".preinscripcion::insc_procesada."'";
				break;
			case preinscripcion::todas:
				break;
			}
		}
		if (!empty($condiciones['duplicada']) && $condiciones['duplicada'] == comunes::si) {
			$where .= ' AND false'; // No existen cuentas duplicadas en Autogestión.
		}

		// Se obtienen datos desde 3w
		$sql = "SELECT 
					CAST(NULL as integer) as id_preinscripcion,
					mdp_personas.persona,
					COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
					COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
					COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS apellido_y_nombres,
					mdp_personas_documentos.pais_documento,
					mdp_personas_documentos.tipo_documento,
					mdp_personas_documentos.nro_documento,
					mdp_personas.sexo,
					mdp_personas.fecha_nacimiento,
					mdp_personas.nacionalidad,
					mdp_personas.localidad_nacimiento AS loc_nacimiento,
					sga_preinscripcion_propuesta.preinscripcion as id_preinscripcion_3w,
					sga_preinscripcion_propuesta.responsable_academica,
					sga_preinscripcion_propuesta.propuesta,
					sga_preinscripcion_propuesta.ubicacion,
					sga_preinscripcion_propuesta.modalidad,
					sga_preinscripcion_propuesta.periodo_insc,
					sga_preinscripcion_propuesta.fecha_preinscripcion,
					sga_preinscripcion_propuesta.fecha_fin_vigencia,
					CASE
						WHEN sga_preinscripcion_propuesta.estado = '". preinscripcion::insc_pendiente. "' THEN '".preinscripcion::lista_para_procesar."'
						WHEN sga_preinscripcion_propuesta.estado = '". preinscripcion::insc_procesada. "' THEN '".preinscripcion::procesada."'							
						ELSE ''
					END as estado,
					sga_preinscripcion_propuesta.atiende,
					COALESCE(COALESCE( ap.apellido_elegido, ap.apellido ) || ', ' || COALESCE( ap.nombres_elegido, ap.nombres ),'Sin Asignar') as atendido_por,
					sga_preinscripcion_propuesta.tipo_ingreso,
					CASE 
						WHEN sga_preinscripcion_propuesta.estado = '" . preinscripcion::insc_procesada . "' THEN 'Sí'
						ELSE 'No'
					END AS procesado,
                    COALESCE((SELECT 
							mdp_personas_contactos.email
							FROM mdp_personas_contactos
							WHERE mdp_personas_contactos.persona = mdp_personas.persona 
							 AND contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
							ORDER BY contacto_tipo = $mail_prim DESC, contacto_tipo = $mail_sec DESC, contacto_tipo = $mail_inst DESC  
							LIMIT 1),'') AS mail,
					COALESCE((SELECT 
							'(' || mdp_personas_contactos.telefono_codigo_area || ') ' || mdp_personas_contactos.telefono_numero
							FROM mdp_personas_contactos
							WHERE mdp_personas_contactos.persona = mdp_personas.persona 
							 AND contacto_tipo = $tel_cel
							LIMIT 1),'') AS telefono_celular,
                    sga_colegios_secundarios.nombre || '(' || sga_colegios_secundarios.cue || ')' as colegio,
					'$origen' AS origen,
					'".comunes::no_desc."' as duplicada
				FROM sga_preinscripcion_propuesta
				JOIN mdp_personas ON mdp_personas.persona = sga_preinscripcion_propuesta.persona
				JOIN mdp_personas_documentos ON mdp_personas_documentos.documento = mdp_personas.documento_principal
					                         AND mdp_personas_documentos.persona = mdp_personas.persona
                LEFT JOIN mdp_datos_estudios ON  mdp_datos_estudios.persona = mdp_personas.persona
                                             AND mdp_datos_estudios.nivel_estudio = ".persona::nivel_secundario."
                LEFT JOIN sga_colegios_secundarios ON mdp_datos_estudios.colegio = sga_colegios_secundarios.colegio                
				LEFT JOIN mdp_personas as ap ON ap.persona = sga_preinscripcion_propuesta.atiende
				
				$where
				";

		return guarani::db()->consultar($sql);
	}
	
	protected function get_datos_reporte_desde_preinscripcion($condiciones, $fuente)
	{
		$where = ' AND ';
		$where .= (!empty($condiciones['where_preinscripcion'])) ? $condiciones['where_preinscripcion'] : ' 1 = 1 ';

		if (!empty($condiciones['propuestas'])) {
			$where .= ' AND ' . guarani_sql::armar_condicion_in($condiciones['propuestas'], 'sga_preinscripcion_propuestas', 'propuesta');
		}
		if (!empty($condiciones['periodos'])) {
			$where .= ' AND ' . guarani_sql::armar_condicion_in($condiciones['periodos'], 'sga_preinscripcion_propuestas', 'periodo_insc');
		}

		if (!empty($condiciones['estado'])) {
			switch ($condiciones['estado']) {
			case preinscripcion::lista_para_procesar:
				$where .= "AND sga_preinscripcion.estado = '".preinscripcion::estado_preparado."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."'";
				break;
			case preinscripcion::con_informacion_solicitada:
				$where .= "AND sga_preinscripcion.estado = '".preinscripcion::estado_activado."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_solicitud_informacion."'";
				break;
			case preinscripcion::sin_finalizar_por_el_aspirante:
				$where .= "AND sga_preinscripcion.estado = '".preinscripcion::estado_activado."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."'";
				break;
			case preinscripcion::procesada:
				$where .= "AND sga_preinscripcion.estado = '".preinscripcion::estado_inscripto."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_procesada."'";
				break;
			case preinscripcion::todas:
				$where .= "AND sga_preinscripcion.estado <> '".preinscripcion::estado_pendiente."' AND sga_preinscripcion_propuestas.estado <> '".preinscripcion::insc_copiada_g3."'";
				break;
			}
		}
		if (!empty($condiciones['duplicada'])) {
			$where .= " AND sga_preinscripcion.duplicada = '{$condiciones['duplicada']}'";
		}
		if (!empty($condiciones['mismo_nombre_fecha_nac'])) {
			$where .= ($condiciones['mismo_nombre_fecha_nac'] == comunes::si) ? ' AND EXISTS ' :  ' AND NOT EXISTS ';
			$where .= "(SELECT otra_preinscripcion.id_preinscripcion 
			              FROM sga_preinscripcion as otra_preinscripcion 
			             WHERE otra_preinscripcion.apellido = sga_preinscripcion.apellido 
						   AND otra_preinscripcion.nombres  = sga_preinscripcion.nombres 
						   AND otra_preinscripcion.fecha_nacimiento = sga_preinscripcion.fecha_nacimiento 
						   AND otra_preinscripcion.id_preinscripcion <> sga_preinscripcion.id_preinscripcion
						 )";
		}

		// Se obtienen datos desde Preinscripción
		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion,
					CAST(NULL as integer) as persona,
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,
                                        COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) || ', ' || COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) AS apellido_y_nombres,
					sga_preinscripcion.pais_documento,
					sga_preinscripcion.tipo_documento,
					sga_preinscripcion.nro_documento,
					CASE 
						WHEN sga_preinscripcion.sexo = '1' THEN 'M'
						WHEN sga_preinscripcion.sexo = '2' THEN 'F'
						WHEN sga_preinscripcion.sexo = '3' THEN 'X'
						ELSE ''
					END AS sexo,
					sga_preinscripcion.fecha_nacimiento,
					sga_preinscripcion.nacionalidad,
					sga_preinscripcion.loc_nacimiento,
					CAST(NULL as integer) as id_preinscripcion_3w,			
					sga_preinscripcion_propuestas.responsable_academica,
					sga_preinscripcion_propuestas.propuesta,
					sga_preinscripcion_propuestas.ubicacion,
					sga_preinscripcion_propuestas.modalidad,
					sga_preinscripcion_propuestas.periodo_insc,
					sga_preinscripcion_propuestas.fecha_preinscripcion,
					sga_preinscripcion_propuestas.fecha_fin_vigencia,
					CASE
						WHEN sga_preinscripcion.estado = '".preinscripcion::estado_preparado."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."' THEN '".preinscripcion::lista_para_procesar."'
						WHEN sga_preinscripcion.estado = '".preinscripcion::estado_activado."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_solicitud_informacion."' THEN '".preinscripcion::con_informacion_solicitada."'
						WHEN sga_preinscripcion.estado = '".preinscripcion::estado_activado."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_pendiente."' THEN '".preinscripcion::sin_finalizar_por_el_aspirante."'	
						WHEN sga_preinscripcion.estado = '".preinscripcion::estado_inscripto."' AND sga_preinscripcion_propuestas.estado = '".preinscripcion::insc_procesada."' THEN '".preinscripcion::procesada."'		
						ELSE ''
					END as estado,
					sga_preinscripcion_propuestas.atiende,
					CASE sga_preinscripcion.es_mayor_25_sin_secundario
					  WHEN '".comunes::si."' THEN 3 -- Mayor de 25 años
					  ELSE 1 -- Con titulo Secundario
					END as tipo_ingreso, 
					CASE 
						WHEN sga_preinscripcion_propuestas.estado = '" . preinscripcion::insc_procesada . "' THEN 'Sí'
						ELSE 'No'
					END AS procesado,
                    sga_preinscripcion.colegio_secundario,
                    sga_preinscripcion.celular_numero as telefono_celular,
                    sga_preinscripcion.e_mail as mail,
					'$fuente' AS origen,
					".guarani_sql::get_case_si_no('sga_preinscripcion', 'duplicada')."
				FROM sga_preinscripcion
				JOIN sga_preinscripcion_propuestas ON sga_preinscripcion.id_preinscripcion = sga_preinscripcion_propuestas.id_preinscripcion
				WHERE
					-- Se excluyen las Preinscripciones copiadas a G3 (se obtienen con el listado de 3w)
					sga_preinscripcion_propuestas.estado <> '" . preinscripcion::insc_copiada_g3 . "'
					$where";
		
		$datos = guarani_db::consultar($sql, null, false, false, '', $fuente);
			foreach ($datos as $key => $dato) {
               if(isset($dato['atiende'])){
				$datos[$key]['atendido_por'] = toba::consulta_php('co_personas')->get_nombre_persona($dato['atiende']); 
				}else{
					$datos[$key]['atendido_por'] = 'Sin Asignar';
	}
			 }
		return $datos;
	
	}
	
	protected function buscar_descripcion($campo, $valor)
	{
		if (empty($this->mapeos)) {
			$this->inicializar_mapeos();
		}
		return (!empty($this->mapeos[$campo][$valor])) ? $this->mapeos[$campo][$valor] : '--';
	}
	
	protected function inicializar_mapeos()
	{
		$this->mapeos = array();
		$this->mapeos['tipo_documento'] = $this->convertir_formato_mapeo(toba::consulta_php('co_personas')->get_tipos_documentos(), 'valor', 'descr_ab');
		$this->mapeos['sexo'] = $this->convertir_formato_mapeo(toba::consulta_php('co_personas')->get_sexos(), 'valor', 'descr');
		$this->mapeos['nacionalidad'] = $this->convertir_formato_mapeo(toba::consulta_php('co_personas')->get_nacionalidades(), 'valor', 'descr');
		$this->mapeos['responsable_academica'] = $this->convertir_formato_mapeo(toba::consulta_php('co_responsables_academicas')->get_responsables_academicas(), 'valor', 'descr');
		$this->mapeos['propuesta'] = $this->convertir_formato_mapeo(toba::consulta_php('co_propuestas')->get_propuestas_nombres(), 'valor', 'descr');
		$this->mapeos['ubicacion'] = $this->convertir_formato_mapeo(toba::consulta_php('co_ubicaciones')->get_ubicaciones(), 'valor', 'descr');
		$this->mapeos['modalidad'] = $this->convertir_formato_mapeo(toba::consulta_php('co_inscripciones')->get_modalidades_cursadas(), 'valor', 'descr');
		$this->mapeos['origen'] = $this->convertir_formato_mapeo($this->get_origenes_preinscripcion_para_reporte(), 'valor', 'descr');
		$this->mapeos['estado'] = $this->convertir_formato_mapeo($this->get_estado_preinscripcion_para_reporte(), 'valor', 'descr');
	}
	
	protected function convertir_formato_mapeo($datos, $campo_valor, $campo_descr)
	{
		$salida = array();
		foreach ($datos as $dato) {
			$salida[$dato[$campo_valor]] = $dato[$campo_descr];
		}
		return $salida;
	}
        
	function get_datos_desde_preinscripcion($where, $where_propuesta, $base)
    {
		$where_gatillo = '';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			// Se filtra por perfil de datos usando la vista vw_ug_propuestas_sin_gestionar_actividades y no vw_ug_propuestas.
			// Esto es porque en las operaciones del módulo de matrícula, si el usuario logueado tiene perfil de datos con dominio "Responsables Académicas",
			// se restringen las propuestas a aquellas que gestiona la RA.
			$propuestas_visibles = toba::consulta_php('co_propuestas')->get_propuestas_perfil_de_datos();
			$where_gatillo = ' AND ' . guarani_sql::armar_condicion_in($propuestas_visibles, 'sga_preinscripcion_propuestas', 'propuesta'); 
		}
		$sql = "SELECT	sga_preinscripcion.id_preinscripcion as id,
						COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) || ', ' || COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) AS apellido_nombres,
						sga_preinscripcion.id_preinscripcion || '/' || sga_preinscripcion.version_impresa as tramite,
						sga_preinscripcion.version_impresa as version_impresa,
						sga_preinscripcion.e_mail as mail,
						sga_preinscripcion.tipo_documento,
						sga_preinscripcion.nro_documento,
						CASE sga_preinscripcion.validado_con_renaper
						   WHEN '".comunes::si."' THEN '".comunes::si_desc."'
						   ELSE '".comunes::no_desc."'
						END as validado_con_renaper,
						".quote(preinscripcion::origen_preinscripcion_desc)." as origen_descr,
						".quote(preinscripcion::origen_preinscripcion)." as origen,
						'$base' as fuente_de_datos,
						".guarani_sql::get_case_si_no('sga_preinscripcion', 'duplicada').",
						sga_preinscripcion.fecha_ult_modif
				FROM	sga_preinscripcion
				WHERE	-- Se excluyen las Preinscripciones copiadas a G3 (se obtienen con el listado de 3w)
						-- Para buscar la propuesta y ubicacion
						sga_preinscripcion.id_preinscripcion IN (	SELECT  id_preinscripcion
																	FROM	sga_preinscripcion_propuestas
																	WHERE	sga_preinscripcion_propuestas.estado <> '".preinscripcion::insc_copiada_g3."' AND
																			sga_preinscripcion_propuestas.estado <> '".preinscripcion::insc_procesada."' AND
																			sga_preinscripcion_propuestas.fecha_fin_vigencia >= CURRENT_DATE
																			$where_gatillo
																			$where_propuesta) AND 
						sga_preinscripcion.estado = '".preinscripcion::estado_preparado."' 
						$where
		";
		$datos = guarani_db::consultar($sql, null, false, false, '', $base);
		//Tengo que traer el tipo Documento
		foreach ($datos as $key => $dato) {
			$datos[$key]['identificacion'] = $this->buscar_descripcion('tipo_documento', $dato['tipo_documento']).' '.$datos[$key]['nro_documento'];
		}
		return $datos;
	}

	/**
	 * Devuelve los datos de los alumnos de autogestion aspirantes que tienen preiscripciones aún no procesadas
	 * 
	 * @param type $where 
	 * @param type $where_propuesta Filtros $filtro_anio $filtro_propuesta $filtro_ubicacion $filtro_tiene_atendedor $filtro_atendido_por
	 * @return type
	 */	
	function get_datos_desde_autogestion($where, $where_propuesta)
	{
		$mail_prim = toba::db()->quote(persona::mail_primario);
		$mail_sec = toba::db()->quote(persona::mail_secundario);
		$mail_inst = toba::db()->quote(persona::mail_institucional);

		$where_gatillo = ' AND true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			// Se filtra por perfil de datos usando la vista vw_ug_propuestas_sin_gestionar_actividades y no vw_ug_propuestas.
			// Esto es porque en las operaciones del módulo de matrícula, si el usuario logueado tiene perfil de datos con dominio "Responsables Académicas",
			// se restringen las propuestas a aquellas que gestiona la RA.
			$where_gatillo = ' AND ' . toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'vw_perfil_de_datos_propuestas', 'sga_propuestas');
		}

		// Recupera personas que tienen preinscripción a propuesta y que estan en condiciones de procesar.
		$sql = "SELECT	persona as id,
						apellido_nombres,
						tipo_nro_documento as identificacion,
						".quote(preinscripcion::origen_autogestion_desc)." as origen_descr,
						".quote(preinscripcion::origen_autogestion)." as origen,
						'' as tramite,
						COALESCE((	SELECT		mdp_personas_contactos.email
									FROM		mdp_personas_contactos
									WHERE		mdp_personas_contactos.persona = vw_personas.persona
												AND	contacto_tipo IN ($mail_prim, $mail_sec, $mail_inst)
									ORDER BY	contacto_tipo = $mail_prim DESC, contacto_tipo = $mail_sec DESC, contacto_tipo = $mail_inst DESC
									LIMIT 1
								),'') as mail,
						'negocio' as fuente_de_datos,
						'' as version_impresa,
						CASE validado_con_renaper
						   WHEN '".comunes::si."' THEN '".comunes::si_desc."'
						   ELSE '".comunes::no_desc."'
						END as validado_con_renaper,
						'".comunes::no_desc."' as duplicada
				FROM	vw_personas
				WHERE	persona	IN	(	SELECT	sga_preinscripcion_propuesta.persona
										FROM	sga_preinscripcion_propuesta
												JOIN sga_propuestas ON sga_propuestas.propuesta = sga_preinscripcion_propuesta.propuesta
										WHERE	sga_preinscripcion_propuesta.estado NOT IN ( ".quote(preinscripcion::insc_procesada)." ,".quote(preinscripcion::insc_nueva)." )AND
												sga_preinscripcion_propuesta.fecha_fin_vigencia >= CURRENT_DATE
												$where_gatillo
												$where_propuesta
									)
						$where
		";

		$datos = guarani_db::consultar($sql, null, false);
		return $datos;
	}

	/**
	 * Orígenes de datos para el reporte. Agrupa el origen Autogestión (alumnos ya 
	 * existentes en la Institución) con las fuentes de datos de Preinscripción 
	 * configuradas.
	 * 
	 * @return array
	 */
	function get_origenes_preinscripcion_para_reporte()
	{
		$salida = array();
		// Caso particular: Autogestión
		$salida[] = array('valor' => preinscripcion::origen_autogestion, 'descr' => 'Autogestión');
		// Fuentes de datos de Preinscripción
		$fuentes = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion();
		foreach ($fuentes as $fuente) {
			$salida[] = array('valor' => $fuente['fuente_de_datos'], 'descr' => $fuente['nombre']);
		}
		return $salida;
	}
	function get_estado_preinscripcion_para_reporte()
	{
		$salida = array();

		$salida=[	['valor' => preinscripcion::lista_para_procesar,			'descr' =>  preinscripcion::lista_para_procesar_desc],
					['valor' => preinscripcion::con_informacion_solicitada,		'descr' =>  preinscripcion::con_informacion_solicitada_desc],
					['valor' => preinscripcion::sin_finalizar_por_el_aspirante,	'descr' =>  preinscripcion::sin_finalizar_por_el_aspirante_desc],
					['valor' => preinscripcion::procesada,						'descr' =>  preinscripcion::procesada_desc],
					['valor' => preinscripcion::todas,							'descr' =>  preinscripcion::todas_desc]
				];

		return $salida;
	}
	
	/**
	 * Obtiene el turno para presentación de la documentación elegido por el alumno o aspirante
	 * 
	 * @param int $ra
	 * @param int $persona
	 * @param int $id_preinscripcion
	 */
	protected function buscar_turno_presentacion($ra, $persona = null, $id_preinscripcion = null)
	{
		$ra = toba::db()->quote($ra);
		
		if (!is_null($persona)) {
			$where = " AND persona = " . toba::db()->quote($persona);
		} else {
			$where = " AND id_preinscripcion = " . toba::db()->quote($id_preinscripcion);
		}
		
		$sql = "SELECT	to_char(fecha,'".comunes::formato_fecha_visual."') || ' ' || hora || ' Op: ' || operador AS turno
				FROM	pre_turnos 
				WHERE	responsable_academica = $ra
						$where
				LIMIT	1";
		
		$res = guarani::db()->consultar_fila($sql);
		return (!empty($res['turno'])) ? $res['turno'] : '';
	}
    
    protected function buscar_colegio_secundario($colegio)
    {        
        $where = " sga_colegios_secundarios.colegio = $colegio";
        $colegio = current(toba::consulta_php('co_instituciones')->get_listado_colegios($where));
        return $colegio;
    }
            
    function get_preinscripto_rest($fuente, $usuario = null, $pais_documento = null, $tipo_documento = null, $nro_documento = null)
    {
        $where = '';        
        if ($usuario) {
            $usuario = toba::db()->quote($usuario);
            $where .= " AND sga_preinscripcion.e_mail = $usuario";
        }
        
        if ($tipo_documento) {
            $tipo_documento =  toba::db()->quote($tipo_documento);
            $where .= " AND sga_preinscripcion.tipo_documento = $tipo_documento";
        }
        
        if ($nro_documento) {
            $nro_documento =  toba::db()->quote($nro_documento); 
            $where .= " AND sga_preinscripcion.nro_documento = $nro_documento";
        }
        
        if ($pais_documento) {
            $pais_documento =  toba::db()->quote($pais_documento);
            $where .= " AND sga_preinscripcion.pais_documento = $pais_documento";
        }
        
        $sql = "
                SELECT
                    id_preinscripcion as id_preinscripcion,
                    e_mail as usuario
                FROM
                    sga_preinscripcion
                WHERE
                    TRUE
                    $where
                ";
		return guarani_db::consultar($sql, null, false, false, '', $fuente);
    }
    
    function get_turno_rest($id_preinscripcion, $fecha)
    {
        if ($fecha == '') {
            $fecha = 'CURRENT_DATE';
        } else {
            $fecha = toba::db()->quote($fecha);
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
                    ws_agenda_del_dia(null, $id_preinscripcion, $fecha, null);
                ";
        return guarani::db()->consultar($sql);
    }
    
    function get_origen()
    {
        $datos = [
                    ['valor' => preinscripcion::origen_ambos, 'descr' => preinscripcion::origen_ambos_desc],
                    ['valor' => preinscripcion::origen_autogestion, 'descr' => preinscripcion::origen_autogestion_desc],
                    ['valor' => preinscripcion::origen_preinscripcion, 'descr' => preinscripcion::origen_preinscripcion_desc],
                 ];
        return $datos;
    }
    
    function get_propuestas_preinscripcion($id_preinscripcion, $fuente)
    {
        $sql = "
                SELECT
                    propuesta
                FROM
                    sga_preinscripcion_propuestas
                WHERE
                    id_preinscripcion = $id_preinscripcion
                ";
        return guarani_db::consultar($sql, null, false, false, '', $fuente);
    }
    
    function get_parametros_campos_form($tipo_propuestas, $fuente)
	{
		$sql = "SELECT 
					columna,
					visible,
					obligatorio,
					se_imprime,
					genera_reporte
				FROM 
					sga_campos_conf
				WHERE
                    $tipo_propuestas
                GROUP BY
                    1,2,3,4,5
                ORDER BY
                    1";
        return guarani_db::consultar($sql, null, false, false, '', $fuente);
	}
    
    function get_campos_form($fuente)
    {
        $sql = "
                SELECT
                    columna,
                    descripcion
                FROM
                    sga_campos_form
                ";
        return guarani_db::consultar($sql, null, false, false, '', $fuente);
    }
    
    function get_responsables_academicas_inc_autogestion($persona)
    {
        $sql = "
                SELECT 	DISTINCT sga_preinscripcion_propuesta.responsable_academica
				FROM 	sga_preinscripcion_propuesta
				WHERE 	sga_preinscripcion_propuesta.persona = $persona                    
                ";
        return guarani::db()->consultar($sql);
    }
    
    function get_responsables_academicas_inc_preinscripcion($id_preinscripcion, $fuente)
    {
        $sql = "
                SELECT 	DISTINCT sga_preinscripcion_propuestas.responsable_academica
				FROM 	sga_preinscripcion_propuestas
				WHERE 	sga_preinscripcion_propuestas.id_preinscripcion = $id_preinscripcion                    
                ";
        return guarani_db::consultar($sql, null, false, false, '', $fuente);
    }
    
    function get_propuestas_insc_preinscripcion($id_preinscripcion, $fuente)
	{
		$sql = "SELECT 
					id_preinscripcion,
					responsable_academica,
					propuesta,
					ubicacion,
					modalidad,
					periodo_insc,
					estado,
					fecha_preinscripcion,
					fecha_fin_vigencia
				FROM 
					sga_preinscripcion_propuestas
				WHERE
					sga_preinscripcion_propuestas.id_preinscripcion = $id_preinscripcion
					AND fecha_fin_vigencia >= CURRENT_DATE
				ORDER BY
					responsable_academica
				";
		
		$datos = guarani_db::consultar($sql, null, false, false, '', $fuente);
		
		// Si corresponde, se obtienen las descripciones
		$salida = array();
		foreach ($datos as $dato) {
            $responsables = current(toba::consulta_php('co_responsables_academicas')->get_responsables_academicas('responsable_academica = '.$dato['responsable_academica']));
			$dato['ra_nombre'] = $responsables['descr'];
            $propuesta = toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas("sga_propuestas.propuesta = ".$dato['propuesta']);            
			$dato['propuesta_nombre'] = current($propuesta)['descr'];
            $ubicaciones = toba::consulta_php('co_ubicaciones')->get_ubicaciones($dato['ubicacion']);
			$dato['ubicacion_nombre'] = current($ubicaciones)['descr'];                        
            $modalidades = $this->get_mapeo_nombres_modalidades_cursada();
            $dato['modalidad_nombre'] = $modalidades[$dato['modalidad']];
            
            $tipo_propuesta = toba::consulta_php('co_propuestas')->get_datos_tipo_de_propuesta_una_propuesta($dato['propuesta']);
			$dato['propuesta_tipo'] = $tipo_propuesta['propuesta_tipo'];
			$dato['tipo_propuesta_descr'] = $tipo_propuesta['descripcion'];
			// Si se obtuvieron descripciones es porque pasó el filtro de perfil de datos
			if ((!empty($dato['ra_nombre'])) && (!empty($dato['propuesta_nombre'])) && (!empty($dato['ubicacion_nombre'])) 
				&& (!empty($dato['modalidad_nombre'])) && (!empty($dato['propuesta_tipo'])) && (!empty($dato['tipo_propuesta_descr']))) {
				$salida[] = $dato;
			}
		}
		
		return $salida;
	}
    
    function get_propuestas_insc_autogestion($persona)
    {
        $sql = "
                SELECT 	
                    sga_preinscripcion_propuesta.preinscripcion as id_preinscripcion,
                    sga_preinscripcion_propuesta.responsable_academica,
                    sga_preinscripcion_propuesta.persona,
                    sga_preinscripcion_propuesta.propuesta,
                    sga_preinscripcion_propuesta.ubicacion,
                    sga_preinscripcion_propuesta.periodo_insc,
                    sga_preinscripcion_propuesta.fecha_preinscripcion,
                    sga_preinscripcion_propuesta.fecha_fin_vigencia,
                    sga_responsables_academicas.nombre as ra_nombre,
                    sga_propuestas.nombre AS propuesta_nombre,
                    sga_ubicaciones.nombre AS ubicacion_nombre,	
                    sga_modalidad_cursada.nombre AS modalidad_nombre,
                    sga_propuestas.propuesta_tipo,
                    sga_propuestas_tipos.descripcion as tipo_propuesta_descr,
                    to_char(sga_preinscripcion_propuesta.fecha_fin_vigencia, '".comunes::formato_fecha_visual."') as fecha_fin_vigencia_visual
                FROM sga_preinscripcion_propuesta
                JOIN sga_propuestas ON sga_preinscripcion_propuesta.propuesta = sga_propuestas.propuesta
                JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
                JOIN sga_ubicaciones ON sga_preinscripcion_propuesta.ubicacion = sga_ubicaciones.ubicacion
                JOIN sga_modalidad_cursada ON sga_preinscripcion_propuesta.modalidad = sga_modalidad_cursada.modalidad
                JOIN sga_responsables_academicas ON sga_preinscripcion_propuesta.responsable_academica = sga_responsables_academicas.responsable_academica
                WHERE 
                    sga_preinscripcion_propuesta.persona = $persona
                    AND sga_preinscripcion_propuesta.fecha_fin_vigencia >= CURRENT_DATE
                    AND sga_preinscripcion_propuesta.estado  <> '" . preinscripcion::insc_procesada . "'";
        return guarani_db::consultar($sql);
    }
    
    function get_requisitos_digitales_preinscripcion($id, $fuente)
    {
        $sql = "
                SELECT
                    requisito_digital,
                    id_preinscripcion,
                    requisito,
                    id_documento,
                    estado
                FROM
                    sga_requisitos_digitales
                WHERE
                    id_preinscripcion = $id
                ";
        return guarani_db::consultar($sql, null, false, false, '', $fuente);
    }

	/**
	 * Retora la historial de preinscripcion 
	 *
	 * @return array
	 */
	function get_historial_preinscripcion($datos)
	{
		
		$where = "";
		if (isset($datos[0]['preinscripcion_propuesta'])) {
			$where = 'where preinscripcion = ' . $datos[0]['preinscripcion_propuesta'];
		} else {
			$where = 'where preinscripcion = ' . $datos[0]['preinscripcion'];
		}
		$sql = "SELECT  
				fecha,
				observaciones,
				estado
				FROM sga_preinscripcion_propuesta_log
				$where
		";

		$datos = guarani_db::consultar($sql);
		foreach ($datos as $key => $dato) {
			if(isset($dato['observaciones'])){				
					$dato['observaciones'] = strip_tags(html_entity_decode($dato['observaciones']));
				}
				$datos[$key] = $dato;
			}
		
		return $datos;
	}

	function get_personas_tipo_gestion($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}


		$sql = "SELECT 	
							mdp_personas.persona,
							COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
							COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
							COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as  nombre_apellido

				FROM 		mdp_personas
				join mdp_personas_tipo_usuario on mdp_personas_tipo_usuario.persona = mdp_personas.persona
				where mdp_personas_tipo_usuario.tipo_usuario = 'Gestion'
				order by apellido;";

		return  guarani_db::consultar($sql);
	}
}
