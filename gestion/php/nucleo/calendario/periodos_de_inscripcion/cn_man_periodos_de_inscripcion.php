<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Períodos de Inscripción
 */
class cn_man_periodos_de_inscripcion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'periodo_inscripcion';
	}
	
	function get_listado($filtro)
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		return empty($perfil_datos) ? toba::consulta_php('co_periodos_de_inscripcion')->get_listado($filtro) : toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_perfil_de_datos($filtro);
	}
	
	function get_proceso_aplanado()
	{
		return 'proceso_aplanar_periodo_inscripcion';
	}
}

?>