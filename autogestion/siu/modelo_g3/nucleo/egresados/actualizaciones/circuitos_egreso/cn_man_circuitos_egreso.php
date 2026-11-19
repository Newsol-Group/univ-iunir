<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_circuitos_egreso extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'circuito_egreso';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_circuitos_tramite')->get_listado_circuitos_egreso($filtro);
	}
}
?>