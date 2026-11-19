<?php
class cache_memoria_apc implements cache_memoria
{
	const salt_clave = '__:dd08f67135c9f036edd938440de45372fa6be123:__';
	
	static protected $instancia;
	
	/**
	 * Retorna una instancia de clase cache_memoria_apc siguiendo el patrón Singleton
	 * 
	 * @return cache_parametros
	 */
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new cache_memoria_apc();
		}
		return self::$instancia;
	}

	/**
	 * Genera una clave única para un dado valor, sea un string o un arreglo de 
	 * valores
	 * 
	 * @param string / array $clave
	 * @return string
	 */
	function generar_clave($clave)
	{
		if (is_array($clave)) {
			$clave = implode(' | ', $clave);
		}
		return sha1(self::salt_clave . $clave);
	}	
	
	/**
	 * Verifica si una clave dada está guardada en caché
	 * 
	 * @param string $clave
	 * @return boolean
	 */
	function existe($clave)
	{
		$retorno = false;
		apcu_fetch($clave, $retorno);
		return $retorno;
	}
	
	/**
	 * Guarda una variable en caché en el almacén de datos.
	 * 
	 * @param string $clave
	 * @param $datos
	 * @param int $expiracion
	 * @return boolean TRUE en caso de éxito o FALSE en caso de error.
	 */
	function guardar($clave, $datos, $expiracion=0)
	{
		return apcu_store($clave, $datos, $expiracion);
	}

	/**
	 * Recupera una variable almacenada desde la caché
	 * 
	 * @param string $clave
	 * @return La variable almacenada o una matriz de variables en caso de éxito; FALSE en caso de error
	 */
	function buscar($clave)
	{
		return apcu_fetch($clave);
	}

	/**
	 * Elimina una variable almacenada de la caché
	 * 
	 * @param string $clave
	 * @return boolean TRUE en caso de éxito o FALSE en caso de error.
	 */
	function eliminar($clave)
	{
		return apcu_delete($clave);
	}	
}
?>