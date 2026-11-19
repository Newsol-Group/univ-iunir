<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Reportes
 * 
 */
class ci_rep_alumnos_por_colacion extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_certificados_otorg';
	}

	function get_datos($filtro)
	{
		// Que no liste los tramites de egreso anulados.
		$filtro_reporte = "sga_certificados_otorg.anulado = 0";
		if ($filtro) {
			$filtro_reporte .= " AND " . $filtro;
		} 
		
		// Recupera los alumnos de una colación de grado.
		return toba::consulta_php('co_tramites_certificacion')->get_listado($filtro_reporte);
	}
}
?>
