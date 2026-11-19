<?php
/**
 *
 *
 * @package Matrículas
 * @subpackage Ingresos
 */
class co_ingresos
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_tipos_ingreso.tipo_ingreso, 
						sga_tipos_ingreso.nombre, 
						sga_tipos_ingreso.descripcion, 
						sga_tipos_ingreso.requiere_res_exp
  				FROM 	sga_tipos_ingreso
						$where
				ORDER BY nombre
						;"; 
		return guarani_db::consultar($sql);
	}
	
	function get_nombre_tipo_ingreso($tipo_ingreso)
	{
		$tipo_ingreso = toba::db()->quote($tipo_ingreso);

		$sql = "SELECT 	sga_tipos_ingreso.nombre
  				FROM 	sga_tipos_ingreso
				WHERE	sga_tipos_ingreso.tipo_ingreso = $tipo_ingreso;"; 
		
		$res = guarani_db::consultar_fila($sql);
		return $res['nombre'];
	}

	function get_requiere_res_exp_tipo_ingreso($tipo_ingreso)
	{
		$tipo_ingreso = toba::db()->quote($tipo_ingreso);
		$sql = "SELECT 	sga_tipos_ingreso.requiere_res_exp
  				FROM 	sga_tipos_ingreso
				WHERE	sga_tipos_ingreso.tipo_ingreso = $tipo_ingreso
		";
		$res = guarani_db::consultar_fila($sql);
		return $res['requiere_res_exp'];
	}
}
?>