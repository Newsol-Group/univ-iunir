<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class co_unidades_gestion
{
	/**
 	 * Obtiene una lista de las Unidades de Gestion según el filtro recibido.
 	 * 
     * @param string 	where	filtro sobre los campos para realizar la consulta.
     */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		
		$sql = "SELECT 	sga_unidades_gestion.unidad_gestion,
						sga_unidades_gestion.nombre
  				FROM 	sga_unidades_gestion
  				$where
  				;";
  						
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene las opciones de UG seleccionables por un usuario
	 * 
	 * @return array
	 */
	function get_opciones_ug_kolla()
	{
		$sql = "SELECT 	
					sga_unidades_gestion.kolla_unidad_gestion AS valor,
					sga_unidades_gestion.nombre AS descr
  				FROM 	
					sga_unidades_gestion
				WHERE
					sga_unidades_gestion.kolla_unidad_gestion IS NOT NULL";
  						
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de unidades de gestión que tienen una UG asociada en SIU-Tehuelche.
	 * 
	 * @return array
	 */
	function get_unidades_gestion_tehuelche()
	{
		$sql = "SELECT	sga_unidades_gestion.tehuelche_unidad_gestion as valor,
						sga_unidades_gestion.nombre as descr
				FROM	sga_unidades_gestion
				WHERE	sga_unidades_gestion.tehuelche_unidad_gestion IS NOT NULL
		";
		return guarani_db::consultar($sql);
	}

	/**
 	 * Valida si un nombre de Unidad de Gestion se puede usar o no.
 	 * 
     * @param string 	nombre	Nombre de Unidad de Gestión que se intenta validar.
     * @param integer ug		Id de una Unidad de Gestión a excluir en la comparación si es distinto de nulo.
     */
	function validar_nombre_unidad_gestion($nombre, $ug=null)
	{
		if (is_null($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	count(sga_unidades_gestion.unidad_gestion) as cant 	
                FROM 	sga_unidades_gestion
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_unidades_gestion.nombre', $nombre).'
				';
		if (!is_null($ug)) {
			$ug = toba::db()->quote($ug);
			$sql .= " AND sga_unidades_gestion.unidad_gestion <> $ug ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	function get_dominio_unidad_gestion($unidad_gestion)
	{
		$unidad_gestion = toba::db()->quote($unidad_gestion);
		$sql = "SELECT	sga_unidades_gestion.dominio
				FROM	sga_unidades_gestion
				WHERE	sga_unidades_gestion.unidad_gestion = $unidad_gestion
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['dominio'];
	}
}
?>