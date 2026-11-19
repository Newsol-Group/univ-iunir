<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_man_tipos_de_sanciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tipo_sancion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_sanciones')->get_listado_tipos($filtro);			
	}
}
?>