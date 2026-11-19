<?php
/**
 * 
 * 
 * @package 	Propuestas Formativas
 * @subpackage 	Reportes
 *
 */
class ci_rep_competencias_otorgadas extends ci_rep_sin_listado_inicial
{
	
	function get_tabla_principal()
	{
		return 'sga_competencias_x_actividad';
	}

	function get_datos($filtro)
	{
		return guarani::co('co_competencias')->get_competencias_otorgadas($filtro);
	}

}
?>