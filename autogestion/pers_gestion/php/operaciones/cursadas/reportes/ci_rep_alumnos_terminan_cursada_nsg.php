<?php
class ci_rep_alumnos_terminan_cursada_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'vw_persona';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_terminan_cursada($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}

/*		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo')->set_condicion_fija('es_igual_a');*/
	}


}
?>