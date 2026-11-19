<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Actualizaciones
 * 
 */
class co_motivos_cambio_plan_estudios
{
	/**
     *  Obtiene una lista de motivos de cambio de calidad de planes de estudio según el filtro 
     *  recibido
     */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}

		$sql = 'SELECT 	sga_alumnos_motivos_plan.motivo_plan,
						sga_alumnos_motivos_plan.nombre,
						sga_alumnos_motivos_plan.descripcion,
			   			'.guarani_sql::get_case('sga_alumnos_motivos_plan', 'estado')."
				FROM 	sga_alumnos_motivos_plan 
				$where
				ORDER BY sga_alumnos_motivos_plan.nombre
				";

		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de motivo de cambio de calidad de plan se puede usar o no.
     */
	function validar_nombre_motivo_plan($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_alumnos_motivos_plan.motivo_plan) as cant
                FROM 	sga_alumnos_motivos_plan
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_alumnos_motivos_plan.nombre', $nombre).'
				';
		
		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_alumnos_motivos_plan.motivo_plan <> $motivo ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
	
	function get_listado_motivos_cambio()
	{
		$where = "sga_alumnos_motivos_plan.motivo_plan IN ('".alumno::motivo_plan_cambio."', '".alumno::motivo_plan_cambio_version."')";
		return $this->get_listado($where);
	}
}
?>