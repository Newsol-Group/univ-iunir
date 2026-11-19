<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_morosos_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'int_sq_morosos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_morosos_perfil_de_datos($filtro);
	}

}

?>