<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_man_responsabilidades_docentes extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'resp_docente';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_responsabilidades_docentes')->get_listado($filtro);			
	}
}
?>