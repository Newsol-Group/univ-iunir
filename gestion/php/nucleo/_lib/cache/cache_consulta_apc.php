<?php
class cache_consulta_apc
{
	const tiempo_cache = 600; // 10 minutos
        const clase_consulta = 'co_xxx';
        const raiz_clave = '__CACHE_XXX__';

        /**
	 * Clave de caché para los datos de una función dada
	 *
	 * @param int $parametros
	 * @param string $funcion
	 *
	 * @return string
	 */
	protected function get_clave_cache($parametros, $funcion)
	{
            $params = implode(' | ', $parametros);
            return static::raiz_clave . $funcion . '__' . $params;
	}

        /**
	 * Método mágico para cachear resultados de las funciones de las clases de consultas
	 * https://www.php.net/manual/en/language.oop5.overloading.php#object.call
         * 
	 * @param string $funcion Función de la clase de consulta
	 * @param array $parametros Parámetros de la función
	 *
	 * @return array Resultado
	 */
        public function __call($funcion, $parametros)
        {
            $clase = static::clase_consulta;
            $clave = $this->get_clave_cache($parametros, $funcion);
            if (guarani::cache()->existe($clave)) {
                $datos = guarani::cache()->buscar($clave);
                toba::logger()->notice("Función '{$clase}:{$funcion}' con parámetros ". var_export($parametros, true)." resuelta por caché");
            } else {
                // https://www.php.net/manual/en/functions.arguments.php#functions.variable-arg-list
                $datos = toba::consulta_php($clase)->$funcion(...$parametros);
                guarani::cache()->guardar($clave, $datos, static::tiempo_cache);
            }
            return $datos;
        }
}