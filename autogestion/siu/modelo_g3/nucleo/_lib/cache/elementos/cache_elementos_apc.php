<?php
class cache_elementos_apc extends cache_consulta_apc
{
	const raiz_clave = '__CACHE_ELEMENTOS__';
        const clase_consulta = 'co_elementos';

	static protected $instancia;

	/**
	 * Retorna una instancia de clase cache_elementos_apc siguiendo el patrón Singleton
	 * 
	 * @return cache_elementos_apc
	 */
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new cache_elementos_apc();
		}
		return self::$instancia;
	}
}