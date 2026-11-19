<?php
class cache_personas_apc extends cache_consulta_apc
{
	const clase_consulta = 'co_personas';
        const tiempo_cache = 300; // 5 minutos
        const raiz_clave = '__CACHE_PERSONAS__';

        static protected $instancia;
        
	/**
	 * Retorna una instancia de clase cache_personas_apc siguiendo el patrón Singleton
	 *
	 * @return cache_personas_apc
	 */
	static function instancia()
	{
            if (!isset(static::$instancia)) {
                static::$instancia = new cache_personas_apc();
            }
            return static::$instancia;
	}
}