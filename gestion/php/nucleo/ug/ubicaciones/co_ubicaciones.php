<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */


class co_ubicaciones
{
	/**
        Obtiene una lista de las ubicaciones según el filtro recibido
    */
	function get_listado($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
			$where = ' AND '.$where.' ';			        
		}
		
		$sql = "SELECT		sga_ubicaciones.ubicacion, 
							sga_ubicaciones.nombre as ubicacion_nombre, 
							sga_ubicaciones.nombre || ' - ' || sga_ubicaciones_tipos.nombre || ' - ' || mug_localidades.nombre as ubicacion_descripcion,
							sga_ubicaciones.ubicacion_tipo, 
							sga_ubicaciones_tipos.nombre as ubicacion_tipo_nombre,
							sga_ubicaciones.localidad, 
							mug_localidades.nombre as localidad_nombre,
							sga_ubicaciones.calle, 
							sga_ubicaciones.numero, 
	       					sga_ubicaciones.codigo_postal, 
	       					sga_ubicaciones.telefono, 
	       					sga_ubicaciones.fax, 
	       					sga_ubicaciones.email 
	  			FROM 		sga_ubicaciones,
							sga_ubicaciones_tipos,
							mug_localidades
				WHERE   	sga_ubicaciones.ubicacion_tipo = sga_ubicaciones_tipos.ubicacion_tipo
							AND sga_ubicaciones.localidad = mug_localidades.localidad
							$where
				ORDER BY 	UPPER(sga_ubicaciones.nombre)
			  ;";
		
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	function get_ubicaciones_perfil_de_datos($where='')
	{
		$filtro = 'true';
		// Cuando el usuario logueado tiene perfil de datos hay que agregar a las ubicaciones de la UG aquellas que no están vinculadas a ninguna UG.
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$filtro = '('.toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_ubicaciones', 'sga_ubicaciones');
			$filtro .= ' OR sga_ubicaciones.ubicacion NOT IN (SELECT vw_ug_propuestas.ubicacion FROM vw_ug_propuestas WHERE ubicacion IS NOT NULL))';
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		return $this->get_listado($filtro, false);
	}

	/**
        Obtiene una lista de los tipos de ubicaciones según el filtro recibido
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';			        
		}
		
		$sql = "SELECT	sga_ubicaciones_tipos.ubicacion_tipo as ubicacion_tipo,
						sga_ubicaciones_tipos.nombre as nombre,
						sga_ubicaciones_tipos.descripcion as descripcion
				FROM 	sga_ubicaciones_tipos
			   	$where
				ORDER BY sga_ubicaciones_tipos.nombre
				;";
		
		return guarani_db::consultar($sql);
		
	}

	/**
        Retorna todas las ubicaciones existentes.
    */
	function get_ubicaciones($ubicacion=null)
	{
		$where = '';
		if (!is_null($ubicacion)) {
			$ubicacion = toba::db()->quote($ubicacion);
			$where .= " WHERE sga_ubicaciones.ubicacion = $ubicacion ";
		}
		$sql = "SELECT 		sga_ubicaciones.ubicacion as valor,
							sga_ubicaciones.nombre as descr
				FROM 		sga_ubicaciones
			   	$where  
			    ORDER BY 	sga_ubicaciones.nombre
			   ;";
		return guarani_db::consultar($sql);
	}

	/**
        Retorna una ubicación con sus datos
    */
	function get_ubicacion_info($ubicacion)
	{
	    $ubicacion = toba::db()->quote($ubicacion);
		$where = " sga_ubicaciones.ubicacion = $ubicacion ";
		$datos = $this->get_listado($where);
		
		if (!empty($datos)) {
			return $datos[0]; 
		} else {
			return false;
		}
	}	
	
	
	/**
 	  * Retorna el nombre de una Ubicación en formato RecordSet (para la carga externa), en caso de que el parámetro sea un 
 	  * array retorna los nombres de las Ubicaciones según la condición del array (para la carga masiva).
      *	@param integer/array	ubicacion	id o arreglo de ids del o de las Ubicaciones para las cuales se consultan los nombres.
    */
	function get_nombre_ubicacion($ubicacion=null)
	{
		if (is_array($ubicacion)) {
			$filtro = guarani_sql::armar_condicion_in($ubicacion,'sga_ubicaciones', 'ubicacion');
			$datos = $this->get_listado($filtro);
			return $datos;
		}
		$ubicacion = toba::db()->quote($ubicacion);
		$datos = $this->get_listado("sga_ubicaciones.ubicacion = $ubicacion");
		return $datos[0];	
	}

