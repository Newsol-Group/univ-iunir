<?php
/**
 *
 *
 * @package ArquitecturaBase
 * @subpackage G3Entidades
 */
class co_g3entidades
{
	function get_listado($where = '')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$sql = "SELECT		sga_g3entidades_subtipos.nombre,
							sga_g3entidades_subtipos.entidad_subtipo,
							sga_g3entidades_subtipos.entidad_tipo,
							sga_g3entidades_subtipos.orden_jerarquico,
							sga_g3entidades_subtipos.php_clase_gui,
							sga_g3entidades_subtipos.php_clase_negocio,
							sga_g3entidades_subtipos.php_clase_edicion,
							sga_g3entidades_subtipos.icono,
							sga_g3entidades_subtipos.es_elemento,
							sga_g3entidades_subtipos.modificable,
							sga_g3entidades_subtipos.revisionable,
							sga_g3entidades_subtipos.para_condiciones,
	  						sga_g3entidades_subtipos.para_requisitos,
							sga_g3entidades_subtipos.tabla,
							sga_g3entidades.entidad
				FROM 		sga_g3entidades_subtipos,
							sga_g3entidades
				WHERE		sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo
							$where
				ORDER BY	sga_g3entidades_subtipos.nombre
				";
		
		return guarani_db::consultar($sql);
	}

	function get_listado_tipo($entidad_tipo = '', $where = '', $return_sql=false)
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$tabla_entidad_tipo = guarani::catalogo_g3entidades()->get_tabla_tipo($entidad_tipo);
		
		$sql = "SELECT	$tabla_entidad_tipo.nombre,
						$tabla_entidad_tipo.nombre as entidad_nombre,
						sga_g3entidades.entidad,
						sga_g3entidades.entidad_subtipo,
						sga_g3entidades_subtipos.nombre as subtipo_nombre,
						sga_g3entidades_subtipos.entidad_tipo,
						sga_g3entidades_tipos.nombre as entidad_tipo_nombre,
						sga_g3entidades_subtipos.icono,						
						sga_g3entidades_subtipos.orden_jerarquico,
						sga_g3entidades_subtipos.php_clase_gui,
						sga_g3entidades_subtipos.php_clase_negocio,
						sga_g3entidades_subtipos.php_clase_edicion,
						sga_g3entidades_subtipos.icono,
						sga_g3entidades_subtipos.es_elemento,
						sga_g3entidades_subtipos.modificable,
						sga_g3entidades_subtipos.revisionable,
						sga_g3entidades_subtipos.para_condiciones,
						sga_g3entidades_subtipos.para_requisitos,
						sga_g3entidades_subtipos.tabla
				FROM 	$tabla_entidad_tipo,
						sga_g3entidades_tipos,
						sga_g3entidades_subtipos LEFT JOIN sga_g3entidades ON sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo
				WHERE 	$tabla_entidad_tipo.entidad = sga_g3entidades.entidad AND
						sga_g3entidades_tipos.entidad_tipo = sga_g3entidades_subtipos.entidad_tipo
						$where
						";
		
		if ($return_sql) return $sql;
		return guarani_db::consultar($sql);
	}
	
	function get_listado_tipo_detallado($entidad_tipo, $where = '')
	{
		$clase_consulta_tipo = guarani::catalogo_g3entidades()->get_clase_consulta_tipo($entidad_tipo);
		$metodo_consulta_tipo = guarani::catalogo_g3entidades()->get_metodo_consulta_tipo($entidad_tipo);
		$parametro = ''; // En algunos casos corresponde al orden de la consulta SQL, en otro indica su filtrado por perfil de datos.
		if ($clase_consulta_tipo == 'co_planes' && $metodo_consulta_tipo == 'get_listado') {
			$parametro = 'propuesta_nombre, plan_nombre';
		} elseif ($clase_consulta_tipo == 'co_propuestas' && $metodo_consulta_tipo == 'get_listado') {
			$parametro = 'sga_propuestas.nombre';
		} elseif ($clase_consulta_tipo == 'co_responsables_academicas' && $metodo_consulta_tipo == 'get_listado') {
			$parametro = true;
		}
		return guarani::co($clase_consulta_tipo)->$metodo_consulta_tipo($where, $parametro);
	}
	
	function get_listado_subtipo($entidad_subtipo = '', $where = '', $return_sql=false)
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$tabla_entidad_subtipo = guarani::catalogo_g3entidades()->get_tabla_subtipo($entidad_subtipo);
		$campo_clave = guarani::catalogo_g3entidades()->get_campo_clave_subtipo($entidad_subtipo);
		
		/**
		 * La columna entidad_nombre se trae duplicada con un alias ya que es necesario para distinguir dos columnas
		 * 'nombre' con carga externa en el mismo datos tabla (sga_requisitos_entidades en Requisitos por Acción).
		 */ 
		$sql = "SELECT	$tabla_entidad_subtipo.$campo_clave as id,
						$tabla_entidad_subtipo.$campo_clave as $campo_clave,
						$tabla_entidad_subtipo.nombre,
						$tabla_entidad_subtipo.nombre as entidad_nombre,
						sga_g3entidades.entidad,
						sga_g3entidades.entidad_subtipo,
						sga_g3entidades_subtipos.nombre as subtipo_nombre,
						sga_g3entidades_subtipos.entidad_tipo,
						sga_g3entidades_tipos.nombre as entidad_tipo_nombre,
						sga_g3entidades_subtipos.icono,						
						sga_g3entidades_subtipos.orden_jerarquico,
						sga_g3entidades_subtipos.php_clase_gui,
						sga_g3entidades_subtipos.php_clase_negocio,
						sga_g3entidades_subtipos.php_clase_edicion,
						sga_g3entidades_subtipos.icono,
						sga_g3entidades_subtipos.es_elemento,
						sga_g3entidades_subtipos.modificable,
						sga_g3entidades_subtipos.revisionable,
						sga_g3entidades_subtipos.para_condiciones,
						sga_g3entidades_subtipos.para_requisitos,
						sga_g3entidades_subtipos.tabla
				FROM 	$tabla_entidad_subtipo,
						sga_g3entidades_tipos,
						sga_g3entidades_subtipos LEFT JOIN sga_g3entidades ON sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo
				WHERE 	$tabla_entidad_subtipo.entidad = sga_g3entidades.entidad AND
						sga_g3entidades_tipos.entidad_tipo = sga_g3entidades_subtipos.entidad_tipo
						$where
						";
		
		if ($return_sql) return $sql;
		return guarani_db::consultar($sql);
	}
	
	function get_listado_subtipos_nombres($where = '')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		$sql = "SELECT 		sga_g3entidades_subtipos.entidad_subtipo as valor,
							sga_g3entidades_subtipos.nombre as descr
  				FROM	 	sga_g3entidades_subtipos
  							$where
  				ORDER BY	sga_g3entidades_subtipos.nombre
						;";
		
		return guarani_db::consultar($sql);
	}
	
	function get_listado_subtipos_nombres_permitidos()
	{
		$where = '';
		$para_requisitos = toba::memoria()->get_dato('para_requisitos');
		if ($para_requisitos && $para_requisitos == comunes::si) {
			$where = " sga_g3entidades_subtipos.para_requisitos = '$para_requisitos'";
		}
		return $this->get_listado_subtipos_nombres($where);
	}

	/**
	 * Obtiene el conjunto de subtipos (id y nombre) correspondientes a un tipo de entidad dado.
	 */
	function get_tipo_subtipos($tipo, $where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		toba::db()->quote($tipo);
		
		$sql = "SELECT 		sga_g3entidades_subtipos.entidad_subtipo,
							sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre
  				FROM	 	sga_g3entidades_subtipos,
  							sga_g3entidades_tipos
  				WHERE		sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo AND
  							sga_g3entidades_tipos.entidad_tipo = $tipo
							$where
  				ORDER BY	sga_g3entidades_subtipos.nombre
				;";
		
		return guarani_db::consultar($sql);
	}

	function get_niveles_aplicacion()
	{
		$tipos_operacion = aplanar_matriz($this->get_tipos_niveles_operacion('800SIUADM003'), 'entidad_tipo');
		$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
		$tipos = array_intersect($tipos_operacion, $tipos_perfil_datos);
		$in_tipos = guarani_sql::armar_condicion_in($tipos, 'sga_g3entidades_tipos', 'entidad_tipo', 'entidad_tipo');

		$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
		$in_subtipos = guarani_sql::armar_condicion_in($subtipos_perfil_datos, 'sga_g3entidades_subtipos', 'entidad_subtipo', 'entidad_subtipo');

		$sql = "SELECT 	'Toda la Institución' as descr,
						'".g3entidad::nivel_aplicacion_tipo_total."' || ',' || sga_g3entidades_tipos.entidad_tipo as valor,
						'".g3entidad::nivel_aplicacion_tipo_total."' as nivel_tipo,
						sga_g3entidades_tipos.entidad_tipo as entidad_tipo,
						sga_g3entidades_tipos.nombre as nombre,
						1 as orden
				FROM	sga_g3entidades_tipos
				WHERE	$in_tipos AND
						sga_g3entidades_tipos.entidad_tipo = ".g3entidad::tipo_institucion."
						
	    		UNION

				SELECT 	'Algunos Tipos de Entidades' as descr,
						'".g3entidad::nivel_aplicacion_tipo_subtipo."' || ',' || sga_g3entidades_tipos.entidad_tipo as valor,
						'".g3entidad::nivel_aplicacion_tipo_subtipo."' as nivel_tipo,
						sga_g3entidades_tipos.entidad_tipo,
						sga_g3entidades_tipos.nombre,
						2
				FROM 	sga_g3entidades_tipos
				WHERE 	$in_tipos AND
						sga_g3entidades_tipos.entidad_tipo != ".g3entidad::tipo_institucion." AND
						1 < (SELECT count(*) FROM sga_g3entidades_subtipos WHERE $in_subtipos AND sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo)

	    		UNION

				SELECT	'Algunas Entidades' as descr,
						'".g3entidad::nivel_aplicacion_tipo_entidad."' || ',' || sga_g3entidades_tipos.entidad_tipo as valor,
						'".g3entidad::nivel_aplicacion_tipo_entidad."' as nivel_tipo,
						sga_g3entidades_tipos.entidad_tipo,
						sga_g3entidades_tipos.nombre,
						3
				FROM	sga_g3entidades_tipos
				WHERE	$in_tipos AND
						sga_g3entidades_tipos.entidad_tipo != ".g3entidad::tipo_institucion."

				ORDER BY orden, valor
	    ";
		$niveles = guarani_db::consultar($sql);

		foreach ($niveles as $id => $nivel) {
			if ($nivel['nivel_tipo'] != g3entidad::nivel_aplicacion_tipo_total) {
				$etiquetas = guarani_etiquetas::get_tabla(g3entidad::get_tabla_tipo($nivel['entidad_tipo']));
			}
			switch ($nivel['nivel_tipo']) {
				case g3entidad::nivel_aplicacion_tipo_entidad:
					$niveles[$id]['descr'] = $etiquetas->get('Algunos');
					break;
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					$niveles[$id]['descr'] = 'Algunos Tipos de '.$etiquetas->get_plural();
			}
		}

		return $niveles;
	}

	function get_tipos_niveles_operacion($operacion)
	{
		$operacion = toba::db()->quote($operacion);
		$sql = "SELECT		sga_g3entidades_niveles.entidad_tipo
				FROM		sga_g3entidades_niveles
				WHERE		sga_g3entidades_niveles.operacion = $operacion
				ORDER BY	sga_g3entidades_niveles.orden
		";
		return guarani_db::consultar($sql);
	}

	/**
        Obtiene los datos de una g3entidad. 
    */	
	function get_datos_entidad($entidad)
	{
		$entidad_subtipo = $this->get_subtipo_entidad($entidad);
		$entidad = toba::db()->quote($entidad);
		$filtro = " sga_g3entidades.entidad = $entidad ";
		$res = $this->get_listado_subtipo($entidad_subtipo,$filtro);	
		if (!empty($res)) {
			return $res[0];
		}
	}
	
	/**
        Obtiene los datos de un elemento corresponidentes a la entidad y al plan version dados
    */	
	function get_datos_elemento_plan($entidad, $plan_version)
	{
		$entidad = toba::db()->quote($entidad);
				
		$sql = "SELECT  
						vw_elementos.codigo as codigo,
						COALESCE (sga_elementos_plan.nombre, vw_elementos.nombre) as nombre,
						COALESCE (sga_elementos_plan.nombre_abreviado, vw_elementos.nombre_abreviado) as nombre_abreviado,
						vw_elementos.entidad_subtipo,
						vw_elementos.entidad_tipo,
						vw_elementos.entidad
				FROM	vw_elementos,
						sga_elementos_revision
				LEFT JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision  = sga_elementos_revision.elemento_revision
								             AND sga_elementos_plan.plan_version = $plan_version
				WHERE sga_elementos_revision.elemento  = vw_elementos.elemento
				AND   vw_elementos.entidad = $entidad ";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/** 
 		Obtiene el nombre de una g3entidad.  
 	*/   
 	function get_nombre_entidad($entidad) 
 	{ 
	 	$res = $this->get_datos_entidad($entidad); 
	 	return (isset($res['nombre']) ? $res['nombre'] : '');
 	} 
 		
	/**
        Obtiene los datos de una g3entidad o una lista de g3entidades. 
    */
	function get_datos_entidades($entidades)
    {
    	if (! is_array($entidades)) {
    		return $this->get_datos_entidad($entidades);
		}
		
		/**
 	  	* En el caso de la carga masiva se arma otro arreglo, porque la columna entidad puede ser nula
 	  	* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de entidad no nulos.
 	  	*/ 
		$entidades_no_nulas = array_diff($entidades, array(null));
    	$filtro = guarani_sql::armar_condicion_in($entidades_no_nulas, 'sga_g3entidades', 'entidad');
    	
    	//Obtengo los distintos subtipos de g3entidades
    	$subtipos = $this->get_subtipo_entidades($entidades_no_nulas);
    	    	
		$sql = '';
		foreach($subtipos as $subtipo) {
			if ($sql != '') {
				$sql .= '
						 UNION 
						';
			}
			$sql .= $this->get_listado_subtipo($subtipo,$filtro,true);
		}
		$rs = empty($sql) ? array() : guarani_db::consultar($sql);
		return $rs;
    }
	
    /**
        Obtiene los datos de los estados posibles, según el filtro pasado como parámetro. 
    */	
	function get_estados($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		$sql = "SELECT 		sga_estados.estado,
							sga_estados.nombre as estado_nombre,
							sga_estados.descripcion
  				FROM		sga_estados
  							$where
  				ORDER BY	sga_estados.nombre
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
      *	Retorna los datos de un estado según el id dado como parámetro en formato RecordSet, 
      *	en caso de ser un array retorna los datos de el/los estados segun la condicion 
      * del array (para la carga masiva).
      * @param int/array $estado id del estado o array de ids de estados para los cuales se obtienen los datos.
    */
    function get_estado_datos($estado)
    {
    	if (is_array($estado)) {
			/**
 	  		* En el caso de la carga masiva se arma otro arreglo, porque la columna estado puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de estado no nulos.
 	  		*/
			$estados = array_diff($estado, array(null));
			$filtro = guarani_sql::armar_condicion_in($estados, 'sga_estados', 'estado');
			$datos = $this->get_estados($filtro);
			return $datos;
		} elseif (!is_null($estado)) {
			$estado = toba::db()->quote($estado);
			$datos = $this->get_estados("sga_estados.estado = $estado");
			return $datos[0];
		}
    }
    
	/**
        Obtiene el subtipo al que pertenece la g3entidad recibida
    */
	function get_subtipo_entidad($entidad)
	{
		$entidad = toba::db()->quote($entidad);
		$sql = "SELECT  sga_g3entidades.entidad_subtipo
				FROM 	sga_g3entidades
				WHERE 	sga_g3entidades.entidad = $entidad
						";
		$res = guarani_db::consultar_fila($sql);
		return $res['entidad_subtipo'];
	}
	
	/**
        Obtiene el subtipo al que pertenece la g3entidad recibida
    */
	function get_tipo_entidad($entidad)
	{
		$entidad = toba::db()->quote($entidad);
		$sql = "SELECT  sga_g3entidades_subtipos.entidad_tipo
				FROM 	sga_g3entidades,
						sga_g3entidades_subtipos
				WHERE 	sga_g3entidades.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo AND
						sga_g3entidades.entidad = $entidad
				;";
		$res = guarani_db::consultar_fila($sql);
		return $res['entidad_tipo'];
	}
	
	/**
        Obtiene una lista de los distintos subtipos a los que pertenecen las g3entidades recibidas
    */
	function get_subtipo_entidades($entidades)
	{
		if (! is_array($entidades)) {
    		return $this->get_subtipo_entidad($entidades);
		} 
    	$where = 'WHERE '.guarani_sql::armar_condicion_in($entidades,'sga_g3entidades', 'entidad');
		
		$sql = "SELECT  DISTINCT 
						sga_g3entidades.entidad_subtipo
				FROM 	sga_g3entidades
				$where
						";
		
		$res = guarani_db::consultar($sql);
		return aplanar_matriz($res,'entidad_subtipo');
	}
	
	function get_datos_tipo($entidad_tipo=null)
	{
		$where = '';
		if ($entidad_tipo) {
			$entidad_tipo = toba::db()->quote($entidad_tipo);
			$where = " WHERE sga_g3entidades_tipos.entidad_tipo = $entidad_tipo ";
		}
		
		$sql = "SELECT 	sga_g3entidades_tipos.entidad_tipo, 
						sga_g3entidades_tipos.nombre as nombre,
						sga_g3entidades_tipos.nombre as tipo_nombre, 
						sga_g3entidades_tipos.descripcion,
				       	sga_g3entidades_tipos.es_elemento 
  				FROM 	sga_g3entidades_tipos
  				$where
				;";
		
		if (!$entidad_tipo) {
			//Si el filtro no es nulo, entonces posiblemente se estèn consultando datos de varias filas, debe
			//realizarse un consultar común y no un consultar fila.
			return guarani_db::consultar($sql);
		} else {
			return guarani_db::consultar_fila($sql);
		}
	}

	function get_datos_subtipo($entidad_subtipo=null, $filtro=null)
	{
		/**
		* La columna subtipo_nombre se trae duplicada con un alias ya que es necesario para distinguir dos columnas
		* 'nombre' con carga externa en el mismo datos tabla (sga_requisitos_grupos en Requisitos por Acción).
		*/ 
		$where = '';
		if (!is_null($entidad_subtipo)) {
			$entidad_subtipo = toba::db()->quote($entidad_subtipo);
			$where = " AND sga_g3entidades_subtipos.entidad_subtipo = $entidad_subtipo ";
		}
		if (!is_null($filtro)) {
			$where .= " AND $filtro ";
		} 
		
		$sql = "SELECT 	sga_g3entidades_subtipos.entidad_subtipo, 
						sga_g3entidades_subtipos.nombre,
						sga_g3entidades_subtipos.nombre as subtipo_nombre, 
						sga_g3entidades_subtipos.entidad_tipo,
						sga_g3entidades_tipos.nombre as entidad_tipo_nombre, 
						sga_g3entidades_subtipos.orden_jerarquico, 
						sga_g3entidades_subtipos.php_clase_gui, 
				       	sga_g3entidades_subtipos.php_clase_negocio, 
				       	sga_g3entidades_subtipos.php_clase_edicion, 
				       	sga_g3entidades_subtipos.icono, 
				       	sga_g3entidades_subtipos.es_elemento, 
				       	sga_g3entidades_subtipos.modificable, 
				       	sga_g3entidades_subtipos.revisionable, 
				       	sga_g3entidades_subtipos.para_condiciones,
				       	sga_g3entidades_subtipos.para_requisitos,
				       	sga_g3entidades_subtipos.tabla 
  				FROM 	sga_g3entidades_subtipos,
  						sga_g3entidades_tipos
  				WHERE	sga_g3entidades_subtipos.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
  				$where
				;";
		
		if (!is_null($filtro)) {
			//Si el filtro no es nulo, entonces posiblemente se estèn consultando datos de varias filas, debe
			//realizarse un consultar común y no un consultar fila.
			return guarani_db::consultar($sql);
		} else {
			return guarani_db::consultar_fila($sql);
		}
	}

	/**
 	  *  Retorna los datos de un Subtipo de entidad en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los datos de los Subtipos de entidades según la condición del array (para la carga masiva).
      *	 @param integer/array	subtipo	id o arreglo de ids del o de los Subtipos para los cuales se consultan los datos.
    */
	function get_datos_subtipos_entidades($subtipo)
	{
		if (is_array($subtipo)) {
			/**
 	  		* En el caso de la carga masiva se arma otro arreglo, porque la columna entidad_subtipo puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de entidad_subtipo no nulos.
 	  		*/
			$subtipos = array_diff($subtipo, array(null));
			$filtro = guarani_sql::armar_condicion_in($subtipos, 'sga_g3entidades_subtipos', 'entidad_subtipo');
			$datos = $this->get_datos_subtipo(null, $filtro);
			return $datos;
		} elseif (!is_null($subtipo)) {
			return $this->get_datos_subtipo($subtipo);
		}
	}
	
	function get_subtipo_estados($subtipo)
	{
		$subtipo = toba::db()->quote($subtipo);
		$sql = "SELECT  sga_g3entidades_subtipos_estados.estado as valor,
						sga_estados.nombre as descr,
						sga_g3entidades_subtipos_estados.estado_default as default
				FROM 	sga_g3entidades_subtipos_estados,
						sga_estados
				WHERE 	sga_g3entidades_subtipos_estados.entidad_subtipo = $subtipo
				AND		sga_estados.estado = sga_g3entidades_subtipos_estados.estado
						";
		$datos = guarani_db::consultar($sql);
		return $datos;
	}

	/**
	 * Obtiene el conjunto de estados validos para una entidad dada, a partir del subtipo asociado a la misma.
	 *
	 * @param integer	$entidad	id de la entidad para la cual se desea averiguar los estados posibles para su subtipo asociado.
	 * @return array	matriz de recordsets que contienen los ids y descripciones de los estados posibles para la entidad dada.
	 */
	function get_entidad_estados($entidad)
	{
		$entidad = toba::db()->quote($entidad);
		$sql = "SELECT  	sga_g3entidades_subtipos_estados.estado as valor,
							sga_estados.nombre as descr,
							sga_g3entidades_subtipos_estados.estado_default as default
				FROM 		sga_g3entidades,
							sga_g3entidades_subtipos,
							sga_g3entidades_subtipos_estados,
							sga_estados
				WHERE 		sga_g3entidades.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo AND
							sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades_subtipos_estados.entidad_subtipo AND
							sga_g3entidades_subtipos_estados.estado = sga_estados.estado AND
							sga_g3entidades.entidad = $entidad
				ORDER BY	sga_estados.nombre
				";
		return guarani_db::consultar($sql);
	}
	
	function get_subtipo_estado_default($subtipo)
	{
		$estados = $this->get_subtipo_estados($subtipo);
		if (guarani_arreglos::count($estados) == 1) {
			return $estados[0]['valor'];
		}
		
		foreach ($estados as $estado) {
			if ($estado['default'] == 'S') {
				return $estado['valor'];
			}
		}
		//Sino, devuelvo el primero que encuentro	
		if (guarani_arreglos::count($estados) > 0) {	
			return $estados[0]['valor'];
		}	
	}

	function get_datos_subtipo_estado($subtipo,$estado)
	{
		$subtipo = toba::db()->quote($subtipo);
		$estado = toba::db()->quote($estado);
		$sql = "SELECT  sga_g3entidades_subtipos_estados.relacion,
						sga_g3entidades_subtipos_estados.estado,
						sga_g3entidades_subtipos_estados.regla,
						sga_g3entidades_subtipos_estados.estado_final
				FROM 	sga_g3entidades_subtipos_estados
				WHERE 	sga_g3entidades_subtipos_estados.entidad_subtipo = $subtipo
				AND		sga_g3entidades_subtipos_estados.estado = $estado
						";
		return guarani_db::consultar_fila($sql);
	}
	
	function get_datos_entidad_estado($entidad, $estado)
	{
		$entidad	= toba::db()->quote($entidad);
		$estado		= toba::db()->quote($estado);
		$sql = "SELECT  sga_g3entidades_subtipos_estados.relacion,
						sga_g3entidades_subtipos_estados.entidad_subtipo,
						sga_g3entidades_subtipos_estados.estado,
						COALESCE(sga_g3entidades_estados.regla,  sga_g3entidades_subtipos_estados.regla) as regla,
						sga_g3entidades_subtipos_estados.estado_final,
						sga_g3entidades_subtipos_estados.estado_default
		
				FROM	sga_g3entidades
						JOIN		sga_g3entidades_subtipos			ON (sga_g3entidades_subtipos.entidad_subtipo			= sga_g3entidades.entidad_subtipo)
						JOIN		sga_g3entidades_subtipos_estados 	ON (sga_g3entidades_subtipos_estados.entidad_subtipo	= sga_g3entidades_subtipos.entidad_subtipo)
						LEFT JOIN	sga_g3entidades_estados				ON (sga_g3entidades_estados.entidad						= sga_g3entidades.entidad)
				WHERE	sga_g3entidades.entidad = $entidad
				AND		sga_g3entidades_subtipos_estados.estado = $estado
				";
		return guarani_db::consultar_fila($sql);
	
	}

	/**
        Obtiene el valor y nombre de una Entidad a partir del id dado para el ef_popup de Entidades.
    */
    static function get_entidad($id = null)
    {
    	if (!isset($id) || trim($id) == '') {
			return array();
		}
		
		$where = '';
	    $subtipo = toba::consulta_php('co_g3entidades')->get_subtipo_entidad($id);
	    if ($subtipo) {
	    	$tabla = guarani::catalogo_g3entidades()->get_tabla_subtipo($subtipo);
	    	$where .= " AND $tabla.entidad = sga_g3entidades.entidad";
	    }
		
		$sql = "SELECT	DISTINCT sga_g3entidades.entidad as valor,
					  	$tabla.nombre as descr
				FROM	sga_g3entidades,
						$tabla
				WHERE	sga_g3entidades.entidad = $id
						$where
				";
			    
		$result = guarani_db::consultar($sql);
		
		if (!empty($result)) {
			return $result[0]['descr'];
		}
    }
    
    /**
        Retorna los tipos de entidades que estan habilitados para una operación dada.
    */
    function get_niveles_operacion($operacion)
    {
    	$operacion = toba::db()->quote($operacion);
		$sql = "SELECT		sga_g3entidades_tipos.entidad_tipo as valor,
							sga_g3entidades_tipos.nombre as descr,
							sga_g3entidades_tipos.entidad_tipo,
							sga_g3entidades_tipos.nombre 
				FROM 		sga_g3entidades_niveles,
							sga_g3entidades_tipos
				WHERE		sga_g3entidades_niveles.entidad_tipo = sga_g3entidades_tipos.entidad_tipo AND
							TRIM(sga_g3entidades_niveles.operacion) = $operacion
				ORDER BY	sga_g3entidades_niveles.orden
					";
		return  guarani_db::consultar($sql);
    }
    
    /**
     * Retorna los subtipos de entidades que estan habilitados para una operación dada. Estos se corresponden
     * con todos los subtipos correspondientes a los tipos que se definen como niveles posibles para ese código 
     * de operación
    */
    function get_subtipos_entidades_niveles_operacion($operacion)
    {
    	$operacion = toba::db()->quote($operacion);
		$sql = "SELECT		sga_g3entidades_subtipos.entidad_subtipo as valor,
							sga_g3entidades_subtipos.nombre as descr
				FROM 		sga_g3entidades_niveles,
							sga_g3entidades_tipos,
							sga_g3entidades_subtipos
				WHERE		sga_g3entidades_niveles.entidad_tipo = sga_g3entidades_tipos.entidad_tipo AND
							sga_g3entidades_tipos.entidad_tipo = sga_g3entidades_subtipo.entidad_tipo AND
							sga_g3entidades_niveles.operacion = $operacion
				ORDER BY	sga_g3entidades_niveles.orden
				";
		return  guarani_db::consultar($sql);
    }
	
	
	function get_entidad_id_nombre($entidad)
	{
		$ejecutar = "SELECT get_entidad_id_nombre($entidad);";
		return guarani_db::consultar_fila($ejecutar); 
	}	
	
	
}

?>