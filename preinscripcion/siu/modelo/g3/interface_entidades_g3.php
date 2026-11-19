<?php
namespace siu\modelo\g3;

interface interface_entidades_g3
{
	/**
	 * Listado de opciones.
	 * 
	 * @param string $condiciones
	 * @param boolean $con_no_seleccion
	 */
	function get_opciones($condiciones = null, $con_no_seleccion = true);
	
	/**
	 * Información completa de una entidad particular
	 * 
	 * @param $id
	 * @return array
	 */
	function get_entidad($id);
	
	/**
	 * Determina si un ID corresponde a un valor existente
	 * 
	 * @param $id
	 * @return boolean
	 */
	function existe($id);
	
	/**
	 * Nombre o campo descriptivo de una entidad particular
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre($id = null);
	
	/**
	 * Nombre o campo descriptivo abreviado de una entidad particular
	 * Si la entidad no lleva registro de nombre abreviado, toma el nombre general
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre_abreviado($id = null);
	
	/**
	 * Nombre o descripción a mostrar en el comprobante PDF. Por defecto se 
	 * retrona el nombre, pero se deja la ventana de extensión por si se necesita
	 * una descripción diferente.
	 * 
	 * @param $id
	 * @return string
	 */
	function get_descr_comprobante($id);
	
	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id
	 * @return array
	 */
	function test($id);
	
}
?>