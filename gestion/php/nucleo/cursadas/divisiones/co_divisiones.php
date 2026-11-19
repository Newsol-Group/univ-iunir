<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Divisiones
 * 
 */
class co_divisiones
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_divisiones.division,
						sga_divisiones.nombre,
						sga_divisiones.codigo,
						sga_divisiones.descripcion,
						sga_divisiones.turno,
						sga_turnos_cursadas.nombre as turno_nombre
				FROM 	sga_divisiones
						LEFT JOIN sga_turnos_cursadas ON
							sga_turnos_cursadas.turno = sga_divisiones.turno
				$where
				";	

		return guarani_db::consultar($sql);
	}	
	
	/**
     *  Retorna la lista de nombres para el combo de divisiones
     */
	function get_divisiones()
	{
		$sql = "SELECT		sga_divisiones.division AS valor,
							sga_divisiones.nombre 	AS descr
				FROM		sga_divisiones
				ORDER BY 	sga_divisiones.nombre
				";

		return guarani_db::consultar($sql);
	}
	
	function get_opciones_combo_divisiones()
	{
		$datos = $this->get_divisiones();
		$datos[] = array('valor' => 'N', 'descr' => 'Ninguna');
		return $datos;
	}
	
	/**
     *  Valida si un nombre de División se puede usar o no.
     */
	function validar_nombre($nombre, $id=null)
	{
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_divisiones.nombre) as cant
                FROM 	sga_divisiones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_divisiones.nombre', $nombre).'
				';
		
		if (!is_null($id)) {
			$id = toba::db()->quote($id);
			$sql .= " AND sga_divisiones.division <> $id ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
	
	/**
     *  Valida si un código de División se puede usar o no.
     */
	function validar_codigo($codigo, $id=null)
	{
		if (!isset($codigo)) {
			return true;
		}
		
		$codigo = toba::db()->quote($codigo);		
		$sql = 'SELECT	count(sga_divisiones.codigo) as cant
                FROM 	sga_divisiones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_divisiones.codigo', $codigo).'
				';
		
		if (!is_null($id)) {
			$id = toba::db()->quote($id);
			$sql .= " AND sga_divisiones.division <> $id ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}	
}
?>