	function get_nombre_ubicacion_sin_perfil_datos($ubicacion)
	{
		if (is_array($ubicacion)) {
			$filtro = guarani_sql::armar_condicion_in($ubicacion,'sga_ubicaciones', 'ubicacion');
			$datos = $this->get_listado($filtro, false);
			return $datos;
		}
		$ubicacion = toba::db()->quote($ubicacion);
		$datos = $this->get_listado("sga_ubicaciones.ubicacion = $ubicacion", false);
		return $datos;
	}

	/**
        Retorna todos los tipos de ubicaciones existentes.
    */
	function get_tipos_ubicaciones($tipo_ubicacion=null)
	{
		$where = '';
		if (!is_null($tipo_ubicacion)) {
			$tipo_ubicacion = toba::db()->quote($tipo_ubicacion);
			$where .= " WHERE sga_ubicaciones_tipos.ubicacion_tipo = $tipo_ubicacion ";
		}
		
		$sql = "SELECT 		sga_ubicaciones_tipos.ubicacion_tipo as valor,
							sga_ubicaciones_tipos.nombre as descr
				FROM 		sga_ubicaciones_tipos
			   	$where  
			    ORDER BY 	sga_ubicaciones_tipos.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna el string correspondiente a la localidad de la ubicacion dada.
    */
	function get_ubicacion_localidad($ubicacion=null)
	{  
		if (!is_null($ubicacion)) { 		
	    	$ubicacion = toba::db()->quote($ubicacion);
	        $sql = "SELECT	sga_ubicaciones.localidad as valor,
	        				mug_localidades.nombre  || ' (' || mug_dptos_partidos.nombre || ' - ' || mug_provincias.nombre || ' - ' || mug_paises.nombre || ') ' as descr
					FROM	sga_ubicaciones, 
							mug_localidades,
							mug_dptos_partidos,
							mug_provincias,
							mug_paises
					WHERE	sga_ubicaciones.localidad = mug_localidades.localidad AND
							mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido AND
							mug_dptos_partidos.provincia = mug_provincias.provincia AND
							mug_provincias.pais = mug_paises.pais AND
							sga_ubicaciones.ubicacion = $ubicacion
					;";
	
			$datos = guarani_db::consultar($sql);
			if (!empty($datos)) {
				return ($datos[0]['descr']);    
			}
		}
		return '';
	}
    
	/**
        Retorna el string correspondiente al tipo de la ubicacion dada.
    */
	function get_ubicacion_tipo_nombre($ubicacion=null)
	{  
		if (!is_null($ubicacion)) { 		
	    	$datos = $this->get_ubicacion_info($ubicacion);
	        if (!empty($datos)) {
				return ($datos['ubicacion_tipo_nombre']);    
			}
		}
		return '';
	}
	
	/**
        Retorna el string correspondiente a la direccion (calle y nro) de la ubicacion dada.
    */
	function get_ubicacion_direccion($ubicacion=null)
	{  
		if (!is_null($ubicacion)) { 		
	    	$datos = $this->get_ubicacion_info($ubicacion);
	        if (!empty($datos)) {
	        	$dir = $datos['calle'] . '' . $datos['numero'];
				return $dir;    
			}
		}
		return '';
	}
	
	/**
        Retorna los valores posibles de codigos postales correspondientes a la localidad de la ubicacion dada.
    */
	function get_ubicacion_codigos_postales($ubicacion=null)
	{  
		$where = '';
		if (!is_null($ubicacion)) { 		
	    	$ubicacion = toba::db()->quote($ubicacion);
	    	$where = " AND sga_ubicaciones.ubicacion = $ubicacion ";
		}
	        
		$sql = "SELECT	sga_ubicaciones.ubicacion,
						sga_ubicaciones.localidad,
	       				mug_localidades.nombre,
	       				mug_cod_postales.codigo_postal
				FROM	sga_ubicaciones, 
						mug_localidades
						LEFT JOIN mug_cod_postales ON mug_localidades.localidad = mug_cod_postales.localidad
				WHERE	sga_ubicaciones.localidad = mug_localidades.localidad
						$where
				;";
	
		return guarani_db::consultar($sql);	
	}
	
    /**
        Retorna el string correspondiente al nombre de la Ubicacion dada.
    */
	function get_ubicacion_nombre($ubicacion=null)
	{  
		if (! is_null($ubicacion)) { 		
	    	$ubicacion = toba::db()->quote($ubicacion);
	        $sql = "SELECT	sga_ubicaciones.ubicacion as valor,
	        				sga_ubicaciones.nombre as descr
					FROM	sga_ubicaciones
					WHERE	sga_ubicaciones.ubicacion = $ubicacion
					;";

			$datos = guarani_db::consultar_fila($sql);
			return $datos['descr'];
		} else {
    		return '';
		}
	}

	/**
        Retorna todas las ubicaciones ligadas a propuestas.
    */
	function get_ubicaciones_propuestas($propuesta=null)
	{
		$where = '';
		if (!is_null($propuesta)) {
			$prop = toba::db()->quote($propuesta);
			$where .= " AND sga_propuestas_oferta.propuesta = $propuesta ";
		}
		
		$sql = "SELECT 		
							sga_ubicaciones.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_ubicaciones_tipos.nombre as tipo_ubicacion_nombre,
							sga_ubicaciones_tipos.ubicacion_tipo
				FROM 		
							sga_ubicaciones,
							sga_ubicaciones_tipos,
							sga_propuestas_oferta
				WHERE 
							sga_propuestas_oferta.ubicacion = sga_ubicaciones.ubicacion AND
							sga_ubicaciones_tipos.ubicacion_tipo = sga_ubicaciones.ubicacion_tipo 
			   	$where  
			    ORDER BY 	sga_ubicaciones.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}
	
	
	/**
	 *  Valida si un nombre de Ubicacion se puede usar o no.
     * @param string   $nombre	Nombre de la Ubicación a validar.
     * @param integer	$ubicacion_tipo	Id de una Ubicación para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_ubicacion($nombre, $ubicacion=null)
	{
		if (is_null($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(sga_ubicaciones.ubicacion) as cant 	
                FROM 	sga_ubicaciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_ubicaciones.nombre', $nombre).'
				';
		if (!is_null($ubicacion)) {
			$ubicacion = toba::db()->quote($ubicacion);
			$sql .= " AND sga_ubicaciones.ubicacion <> $ubicacion ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 *  Valida si un nombre de Tipo de Ubicacion se puede usar o no.
     * @param string   $nombre	Nombre del Tipo de Ubicación a validar.
     * @param integer	$ubicacion_tipo	Id de un Tipo de Ubicación para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_tipo_ubicacion($nombre, $ubicacion_tipo=null)
	{
		if (is_null($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	COUNT (sga_ubicaciones_tipos.ubicacion_tipo) as cant
				FROM 	sga_ubicaciones_tipos 
				WHERE	'.guarani_sql::armar_condicion_compara_cadenas('sga_ubicaciones_tipos.nombre', $nombre).'
			   ';
		if (!is_null($ubicacion_tipo)) {
			$ubicacion_tipo = toba::db()->quote($ubicacion_tipo);
			$sql .= " AND sga_ubicaciones_tipos.ubicacion_tipo <> $ubicacion_tipo ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	function get_ubicaciones_x_prop_plan_version($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	DISTINCT sga_ubicaciones.ubicacion,
						sga_ubicaciones.nombre			AS ubicacion_nombre,
						sga_ubicaciones_tipos.nombre	AS ubicacion_tipo_nombre
		
				FROM 	sga_propuestas_aspira,
						sga_situacion_aspirante,
						sga_ubicaciones,
						sga_ubicaciones_tipos
						
				WHERE 	sga_propuestas_aspira.situacion_asp		= sga_situacion_aspirante.situacion_asp
				AND		sga_situacion_aspirante.resultado_asp	= '".propuesta::inscripcion_pendiente."'
				AND		sga_propuestas_aspira.ubicacion			= sga_ubicaciones.ubicacion
				AND		sga_ubicaciones.ubicacion_tipo			= sga_ubicaciones_tipos.ubicacion_tipo
						$where
				
				ORDER BY sga_ubicaciones.nombre
                        ;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de ubicaciones sin aplicar filtros por perfil de datos.
	 * 
	 * @return integer
	 */
	function get_cantidad_ubicaciones()
	{
		$sql = "SELECT	COUNT(sga_ubicaciones.ubicacion) as cant
				FROM	sga_ubicaciones
		";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['cant'];
	}

}
?>