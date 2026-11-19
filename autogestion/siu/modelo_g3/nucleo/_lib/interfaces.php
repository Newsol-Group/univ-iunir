<?php

/**
 * @package Central
 * @subpackage Entidades
 */
interface i_manejador_entidad
{
	function get_listado($filtro=null);
	function get_indice_instancia();
	function ini();
	function nueva();
	function cargar($id);
	function entidad_es_nueva($id=null);
	function entidad($id=null);
	function seleccionar($id);
	function eliminar($id=null);
	function guardar();
	function resetear();
}
 
interface i_entidad
{
	function inicializar_estado();
	function cargar($id);
	function sincronizar();
	function eliminar();
	function resetear();
}


?>