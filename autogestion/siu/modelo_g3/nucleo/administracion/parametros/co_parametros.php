<?php
/**
 *
 *
 * @package		Administración
 * @subpackage	Parámetros
 * 
 */
class co_parametros
{
	/**
	 * Obtiene una lista de parámetros del sistema según el filtro recibido
	 */
	function get_listado($where = '', $orden = 'descripcion')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT 	
					par_parametros_sistema.parametro,
					par_parametros_sistema.descripcion as nombre_parametro,
					par_parametros_sistema.referencia,
					par_parametros_sistema.tipo_de_parametro,
					par_parametros_sistema.tipo_de_dato,
					par_parametros_sistema.nivel,
					par_parametros_sistema.valor_desde,
					par_parametros_sistema.valor_hasta,
					par_parametros_sistema.valor_default as valor,
					par_tipo_parametro.nombre as nombre_tipo_de_parametro
				FROM 	
					par_parametros_sistema,
					par_tipo_parametro
				WHERE 	
					par_tipo_parametro.tipo_de_parametro = par_parametros_sistema.tipo_de_parametro
					$where
				ORDER BY 
					par_parametros_sistema.$orden
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de valores de parámetros del sistema según el parámetro seleccionado
	 */
	function get_listado_valores($filtro)
	{
		if ($filtro) {
			$filtro = ' ' . $filtro . ' ';
		}

		$parametro = toba::memoria()->get_dato('parametro');
		if ($parametro) {
			$parametro = toba::db()->quote(strtoupper($parametro));
		}

		$sql = "SELECT 	par_parametros_sistema_valores.parametro,
						par_parametros_sistema_valores.valor,
						par_parametros_sistema_valores.descripcion
				FROM 	par_parametros_sistema_valores
				WHERE 	upper(par_parametros_sistema_valores.parametro) = $parametro
						$filtro
				ORDER BY par_parametros_sistema_valores.valor
				;";

		return guarani_db::consultar($sql);
	}

	static function get_valor($valor = null)
	{
		if (!isset($valor) || trim($valor) == '') {
			return array();
		}
		$valor = toba::db()->quote($valor);
		$parametro = toba::memoria()->get_dato('parametro');
		if ($parametro) {
			$parametro = toba::db()->quote(strtoupper($parametro));
		}

		$sql = "SELECT 	par_parametros_sistema_valores.valor,
						par_parametros_sistema_valores.descripcion
				FROM 	par_parametros_sistema_valores
				WHERE 	upper(par_parametros_sistema_valores.parametro) = $parametro
				AND		par_parametros_sistema_valores.valor = $valor
				ORDER BY par_parametros_sistema_valores.valor
				;";

		$result = guarani_db::consultar($sql);

		if (!empty($result)) {
			return $result[0]['descripcion'];
		}
	}

