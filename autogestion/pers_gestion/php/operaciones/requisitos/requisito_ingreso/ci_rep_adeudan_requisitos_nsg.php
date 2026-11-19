<?php 
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_rep_adeudan_requisitos_nsg extends ci_rep_adeudan_requisitos 
{
	function get_tabla_principal()
	{
		return 'sga_requisitos_aspirante';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_requisitos_ingreso')->get_alumnos_deudores($filtro);
	}
}
?>