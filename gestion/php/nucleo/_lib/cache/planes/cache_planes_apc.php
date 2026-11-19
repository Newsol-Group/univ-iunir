<?php
class cache_planes_apc extends cache_consulta_apc
{
	const clase_consulta = 'co_planes';
	const tiempo_cache = 300; // 5 minutos
	const raiz_clave = '__CACHE_PLANES__';

	static protected $instancia;

	/**
	 * Retorna una instancia de clase cache_planes_apc siguiendo el patrón Singleton
	 *
	 * @return cache_planes_apc
	 */
	static function instancia()
	{
		if (!isset(static::$instancia)) {
			static::$instancia = new cache_planes_apc();
		}
		return static::$instancia;
	}
}