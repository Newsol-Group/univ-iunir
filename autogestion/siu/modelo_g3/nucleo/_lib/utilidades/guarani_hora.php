<?php
/**
 * Manipulación de horas.
 * 
 * @package Centrales
 * @subpackage Utilidades
 */
class guarani_hora
{
	/**
	 * Determina si el primer horario recibido es posterior al otro.
	 *
	 * @param string $hora1		Primer horario a comparar (formato 'HH:MM').
	 * @param string $hora2		Segundo horario a comparar (formato 'HH:MM').
	 * @param boolean $igual	Indica si se debe comparar por mayor o igual (true) o sólo por mayor (false).
	 */
	static function es_mayor($hora1, $hora2, $igual = false)
	{
		if (!guarani_validar::es_hora_minutos($hora1)) {
			return false;
		}
		if (!guarani_validar::es_hora_minutos($hora2)) {
			return false;
		}
		$pos = strpos($hora1, ':');
		$hs1 = substr($hora1, 0, $pos);
		$mins1 = substr($hora1, $pos + 1);
		$pos = strpos($hora2, ':');
		$hs2 = substr($hora2, 0, $pos);
		$mins2 = substr($hora2, $pos + 1);

		if ($hs1 > $hs2) {
			return true;
		}
		$operador = $igual ? ' >= ' : ' > ';
		if ($hs1 == $hs2) {
			return (eval("return $mins1 $operador $mins2;"));
		}
		return false;
	}

	static function es_mayor_igual($hora1, $hora2)
	{
		return static::es_mayor($hora1, $hora2, true);
	}
	
	static function es_menor($hora1, $hora2, $igual = false)
	{
		if (!empty($hora1) && !empty($hora2)) {
			$hora1 = toba::db()->quote($hora1);
			$hora2 = toba::db()->quote($hora2);
			$tipo = 'TIME';
			$igual ? $operador = ' <= ' : $operador = ' < ';
			$sql = "SELECT ($tipo $hora1 $operador $tipo $hora2) as es_menor";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['es_menor'];
		}
	}

	/**
	 *  Valida si el rango [Desde 1, Hasta 1] se solapa con el rango [Desde 2, Hasta 2].
	 */
	static function rango_solapado_en_rango($hora_desde1, $hora_hasta1, $hora_desde2, $hora_hasta2)
	{
		$hora_desde1 = toba::db()->quote($hora_desde1);
		$hora_hasta1 = toba::db()->quote($hora_hasta1);
		$hora_desde2 = toba::db()->quote($hora_desde2);
		$hora_hasta2 = toba::db()->quote($hora_hasta2);

		$sql = "SELECT
					($hora_desde2 > TIME $hora_desde1 AND $hora_desde2 < TIME $hora_hasta1)
					OR
					($hora_hasta2 > TIME $hora_desde1 AND $hora_hasta2 < TIME $hora_hasta1)
					OR
					(TIME $hora_desde2 <= TIME $hora_desde1 AND TIME $hora_hasta2 >= TIME $hora_hasta1) as solapa_rango
		";
		$rs = guarani_db::consultar_fila($sql, null, false);

		return $rs['solapa_rango'];
	}

	static function sumar_minutos_a_hora($hora, $minutos)
	{
		if (!empty($hora)) {
			$hora = toba::db()->quote($hora);
			$sql = "SELECT (TIME $hora + interval '$minutos MINUTES') as result";
			$rs = guarani_db::consultar_fila($sql, null, false);
			return $rs['result'];
		}
	}

}