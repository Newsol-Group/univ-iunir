<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Circuitos
 * 
 */
class cn_man_formularios_tramite extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'formulario_tramite';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_circuitos_tramite')->get_listado_formularios($filtro);
	}
}
?>