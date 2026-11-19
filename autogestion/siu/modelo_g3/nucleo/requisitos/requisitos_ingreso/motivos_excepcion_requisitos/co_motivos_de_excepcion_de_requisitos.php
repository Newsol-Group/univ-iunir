<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class co_motivos_de_excepcion_de_requisitos
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_req_motivos_excepcion.motivo_excepcion,
						sga_req_motivos_excepcion.nombre, 
						sga_req_motivos_excepcion.descripcion
				FROM 	sga_req_motivos_excepcion
				$where
				ORDER BY 2
				;"; 
		
		return guarani_db::consultar($sql);
	}
	
	
	/**
        Retorna todos los motivos de excepcion de requisitos existentes.
    */
	function get_motivos_excepcion_requisitos($motivo=null)
	{
		$where = '';
		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$where .= " WHERE sga_req_motivos_excepcion.motivo_excepcion = $motivo ";
		}
		$sql = "SELECT 	sga_req_motivos_excepcion.motivo_excepcion as valor,
						sga_req_motivos_excepcion.nombre as descr 
				FROM 	sga_req_motivos_excepcion
				$where
				ORDER BY 2
			   ;";
		return guarani_db::consultar($sql);
	}

	
	/**
        Valida si un nombre de motivo de excepción de requisitos se puede usar o no.
    */
	function validar_nombre_motivo_de_excepcion($nombre, $motivo_excepcion)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_req_motivos_excepcion.motivo_excepcion) as cant
                FROM 	sga_req_motivos_excepcion
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_req_motivos_excepcion.nombre', $nombre).'
				';
		
		if (!is_null($motivo_excepcion)) {
			$motivo_excepcion = toba::db()->quote($motivo_excepcion);
			$sql .= " AND sga_req_motivos_excepcion.motivo_excepcion <> $motivo_excepcion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}
?>