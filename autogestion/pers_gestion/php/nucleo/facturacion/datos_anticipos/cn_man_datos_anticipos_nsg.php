<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_man_datos_anticipos_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'datoanticipo';
	}
	
	function etiquetas($dep=null)
	{
		return guarani_etiquetas::get_tabla('sga_alumnos');

	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($filtro);
	}
}
?>