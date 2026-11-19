<?php

namespace siu\modelo\g3;

interface interface_propuestas extends interface_entidades_g3
{
	/**
	 * Obtiene el ID de tipo de propuesta a partir de una propuesta dada.
	 * 
	 * @param int $propuesta
	 * @return int
	 */
	function get_id_tipo_propuesta($propuesta);
	
	/**
	 * Obtiene la DESCRIPCIÓN de tipo de propuesta a partir de una propuesta dada.
	 * 
	 * @param int $propuesta
	 * @return string
	 */
	function get_tipo_propuesta($propuesta);
	
	/**
	 * Obtiene la lista de todos los tipos de propuesta (sin perfil de datos)
	 */
	function get_tipos_propuesta_sin_perfil_datos();
	
	/**
	 * Obtiene las ubicaciones donde se ofrece una propuesta
	 * 
	 * @param int $propuesta
	 * @return array
	 */
	function get_ubicaciones_propuesta($propuesta);
	
	/**
	 * Obtiene las modalidades disponibles para una propuesta
	 * 
	 * @param int $propuesta
	 * @return array
	 */
	function get_modalidades_propuesta($propuesta , $ubicacion);
	
	/**
	 * Obtiene todas las propuestas disponibles para Preinscripción, a partir de
	 * una Responsable Académica dada, excluyendo las recibidas como segundo parámetro
	 * 
	 * @param int $ra
	 * @param array $excluir propuestas a excluir del listado
	 * @return array
	 */
	function get_propuestas_disponibles_preinscripcion($ra, $excluir = array());
	
	/**
	 * Obtiene el listado de propuestas que tienen planes vigentes, sin pasar
	 * por el filtro del perfil de datos
	 * 
	 * @return array
	 */
	function get_propuestas_sin_perfil_datos();
	
}
?>