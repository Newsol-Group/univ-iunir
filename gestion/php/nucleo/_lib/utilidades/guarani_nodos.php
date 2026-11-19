<?php 

/**
 * Mètodos para la manipulación de objetos nodos que extienden de toba_nodo_basico.
 * No son funciones que actuen sobre los objetos propiamente dichos, si no sobre estructuras que los contienen.
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_nodos
{
	/**
 	* Permite determinar si en un arreglo de nodos dado, que conforman las raíces de un arbol, existe un nodo o raíz
 	* con la clave indicada. Si es así se retorna dicho objeto nodo.
 	* @param	string	$clave_nodo es la clave del nodo a buscar en el arreglo.
 	* @param	array	$raices es el arreglo de objetos de tipo nodo sobre los cuales se desea buscar dicha clave.
 	*/
	static function existe_nodo_raiz ($clave_nodo, $raices)
	{
		$objeto_raiz = null;
		foreach ($raices as $raiz) {
			if ($raiz->get_id() == $clave_nodo) {
				$objeto_raiz = $raiz;	
				break;
			}	
		}
		return $objeto_raiz;
	}

}

?>