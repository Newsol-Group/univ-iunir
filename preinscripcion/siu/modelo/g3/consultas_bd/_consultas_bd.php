<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\InterfaceDb;
use SIU\Chulupi\util\db\dba;
use siu\modelo\util;

/**
 * Consultas a la base de datos de negocio de Guaraní.
 */
abstract class _consultas_bd
{
	/**
	 * @var InterfaceDb
	 */
	static protected $db;
	
	/**
	 * Tiempo de vida en caché de las consultas implementadas por esta clase
	 * 
	 * @var int	Expresado en segundos (por defecto, establecemos cinco minutos)
	 *			0 (cero) denota que el valor persistirá hasta que sea eliminado manualmente
	 */
	protected $tiempo_cache = 300;
	
	/**
	 * Datos de los bloques para armar las distintas querys para abastecer con 
	 * datos a las funciones de la interfaz implementada.
	 * 
	 * A completar por cada entidad concreta.
	 * 
	 * @var array
	 */
	protected $sql = array(
		'select' => '',
		'from' => '',
		'where' => '',
		'order_by' => '',
		'clave' => '',
		'campo_nombre' => ''
	);

	abstract static function instancia();
	
	protected function consultar($sql, $clave_cache = null)
	{
		if (util::catalogo()->usa_cache()) {

                        if (is_null($clave_cache)) {
                            $clave_cache = util::catalogo()->generar_id($sql);
			}
			
			if (util::catalogo()->existe($clave_cache)) {
				$datos = util::catalogo()->buscar($clave_cache);
			} else {
				$datos = static::db_g3()->consultar($sql);
				util::catalogo()->guardar($clave_cache, $datos, $this->tiempo_cache);
			}
		} else {
			$datos = static::db_g3()->consultar($sql);
		}
		
		return $datos;
	}
	
	protected function ejecutar($sql)
	{
		static::db_g3()->ejecutar($sql);
	}

	protected function consultar_fila($sql, $clave_cache = null)
	{
		return current($this->consultar($sql, $clave_cache));
	}

	protected static function db_g3()
	{
		if (!self::$db) {
			$param = kernel::proyecto()->get_parametros_db_gestion();
			self::$db = dba::conectar($param);
		}
		return self::$db;
	}

	/**
	 * 
	 * @param $where	Condición adicional para la cláusula WHERE
	 * @param $id		ID de una entidad particular (si es null, se omite)
	 *					Cuando la clave es un campo simple, se espera directamente el valor
	 *					Cuando la clave es compuesta, se espera un array de la forma:
	 *					[
	 *						'campo_clave_1' => valor,
	 *						'campo_clave_2' => valor,
	 *						...
	 *					]
	 * @param $con_perfil_datos boolean
	 */
	protected function armar_sql($where = '', $id = null, $con_perfil_datos = true)
	{
		//---- Select / From ---------------------------------------------------
		$sql = "SELECT
					{$this->sql['select']}
				FROM
					{$this->sql['from']}
			";

		//---- Where -----------------------------------------------------------
		$clausula_where = '';
		if (!empty($this->sql['where'])) {
			$clausula_where .= "
				WHERE
					{$this->sql['where']}
			";
		}
		
		// Condiciones de filtrado adicionales
		if (!empty($where)) {
			$conector = (empty($clausula_where)) ? 'WHERE ' : 'AND ';	
			$clausula_where .= " $conector $where ";
		}
		
		if ($con_perfil_datos) {
			$filtro_perfil_datos = $this->filtro_perfil_datos();
			if (!empty($filtro_perfil_datos)) {
				$conector = (empty($clausula_where)) ? 'WHERE ' : 'AND ';	
				$clausula_where .= " $conector $filtro_perfil_datos ";
			}
		}
		
		$sql .= $clausula_where;

		if (!is_null($id)) {
			$conector = (empty($clausula_where)) ? 'WHERE ' : 'AND ';
			
			$sql .= " $conector ";
			$claves = explode(',', $this->sql['clave']);
			$cant_claves = count($claves);
			
			if ($cant_claves == 1) {
				// Clave simple
				$sql .= $this->sql['clave'] . ' = ' . kernel::db()->quote($id);
			} else {
				// Clave compuesta
				for ($i = 0; $i < $cant_claves; $i++) {
					if ($i > 0) {
						$sql .= " AND ";
					}
					$campo_clave = trim($claves[$i]);
					$sql .= $campo_clave . ' = ' . kernel::db()->quote($id[$campo_clave]);
				}
			}
		}
		
		

		//---- Order by --------------------------------------------------------
		if (!empty($this->sql['order_by'])) {
			$sql .= "
				ORDER BY
					{$this->sql['order_by']}
			";
		}
		
		return $sql;
	}

	/**
	 * La mayoría de las entidades consultadas de G3 no son afectadas por las 
	 * configuraciones de perfil de datos, por eso la salida por defecto es un
	 * string vacío. Las entidades que necesitan filtrar por perfil de datos 
	 * redefinen esta función.
	 * 
	 * @return string
	 */
	protected function filtro_perfil_datos()
	{
		return '';
	}
	
}