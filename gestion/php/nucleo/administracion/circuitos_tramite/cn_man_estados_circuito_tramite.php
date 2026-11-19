<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Circuitos
 * 
 */
class cn_man_estados_circuito_tramite extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'estado_circuito_tramite';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_circuitos_tramite')->get_listado_estados($filtro);
	}
}
?>