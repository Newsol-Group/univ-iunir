<?php

use SIU\Chulupi\kernel;

class toba_memoria
{
	
	/**
	 * Almacena un dato en la sesion del proyecto y perdura durante toda la sesión
	 * Similar al manejo normal del $_SESSION en una aplicacion ad-hoc
	 */
	function set_dato($indice, $datos)
	{
		kernel::sesion()->set($indice, $datos);
	}
	
    /**
	 * Recupera un dato almacenado con set_dato
	 * @return mixed Si el dato existe en la memoria lo retorna sino retorna null
	 */
	function get_dato($indice)
	{
		if(kernel::sesion()->esta_seteada($indice))	{
			return kernel::sesion()->get($indice);
		}else{
			return null;
		}
	}
	
	/**
	 * Elimina un dato de la memoria
	 */
	function eliminar_dato($indice)
	{
		if(kernel::sesion()->esta_seteada($indice)){
			kernel::sesion()->borrar($indice);
		}
	}
	
	/**
	 * Determina si un dato esta disponible en la memoria
	 */	
	function existe_dato($indice)
	{
		return kernel::sesion()->esta_seteada($indice);
	}
	
}