<?php
/**
 *
 * @package Calendario
 * @subpackage Periodos Lectivos
 */
class cn_man_rango_inscripcion_por_coeficientes extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'rango_por_coeficiente';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_con_rangos($filtro);
	}
}
?>