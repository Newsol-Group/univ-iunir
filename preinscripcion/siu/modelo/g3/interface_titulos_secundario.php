<?php

namespace siu\modelo\g3;

interface interface_titulos_secundario extends interface_entidades_g3
{
	/**
	 * Obtiene una lista de títulos secundarios a partir de una parte de su nombre.
	 * Para usar con el selector de títulos secundario.
	 * 
	 * @param string $titulo_nombre
	 * @return array
	 */
	function get_titulos($titulo_nombre);
	
}
?>