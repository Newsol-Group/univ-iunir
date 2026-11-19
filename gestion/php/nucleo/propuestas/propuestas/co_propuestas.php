<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class co_propuestas
{
	function get_listado($where='', $order='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if (!$order) {
			$order = 'sga_propuestas.codigo';
		}
		
		$sql = "	SELECT  sga_propuestas.propuesta,
							sga_propuestas.codigo,
							sga_propuestas.nombre,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre || ' (' || sga_propuestas.codigo || ')' as entidad_descripcion,
							sga_propuestas.nombre_abreviado,
							".guarani_sql::get_case_si_no('sga_propuestas','publica').",
							sga_propuestas.propuesta_tipo,
							sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
							sga_propuestas.fecha_alta,
							sga_propuestas.documento_alta,
							res_alta.documento_numero as documento_alta_numero,
							sga_propuestas.fecha_baja,
							sga_propuestas.documento_baja,
							res_baja.documento_numero as documento_baja_numero,
							sga_propuestas.estado as id_estado,
							sga_propuestas_estados.descripcion as estado,
							sga_g3entidades.entidad

					FROM	sga_propuestas
							JOIN sga_g3entidades ON sga_propuestas.entidad = sga_g3entidades.entidad
							JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
							JOIN sga_propuestas_estados ON sga_propuestas.estado = sga_propuestas_estados.estado
							LEFT JOIN sga_documentos as res_alta ON sga_propuestas.documento_alta = res_alta.documento
							LEFT JOIN sga_documentos as res_baja ON sga_propuestas.documento_baja = res_baja.documento
			
					$where

					ORDER BY $order";

		return guarani_db::consultar($sql);
	}

	function get_propuestas_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT	vw_perfil_de_datos_propuestas.propuesta,
						vw_perfil_de_datos_propuestas.nombre,
						vw_perfil_de_datos_propuestas.nombre_abreviado
				FROM	vw_perfil_de_datos_propuestas
				$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de propuestas con planes-versiones activos o vigentes según el filtro especificado.
	 *
	 * @param unknown_type 
	 * @return unknown
	 */
	function get_propuestas($where='')
	{
		if ($where) {
			$where = " AND $where";
		}        
		$sql = "SELECT 		sga_propuestas.propuesta,
							sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.codigo AS propuesta_codigo,
							sga_propuestas_tipos.descripcion AS propuesta_tipo_descripcion,
							sga_planes.nombre AS plan_nombre,
                            sga_planes.codigo AS plan_codigo,
                            sga_planes.estado AS plan_estado,
                            sga_planes.plan AS plan,
							('(' || sga_planes.codigo || ') ' || sga_planes.nombre) as plan_codigo_nombre,
							sga_planes_versiones.nombre AS version_nombre,
                            sga_planes_versiones.estado AS plan_version_estado,
							sga_planes_versiones.plan_version,
							sga_planes_versiones.version,
                            sga_planes_versiones.fecha_entrada_vigencia
				FROM 		sga_propuestas,
							sga_planes,
							sga_planes_versiones,
							sga_propuestas_tipos
				WHERE 		sga_propuestas.propuesta = sga_planes.propuesta AND
							sga_planes.plan = sga_planes_versiones.plan AND
							sga_planes_versiones.estado IN ('".planes::estado_activo."','".planes::estado_vigente."') AND
							sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
							$where
				ORDER BY	sga_propuestas.nombre, sga_planes.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_listado_rest($where = '', $order = '')
	{
		if ($where) {
			$where = "AND $where";
		}

		$estado_activo = toba::db()->quote(propuesta::estado_activo);
		
		$sql = "
			SELECT 	sga_propuestas.propuesta							AS propuesta,
					sga_propuestas.nombre								AS propuesta_nombre,
					sga_propuestas.nombre_abreviado						AS propuesta_nombre_abreviado,
					sga_propuestas.codigo								AS propuesta_codigo,
					sga_propuestas_tipos.propuesta_tipo					AS propuesta_tipo,
					sga_propuestas_tipos.descripcion					AS propuesta_tipo_descripcion,
					sga_responsables_academicas.responsable_academica	AS responsable_academica,
					sga_responsables_academicas.nombre					AS responsable_academica_nombre,
					sga_responsables_academicas.codigo					AS responsable_academica_codigo,
					sga_instituciones.institucion						AS institucion,
					sga_instituciones.nombre							AS institucion_nombre,
					sga_instituciones.nombre_abreviado					AS institucion_nombre_abreviado,
					sga_instituciones.codigo							AS institucion_codigo
			FROM sga_propuestas
			JOIN sga_propuestas_tipos			ON (sga_propuestas_tipos.propuesta_tipo = sga_propuestas.propuesta_tipo)
			JOIN sga_propuestas_ra				ON (sga_propuestas_ra.propuesta = sga_propuestas.propuesta)
			JOIN sga_responsables_academicas	ON (sga_responsables_academicas.responsable_academica = sga_propuestas_ra.responsable_academica)
			JOIN sga_instituciones				ON (sga_instituciones.institucion = sga_responsables_academicas.institucion)
			WHERE sga_propuestas.estado = $estado_activo
			$where
			$order
		";

		return guarani_db::consultar($sql, null, false);
	}

	/**
        Obtiene una lista de nombres de propuestas según el tipo
    */
	function get_propuestas_x_proptipo($tipo_prop)
	{
		$tipo_prop = toba::db()->quote($tipo_prop);
		return $this->get_propuestas_nombres("sga_propuestas.propuesta_tipo = $tipo_prop");
	}
	
	/**
	 * Retorna el listado de las distintas propuestas-planes con planes-versiones activos o vigentes según el filtro especificado.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_propuestas_planes_versiones_activos($where=null)
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 		DISTINCT sga_planes.plan,
							sga_planes.nombre AS plan_nombre,
							sga_propuestas.nombre AS propuesta_nombre
				FROM 		sga_propuestas,
							sga_planes,
							sga_planes_versiones
				WHERE 		sga_propuestas.propuesta = sga_planes.propuesta AND
							sga_planes.plan = sga_planes_versiones.plan AND
							sga_planes_versiones.estado IN ('".planes::estado_activo."','".planes::estado_vigente."')
							$where
				ORDER BY	sga_propuestas.nombre, sga_planes.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de propuestas que cumplen las condiciones necesarias para admitir inscripciones:
	 *  - Tienen al menos un plan en estado "Activo Vigente", con inscripción habilitada y modalidad de cursada asociada.
	 *  - Tienen al menos un plan-versión en estado "Activo Vigente".
	 *
	 * @param string $where
	 * @return array
	 */
	function get_propuestas_para_inscripcion($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 	sga_propuestas.propuesta,
						sga_propuestas.nombre
				FROM 	sga_propuestas,
						sga_planes,
						sga_planes_versiones,
						sga_planes_modalidad
				WHERE 	sga_propuestas.propuesta = sga_planes.propuesta AND
						sga_planes.plan = sga_planes_versiones.plan AND
						sga_planes.plan = sga_planes_modalidad.plan AND
						sga_planes.inscripcion_habilitada = '".comunes::si."' AND
						sga_planes.estado = '". planes::estado_vigente ."' AND
						sga_planes_versiones.estado = '". planes::estado_vigente ."'
						$where
		";
		return guarani_db::consultar($sql);
	}
	
	function get_listado_nombres_propuestas($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 		sga_propuestas.propuesta		AS valor,
							sga_propuestas.nombre			AS descr,
							'('  || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descr
				FROM 		sga_propuestas
				$where
				ORDER BY 	sga_propuestas.nombre		
			   ";
		return guarani_db::consultar($sql);
	}
	
	function get_nombres_propuestas($propuesta=null)
	{
		$where = '';
		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$where .= " WHERE sga_propuestas.propuesta = $propuesta";
		}
		
		$sql = "SELECT 		sga_propuestas.propuesta,
							sga_propuestas.nombre
				FROM 		sga_propuestas
				$where
				ORDER BY 	sga_propuestas.nombre		
			   ";

		return guarani_db::consultar($sql);
	}
	
	function get_listado_nombres_abreviados_propuestas()
	{
		$sql = 'SELECT 		sga_propuestas.propuesta			AS valor,
							sga_propuestas.nombre_abreviado		AS descr
				FROM 		sga_propuestas
				ORDER BY 	sga_propuestas.nombre_abreviado		
			   ';

		return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna los datos correspondientes a una propuesta en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de las propuestas correspondientes según la condición del array (para la carga masiva).
      *	@param integer/array	propuesta	id o arreglo de ids de la o las propuestas para los cuales se consultan los datos.
    */
	function get_propuestas_datos($propuesta)
	{
		if (is_array($propuesta)) {
			$filtro = guarani_sql::armar_condicion_in($propuesta, 'sga_propuestas', 'propuesta');
			$datos = $this->get_listado($filtro);
			return $datos;
		} 
		$propuesta = toba::db()->quote($propuesta);
		$datos = $this->get_listado("sga_propuestas.propuesta = $propuesta");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	function get_propuestas_estados()
	{
		$sql = 'SELECT estado as valor,
				descripcion as descr
				FROM  sga_propuestas_estados';

		return guarani_db::consultar($sql);
	}

	function get_propuestas_tipos($filtrar_perfil_de_datos=true)
	{
		$sql = 'SELECT 		propuesta_tipo as valor,
							descripcion as descr
				FROM  		sga_propuestas_tipos
				ORDER BY	descripcion';
		
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**
	 * Retorna el conjunto de tipos de propuesta teniendo en cuenta el perfil de datos del usuario logueado:
	 *  - Si el dominio de su unidad de gestión es "Tipos de Propuesta" sólo se incluyen los tipos asociados a la misma.
	 *  - En cualquier otro caso se incluyen todos los tipos posibles.
	 * 
	 * @return array
	 */
	function get_propuesta_tipos_sin_perfil_datos()
	{
		$filtrar_perfil_de_datos = usuario::tiene_perfil_por_tipo_propuesta() ? true : false;
		return $this->get_propuestas_tipos($filtrar_perfil_de_datos);
	}

	function get_propuesta_tipo_descr($propuesta_tipo)
	{
		$propuesta_tipo = toba::db()->quote($propuesta_tipo);
		$sql = "SELECT 		descripcion
				FROM  		sga_propuestas_tipos
				WHERE		propuesta_tipo = $propuesta_tipo";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['descripcion'];
	}

	function get_propuestas_tipos_reporte($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$sql = "SELECT 		sga_propuestas_tipos.propuesta_tipo,
							sga_propuestas_tipos.descripcion,
							sga_propuestas_tipos.otorga_titulo,
							sga_propuestas_tipos.reporta_araucano,
							sga_propuestas_tipos.permite_matricular,
							sga_propuestas_tipos.permite_inscribir,
							sga_propuestas_tipos.disponible_en_autogestion,
							sga_g3entidades_subtipos.entidad_subtipo,
							sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre
				FROM  		sga_propuestas_tipos,
							sga_g3entidades_subtipos
				WHERE		sga_propuestas_tipos.propuesta_tipo = sga_g3entidades_subtipos.entidad_subtipo
							$where
				ORDER BY	sga_propuestas_tipos.descripcion
						;";
		return guarani_db::consultar($sql);
	}

	function get_propuestas_planes($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}
		
		$sql = "SELECT 		sga_planes.propuesta_tipo,
							sga_propuestas_tipos.descripcion,
							sga_propuestas_tipos.otorga_titulo,
							sga_propuestas_tipos.reporta_araucano,
							sga_propuestas_tipos.permite_matricular,
							sga_propuestas_tipos.permite_inscribir
				FROM  		sga_propuestas_tipos
							$where
				ORDER BY	sga_propuestas_tipos.descripcion
						;";
		return guarani_db::consultar($sql);
	}
	
	function get_planes_para_inscripcion($propuesta, $ver_planes_activos=false, $where='', $params_sistema = array()) 
	{
		$param_insc_propuesta_plan = $params_sistema['inscripcion_propuesta_plan'];
		$param_insc_propuesta_version = $params_sistema['inscripcion_propuesta_version'];

   		$propuesta = toba::db()->quote($propuesta);
   		
		// Sólo se permite la Inscripción manual a Planes Estructurados, ya que en los Personalizados
		// se realiza de forma automática junto con la creación del plan.
		$filtro = "AND sga_planes.tipo_plan = '" . planes::tipo_estructurado . "'";
   		if ($ver_planes_activos) {
   			$filtro .= "AND ((sga_planes.estado IN ('".planes::estado_activo."','".planes::estado_vigente."') AND '$param_insc_propuesta_plan' = '".comunes::todos."') 
						OR 	sga_planes.estado = '$param_insc_propuesta_plan') 
						AND ((sga_planes_versiones.estado IN ('".planes::estado_activo."','".planes::estado_vigente."') AND '$param_insc_propuesta_version' = '".comunes::todos."') 
						OR 	sga_planes_versiones.estado = '$param_insc_propuesta_version') 
					";
   		} else {
   			$filtro .= "AND (sga_planes.estado IN ('". planes::estado_vigente ."')) 
						AND (sga_planes_versiones.estado IN ('". planes::estado_vigente ."'))
					";
   		}

		if ($where) {
			$where = " AND $where ";
		}
		$where .= $filtro;
   		
		$sql = "SELECT 	sga_planes.plan,
						sga_planes.nombre as plan_nombre,
						sga_planes.codigo as plan_codigo, 
						sga_planes.version_actual, 
						sga_planes.fecha_entrada_vigencia as plan_vigencia, 
						sga_planes.entidad as plan_entidad, 
						sga_planes.estado as plan_estado,
						sga_planes_estados.descripcion as plan_estado_desc,
						sga_planes_versiones.plan_version,
						sga_planes_versiones.nombre as version_nombre,
						sga_planes_versiones.version as version, 
						sga_planes_versiones.fecha_entrada_vigencia as version_vigencia, 
						sga_planes_versiones.entidad as version_entidad, 
						sga_planes_versiones.estado as version_estado,
						sga_planes_modalidad.modalidad, 
						sga_modalidad_cursada.descripcion as modalidad_desc,
						sga_planes.nombre || ' - ' || sga_planes_versiones.nombre as plan_version_nombre
  				FROM 	sga_planes,
  						sga_planes_versiones,
  						sga_planes_modalidad,
  						sga_planes_estados,
  						sga_modalidad_cursada  
  				WHERE 	sga_planes.propuesta = $propuesta
						AND sga_planes_versiones.plan = sga_planes.plan
						AND sga_planes.inscripcion_habilitada = '".comunes::si."'
						AND sga_planes_modalidad.plan = sga_planes.plan
						AND sga_planes_modalidad.modalidad = sga_modalidad_cursada.modalidad
						AND sga_planes_estados.estado = sga_planes.estado
						$where 
				ORDER BY 	
						sga_planes.nombre,
						sga_planes_versiones.version,
							sga_modalidad_cursada.descripcion 
						;";	
		return guarani_db::consultar($sql);		
	}

	function get_planes_para_inscripcion_prop_modalidad($propuesta, $modalidad, $params_sistema = array()) 
	{
		$modalidad = toba::db()->quote($modalidad);
		$where = " sga_planes_modalidad.modalidad = $modalidad";
		return $this->get_planes_para_inscripcion($propuesta, false, $where, $params_sistema);
	}

	/**
 	  * Recupera Propuestas con Planes Activos (Activos Vigentes y no Vigentes V-A)
    */
	function get_propuestas_planes_activos($where=null)
	{
		if ($where) {
			$where = "AND $where";
		}
		$sql = "SELECT		DISTINCT ON (vw_perfil_de_datos_propuestas.codigo) 
							vw_perfil_de_datos_propuestas.codigo,
							vw_perfil_de_datos_propuestas.propuesta,
							vw_perfil_de_datos_propuestas.nombre,
							vw_perfil_de_datos_propuestas.nombre_abreviado,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							vw_perfil_de_datos_propuestas.propuesta_tipo,
							sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
							sga_propuestas_estados.descripcion as estado,
							sga_planes.nombre as plan_nombre
				FROM		vw_perfil_de_datos_propuestas,
							sga_propuestas_tipos,
							sga_propuestas_estados,
							sga_planes
				WHERE		vw_perfil_de_datos_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo AND
							vw_perfil_de_datos_propuestas.estado = sga_propuestas_estados.estado AND
							vw_perfil_de_datos_propuestas.propuesta = sga_planes.propuesta AND
							sga_planes.estado IN ('".planes::estado_activo."','".planes::estado_vigente."')
							$where
				ORDER BY	vw_perfil_de_datos_propuestas.codigo,
							vw_perfil_de_datos_propuestas.nombre_abreviado
		";	
		return guarani_db::consultar($sql);		
	}
	
	/**
	 * Obtiene la lista de planes Activos y sus Propuestas asociadas, para aquellos planes que estén en al menos una
	 * versión activa (vigente o no), según el filtro recibido.
	 * Sólo se traerá una vez la combinación Propuesta-Plan, sin considerar si este último está en más de una versión.
	 * Se excluyen los planes personalizados y convenios.
    */
	function get_planes_activos_con_propuestas_en_versiones_activas($where)
	{
		if ($where) {
			$where = 'AND '.$where.' ';
		}
		$sql = "SELECT		sga_propuestas.codigo,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_desc,
							sga_propuestas.nombre_abreviado || ' (' || sga_planes.codigo || ')' AS propuesta_plan,
							sga_propuestas.propuesta_tipo,
							sga_propuestas_tipos.descripcion AS propuesta_tipo_desc,
							sga_planes.plan,
							sga_planes.nombre as plan_nombre,
							sga_planes.codigo as plan_codigo,
							sga_planes_estados.descripcion as plan_estado
  				FROM		sga_propuestas
							JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
							JOIN sga_planes ON sga_planes.propuesta = sga_propuestas.propuesta
							JOIN sga_planes_estados ON sga_planes.estado = sga_planes_estados.estado
  				WHERE		sga_planes.tipo_plan = '".planes::tipo_estructurado."' AND
							sga_planes.estado IN ('".planes::estado_activo_vigente."','".planes::estado_activo_no_vigente."') AND
							sga_propuestas.estado = '" . propuesta::estado_activo . "' AND
							sga_planes.plan IN (SELECT	sga_planes_versiones.plan
												FROM	sga_planes_versiones
												WHERE	sga_planes_versiones.estado IN ('" . planes::estado_activo_vigente . "','" . planes::estado_activo_no_vigente . "'))
							$where
				ORDER BY	sga_propuestas.nombre, sga_propuestas.codigo, sga_planes.codigo
		";
		return guarani_db::consultar($sql);		
	}
	
	function get_listado_tipos($where)
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}
		
		$sql = "SELECT 		sga_propuestas_tipos.propuesta_tipo,
							sga_propuestas_tipos.descripcion as nombre,
							sga_propuestas_tipos.otorga_titulo,
							sga_propuestas_tipos.reporta_araucano,
							sga_propuestas_tipos.permite_matricular,
							sga_propuestas_tipos.permite_inscribir
				FROM  		sga_propuestas_tipos
							$where
				ORDER BY	sga_propuestas_tipos.descripcion
						;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de nombres de propuestas según el filtro recibido
    */
	function get_propuestas_nombres($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		sga_propuestas.propuesta as valor,
							'('  || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as descr,
							sga_propuestas.nombre_abreviado
  				FROM		sga_propuestas
				$where
				ORDER BY	sga_propuestas.nombre
		";	
		return guarani_db::consultar($sql);
	}
	
	// Retorna la descripción de una propuesta dada.
	static function get_propuesta_descr($id=null)
	{
		if (! isset($id) || trim($id) == '') {
			return array();
		}    	
		
	    $id = toba::db()->quote($id);

				
		$sql = "SELECT 	sga_propuestas.propuesta as valor, 
						'('  || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS descr
				FROM	sga_propuestas
				WHERE	sga_propuestas.propuesta = $id 
				";				
			
		$result = guarani_db::consultar($sql);

		if (! empty($result)) {
			return $result[0]['descr'];
		}
	}
	
	/**
	 * Obtiene el nombre de una Propuesta Curricular cuyo id se da como parámetro, retornando la string para el método
	 * descripción de un combo editable.
	 *
	 * @param integer $propuesta id de la propuesta curricular para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_propuesta($propuesta=null)
	{
		if (!isset($propuesta)) {
			return '';
		}
		$propuesta = toba::db()->quote($propuesta);
		
		$result = toba::consulta_php('co_propuestas')->get_propuestas_nombres("sga_propuestas.propuesta = $propuesta");
		return empty($result) ? '' : $result[0]['descr'];
	}
	
	/**
	* Obtiene una lista de las propuestas ofrecidas por unidades responsables academicas en diferentes ubicaciones.
	* Si diferentes responsables academicas ofrecen la misma propuesta en la misma ubicacion, esta se retorna una única vez.
	* @param string 	where	filtro sobre los campos para realizar la consulta.
	*/
	function get_propuestas_ofertas($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		$sql = "SELECT 	DISTINCT ON (sga_propuestas_oferta.propuesta,sga_propuestas_oferta.ubicacion)
						sga_propuestas_oferta.propuesta,
						sga_propuestas_ra.responsable_academica,
						sga_propuestas.nombre,
						sga_propuestas.codigo,
						sga_propuestas.propuesta_tipo,
						sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
						sga_propuestas.estado,
						sga_propuestas_estados.descripcion as propuesta_estado_desc,
						sga_propuestas_oferta.ubicacion,
						sga_ubicaciones.nombre as propuesta_ubicacion_nombre
				FROM	sga_propuestas_oferta,
						sga_propuestas_ra,
						sga_propuestas,
						sga_propuestas_tipos,
						sga_propuestas_estados,
						sga_ubicaciones
				WHERE	sga_propuestas_oferta.propuesta = sga_propuestas_ra.propuesta AND
						sga_propuestas_oferta.propuesta = sga_propuestas.propuesta AND
						sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo AND
						sga_propuestas.estado = sga_propuestas_estados.estado AND
						sga_propuestas_oferta.ubicacion = sga_ubicaciones.ubicacion
						$where
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	* Obtiene una lista de las propuestas con sus planes activos o vigentes, ofrecidas por unidades responsables 
	* academicas en diferentes ubicaciones.
	* Si diferentes responsables academicas ofrecen la misma propuesta-plan en la misma ubicacion, esta se retorna 
	* una única vez.
	* @param string 	where	filtro sobre los campos para realizar la consulta.
	*/
	function get_propuestas_planes_ofertas($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		$sql = "SELECT 	DISTINCT ON (sga_propuestas_oferta.propuesta, sga_planes.plan, sga_propuestas_oferta.ubicacion)
						sga_propuestas_oferta.propuesta,
						sga_propuestas_ra.responsable_academica,
						sga_responsables_academicas.nombre as responsable_academica_nombre,
						sga_propuestas.nombre,
						sga_propuestas.codigo,
						sga_planes.plan,
						sga_planes.codigo as plan_codigo,
						sga_planes.nombre as plan_nombre,
						sga_propuestas.propuesta_tipo,
						sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
						sga_propuestas.estado,
						sga_propuestas_estados.descripcion as propuesta_estado_desc,
						sga_propuestas_oferta.ubicacion,
						sga_ubicaciones.nombre as propuesta_ubicacion_nombre
				FROM	sga_propuestas_oferta,
						sga_propuestas_ra,
						sga_responsables_academicas,
						sga_propuestas,
						sga_planes,
						sga_propuestas_tipos,
						sga_propuestas_estados,
						sga_ubicaciones
				WHERE	sga_propuestas_oferta.propuesta = sga_propuestas_ra.propuesta AND
						sga_responsables_academicas.responsable_academica = sga_propuestas_ra.responsable_academica AND
						sga_propuestas_oferta.propuesta = sga_propuestas.propuesta AND
						sga_propuestas.propuesta = sga_planes.propuesta AND
						sga_planes.estado IN (' " . planes::estado_activo . "', '" . planes::estado_vigente . "') AND
						sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo AND
						sga_propuestas.estado = sga_propuestas_estados.estado AND
						sga_propuestas_oferta.ubicacion = sga_ubicaciones.ubicacion
						$where
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene las diferentes ubicaciones donde se ofrece una dada propuesta.
	 *
	 * @param integer $propuesta id de la propuesta para la cual se consulta la oferta académica.
	 */
	function get_propuesta_ubicaciones($propuesta, $where='', $filtrar_perfil_de_datos=true)
	{
		$propuesta = toba::db()->quote($propuesta);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 	sga_propuestas_oferta.ubicacion as valor,
						sga_ubicaciones.nombre as descr
				FROM	sga_propuestas_oferta,
						sga_ubicaciones
				WHERE	sga_propuestas_oferta.propuesta = $propuesta AND
						sga_propuestas_oferta.ubicacion = sga_ubicaciones.ubicacion
						$where
				;";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}
	
	/**
	 * Obtiene los datos de las diferentes ubicaciones donde se ofrecen las diferentes propuestas.
	 *
	 * @param str $where filtro a aplicar sobre las ubicaciones o propuestas.
	 */
	static function get_ubicaciones_propuestas($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}
		$sql = "SELECT 		sga_propuestas_oferta.ubicacion,
							sga_propuestas_oferta.propuesta,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_ubicaciones.ubicacion_tipo,
							sga_ubicaciones_tipos.nombre as ubicacion_tipo_nombre,
							sga_propuestas.nombre as propuesta_nombre
				FROM		sga_propuestas_oferta
				JOIN		sga_ubicaciones			ON sga_ubicaciones.ubicacion					= sga_propuestas_oferta.ubicacion
				JOIN		sga_ubicaciones_tipos	ON sga_ubicaciones_tipos.ubicacion_tipo			= sga_ubicaciones.ubicacion_tipo
				JOIN		sga_propuestas			ON sga_propuestas.propuesta						= sga_propuestas_oferta.propuesta
				JOIN		sga_propuestas_tipos	ON sga_propuestas_tipos.propuesta_tipo			= sga_propuestas.propuesta_tipo
				$where
				ORDER BY	sga_ubicaciones_tipos.nombre, sga_ubicaciones.nombre
				;";
		return guarani_db::consultar($sql);
	}
	
	/*
	 * Se traen los nombres para utilizar en la carga externa de la tabla
	 * sga_propuestas_regularidad.
	 *	@param integer 	prop	codigo de la propuesta
	 */
	function get_nombre_ubicacion_propuesta($propuesta = null, $ubicacion = null)
	{
		$where = '';
		if(isset($propuesta)) {
			$where .= "sga_propuestas_oferta.propuesta = '" . $propuesta . "' AND ";
		}
		if(isset($ubicacion)) {
			$where .= "sga_propuestas_oferta.ubicacion = '" . $ubicacion . "'";
		}
		return static::get_ubicaciones_propuestas($where);
	}
	
	
	/**
	 *  Retorna la institucion asociada a una propuesta.
	 *	 @param integer 	prop	codigo de la propuesta
	 *	TODO: ver qué corresponde segun la nueva definicion del caso de Uso.
	 *		se modificó momentaneamente para que sigan funcionando las operaciones involucradas
	 */

	function get_prop_institucion($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT	sga_instituciones.institucion, 
						sga_instituciones.nombre as institucion_nombre,
						sga_responsables_academicas.nombre as responsable_academica_nombre
				FROM	sga_propuestas,
						sga_responsables_academicas,
						sga_instituciones 
				WHERE 	sga_propuestas.propuesta = $propuesta
                AND     sga_responsables_academicas.responsable_academica = (select max(sga_propuestas_ra.responsable_academica)
																from 	sga_propuestas_ra 
																where 	sga_propuestas_ra.propuesta = sga_propuestas.propuesta)
				AND		sga_instituciones.institucion = sga_responsables_academicas.institucion
                        ;";
		return guarani_db::consultar($sql);
	}
	
	function get_lista_propuestas()
	{
		$sql = 'SELECT propuesta, nombre_abreviado
				FROM sga_propuestas
				ORDER BY nombre_abreviado';
		return guarani_db::consultar($sql);	
	}
	
	function get_lista_propuestas_activas($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 		sga_propuestas.propuesta,
							sga_propuestas.nombre_abreviado
				FROM		sga_propuestas
				WHERE		sga_propuestas.estado = '" . propuesta::estado_activo . "'
							$where
				ORDER BY	sga_propuestas.nombre_abreviado
				;";
		return guarani_db::consultar($sql);
	}

	function get_certificados($where='')
	{
		if ($where) {
			$where = ' and '.$where.' ';
		}

		$sql = "SELECT 	sga_propuestas.nombre 				as propuesta,
					 	sga_planes.nombre 					as plan,
					 	sga_planes_versiones.version 		as version,
					 	sga_certificados.nombre 			as certificado,
					 	sga_certificados_tipos.descripcion 	as certificado_tipo
				FROM 	sga_propuestas,
					 	sga_planes,
						sga_planes_versiones,
					 	sga_planes_certificados,
					 	sga_certificados,
						sga_certificados_tipos 
				WHERE 	sga_propuestas.propuesta = sga_planes.propuesta
				AND 	sga_planes.plan 					= sga_planes_versiones.plan
				AND 	sga_planes_versiones.plan_version 	= sga_planes_certificados.plan_version
				AND 	sga_planes_certificados.certificado	= sga_certificados.certificado
				AND 	sga_certificados.certificado_tipo 	= sga_certificados_tipos.certificado_tipo
				 		$where
				 		";

		return guarani_db::consultar($sql);	
	}	
	
	function get_datos_propuesta($id)
	{
		$id = toba::db()->quote($id);
		$sql = "SELECT 	sga_propuestas.propuesta,
						sga_propuestas.nombre,
						sga_propuestas.nombre_abreviado,
						sga_propuestas.codigo,
						sga_propuestas.publica,
						sga_propuestas.propuesta_tipo,
						sga_propuestas_tipos.descripcion as propuesta_tipo_descripcion,
						sga_propuestas.documento_alta,
						sga_propuestas.fecha_alta,
						sga_propuestas.documento_baja,
						sga_propuestas.fecha_baja,
						sga_propuestas.estado
				FROM 	sga_propuestas
				JOIN	sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
				WHERE 	sga_propuestas.propuesta = $id
				;";
		return guarani_db::consultar_fila($sql);	
	}

	function get_datos_propuesta_propia($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT	sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
						f_instituciones_propuesta(sga_propuestas.propuesta) as institucion_nombre
				FROM	sga_propuestas,
						sga_propuestas_tipos
				WHERE	sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo AND
						sga_propuestas.propuesta = $propuesta
		";
		return guarani_db::consultar_fila($sql);
	}

	function get_instituciones_propuesta($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT	DISTINCT sga_responsables_academicas.institucion
				FROM	sga_propuestas_ra,
						sga_responsables_academicas
				WHERE	sga_propuestas_ra.responsable_academica = sga_responsables_academicas.responsable_academica AND
						sga_propuestas_ra.propuesta = $propuesta
		";
		return guarani_db::consultar($sql);
	}

	function get_propuesta_tipo_de_propuesta($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT 	sga_propuestas.propuesta_tipo as propuesta_tipo
				FROM 	sga_propuestas
				WHERE 	sga_propuestas.propuesta = $propuesta
						;";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['propuesta_tipo'];
	}

	/*
	 * Retorna los datos relacionados con un tipo de propuesta de la propuesta pasada por parametro
	*/
	function get_datos_tipo_de_propuesta_una_propuesta($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT 	sga_propuestas_tipos.propuesta_tipo,
                        sga_propuestas_tipos.descripcion
				FROM	sga_propuestas 
						JOIN sga_propuestas_tipos ON sga_propuestas_tipos.propuesta_tipo = sga_propuestas.propuesta_tipo
				WHERE	sga_propuestas.propuesta = $propuesta
		";
		$datos_tipo_propuesta = guarani_db::consultar_fila($sql);
		return $datos_tipo_propuesta;
	}

	function get_datos_propuesta_aspira($propuesta_aspira)
	{
		if (is_array($propuesta_aspira)) {
			$filtro = guarani_sql::armar_condicion_in($propuesta_aspira, 'sga_propuestas_aspira', 'propuesta_aspira');
			$condicion = " AND $filtro";
		} else {
			$condicion = " AND	sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira ";
		}
		$sql = "SELECT	sga_propuestas_aspira.propuesta_aspira,
						sga_propuestas.nombre as propuesta_nombre,
						sga_situacion_aspirante.resultado_asp,
						sga_propuestas_aspira.persona,
						sga_planes.nombre as plan_nombre,
						sga_planes_versiones.nombre as version_nombre
		        FROM	sga_propuestas_aspira,
						sga_propuestas,
						sga_situacion_aspirante,
						sga_planes_versiones,
						sga_planes
				WHERE	sga_propuestas.propuesta 				= sga_propuestas_aspira.propuesta
				AND		sga_situacion_aspirante.situacion_asp 	= sga_propuestas_aspira.situacion_asp
				AND		sga_propuestas_aspira.plan_version		= sga_planes_versiones.plan_version
				AND		sga_planes_versiones.plan				= sga_planes.plan
				$condicion ";
		$datos = guarani_db::consultar($sql);
		return $datos;
	}
	
	 /**
	   Valida si un código se puede usar o no
	*/
	function validar_codigo_propuesta($codigo, $propuesta=null)
	{
		$codigo = toba::db()->quote($codigo);
		if (isset($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
		}
		$sql = "SELECT 	count(*) as cant 	
				FROM 	sga_propuestas
				WHERE 	sga_propuestas.codigo = $codigo
				";
		if (isset($propuesta)) {
			$sql .= " AND sga_propuestas.propuesta <> $propuesta ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ( $res['cant'] == 0);
	}
	
	/**
	 * Obtiene el listado de Grupos de Propuestas
	 */
	function get_propuestas_grupos($filtro='')
	{
		if ($filtro) {
			$where = ' WHERE '.$filtro.' ';
		} else {
			$where = '';
		}
		
		$sql = "SELECT	sga_propuestas_grupos.grupo_propuesta,
						sga_propuestas_grupos.nombre,
						sga_propuestas_grupos.observaciones
				FROM	sga_propuestas_grupos
				$where
				ORDER BY 2
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 *  Retorna las Responsables Académicas asociadas a una propuesta.
	 *	 @param integer 	propuesta	código de la propuesta
	 */
	function get_ras_propuesta($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT 	sga_propuestas_ra.responsable_academica
				FROM 	sga_propuestas_ra 
				WHERE 	sga_propuestas_ra.propuesta = $propuesta
                        ;";
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'responsable_academica');
	}

	/**
	 *  Retorna las Áreas Temáticas asociadas a una propuesta.
	 *	 @param integer 	propuesta	código de la propuesta
	 */
	function get_areas_tematicas_propuesta($propuesta)
	{
		$sql = "SELECT
				sga_rd_areas.area,
				sga_rd_areas.nombre,
				sga_rd_areas.descripcion,
				sga_rd_areas.codigo_araucano
				FROM sga_propuestas_areas_tem
				JOIN sga_rd_areas ON (sga_propuestas_areas_tem.area_tematica = sga_rd_areas.area)
				WHERE sga_propuestas_areas_tem.propuesta = $propuesta
				ORDER BY sga_rd_areas.nombre";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los ids y nombres de todas las propuestas, y sus planes asociados (solo con condicion de activos, ya sea 
	 * vigentes o no), que contienen al elemento dado como parámetro, según el filtro dado. Si el parámetro comisión no es
	 * nulo, se retorna además un campo requerido que será true o false dependiendo de si la propuesta en cuestión está o 
	 * no asociada la comisión dada. En caso contrario el campo requerido será siempre true (utilizado para un 
	 * guarani_ei_formulario_ml).
	 * 
	 * @param integer	$comision	id de la comisión a la que se asocian o no las propuestas.
	 * @param integer	$elemento	id del elemento para el cual se quieren recuperar las propuestas y planes asociados.
	 * @param str		$where		filtro sobre las propuestas y planes a retornar.
	 */
	function get_propuestas_por_elemento($elemento=null, $where='')
	{
		$filtro = '';
		if (!is_null($elemento)) {
			$elemento = toba::db()->quote($elemento);
			$filtro = " AND sga_elementos_revision.elemento = $elemento ";
		}
		if ($where) {
			$where = ' AND '.$where;
		}

		$sql = "SELECT		sga_propuestas.propuesta,
							sga_propuestas.propuesta_tipo,
				            sga_propuestas.codigo,
							sga_propuestas.nombre_abreviado,
				            sga_planes.plan,
				            sga_planes.nombre as plan_nombre,
				            sga_planes.codigo as plan_codigo,
				            COALESCE('(' || sga_planes.codigo || ') ', '') || sga_planes.nombre as plan_descr
				FROM	    sga_planes,
							sga_propuestas
				WHERE		sga_propuestas.propuesta = sga_planes.propuesta
				AND			sga_planes.estado IN ('".planes::estado_vigente."','".planes::estado_activo."')
				AND			sga_planes.plan IN (SELECT	sga_planes_versiones.plan
				    							FROM 	sga_planes_versiones,
				            							sga_elementos_plan,
				            							sga_elementos_revision
				            					WHERE   sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
				            					AND		sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
				            					AND		sga_planes_versiones.estado IN ('".planes::estado_vigente."','".planes::estado_activo."')
				            					AND		sga_elementos_plan.requiere_cursada = '".comunes::si."'
				            							$filtro
				            					)
							$where
				ORDER BY	sga_propuestas.nombre_abreviado
				";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los grupos que contienen alguna propuesta que incluya al elemento dado como parámetro.
	 * Se tienen en cuenta sólo planes activos/vigentes y de tipo "Estructurado".
	 * Se consideran sólo versiones activas/vigentes donde el elemento requiera cursada.
	 *
	 * @param int $elemento
	 * @return array
	 */
	function get_propuestas_grupos_por_elemento($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT		sga_propuestas_grupos.grupo_propuesta,
							sga_propuestas_grupos.nombre
				FROM		sga_propuestas_grupos
							JOIN sga_propuestas_x_grupo ON sga_propuestas_grupos.grupo_propuesta = sga_propuestas_x_grupo.grupo_propuesta
							JOIN sga_planes ON sga_propuestas_x_grupo.propuesta = sga_planes.propuesta
							JOIN sga_planes_versiones ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_elementos_plan ON sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
							JOIN sga_elementos_revision ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
				WHERE		sga_planes.estado IN ('".planes::estado_vigente."','".planes::estado_activo."') AND
							sga_planes.tipo_plan = '".planes::tipo_estructurado."' AND
							sga_planes_versiones.estado IN ('".planes::estado_vigente."','".planes::estado_activo."') AND
							sga_elementos_plan.requiere_cursada = '".comunes::si."' AND
							sga_elementos_revision.elemento = $elemento
				ORDER BY	sga_propuestas_grupos.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los ids y nombres de todas las propuestas, y sus planes asociados (solo con condicion de activos, ya sea 
	 * vigentes o no), que contienen al elemento dado como parámetro y que requieran examen, según el filtro dado. 
	 * 
	 * @param integer	$elemento	id del elemento para el cual se quieren recuperar las propuestas y planes asociados.
	 * @param str		$where		filtro sobre las propuestas y planes a retornar.
	 */
	function get_propuestas_por_elemento_para_examen($elemento=null, $where='')
	{
		$filtro = '';
		if (!is_null($elemento)) {
			$elemento = toba::db()->quote($elemento);
			$filtro = " AND sga_elementos_revision.elemento = $elemento ";
		}
		if ($where) {
			$where = ' AND '.$where;
		}

		$sql = "SELECT		sga_propuestas.propuesta,
				            sga_propuestas.codigo,
							sga_propuestas.nombre_abreviado,
				            sga_planes.plan,
				            sga_planes.nombre as plan_nombre,
				            sga_planes.codigo as plan_codigo,
				            COALESCE('(' || sga_planes.codigo || ') ', '') || sga_planes.nombre as plan_descr
				FROM	    sga_planes,
							sga_propuestas
				WHERE		sga_propuestas.propuesta = sga_planes.propuesta
				AND			sga_planes.estado IN ('".planes::estado_vigente."','".planes::estado_activo."')
				AND			sga_planes.plan IN (SELECT	sga_planes_versiones.plan
				    							FROM 	sga_planes_versiones,
				            							sga_elementos_plan,
				            							sga_elementos_revision
				            					WHERE   sga_planes_versiones.plan_version = sga_elementos_plan.plan_version
				            					AND		sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
				            					AND		sga_planes_versiones.estado IN ('".planes::estado_vigente."','".planes::estado_activo."')
				            					AND		sga_elementos_plan.requiere_examen = '".comunes::si."'
				            							$filtro
				            					)
							$where
				ORDER BY	sga_propuestas.nombre_abreviado
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene todas las Actividades y sus subtipos, asociadas a una propuesta, según el filtro dado.
	 *
	 * @param integer	$id_propuesta		id de la propuesta para la cual se recuperan todas las actividades asociadas.
	 * @param str		$where				filtro sobre las actividades a recuperar.
	 */
	function get_actividades_propuesta($id_propuesta, $where)
	{
		$id_propuesta = toba::db()->quote($id_propuesta);
		if ($where) {
			$where = ' and '.$where.' ';
		}
		$sql = "SELECT 	sga_elementos.elemento,
						sga_elementos.nombre as actividad_nombre, 
						sga_elementos.codigo,
						COALESCE(sga_elementos.codigo, 'Sin código') || ' - ' || sga_elementos.nombre as actividad_descr,
						sga_elementos.entidad,
						sga_g3entidades_subtipos.entidad_subtipo as		entidad_subtipo,
						sga_g3entidades_subtipos.nombre as				subtipo_nombre 
				  FROM 	sga_elementos,
				  		sga_g3entidades_subtipos,
				  		sga_elementos_revision,
				  		sga_elementos_plan,
				  		sga_planes_versiones,
				  		sga_planes,
				  		sga_propuestas
				 WHERE 	sga_elementos.entidad_subtipo 			= sga_g3entidades_subtipos.entidad_subtipo
				 AND	sga_g3entidades_subtipos.entidad_tipo 	= ".elemento::tipo_actividad."
				 AND 	sga_elementos_revision.elemento			= sga_elementos.elemento 
				 AND	sga_elementos_plan.elemento_revision	= sga_elementos_revision.elemento_revision
				 AND	sga_elementos_plan.plan_version			= sga_planes_versiones.plan_version
				 AND	sga_planes_versiones.plan				= sga_planes.plan
				 AND	sga_planes.propuesta					= sga_propuestas.propuesta
				 AND	sga_propuestas.propuesta				= $id_propuesta
				 " . $where . "
				 ORDER BY sga_elementos.nombre
				 ;"; 
		return guarani_db::consultar($sql);	
	}
	
	/**
	 * Obtiene los ids y nombres de todas las propuestas, y sus planes asociados (solo con condicion de activos, ya sea 
	 * vigentes o no), que contienen al elemento dado como parámetro, según el filtro dado. Si el parámetro mesa no es
	 * nulo, se retorna además un campo requerido que será true o false dependiendo de si la propuesta en cuestión está o 
	 * no asociada la mesa dada. En caso contrario el campo requerido será siempre true (utilizado para un 
	 * guarani_ei_formulario_ml).
	 * 
	 * @param integer	$elemento	id del elemento para el cual se quieren recuperar las propuestas y planes asociados.
	 * @param integer	$mesa		id de la mesa a la que se asocian o no las propuestas.
	 * @param str		$where		filtro sobre las propuestas y planes a retornar.
	 */
	function get_propuestas_asignadas_a_mesa_elemento($elemento, $mesa=null, $where='')
	{
		$elemento = toba::db()->quote($elemento);
		if ($where) {
			$where .= ' AND ' . $where;
		}
		$select = '';
		$from = '';
		if (!empty($mesa)) {
			$mesa = toba::db()->quote($mesa);
			$select .= ", $mesa as mesa	
						, CASE
							WHEN sga_mesas_examen_propuestas.mesa_examen IS NULL 
								THEN false
                    			ELSE true
							END AS requerido
				        ";
			$from = " LEFT JOIN sga_mesas_examen_propuestas ON (sga_mesas_examen_propuestas.propuesta = sga_propuestas.propuesta AND sga_mesas_examen_propuestas.mesa_examen = $mesa) ";
		} else {
			//Si se buscan TODAS las propuestas, sin que estén asociadas a la mesa, entonces se setea para todas ellas
			//el campo requerido como false.
			$select .= ', 0 AS requerido';
		}

		$sql = "SELECT		sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado as propuesta_nombre_ab,
				            sga_propuestas.codigo as propuesta_codigo,
				            sga_planes.plan,
				            sga_planes.nombre as plan_nombre,
				            sga_planes.codigo as plan_codigo
				            $select
				FROM	    sga_planes,
							sga_propuestas
				            $from
				WHERE		sga_propuestas.propuesta = sga_planes.propuesta AND
				            sga_planes.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND   
				            sga_planes.plan IN (SELECT	sga_planes_versiones.plan
				    							FROM 	sga_planes_versiones,       	
				            							sga_elementos_plan,
				            							sga_elementos_revision   
				            					WHERE   sga_planes_versiones.plan_version = sga_elementos_plan.plan_version AND
				            							sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
				            							sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND
				            							sga_elementos_revision.elemento = $elemento 
												)
							$where					
				ORDER BY	sga_propuestas.nombre_abreviado
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 *  Retorna la Responsables Académicas por defecto de una propuesta.
	 *	 @param integer 	propuesta	código de la propuesta
	 */
	function get_ra_por_defecto($propuesta)
	{
		$ras = $this->get_ras_propuesta($propuesta);
		
		return $ras == array() ? false : $ras[0];
	}

	/**
	 *  Obtiene el listado de las propuestas y sus responsables academicas asociadas.
	 */
	function get_listado_prop_ras($where='')
	{
		if ($where) {
			$where = ' and '.$where.' ';
		}
		
		$sql = '	SELECT  sga_propuestas.propuesta,
							sga_propuestas.nombre_abreviado,
							sga_responsables_academicas.responsable_academica, 
							sga_responsables_academicas.nombre as responsable_academica_nombre
					FROM	sga_propuestas,
							sga_propuestas_ra,
							sga_responsables_academicas
					WHERE	sga_propuestas.propuesta				= sga_propuestas_ra.propuesta
					AND		sga_propuestas_ra.responsable_academica	= sga_responsables_academicas.responsable_academica '
					. $where . '
					ORDER BY 2';
	
		return guarani_db::consultar($sql);
	}

	/**
	 * Devuelve una cadena con el listado de las responsables academicas asociadas a las propuestas en forma de lista para 
	 * utilizar en la impresión o forms.
	 */
	function get_listado_prop_ras_string($where='')
	{
		$props = $this->get_listado_prop_ras($where);
		$ra = "";
		foreach ($props as $prop) {
			$ra .= $prop['responsable_academica_nombre'] . " / ";
		}
		return (string) substr($ra, 0, -3);
	}
	
	/**
	 * Obtiene los ids y descripciones de todas las Propuestas Curriculares, cuyos nombres contengan al string 
	 * ingresado como filtro (mediante un combo editable).
	 *
	 * @param str $filtro porción del nombre de la/las Propuesta/s ingresada por el usuario.
	 */
	function get_listado_propuestas_descr($filtro='', $responsable_academica = null)
	{
		$condicion = 'true';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion .= ' AND ' . $filtro_condicion->get_sql('f_limpiar_acentos(sga_propuestas.nombre::varchar)', $filtro);
		}
		if (isset($responsable_academica)) {
			$condicion .= " AND sga_propuestas.propuesta IN (SELECT sga_propuestas_ra.propuesta FROM sga_propuestas_ra WHERE responsable_academica = $responsable_academica)";
		}
		$condicion .= " AND sga_propuestas.estado = '".comunes::estado_activo."'";
		return $this->get_listado_nombres_propuestas($condicion);
	}

	function get_propuestas_propias($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_propuestas.propuesta,
						sga_propuestas.nombre,
						sga_propuestas_tipos.descripcion as propuesta_tipo_desc,
						f_instituciones_propuesta(sga_propuestas.propuesta) as institucion_nombre,
						0 as propuesta_ext
				FROM	sga_propuestas,
						sga_propuestas_tipos
				WHERE	sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
						$where
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 *  Retorna las Propuestas asociadas a una Persona dada.
	 *	 @param integer 	persona	código de la persona
	 */
	function get_propuestas_alumno($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT 	sga_propuestas.propuesta as valor,
						sga_propuestas.nombre as descr
				FROM 	sga_alumnos,
						sga_propuestas
				WHERE 	sga_alumnos.persona = $persona AND
						sga_alumnos.propuesta = sga_propuestas.propuesta
                        ;";
		return guarani_db::consultar($sql);
	}

	/**
	 *  Retorna las Propuestas en un estado dado asociadas a una Persona dada.
	 */	
	function get_propuestas_aspira_alumno($persona, $estado)
	{
		$persona = toba::db()->quote($persona);
		$estado = toba::db()->quote($estado);
		
		$sql = "SELECT 	sga_propuestas.nombre 			as propuesta_nombre,						
						sga_planes.nombre 				as plan_nombre,
						sga_planes_versiones.nombre 	as version_nombre,
						sga_ubicaciones.nombre			as ubicacion_nombre,
						sga_modalidad_cursada.nombre	as modalidad_nombre,
						sga_propuestas_aspira.propuesta_aspira
				FROM 	sga_propuestas_aspira,
						sga_situacion_aspirante,
						sga_propuestas,
						sga_planes_versiones,
						sga_planes,
						sga_ubicaciones,
						sga_modalidad_cursada
				WHERE 	sga_propuestas_aspira.persona 			= $persona
				AND		sga_propuestas_aspira.situacion_asp 	= sga_situacion_aspirante.situacion_asp
				AND		sga_situacion_aspirante.resultado_asp	= $estado
				AND		sga_propuestas_aspira.propuesta			= sga_propuestas.propuesta
				AND		sga_propuestas_aspira.plan_version		= sga_planes_versiones.plan_version
				AND		sga_planes_versiones.plan				= sga_planes.plan
				AND		sga_propuestas_aspira.ubicacion			= sga_ubicaciones.ubicacion
				AND		sga_propuestas_aspira.modalidad			= sga_modalidad_cursada.modalidad
				ORDER BY propuesta_nombre
                        ;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 *  Retorna las Propuestas Aspira en estado pendiente de acuerdo a un Requisito y un filtro dado.
	 */
	function get_propuestas_aspira_pendientes_x_req($requisito, $where='')
	{
		$requisito = toba::db()->quote($requisito);
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	DISTINCT sga_propuestas.propuesta,
						sga_propuestas.nombre			as propuesta_nombre,
						sga_planes.nombre				as plan_nombre,
						sga_modalidad_cursada.nombre	as modalidad_nombre,
						sga_planes_versiones.version
		
				FROM 	sga_propuestas_aspira,
						sga_modalidad_cursada,
						sga_planes_versiones,
						sga_planes,
						sga_propuestas,
						sga_situacion_aspirante,
						sga_requisitos_aspirante
						
				WHERE 	sga_propuestas_aspira.propuesta				= sga_propuestas.propuesta
				AND		sga_propuestas_aspira.plan_version			= sga_planes_versiones.plan_version
				AND		sga_propuestas_aspira.modalidad				= sga_modalidad_cursada.modalidad
				AND		sga_planes_versiones.plan					= sga_planes.plan
				AND		sga_propuestas_aspira.situacion_asp			= sga_situacion_aspirante.situacion_asp
				AND		sga_situacion_aspirante.resultado_asp		= '".propuesta::inscripcion_pendiente."'
				AND		sga_requisitos_aspirante.propuesta_aspira	= sga_propuestas_aspira.propuesta_aspira
				AND		sga_requisitos_aspirante.requisito			= $requisito
						$where
				
				ORDER BY propuesta_nombre
                        ;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_propuestas_aspira($where)
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	sga_propuestas_aspira.propuesta_aspira
		
				FROM 	sga_propuestas_aspira,
						sga_alumnos
						
				WHERE 	sga_propuestas_aspira.persona = sga_alumnos.persona				
						$where
                        ;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
 	  * Valida si un nombre de Grupo de Propuestas se puede usar o no.
      *	@param string  nombre	Nombre del Grupo de Propuestas que se intenta validar.
	  * @param integer grupo	Id del Grupo de Propuestas si se encuentra en edición.
    */
	function validar_nombre_grupo_propuestas($nombre, $grupo=null)
	{		
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = "SELECT 	count(sga_propuestas_grupos.grupo_propuesta) as cant 	
                FROM 	sga_propuestas_grupos
				WHERE 	".guarani_sql::armar_condicion_compara_cadenas('sga_propuestas_grupos.nombre', $nombre);
		
		if (!is_null($grupo)) {
			$grupo = toba::db()->quote($grupo);
			$sql .= " AND sga_propuestas_grupos.grupo_propuesta <> $grupo ";
		}
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	//-----------------------------------------------------------------------
	//-- Proceso de aplanado.
	//-----------------------------------------------------------------------

	function get_propuestas_por_institucion($entidad)
	{
		$entidad = toba::db()->quote($entidad);

		$sql = "SELECT	sga_propuestas.propuesta
				FROM	sga_propuestas
				WHERE	sga_propuestas.estado = '".propuesta::estado_activo."' AND
						sga_propuestas.propuesta IN (
						SELECT	sga_propuestas_ra.propuesta
						FROM	sga_propuestas_ra,
								sga_propuestas
						WHERE	sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND
								sga_propuestas_ra.responsable_academica IN (
								SELECT	sga_responsables_academicas.responsable_academica
								FROM	sga_responsables_academicas
								WHERE	sga_responsables_academicas.institucion = (
										SELECT	sga_instituciones.institucion
										FROM	sga_instituciones
										WHERE	sga_instituciones.entidad = $entidad)
		))
		";
		return guarani_db::consultar($sql);
	}

	function get_propuestas_por_ra($entidades)
	{
		$where = guarani_sql::armar_condicion_in($entidades,'sga_responsables_academicas','entidad','entidad');

		$sql = "SELECT	sga_propuestas.propuesta
				FROM	sga_propuestas
				WHERE	sga_propuestas.estado = '".propuesta::estado_activo."' AND
						sga_propuestas.propuesta IN (
						SELECT	sga_propuestas_ra.propuesta
						FROM	sga_propuestas_ra,
								sga_propuestas
						WHERE	sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND
								sga_propuestas_ra.responsable_academica IN (
								SELECT	sga_responsables_academicas.responsable_academica
								FROM	sga_responsables_academicas
								WHERE	$where
		))
		";
		return guarani_db::consultar($sql);
	}

	function get_propuestas_por_subtipos_entidades($subtipos, $entidades)
	{
		if (!empty($subtipos)) {
			$where = guarani_sql::armar_condicion_in($subtipos,'sga_g3entidades','entidad_subtipo','entidad_subtipo');
		} else {
			$where = guarani_sql::armar_condicion_in($entidades,'sga_propuestas','entidad','entidad');
		}

		$sql = "SELECT	sga_propuestas.propuesta
				FROM	sga_propuestas,
						sga_g3entidades
				WHERE	sga_propuestas.entidad = sga_g3entidades.entidad AND
						sga_propuestas.estado = '".propuesta::estado_activo."' AND
						$where
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de propuestas de tipo cursos activas
	 * Planes activos, que administran curos y con inscripciÃ³n habilitada
	 * 
	 * @return array recordset
	 */
	function get_propuestas_administran_cursos()
	{
		$sql = "SELECT 
					sga_propuestas.propuesta AS valor,
					sga_propuestas.nombre AS descr,
					sga_propuestas.nombre || ' (' || f_responsables_academicas_propuesta(sga_propuestas.propuesta) || ')' AS descr_con_ra
				FROM sga_propuestas
				WHERE sga_propuestas.estado = '" . propuesta::estado_activo . "'
				  AND sga_propuestas.propuesta_tipo = '".propuesta::tipo_curso."'
				  AND sga_propuestas.propuesta IN (
						SELECT sga_planes.propuesta
						  FROM sga_planes
						 WHERE sga_planes.administra_cursos = '" . comunes::si . "'
						   AND sga_planes.inscripcion_habilitada = '" . comunes::si . "'
						   AND sga_planes.estado IN ('".planes::estado_vigente."', '".planes::estado_activo."')
					)
                ORDER BY descr
                ";
		
		return guarani_db::consultar($sql);
	}
	
	function get_plan_y_plan_version_propuesta_cursos($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);
		
		$sql = "SELECT 
					sga_planes.plan,
					sga_planes_versiones.plan_version
				FROM 
					sga_planes,
					sga_planes_versiones
				WHERE
					sga_planes.propuesta = $propuesta
					AND sga_planes_versiones.plan = sga_planes.plan
					AND sga_planes.estado IN ('A','V')";
		
		return guarani_db::consultar_fila($sql);
	}
	
	function get_requisitos_no_cumplidos($propuesta)
	{
		$sql = "
				SELECT 
					r.nombre as requisito_descr,
					r.requisito as requisito
				FROM 
					sga_propuestas_aspira_rechazo as par
				INNER JOIN
					sga_requisitos as r
				ON
					r.requisito = par.requisito
				WHERE
					par.propuesta_aspira = $propuesta
				";
		return guarani_db::consultar($sql);
	}	
    
    function get_tipo_propuesta_propuesta($propuesta)
    {
        $where = 'sga_propuestas.propuesta = '.$propuesta;
        $datos = current($this->get_listado($where));
        return $datos['propuesta_tipo'];
    }
}