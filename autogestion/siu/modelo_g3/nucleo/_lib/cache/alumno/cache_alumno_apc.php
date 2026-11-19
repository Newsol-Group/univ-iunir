<?php
class cache_alumno_apc extends cache_consulta_apc
{
        const raiz_clave = '__CACHE_ALUMNO__';
        const tiempo_cache = 300; // 5 minutos
        const clase_consulta = 'co_alumnos';

	static protected $instancia;

	/**
	 * Retorna una instancia de clase cache_alumno_apc siguiendo el patrón Singleton
	 * 
	 * @return cache_alumno_apc
	 */
	static function instancia()
	{
            if (!isset(static::$instancia)) {
                static::$instancia = new cache_alumno_apc();
            }
            return static::$instancia;
	}
        
	/**
	 * Obtiene el nombre a partir del ID de alumno
	 * 
	 * @param integer	
	 */
	function get_alumno_nombre($alumno)
	{
		if (!isset($alumno) || trim($alumno) == '') {
			return array();
		}
                // Aunque no esta declarada la función 'get_datos_alumno'
                // la carga desde  la función '__call' de 'php/nucleo/_lib/cache/cache_consulta_apc.php'
                // ver: https://www.php.net/manual/en/language.oop5.overloading.php#object.call
		$result = $this->get_datos_alumno($alumno);
		if (!empty($result)) {
			return $result[0]['apellido_y_nombre'];
		}
	}
	
	/**
	 * Obtiene el nombre de la propuesta de un alumno dado.
	 *
	 * @param integer $alumno
	 * @return string
	 */
	function get_alumno_propuesta_nombre($alumno)
	{
		if (!isset($alumno) || trim($alumno) == '') {
			return array();
		}
                // Aunque no esta declarada la función 'get_datos_alumno'
                // la carga desde  la función '__call' de 'php/nucleo/_lib/cache/cache_consulta_apc.php'
                // ver: https://www.php.net/manual/en/language.oop5.overloading.php#object.call
		$result = $this->get_datos_alumno($alumno);
		if (!empty($result)) {
			return $result[0]['propuesta_nombre'];
		}
	}
}