<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Reportes
 * 
 */
class ci_rep_total_inscriptos_cursada_periodo_testing_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_insc_cursada';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_listado_total_inscriptos_cursada_testing($filtro);
	}

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}

		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		//$filtro->columna('periodo')->set_condicion_fija('es_igual_a');
	}

}
?>