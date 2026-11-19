<?php
namespace siu\modelo\g3;

interface interface_parametros 
{
	/**
	 * Obtiene el valor de un parámetro de Guaraní.
	 * LIMITACIÓN: únicamente parámetros definidos en nivel 1 (Para toda la Institución)
	 * 
	 * Uso: para obtener los valores de
	 *  + pre_meses_vigencia_preinsc: Cantidad de meses de vigencia de la preinscripción a propuesta
	 *  + pre_cant_max_propuestas_preinsc: Cantidad máxima de propuestas en las que se puede preinscribir el aspirante
	 * 
	 * @param string $parametro
	 */
	function get_valor($parametro);
	
	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $parametro
	 * @return array
	 */
	function test($parametro);
}
?>