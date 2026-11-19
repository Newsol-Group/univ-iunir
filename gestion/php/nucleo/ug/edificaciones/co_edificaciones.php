<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */
class co_edificaciones
{
	/**
        Obtiene una lista de edificaciones según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT		sga_edificaciones.edificacion, 
							sga_edificaciones.nombre,
							sga_edificaciones.edificacion_tipo, 
							sga_edificaciones_tipos.nombre as tipo_nombre,  
							sga_edificaciones.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_edificaciones.localidad,
							mug_localidades.nombre as localidad_nombre, 
							sga_edificaciones.calle,
							sga_edificaciones.numero, 
							sga_edificaciones.codigo_postal, 
							sga_edificaciones.telefono,
							sga_edificaciones.fax,
							sga_edificaciones.email,
							sga_edificaciones.estado, 
							sga_edificaciones_estados.nombre as estado_nombre   
				FROM  sga_edificaciones
				JOIN sga_edificaciones_tipos ON sga_edificaciones.edificacion_tipo = sga_edificaciones_tipos.edificacion_tipo
				JOIN sga_edificaciones_estados ON sga_edificaciones.estado = sga_edificaciones_estados.estado
				JOIN sga_ubicaciones ON sga_edificaciones.ubicacion = sga_ubicaciones.ubicacion
				LEFT JOIN mug_localidades ON sga_edificaciones.localidad = mug_localidades.localidad
				$where 
				ORDER BY	sga_edificaciones.nombre
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene una lista de los Tipos de Edificaciones segun el filtro recibido.
     * @param string   $where	filtro sobre los campos para realizar la consulta. no
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_edificaciones_tipos.edificacion_tipo,
						sga_edificaciones_tipos.nombre,
						sga_edificaciones_tipos.descripcion
				FROM 	sga_edificaciones_tipos
				$where
				ORDER BY sga_edificaciones_tipos.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de edificaciones de una ubicación dada según filtro especificado.
	 *
	 * @param integer $ubicacion
	 * @param string $where
	 * @return array
	 */
	function get_edificaciones($ubicacion=null, $where='')
	{
		$filtro = 'true';
		if (! empty($ubicacion)) {
			$ubicacion = toba::db()->quote($ubicacion);
			$filtro .= " AND sga_edificaciones.ubicacion = $ubicacion";
		}
		if ($where) {
			$filtro .= " AND $where";
		}
	   	$sql = "SELECT		sga_edificaciones.edificacion,
	   						sga_edificaciones.nombre
				FROM 		sga_edificaciones
				WHERE		$filtro
	   			ORDER BY	sga_edificaciones.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de edificaciones ACTIVAS de una ubicación dada.
	 *
	 * @param integer $ubicacion
	 * @return array
	 */
	function get_edificaciones_activas($ubicacion=null)
	{
		$filtro = "sga_edificaciones.estado = '".comunes::estado_activo."'";
		return $this->get_edificaciones($ubicacion, $filtro);
	}

	/**
        Obtiene la ubicacion de una edificación
    */
	function get_edificacion_ubicacion($edificacion=null)
	{
		$where = '';
		if (isset($edificacion)) {
			$edificacion = toba::db()->quote($edificacion);
			$where .= "WHERE sga_edificaciones.edificacion = $edificacion ";
		} else {
			return null;
		}
	   	$sql = "SELECT	sga_edificaciones.ubicacion 
				FROM 	sga_edificaciones 
						$where 
						;";
		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna los estados posibles de las edificaciones
    */
	function get_edificaciones_estados($filtro='')
	{
    	$filtro = toba::db()->quote("%$filtro%");
		$sql = "SELECT 	sga_edificaciones_estados.estado as valor,
						sga_edificaciones_estados.nombre as nombre,
						sga_edificaciones_estados.descripcion as descr
  				FROM 	sga_edificaciones_estados
				WHERE 	sga_edificaciones_estados.nombre ILIKE $filtro OR
						sga_edificaciones_estados.descripcion ILIKE $filtro 
						;";			
		return guarani_db::consultar($sql);
	}

	/**
        Retorna los tipos posibles de las edificaciones
    */
	function get_edificaciones_tipos($filtro='')
	{
    	$filtro = toba::db()->quote("%$filtro%");
		$sql = "SELECT 	sga_edificaciones_tipos.edificacion_tipo as valor,
						sga_edificaciones_tipos.nombre as nombre,
						sga_edificaciones_tipos.descripcion as descr
  				FROM 	sga_edificaciones_tipos
				WHERE 	sga_edificaciones_tipos.nombre ILIKE $filtro OR
						sga_edificaciones_tipos.descripcion ILIKE $filtro 
						ORDER BY nombre;";			
		return guarani_db::consultar($sql);
	}

	/**
	 *  Valida si un nombre de Edificación se puede usar o no.
     * @param string	$nombre		Nombre de la Edificación a validar.
     * @param integer	$edif		Id de una Edificación para excluir en la validación, si es distinto de nulo.
     * @param integer	$localidad 	Id de la localidad, puede ser nulo y compara con todas las edificaciones.
     * @return boolean	Si se puede usar ese nombre dentro de la localidad porque todavía no existe.
    */
	function validar_nombre_edificacion($nombre, $edif=null, $localidad=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	COUNT(sga_edificaciones.edificacion) as cant 	
                FROM 	sga_edificaciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_edificaciones.nombre', $nombre).'
				';
		
		if (!is_null($edif)) {
			$edif = toba::db()->quote($edif);
			$sql .= " AND sga_edificaciones.edificacion <> $edif ";
		}
		if (!is_null($localidad)) {
			$localidad = toba::db()->quote($localidad);
			$sql .= " AND sga_edificaciones.localidad = $localidad ";
		}
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 *  Valida si un nombre de Tipo de Edificación se puede usar o no.
     * @param string   $nombre	Nombre del Tipo de Edificación a validar.
     * @param integer	$tipo_edif	Id de un Tipo de Edificación para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_tipo_edificacion($nombre, $tipo_edif=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	COUNT(sga_edificaciones_tipos.edificacion_tipo) as cant 	
                FROM 	sga_edificaciones_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_edificaciones_tipos.nombre', $nombre).'
				';
		if (!is_null($tipo_edif)) {
			$tipo_edif = toba::db()->quote($tipo_edif);
			$sql .= " AND sga_edificaciones_tipos.edificacion_tipo <> $tipo_edif ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
}
?>