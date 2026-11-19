<?php

/**
 * Métodos relacionados a la manipulación de arreglos y matrices. 
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_arreglos
{
	/**
     * Toma una matriz y lo aplana a una sola dimension conteniendo los valores de un campo dado. Si no se especifica 
     * un campo, se elige el primero de los recordsets componentes de la matriz. Si un recordset dado contiene un valor
     * nulo para ese campo, entonces dicho valor no se agrega al resultado aplanado.
     * Util para aplanar recordset de consultas o de filas de un datos tabla.
     * Ej: array(0 => array('campo' => 'cero', 'c2' => ...), 1 => array('campo' => 'uno', 'c2' => ...), 
     * 			 2 => array('campo' => null, 'c2' => ...))  (return) --->  array('cero', 'uno')
     */
	static function aplanar_matriz_sin_nulos($matriz, $campo = null)
	{
		$aplanado = array();
		foreach ($matriz as $clave => $arreglo) {
			//Compara igualdad de valores y de tipos.
			if ($campo === null && !is_null(current($arreglo))) {
				$aplanado[$clave] = current($arreglo);
			} elseif (isset($arreglo[$campo]) && !is_null($arreglo[$campo])) {
				$aplanado[$clave] = $arreglo[$campo];
			}			
		}
		return $aplanado;
	}

	/**
	 * Elimina los indices de una matriz cuyos valores son nulos
	 * @param array $matriz
	 * @return array
	 */
	static function matriz_sin_nulos($matriz)
	{
		foreach ($matriz as $clave => $valor) {
			if ($valor === null) {
				unset($matriz[$clave]);
			}
		}
		return $matriz;
	}
	
	/**
	 * Adaptación del count de PHP para que en la versión 7.2 no se muestre el 
	 * warning: "Parameter must be an array or an object that implements Countable".
	 * @param mixed $dato
	 * @return int 0 o el tamaño de un arreglo u objeto contable
	 */
	static function count($dato) {
		return (is_array($dato) || $dato instanceof Countable) ? count($dato) : 0;
	}
	
	/**
	 * Alias de count.
	 * @param mixed $dato
	 * @return int 0 o el tamaño de un arreglo u objeto contable
	 */
	static function sizeof($dato) {
		return static::count($dato);
	}
}
?>