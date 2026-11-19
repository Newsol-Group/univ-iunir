<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Años Académicos
 */
class cn_man_anios_academicos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'anio_academico';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_anios_academicos')->get_listado($filtro);			
	}
}

?>