<?php

namespace siu\modelo\g3;

interface interface_extensiones
{
	/**
	 * Obtiene las extensiones aceptadas para los archivos RDI
	 * @return array
	 */
	function get_extensiones_permitidas();

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @return array
	 */
	function test();
}
?>