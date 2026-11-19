<?php

namespace siu\modelo\g3;

interface interface_responsables_academicas extends interface_entidades_g3
{
	/**
	 * Obtiene todas las Responsables Académicas sin perfil de datos
	 * 
	 * @param string $condiciones
	 * @return array
	 */
	function get_opciones_sin_perfil_datos($condiciones = null);
		
}
?>