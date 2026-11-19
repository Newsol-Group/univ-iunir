<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage	Regularidad
 */
class ci_rep_alumnos_readmitidos extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_readmisiones';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_readmisiones')->get_readmisiones_alumnos($filtro);
	}
	
}
?>