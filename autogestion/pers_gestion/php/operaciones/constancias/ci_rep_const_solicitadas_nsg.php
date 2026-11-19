<?php
/**
 *
 * 
 * @package 	Constancias y Certificados
 * 
 */
class ci_rep_const_solicitadas_nsg extends ci_rep_sin_listado_inicial 
{
	
	function get_tabla_principal()
	{
		return 'sga_constancias_solicitud';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_constancias')->get_listado_const_solicitadas($filtro);
	}
	
}
?>