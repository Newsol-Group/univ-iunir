<?php
/**
 *
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_suplementos_titulo extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'suplementos_titulo';
	}

	function get_listado($filtro)
	{
		// Sólo trámites de egreso activos (no anulados) 
		return toba::consulta_php('co_tramites_certificacion')->get_tramites_egreso_activos($filtro);
	}
}
?>