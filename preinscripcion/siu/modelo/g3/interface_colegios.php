<?php

namespace siu\modelo\g3;

interface interface_colegios extends interface_entidades_g3
{
	/**
	 * Obtiene una lista de colegios a partir de una parte de su nombre y su ciudad.
	 * Para usar con el autocomplete de colegios secundarios.
	 * 
	 * @param string $colegio_nombre
	 * @param int $localidad
	 * @return array
	 */
	function get_colegios($colegio_nombre, $localidad);
	
}
?>