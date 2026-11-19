<?php

namespace siu\modelo\g3;

interface interface_paises extends interface_entidades_g3
{
	/**
	 * Determina si, de acuerdo a lo configurado en G3, un país requiere visa
	 * 
	 * @param int $pais
	 * @return boolean
	 */
	function necesita_visa($pais);
	
	/**
	 * Determina si, de acuerdo a lo configurado en G3, un país permite cédula 
	 * 
	 * @param int $pais
	 * @return boolean
	 */
	function permite_cedula($pais);
	
}
?>