<?php
/**
 * Interface que debe implementar una extensión o redefinición de un complemento de guarani que se desea mostrar en la pantalla de inicio
 */
interface guarani_interface_complemento
{
	/**
	 * Setea los Datos Basicos del complemento (los que surgen de la tabla mdp_complementos)
	 * @return array Datos Basicos del Complemento Implementado
	 */
	function set_datos_basicos();

	/**
	 * Devuleve el ID del complemento
	 * @return string
	 */
	function get_id();
	
	/**
	 * Devuelve los Datos Basicos del complemento 
	 * @return array Datos Basicos del Complemento Implementado
	 */
	function get_datos_basicos();

	/**
	 *  @return string Ubicacion del Complemento en la pantalla
	 */
	function get_ubicacion();

	/**
	 * @return string Nombre de la clase que implementa el complemento
	 */
	function get_clase_implementacion();

	//-- Interface Gráfica --------------------------------------------

	/**
	 * Devuelve el codigo HTML para mostrar el complemento en la Pantalla de Inicio
	 * @return string Codigo HTML que muestra el complemento 
	 */
	function generar_layout();

}
?>