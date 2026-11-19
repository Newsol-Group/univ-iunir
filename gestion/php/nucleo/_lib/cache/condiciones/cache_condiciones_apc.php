<?php
class cache_condiciones_apc extends cache_consulta_apc
{
	const raiz_clave = '__CACHE_CONDICIONES__';
        const clase_consulta = 'co_condiciones';
	
	static protected $instancia;

	/**
	 * Retorna una instancia de clase cache_condiciones_apc siguiendo el patrón Singleton
	 * 
	 * @return cache_condiciones_apc
	 */
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new cache_condiciones_apc();
		}
		return self::$instancia;
	}
	
}