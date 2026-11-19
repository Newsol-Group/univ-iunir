<?php
class ci_rep_listado_bajas_de_materias_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$datos = guarani::co('co_inscripciones_cursadas')->get_listado_bajas_materias($filtro);
		return $datos;
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