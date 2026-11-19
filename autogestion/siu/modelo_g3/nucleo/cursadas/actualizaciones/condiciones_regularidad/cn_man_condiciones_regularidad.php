<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_man_condiciones_regularidad extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'condicion_regularidad';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_condiciones_regularidad')->get_listado($filtro);			
	}
}
?>