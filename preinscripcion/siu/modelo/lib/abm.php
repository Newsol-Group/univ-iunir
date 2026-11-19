<?php

namespace siu\modelo\lib;

use SIU\Chulupi\kernel;

class abm
{
	static function get_valor_sql($valor)
	{
		if ($valor == '0') {
			return kernel::db()->quote($valor);
		} elseif (($valor === null) || empty($valor)) {
			return 'NULL';
		} elseif (is_bool($valor)) {
			return $valor ? '1' : '0';
		} else {
			return kernel::db()->quote($valor);
		}
	}

	/**
	 * Alta de datos en una tabla
	 *
	 * @param  String $tabla
	 * @param  recordset $datos
	 * @return mixed
	 */
	static function alta($tabla, $datos, $datos_quoteados = array())
	{
		$valores = array();
		foreach ($datos as $clave => $valor) {
			$valores[$clave] = static::get_valor_sql($valor);
		}

		foreach ($datos_quoteados as $clave => $valor) {
			if ($valor === null) {
				$valor = "NULL";
			}
			$valores[$clave] = $valor;
		}
		$campos = implode(', ', array_keys($valores));
		$valores = implode(", ", $valores);
		$sql = "INSERT INTO $tabla ($campos) VALUES ($valores)";
		kernel::db()->ejecutar($sql);
	}

	static function baja($tabla, $clave)
	{
		$where = static::get_where_de_clave($clave);
		$sql = "DELETE FROM $tabla WHERE $where";
		kernel::db()->ejecutar($sql);
	}

	static function modificacion($tabla, $datos, $clave, $where_extra = null, $set_extra = null)
	{
		$where = static::get_where_de_clave($clave);
		$valores = array();
		foreach ($datos as $campo => $valor) {
			$valores[] = $campo . ' = ' . static::get_valor_sql($valor);
		}

		$valores = implode(', ', $valores);
		if (isset($set_extra)) {
			$valores .= ', ' . $set_extra;
		}

		if (isset($where_extra)) {
			$where .= ' ' . $where_extra;
		}
		$sql = "UPDATE $tabla SET $valores WHERE $where";
		kernel::db()->ejecutar($sql);
	}

	/**
	 * Determina si una tabla contiene registro/s con una clave dada
	 * Una pavada, pero para ahorrar 3 lineas de codigo sirve!
	 */
	static function existe_registro($tabla, $clave, $clave_excluir = null)
	{
		$where = static::get_where_de_clave($clave);
		if (isset($clave_excluir)) {
			$where .= ' AND NOT (' . static::get_where_de_clave($clave_excluir) . ')';
		}
		$sql = "SELECT COUNT(*) as cant FROM $tabla WHERE $where";
		$rs = kernel::db()->consultar_fila($sql);
		return $rs['cant'] > 0;
	}

	/**
	 * Determina si el nuevo set de datos difiere del actualmente cargado en base, esto evita que se haga un update y se actualize una vigencia por ejemplo
	 */
	static function hubo_cambios_tabla($tabla, $clave, $datos_nuevos)
	{
		$campos = array_keys($datos_nuevos);
		$where = static::get_where_de_clave($clave);
		$sql = "SELECT " . implode(', ', $campos) . " FROM $tabla WHERE $where";
		$rs = kernel::db()->consultar_fila($sql);
		if (empty($rs)) {
			return true;
		} else {
			foreach ($datos_nuevos as $campo => $valor) {
				if (is_bool($rs[$campo])) { //Caso particular campos boolean en base
					$rs[$campo] = ($rs[$campo]) ? 1 : 0;
				}
				if (is_bool($valor)) { //Caso particular campos boolean en php
					$valor = ($valor) ? 1 : 0;
				}

				if (trim($rs[$campo]) != trim($valor)) {
					return true;
				}
			}
			return false;
		}
	}

	static function get_where_de_clave($clave)
	{
		$where = 'TRUE';
		if (!empty($clave)) {
			$where = array();
			foreach ($clave as $campo => $valor) {
				$where[] = $campo . ' = ' . kernel::db()->quote($valor);
			}
			$where = implode(' AND ', $where);
		}
		return $where;
	}

	static function recuperar_secuencia($secuencia)
	{
		$secuencia = kernel::db()->quote($secuencia);
		$sql = "SELECT currval($secuencia) as seq;";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['seq'];
	}

}
?>
