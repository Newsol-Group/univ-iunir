<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_sanciones extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_sanciones';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_sanciones')->get_sanciones_perfil_de_datos($filtro);
	}
}
?>