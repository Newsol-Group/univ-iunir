<?php

namespace siu\modelo\g3;

interface interface_localidades extends interface_entidades_g3
{
	/**
	 * Obtiene una lista de localidades a partir de una parte de su nombre y el país.
	 * Para usar principalmente con el autocomplete de localidades.
	 * 
	 * @param string $localidad_nombre
	 * @param int $pais
	 * @return array
	 */
	function get_lista_localidades($localidad_nombre = '', $pais = null);
	
}
?>