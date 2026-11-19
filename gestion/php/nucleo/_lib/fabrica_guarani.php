<?php
/**
 * @package Centrales
 * Esta es la fábrica central de Guaraní3
 */
class fabrica_guarani implements fabrica
{

	static private $actualizaciones_php;
	static private $rest_php;

	static function catalogo_comunes()
	{
		return comunes::instancia();
	}

	static function catalogo_elementos_plan()
	{
		return elemento::instancia();
	}

	static function catalogo_personas()
	{
		return persona::instancia();
	}

	static function db()
	{
		return new guarani_db();
	}

	static function parametros()
	{
		return guarani_parametros::instancia();
	}

	static function catalogo_g3entidades()
	{
		return g3entidad::instancia();
	}

	static function complemento($id_complemento)
	{
		return guarani_complemento::instancia($id_complemento);
	}

	static function catalogo_planes()
	{
		return planes::instancia();
	}

	static function catalogo_modalidades()
	{
		return modalidades::instancia();
	}

	/**
	 * Retorna una clase de actualización de php declarada en el contexto de ejecución
	 * @param string $clase Nombre de la clase	 
	 */
	static function act_php($clase)
	{
		if (!isset(self::$actualizaciones_php[$clase])) {
			self::$actualizaciones_php[$clase] = new $clase();
		}
		return self::$actualizaciones_php[$clase];
	}

	/**
	 * Clase del modelo que implementa la API REST
	 */
	static function rest_php($clase)
	{
		if (!isset(self::$rest_php[$clase])) {
			self::$rest_php[$clase] = new $clase();
		}
		return self::$rest_php[$clase];
	}

	static function error($mensajes, $mensaje_debug = '')
	{
		return new guarani_error($mensajes, $mensaje_debug);
	}

	static function constructor_arbol()
	{
		return new guarani_constructor_arbol();
	}

	static function arbol_nivel($id, $columna_id, $columna_desc, $nombre = null)
	{
		return new guarani_arbol_nivel($id, $columna_id, $columna_desc, $nombre);
	}

	static function proceso_bk($proceso)
	{
		if (class_exists($proceso)) {
			return new $proceso();
		} else {
			throw guarani::error("No existe la clase de proceso $proceso");
		}
	}

	static function invocador_procesos_bk()
	{
		return invocador_procesos_bk::instancia();
	}

}
?>