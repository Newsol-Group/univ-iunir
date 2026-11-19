<?php

use SIU\Chulupi\kernel;

class toba
{

	static protected $mensajes;
	static protected $memoria;
	
	/**
	 * Retorna una clase de consultas php declarada en el editor
	 * @param string $clase Nombre de la clase	 
	 */
	static function consulta_php($clase, $usar_cache=false)
	{
		return new toba_consulta($clase, $usar_cache);
	}
	
	static function db()
	{
		$db = kernel::db();
		//$db->emular_db_toba();
		return $db;
	}
	
	static function perfil_de_datos()
	{
		return new toba_perfil_datos();
	}
	
	static function logger()
	{
		return new toba_logger();
	}

    static function cn($clase)
    {
        return new $clase();
    }

    static function servicio_web_rest($id)
    {
        return kernel::proyecto()->get_ws_cliente($id);
    }

	/**
	 * Obtiene los mensajes del proyecto definidos en el editor, útiles para evitar fijar los mensajes del usuario en el código
	 * @return toba_mensajes
	 */
	static function mensajes()
	{
		if (!isset(static::$mensajes)) {
			static::$mensajes = new toba_mensajes();
		}
		return static::$mensajes;
	}

	/**
	* @ignore
	* @return toba_manejador_sesiones
	*/
	static function manejador_sesiones()
	{
		return toba_manejador_sesiones::instancia();
	}
	
	/**
	* @return toba_memoria
	*/
	static function memoria()
	{
		if (!isset(static::$memoria)) {
			static::$memoria = new toba_memoria();
		}
		return static::$memoria;
	}

}