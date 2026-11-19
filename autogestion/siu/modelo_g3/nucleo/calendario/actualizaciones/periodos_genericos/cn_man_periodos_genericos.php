<?php
/**
 *	
 *
 * @package Calendario
 * @subpackage Períodos Genéricos
 */
class cn_man_periodos_genericos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'periodo_generico';
	}
	
	function get_listado($filtro)
	{
		$where = "sga_periodos_genericos_tipos.periodo_generico_tipo = ".periodo_generico::tipo_periodo_lectivo;
		if ($filtro) {
			$where .= "	AND $filtro";
		}
		return toba::consulta_php('co_periodos_genericos')->get_listado($where);
	}
}

?>