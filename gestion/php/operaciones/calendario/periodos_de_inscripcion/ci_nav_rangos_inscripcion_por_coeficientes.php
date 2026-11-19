<?php
class ci_nav_rangos_inscripcion_por_coeficientes extends ci_nav_sin_listado_inicial
{
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(guarani_pers_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
	function get_datos_periodo()
	{
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_con_rangos($this->s__filtro);
		return $datos[0];
	}
}
?>