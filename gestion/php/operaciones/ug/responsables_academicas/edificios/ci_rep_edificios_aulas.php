<?php 
/**
 *
 *
 * @package Responsables Académicas
 * @subpackage Edificios
 */
class ci_rep_edificios_aulas extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_edificios';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_edificios')->get_listado_reporte($filtro);
	}

}

?>