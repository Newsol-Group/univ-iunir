<?php

namespace siu\modelo\g3;

interface interface_requisitos
{
	/**
	 * Obtiene el conjunto de requisitos documentales que tienen un tipo de requisito
	 * digital asociado, que aplican como requisito de ingreso de las propuestas en las 
	 * que se preinscribe el aspirante.
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	function requisitos_a_solicitar($propuestas);
	
	/**
	 * Obtiene el tipo de documento digital de un requisito dado
	 * 
	 * @param int $requisito
	 * @return string
	 */
	function tipo_documento_digital($requisito);

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @return array
	 */
	function test($id);
	
}
?>