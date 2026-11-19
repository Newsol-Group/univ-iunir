<?php
/**
 *
 * 
 * @package 	Tesis
 * @subpackage 	
 * 
 */
class cn_man_circuitos_tesis extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'circuito_tesis';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_circuitos_tramite')->get_listado_circuitos_tesis($filtro);
	}

}
?>