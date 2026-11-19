<?php

namespace siu\modelo\g3;

interface interface_personas
{
	/**
	 * Determina si un documento pertenece a un alumno ya registrado en Guaraní
	 * 
	 * @param $pais_documento
	 * @param $tipo_documento
	 * @param $nro_documento
	 * @return boolean
	 */
	function es_alumno($pais_documento, $tipo_documento, $nro_documento);
	
	/**
	 * Determina si un documento pertenece a un docente ya registrado en Guaraní
	 * 
	 * @param $pais_documento
	 * @param $tipo_documento
	 * @param $nro_documento
	 * @return boolean
	 */
	function es_docente($pais_documento, $tipo_documento, $nro_documento);
	
	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id = array('pais_documento' => x, 'tipo_documento' => y, 'nro_documento' => 'z')
	 * @return array
	 */
	function test($id);
	
}
?>