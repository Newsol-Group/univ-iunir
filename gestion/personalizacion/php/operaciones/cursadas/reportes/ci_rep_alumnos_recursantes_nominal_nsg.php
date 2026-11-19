<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Reportes
 * 
 */
class ci_rep_alumnos_recursantes_nominal_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_insc_cursada';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_alumnos_recursantes_nominal($filtro);
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
	}

}
?>