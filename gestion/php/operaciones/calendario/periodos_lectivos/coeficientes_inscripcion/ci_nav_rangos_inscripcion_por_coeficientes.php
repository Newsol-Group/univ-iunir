<?php
class ci_nav_rangos_inscripcion_por_coeficientes extends ci_nav_sin_listado_inicial
{
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
	}

	function get_datos_periodo()
	{
		$filtro = "	vw_periodos_inscripcion.periodo_insc = {$this->s__seleccion['periodo_insc']} AND
					vw_planes.propuesta = {$this->s__seleccion['propuesta']}";
		return current(toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_con_rangos($filtro));
	}

	function get_seleccion()
	{
		return $this->s__seleccion;
	}
}
?>