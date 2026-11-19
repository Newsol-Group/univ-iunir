<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Reportes
 * 
 */
class ci_rep_retencion_alumnos_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_insc_cursada';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_retencion_alumnos($filtro);
	}

}
?>