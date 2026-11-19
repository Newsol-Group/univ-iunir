<?php
/**
 *
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_anular_tramite_certificacion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'anular_tramites_certificacion';
	}

	function get_listado($filtro)
	{
		$filtro = empty($filtro) ? 'true' : $filtro;
		// Sólo solicitudes no anuladas.
		$filtro .= ' AND sga_certificados_otorg.anulado = 0';
		// Sólo solicitudes que no estén en el estado final del circuito.
		$filtro .= ' AND (sga_certificados_otorg.estado IS NULL OR EXISTS (	SELECT	mce_caminos.camino
																			FROM	mce_caminos
																			WHERE	mce_caminos.circuito = sga_certificados_otorg.circuito AND
																					mce_caminos.estado_inicial = sga_certificados_otorg.estado AND
																					mce_caminos.estado_final <> sga_certificados_otorg.estado))';
		return toba::consulta_php('co_tramites_certificacion')->get_listado($filtro);
	}
}
?>