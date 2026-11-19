<?php 
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 */
class ci_rep_notas_eval_en_mesa_examen extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_eval_detalle';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_mesas_de_examen')->get_listado_reporte_notas_eval_en_mesa_examen($filtro);
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
	}
}
?>