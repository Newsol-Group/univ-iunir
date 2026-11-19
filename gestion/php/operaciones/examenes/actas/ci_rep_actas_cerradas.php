<?php 
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 */
class ci_rep_actas_cerradas extends ci_rep_sin_listado_inicial
{
	protected $s__anio_academico;
	
	function get_tabla_principal()
	{
		return 'sga_actas';
	}

	function get_datos($filtro)
	{
		if (isset($this->s__anio_academico)) {
			return toba::consulta_php('co_actas')->get_listado_reporte_actas_cerradas($this->s__anio_academico, $filtro);
		} else {
			return array();
		}
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
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__anio_academico = $datos['anio_academico']['valor'];
			$this->dep('filtro')->columna('anio_academico')->resetear_estado();
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			unset($this->s__anio_academico);
			unset($this->s__filtro);
		}
	}
	
	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__anio_academico);
		unset($this->s__filtro);
	}
}
?>