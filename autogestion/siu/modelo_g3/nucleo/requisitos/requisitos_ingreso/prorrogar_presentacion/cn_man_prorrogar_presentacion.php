<?php
/**
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 */
class cn_man_prorrogar_presentacion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'prorrogar_presentacion';
	}

	function get_listado_prorrogar_presentacion($filtro='true')
	{
		$filtro .= ' AND sga_periodos_inscripcion.periodo_generico_tipo = ' . periodo_generico::tipo_propuesta;
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		return empty($perfil_datos) ? toba::consulta_php('co_periodos_de_inscripcion')->get_listado($filtro) : toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_perfil_de_datos($filtro);
	}
}