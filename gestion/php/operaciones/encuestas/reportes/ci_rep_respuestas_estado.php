<?php
/**
 *
 *
 * @package		Encuestas
 * @subpackage	Reportes
 * 
 */
class ci_rep_respuestas_estado extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'gde_encuestas_pendientes';
	}

	function get_datos($filtro)
	{
		$datos = toba::consulta_php('co_encuestas')->get_listado_respuestas($filtro);
		return $datos;
	}

}
?>