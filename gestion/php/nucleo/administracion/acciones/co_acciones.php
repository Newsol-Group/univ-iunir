<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Acciones
 */
class co_acciones
{
	/**
        Obtiene una lista de acciones según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
	    	$where = ' WHERE '.$where;
		}

		$sql = "	SELECT 		sga_acciones.accion,
								sga_acciones.nombre,
								sga_acciones.descripcion
					FROM 		sga_acciones
					$where
					ORDER BY 	sga_acciones.nombre;
		"; 
		return guarani_db::consultar($sql);
	}
	
	/**
      *	Retorna los datos de una accion según el id dado como parámetro en formato RecordSet, 
      *	en caso de ser un array retorna los datos de la/z acciones segun la condicion 
      * del array (para la carga masiva).
      * @param int/array $accion id del estado o array de ids de acciones para los cuales se obtienen los datos.
    */
    function get_accion_datos($accion)
    {
    	if (is_array($accion)) {
			/**
 	  		* En el caso de la carga masiva se arma otro arreglo, porque la columna accion puede ser nula
 	  		* para algunas filas. Solo se pasa al armado de la condición in aquellos valores de accion no nulos.
 	  		*/
			$acciones = array_diff($accion, array(null));
			$filtro = guarani_sql::armar_condicion_in($acciones, 'sga_acciones', 'accion');
			$datos = $this->get_listado($filtro);
			return $datos;
		} elseif (!is_null($accion)) {
			$accion = toba::db()->quote($accion);
			$datos = $this->get_listado("sga_acciones.accion = $accion");
			return $datos[0];
		}
    }

    
	/**
        Obtiene una lista de acciones según el filtro recibido
    */
	function get_acciones_operacion($where='')
	{
		if ($where) {
	    	$where = ' WHERE ' . $where;
		}

		$sql = "	SELECT 		sga_acciones.accion,
								sga_acciones.nombre,
								sga_acciones.descripcion,
								sga_acciones_operacion.operacion
					FROM 		sga_acciones
					JOIN 		sga_acciones_operacion USING (accion)
					$where
					ORDER BY 	sga_acciones.nombre;
		"; 
		return guarani_db::consultar($sql);
	}
    
	/**
        Obtiene una lista de las interfaces posibles para realizar acciones, según el filtro recibido
    */
	function get_interfaces_acciones($where='')
	{
		if ($where) {
	    	$where = ' WHERE ' . $where;
		}

		$sql = "	SELECT 		acc_interfaces.interfaz as valor,
								acc_interfaces.nombre as descr,
								acc_interfaces.descripcion
					FROM 		acc_interfaces
					$where
					ORDER BY 	acc_interfaces.nombre;
		"; 
		return guarani_db::consultar($sql);
	}
	
	/**
 	  * Valida si un nombre de Acción se puede usar o no.
      *	@param string  nombre	Nombre de la Acción que se intenta validar.
    */
	function validar_nombre_accion($nombre, $accion)
	{		
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = "SELECT 	count(sga_acciones.accion) as cant 	
                FROM 	sga_acciones
				WHERE 	".guarani_sql::armar_condicion_compara_cadenas('sga_acciones.nombre', $nombre);
		
		if (!is_null($accion)) {
			$accion = toba::db()->quote($accion);
			$sql .= " AND sga_acciones.accion <> $accion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}
?>