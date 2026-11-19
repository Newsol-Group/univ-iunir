<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_man_sanciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'sancion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_sanciones')->get_sanciones_perfil_de_datos($filtro);
	}
}
?>