	/**
	 * Obtiene los datos de un parámetro del sistema
	 */
	function get_datos_parametro($parametro, $dato = null)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));
		$where = " upper(par_parametros_sistema.parametro) = $parametro ";
		$datos = current($this->get_listado($where));
		return isset($dato) ? $datos[$dato] : $datos;
	}

	/**
	 * Obtiene el nombre de un parámetro del sistema
	 */
	function get_nombre_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'nombre_parametro');
	}

	/**
	 * Obtiene el tipo de un parámetro
	 */
	function get_tipo_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'tipo_de_parametro');
	}

	/**
	 * Obtiene el valor por defecto de un parámetro del sistema
	 */
	function get_valor_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'valor');
	}

	/**
	 * Obtiene el valor desde de un parámetro siempre que su tipo de parámetro sea R
	 */
	function get_valor_desde_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'valor_desde');
	}

	/**
	 * Obtiene el valor hasta de un parámetro siempre que su tipo de parámetro sea R
	 */
	function get_valor_hasta_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'valor_hasta');
	}

	/**
	 * Obtiene el nivel de un parámetro del sistema
	 */
	function get_nivel_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'nivel');
	}

	/**
	 * Obtiene el tipo de dato de un parámetro del sistema
	 */
	function get_tipo_dato_parametro($parametro)
	{
		return $this->get_datos_parametro($parametro, 'tipo_de_dato');
	}

	/**
	 * Obtiene los valores permitidos para un parámetro siempre que su tipo de parámetro sea E
	 */
	function get_enumerado_parametro($parametro)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));

		$sql = "SELECT 	
					par_parametros_sistema_valores.valor,
					par_parametros_sistema_valores.descripcion
				FROM 	
					par_parametros_sistema_valores
				WHERE	
					upper(par_parametros_sistema_valores.parametro) = $parametro
				ORDER BY
					par_parametros_sistema_valores.descripcion";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el valor particular de un parámetro dado
	 */
	function get_valor_param($parametro)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));

		$sql = "	SELECT 	par_parametros_sistema.valor_default as valor
					FROM 	par_parametros_sistema
					WHERE	upper(par_parametros_sistema.parametro) = $parametro
							;";
		$resp = guarani_db::consultar_fila($sql);
		return isset($resp['valor']) ? $resp['valor'] : false;
	}

	/**
	 * Obtiene el valor particular para una Responsable Académica de un parámetro dado
	 */
	function get_valor_param_ra($parametro, $resp_acad)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));
		$resp_acad = toba::db()->quote($resp_acad);

		$sql = "SELECT 	par_param_sist_valores.valor
				FROM 	par_param_sist_valores,
						par_param_sist_valores_entidad,
						sga_responsables_academicas
				WHERE	par_param_sist_valores.param_valor = par_param_sist_valores_entidad.param_valor AND
						par_param_sist_valores_entidad.entidad = sga_responsables_academicas.entidad AND
						upper(par_param_sist_valores.parametro) = $parametro AND
						sga_responsables_academicas.responsable_academica = $resp_acad
		";
		$resp = guarani_db::consultar_fila($sql);
		return isset($resp['valor']) ? $resp['valor'] : false;
	}

	/**
	 * Obtiene el valor particular para un tipo de propuesta de un parámetro dado
	 */
	function get_valor_param_propuesta_tipo($parametro, $propuesta_tipo)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));
		$propuesta_tipo = toba::db()->quote($propuesta_tipo);

		$sql = "SELECT 	par_param_sist_valores.valor
				FROM 	par_param_sist_valores,
						par_param_sist_valores_subtipo
				WHERE	par_param_sist_valores.param_valor = par_param_sist_valores_subtipo.param_valor AND
						upper(par_param_sist_valores.parametro) = $parametro AND
						par_param_sist_valores_subtipo.entidad_subtipo = $propuesta_tipo
		";
		$resp = guarani_db::consultar_fila($sql);
		return isset($resp['valor']) ? $resp['valor'] : false;
	}

	/**
	 * Obtiene el valor particular para una propuesta de un parámetro dado
	 */
	function get_valor_param_propuesta($parametro, $propuesta)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));
		$propuesta = toba::db()->quote($propuesta);

		$sql = "SELECT 	par_param_sist_valores.valor
				FROM 	par_param_sist_valores,
						par_param_sist_valores_entidad,
						sga_propuestas
				WHERE	par_param_sist_valores.param_valor = par_param_sist_valores_entidad.param_valor AND
						par_param_sist_valores_entidad.entidad = sga_propuestas.entidad AND
						upper(par_param_sist_valores.parametro) = $parametro AND
						sga_propuestas.propuesta = $propuesta
		";
		$resp = guarani_db::consultar_fila($sql);
		return isset($resp['valor']) ? $resp['valor'] : false;
	}

	/**
	 * Obtiene el valor particular para un plan de un parámetro dado
	 */
	function get_valor_param_plan($parametro, $plan)
	{
		$parametro = toba::db()->quote(strtoupper($parametro));
		$plan = toba::db()->quote($plan);

		$sql = "SELECT 	par_param_sist_valores.valor
				FROM 	par_param_sist_valores,
						par_param_sist_valores_entidad,
						sga_planes
				WHERE	par_param_sist_valores.param_valor = par_param_sist_valores_entidad.param_valor AND
						par_param_sist_valores_entidad.entidad = sga_planes.entidad AND
						upper(par_param_sist_valores.parametro) = $parametro AND
						sga_planes.plan = $plan
		";
		$resp = guarani_db::consultar_fila($sql);
		return isset($resp['valor']) ? $resp['valor'] : false;
	}

	/**
	 * Obtiene los niveles de aplicación habilitados para un nivel de parámetro en particular.
	 */
	function get_niveles_aplicacion($nivel)
	{
		$nivel = toba::db()->quote($nivel);
		$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
		$in_tipos = guarani_sql::armar_condicion_in($tipos_perfil_datos, 'par_niveles', 'entidad_tipo', 'entidad_tipo');
		$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
		$filtro_subtipos = empty($subtipos_perfil_datos) ? " par_niveles.tipo = '" . parametro::tipo_entidad . "'" : 'true';
		$sql = "SELECT		par_niveles.tipo || ',' || par_niveles.entidad_tipo as valor,
							par_niveles.tipo,
            				par_niveles.entidad_tipo
				FROM		par_niveles
				WHERE		par_niveles.nivel <= $nivel AND
							par_niveles.entidad_tipo <> " . g3entidad::tipo_institucion . " AND
							$in_tipos AND
							$filtro_subtipos
				ORDER BY	par_niveles.nivel
		";
		$niveles = guarani_db::consultar($sql);
		foreach ($niveles as $id => $nivel) {
			$etiquetas = guarani_etiquetas::get_tabla(g3entidad::get_tabla_tipo($nivel['entidad_tipo']));
			switch ($nivel['tipo']) {
				case parametro::nivel_tipo_entidad:
					$niveles[$id]['descr'] = $etiquetas->get('Algunos');
					break;
				case parametro::nivel_tipo_subtipo:
					$niveles[$id]['descr'] = 'Algunos Tipos de ' . $etiquetas->get_plural();
					break;
			}
		}
		return $niveles;
	}

	function get_param_sist_valores_entidad($parametro, $tipo, $entidad_tipo)
	{
		$parametro = toba::db()->quote($parametro);
		$tipo = toba::db()->quote($tipo);
		$entidad_tipo = toba::db()->quote($entidad_tipo);

		$sql = "SELECT 
					par_param_sist_valores_entidad.param_valor,
					par_param_sist_valores_entidad.entidad
				FROM 
					par_param_sist_valores_entidad,
				    par_param_sist_valores
				WHERE
					par_param_sist_valores.param_valor = par_param_sist_valores_entidad.param_valor
				    AND par_param_sist_valores.parametro = $parametro
				    AND par_param_sist_valores.tipo = $tipo
				    AND par_param_sist_valores.entidad_tipo = $entidad_tipo";

		return guarani_db::consultar($sql);
	}

	function get_param_sist_valores_subtipo($parametro, $tipo, $entidad_tipo)
	{
		$parametro = toba::db()->quote($parametro);
		$tipo = toba::db()->quote($tipo);
		$entidad_tipo = toba::db()->quote($entidad_tipo);

		$sql = "SELECT 
					par_param_sist_valores_subtipo.param_valor,
					par_param_sist_valores_subtipo.entidad_subtipo
				FROM 
					par_param_sist_valores_subtipo,
				    par_param_sist_valores
				WHERE
					par_param_sist_valores.param_valor = par_param_sist_valores_subtipo.param_valor
				    AND par_param_sist_valores.parametro = $parametro
				    AND par_param_sist_valores.tipo = $tipo
				    AND par_param_sist_valores.entidad_tipo = $entidad_tipo";

		return guarani_db::consultar($sql);
	}

	function get_desc_enumerado_valor($parametro, $tipo_de_parametro, $valor)
	{
		if ($tipo_de_parametro != parametro::tipo_param_enumerado) {
			return array('valor_default_desc' => $valor);
		} else {
			$parametro = toba::db()->quote($parametro);
			$valor = toba::db()->quote($valor);

			$sql = "SELECT 
						par_parametros_sistema_valores.descripcion as valor_default_desc
					FROM 
						par_parametros_sistema_valores
					WHERE
						par_parametros_sistema_valores.parametro = $parametro
						AND par_parametros_sistema_valores.valor = $valor
					";

			$datos = guarani_db::consultar_fila($sql);
			return $datos;
		}
	}

	function get_nombre_entidad($entidad)
	{
		$entidad = toba::db()->quote($entidad);

		$sql = "SELECT 
				    'Propuesta: ' || sga_propuestas.nombre AS entidad_nombre
				FROM 
				    sga_propuestas
				WHERE 
				    sga_propuestas.entidad = $entidad
				    
				UNION
				
				SELECT 
				    'Resp. Acad.: ' || sga_responsables_academicas.nombre AS entidad_nombre
				FROM 
				    sga_responsables_academicas
				WHERE
				    sga_responsables_academicas.entidad = $entidad

				UNION
				
				SELECT
				    'Plan: ' || sga_planes.nombre AS entidad_nombre
				FROM 
				    sga_planes
				WHERE 
				    sga_planes.entidad = $entidad
				";

		return guarani_db::consultar($sql);
	}

	function get_nombre_subtipo($entidad_subtipo)
	{
		$entidad_subtipo = toba::db()->quote($entidad_subtipo);

		$sql = "SELECT 
				    'Tipo de propuesta: ' || sga_g3entidades_subtipos.nombre AS ent_subt_descr
				FROM 
				    sga_g3entidades_subtipos
				WHERE 
				    sga_g3entidades_subtipos.entidad_subtipo = $entidad_subtipo
				";

		return guarani_db::consultar($sql);
	}

	function get_version_app_base()
	{
		$sql = "SELECT		trim(version_app) as version_actual 
				FROM		app_versiones_base
				ORDER BY	id_conversion DESC
				LIMIT		1
		";
		$datos = guarani_db::consultar_fila($sql, null, false);
		$version = isset($datos['version_actual']) ? $datos['version_actual'] : 0;
		return $version;
	}

	function get_estructura_parametro($parametro)
	{
		$parametro = toba::db()->quote($parametro);

		$sql = "SELECT 
					par_parametros_sistema.parametro,
					par_parametros_sistema.descripcion,
					par_parametros_sistema.tipo_de_parametro,
					par_parametros_sistema.tipo_de_dato,
					par_parametros_sistema.nivel,
					par_parametros_sistema.valor_desde,
					par_parametros_sistema.valor_hasta,
					par_parametros_sistema.valor_default,
					par_parametros_sistema.tabla_referenciada
				FROM 
					par_parametros_sistema
				WHERE
					par_parametros_sistema.parametro = $parametro
				";

		return guarani_db::consultar_fila($sql);
	}

	//--------------------------------------------------------------------------
	//---- Manejo de etiquetas de parámetros
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene todos los parámetros que intervienen en una operación
	 * 
	 * @param string $codigo_operacion
	 * @return array aplanado
	 */
	function get_parametros_operacion($codigo_operacion)
	{
		$codigo_operacion = toba::db()->quote($codigo_operacion);

		$sql = "SELECT	parametro
				FROM	par_parametros_etiquetas
				WHERE	etiqueta = $codigo_operacion	
						AND es_operacion
				";

		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'parametro');
	}

	/**
	 * Obtiene los datos de definición de los parámetros involucrados en una operación.
	 * 
	 * @param string $codigo_operacion
	 * @return array
	 */
	function get_datos_parametros_operacion($codigo_operacion)
	{
		$codigo_operacion = toba::db()->quote($codigo_operacion);

		$sql = "SELECT 
					par_parametros_sistema.parametro,
					par_parametros_sistema.descripcion,
					par_parametros_sistema.tipo_de_parametro,
					par_parametros_sistema.tipo_de_dato,
					par_parametros_sistema.nivel
				FROM 
					par_parametros_sistema,
					par_parametros_etiquetas
				WHERE
					par_parametros_etiquetas.parametro = par_parametros_sistema.parametro
					AND par_parametros_etiquetas.es_operacion
					AND par_parametros_etiquetas.etiqueta = $codigo_operacion
				ORDER BY
					nivel,
					parametro
				";

		return guarani_db::consultar_cache($sql);
	}

	/**
	 * Determina si para un parámetro en una operación no se debe mostrar la 
	 * resolución automática de conflicto (en caso de haberlo).
	 * 
	 * @param string $parametro
	 * @param string $codigo_operacion
	 * @return boolean
	 */
	function no_usar_resolucion_automatica_conflicto($parametro, $codigo_operacion)
	{
		$parametro = toba::db()->quote($parametro);
		$codigo_operacion = toba::db()->quote($codigo_operacion);

		$sql = "SELECT 
					par_parametros_etiquetas.no_usar_res_automatica_conflicto
				FROM 
					par_parametros_etiquetas
				WHERE
					par_parametros_etiquetas.parametro = $parametro
					AND par_parametros_etiquetas.etiqueta = $codigo_operacion
				";

		$datos = guarani_db::consultar_fila_cache($sql);
		return $datos['no_usar_res_automatica_conflicto'];
	}
	
	/**
	 * Obtiene el listado de operaciones del sistema que utilizan parámetros
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_operaciones_con_parametros($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where . ' ';
		}
		
		$schema_toba = toba::instancia()->get_db()->get_schema();
		
		$sql = "SELECT 	DISTINCT par_parametros_etiquetas.etiqueta AS operacion_codigo,
						$schema_toba.apex_item.nombre AS operacion_nombre
				FROM 	par_parametros_etiquetas,
						$schema_toba.apex_item
				WHERE	par_parametros_etiquetas.es_operacion = true
						AND $schema_toba.apex_item.parametro_a = par_parametros_etiquetas.etiqueta
					    $where
				ORDER BY operacion_nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de módulos del sistema que utilizan parámetros
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_modulos_con_parametros($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where . ' ';
		}
		
		$sql = "SELECT 	DISTINCT par_parametros_etiquetas.etiqueta AS modulo
				FROM 	par_parametros_etiquetas
				WHERE	par_parametros_etiquetas.es_operacion = false
					    $where
				ORDER BY modulo
				";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todas las operaciones donde se consume un parámetro
	 * 
	 * @param string $parametro
	 * @return array
	 */
	function get_operaciones_de_parametro($parametro)
	{
		$parametro = toba::db()->quote($parametro);
		
		$schema_toba = toba::instancia()->get_db()->get_schema();
		
		$sql = "SELECT 	par_parametros_etiquetas.etiqueta || ' - ' || $schema_toba.apex_item.nombre AS operacion
				FROM 	par_parametros_etiquetas,
						$schema_toba.apex_item
				WHERE	par_parametros_etiquetas.parametro = $parametro 
						AND par_parametros_etiquetas.es_operacion = true
						AND $schema_toba.apex_item.parametro_a = par_parametros_etiquetas.etiqueta
				ORDER BY operacion
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene todos los módulos donde se consume un parámetro
	 * 
	 * @param string $parametro
	 * @return array
	 */
	function get_modulos_de_parametro($parametro)
	{
		$parametro = toba::db()->quote($parametro);
		
		$sql = "SELECT 	DISTINCT par_parametros_etiquetas.etiqueta AS modulo
				FROM 	par_parametros_etiquetas
				WHERE	par_parametros_etiquetas.parametro = $parametro
						AND par_parametros_etiquetas.es_operacion = false					    
				ORDER BY modulo
				";
		
		return guarani_db::consultar($sql);
	}
	
}
?>