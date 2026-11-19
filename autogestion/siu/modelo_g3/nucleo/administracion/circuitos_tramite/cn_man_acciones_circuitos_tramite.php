<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Circuitos
 * 
 */
class cn_man_acciones_circuitos_tramite extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'accion_circuito_tramite';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_circuitos_tramite')->get_listado_acciones($filtro);
	}
}
?>