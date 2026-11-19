<?php
class ci_rep_actas_comisiones extends ci_rep_actas
{
	protected $s__propuesta = null;
	
	// Manejo de filtros especiales
	protected $s__filtros_especiales = array('propuesta', 'instancia', 'rectifica_a', 'acta_vacia', 'version_desactualizada');
	protected $s__parametros_filtro = array();
	
	function get_datos($filtro)
	{
		$actas = toba::consulta_php('co_actas')->get_listado_reporte_actas_comisiones($this->s__parametros_filtro, $filtro);
		return $this->formatear_datos_cuadro($actas);
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
		
		foreach ($this->s__filtros_especiales as $columna) {
			$filtro->columna($columna)->set_condicion_fija('es_igual_a');
		}
	}
	
	function evt__filtro__filtrar($datos)
	{
		//---- Manejo de columnas optativas ------------------------------------
		if (isset($datos)) {
			if (isset($datos['columnas_opc'])) {
				$this->s__columnas = $datos['columnas_opc']['valor'];
				$this->dep('filtro')->columna('columnas_opc')->resetear_estado();
			} else {
				$this->s__columnas = array();
			}
		}
		
		foreach ($this->s__filtros_especiales as $columna) {
			if (!empty($datos[$columna])) {
				$this->s__parametros_filtro[$columna] = $datos[$columna]['valor'];	
				$this->dep('filtro')->columna($columna)->resetear_estado();
			} else {
				$this->s__parametros_filtro[$columna] = null;
			}
		}
		
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			unset($this->s__filtro);
		}
	}
	
	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
		foreach ($this->s__filtros_especiales as $columna) {
			$this->s__parametros_filtro[$columna] = null;
		}
		$this->s__columnas = array();
	}
}