<?php
/**
 *
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_man_docente_resp_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'docentes_resp';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_docentes')->get_listado_docente_resp($filtro);			
	}
}
?>