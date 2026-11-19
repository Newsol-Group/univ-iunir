<?php
/**
 *
 *
 * @package Unidad de Gestión
 * @subpackage Espacios
 */
class co_espacios
{
	/**
        Obtiene una lista de los espacios según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_espacios.espacio,
						sga_espacios.nombre,
						sga_espacios.codigo,
						sga_espacios.espacio_tipo,
						sga_espacios_tipos.nombre AS espacio_tipo_nombre,
						sga_espacios.edificacion,
						sga_edificaciones.nombre AS edificacion_nombre,
						sga_edificaciones.calle || ' ' || COALESCE(sga_edificaciones.numero,' ') AS edificacion_direccion,
						sga_espacios.piso,
						sga_espacios.capacidad,
						sga_espacios.datos_localizacion,
						sga_espacios.observaciones,
						sga_espacios.estado,
						sga_espacios_estados.nombre AS estado_nombre,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						" . guarani_sql::get_case_si_no('sga_espacios', 'accesible', 'accesible') . "
				FROM 	sga_espacios
				JOIN	sga_espacios_tipos ON sga_espacios.espacio_tipo = sga_espacios_tipos.espacio_tipo
				JOIN	sga_espacios_estados ON sga_espacios.estado = sga_espacios_estados.estado 
				JOIN	sga_edificaciones ON sga_edificaciones.edificacion = sga_espacios.edificacion
				JOIN	sga_ubicaciones ON sga_edificaciones.ubicacion = sga_ubicaciones.ubicacion
				$where
				ORDER BY sga_espacios.nombre 
						;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la lista de espacios que son utilizados para clases, según los parámetros recibidos.
	 */
	function get_aulas_edificacion($edificacion=null, $where='')
	{
		$filtro = '';
		if (!empty($edificacion)) {
			$edificacion = toba::db()->quote($edificacion);
			$filtro .= " AND sga_espacios.edificacion = $edificacion";
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		$sql = "SELECT	sga_espacios.espacio,
						sga_espacios.nombre,
						sga_espacios.nombre || ' - ' || sga_edificaciones.nombre AS descripcion
				FROM	sga_espacios
				JOIN	sga_edificaciones ON sga_espacios.edificacion = sga_edificaciones.edificacion
				JOIN	sga_espacios_tipos ON sga_espacios.espacio_tipo = sga_espacios_tipos.espacio_tipo
				WHERE	sga_espacios_tipos.usado_para_clase = '".comunes::si."'
				$filtro
				ORDER BY	sga_espacios.nombre,
							sga_edificaciones.nombre
				";
		return guarani_db::consultar($sql);
	}

	/**
        Obtiene una lista de tipos de espacios según el filtro recibido
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_espacios_tipos.espacio_tipo,
						sga_espacios_tipos.nombre,
						sga_espacios_tipos.descripcion
  				FROM 	sga_espacios_tipos
				$where
				ORDER BY sga_espacios_tipos.nombre
				;";	
		return guarani_db::consultar($sql);
	}
		
	/**
 	  *  Retorna los datos de un Espacio en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  *  los datos de los Espacios según la condición del array (para la carga masiva).
      *	 @param integer/array	espacio	id o arreglo de ids del o de los Espacios para los cuales se consultan los datos.
    */
	function get_datos_espacio($espacio='')
	{
		if (is_array($espacio)) {
			$filtro = guarani_sql::armar_condicion_in($espacio, 'sga_espacios', 'espacio');
			$datos = $this->get_listado($filtro);
			return $datos;
		}
		$espacio = toba::db()->quote($espacio);
		$datos = $this->get_listado("sga_espacios.espacio = $espacio");
		return $datos[0];
	}

	function get_nombre_aula($espacio)
	{
		if (is_array($espacio)) {
			$filtro = guarani_sql::armar_condicion_in($espacio, 'sga_espacios', 'espacio');
		} else {
			$espacio = toba::db()->quote($espacio);
			$filtro = "sga_espacios.espacio = $espacio";
		}
		$sql = "SELECT	sga_espacios.espacio,
						sga_espacios.nombre || ' - ' || sga_edificaciones.nombre as espacio_edificacion_nombre
				FROM	sga_espacios,
						sga_edificaciones
				WHERE	sga_espacios.edificacion = sga_edificaciones.edificacion AND
						$filtro
		";
		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna los tipos de espacios
    */
	function get_espacios_tipos($filtro='')
	{
    	$filtro = toba::db()->quote("%$filtro%");
		$sql = "SELECT 	sga_espacios_tipos.espacio_tipo as valor,
						sga_espacios_tipos.nombre as nombre,
						sga_espacios_tipos.descripcion as descr
  				FROM 	sga_espacios_tipos
				WHERE 	sga_espacios_tipos.nombre ILIKE $filtro OR
						sga_espacios_tipos.descripcion ILIKE $filtro ORDER BY nombre
						;";			
		return guarani_db::consultar($sql);
	}

	/**
        Retorna los estados posibles de los espacios
    */
	function get_espacios_estados($filtro='')
	{
    	$filtro = toba::db()->quote("%$filtro%");
		$sql = "SELECT 	sga_espacios_estados.estado as valor,
						sga_espacios_estados.nombre as nombre,
						sga_espacios_estados.descripcion as descr
  				FROM 	sga_espacios_estados
				WHERE 	sga_espacios_estados.nombre ILIKE $filtro OR
						sga_espacios_estados.descripcion ILIKE $filtro 
						ORDER BY nombre;";			
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si puede cambiarse el tipo y la ubicación de un espacio.
	*/
	function puede_cambiar_tipo_ubicacion($espacio=null)
	{
		if (!$espacio) {
			return true;
		}
		$espacio = toba::db()->quote($espacio);
		$sql = "SELECT	count(*) as cant
  				FROM	sga_ug_espacios
				WHERE	sga_ug_espacios.espacio = $espacio
						;";
		$res = guarani_db::consultar($sql);
		return ($res[0]['cant'] == 0);
	}
	
	/**
        Valida si un código se puede usar o no
    */
	function validar_codigo_espacio($codigo, $edificacion=null, $espacio=null)
	{
		if (! isset($codigo)) {
			return false;
		}
		if (! isset($edificacion)) {
			return false;
		}
		$codigo = toba::db()->quote($codigo);
		$edificacion = toba::db()->quote($edificacion);
		$sql = "SELECT 	count(1) as cant 	
                FROM 	sga_espacios
				WHERE 	sga_espacios.edificacion = $edificacion
				AND 	".guarani_sql::armar_condicion_compara_cadenas('sga_espacios.codigo', $codigo);
		if (isset($espacio)) {
			$espacio = toba::db()->quote($espacio);			
			$sql .= " AND sga_espacios.espacio <> $espacio ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
        Valida si un nombre se puede usar o no
    */
	function validar_nombre_espacio($nombre, $edificacion, $espacio=null)
	{
		$nombre = toba::db()->quote($nombre);
		$edificacion = toba::db()->quote($edificacion);
		$sql = "SELECT	count(1) as cant
                FROM	sga_espacios
				WHERE	sga_espacios.edificacion = $edificacion	AND
						".guarani_sql::armar_condicion_compara_cadenas('sga_espacios.nombre', $nombre);
		if (isset($espacio)) {
			$espacio = toba::db()->quote($espacio);
			$sql .= " AND sga_espacios.espacio <> $espacio ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
        Valida si un nombre de tipo de espacio se puede usar o no.
    */
	function validar_nombre_espacio_tipo($nombre, $espacio_tipo)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_espacios_tipos.espacio_tipo) as cant
                FROM 	sga_espacios_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_espacios_tipos.nombre', $nombre).'
				';
		
		if (!is_null($espacio_tipo)) {
			$espacio_tipo = toba::db()->quote($espacio_tipo);
			$sql .= " AND sga_espacios_tipos.espacio_tipo <> $espacio_tipo ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}
?>