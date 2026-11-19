<?php 
/**
 *
 *
 * @package Responsables Académicas
 * @subpackage documentos
 */
class ci_rep_documentos extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_documentos';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_documentos')->get_listado_reporte($filtro);
	}

}

?>