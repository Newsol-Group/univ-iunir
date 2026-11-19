<?php 
/**
 *
 * @package 	Docentes
 * @subpackage 	
 */
class ci_rep_docentes_nsg extends ci_rep_sin_listado_inicial
{
	
	function get_tabla_principal()
	{
		return 'sga_docentes';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_docentes')->get_listado($filtro);
	}

}
?>