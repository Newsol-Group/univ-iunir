<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class ci_rep_tipo_propuestas extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_propuestas_tipos';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_propuestas_tipos_reporte($filtro);
	}

}

?>