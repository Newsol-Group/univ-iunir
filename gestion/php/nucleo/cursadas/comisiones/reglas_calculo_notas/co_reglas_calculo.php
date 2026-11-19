<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class co_reglas_calculo
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT 		sga_reglas_calculo.regla_calculo,
							sga_reglas_calculo.nombre,
							sga_reglas_calculo.descripcion,
							sga_reglas_calculo.php_clase
				FROM 		sga_reglas_calculo
				$where
				ORDER BY	nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_php_clase_regla($regla_calculo)
	{
		$regla_calculo = toba::db()->quote($regla_calculo);
		$where = "sga_reglas_calculo.regla_calculo = $regla_calculo";
		$datos = current($this->get_listado($where));
		return $datos['php_clase'];
	}

	function validar_nombre_regla_calculo_notas($nombre, $regla_calculo)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_reglas_calculo.regla_calculo) as cant
				FROM	sga_reglas_calculo
				WHERE	'.guarani_sql::armar_condicion_compara_cadenas('sga_reglas_calculo.nombre', $nombre).'
		';
		if (!is_null($regla_calculo)) {
			$regla_calculo = toba::db()->quote($regla_calculo);
			$sql .= " AND sga_reglas_calculo.regla_calculo <> $regla_calculo";
		}
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
}
?>