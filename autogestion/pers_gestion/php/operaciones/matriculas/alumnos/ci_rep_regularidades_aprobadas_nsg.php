<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Reportes
 * 
 */
class ci_rep_regularidades_aprobadas_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'vw_regularidades_basica';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado_regularidades_aprobadas_sin_tfi($filtro);
	}

}
?>