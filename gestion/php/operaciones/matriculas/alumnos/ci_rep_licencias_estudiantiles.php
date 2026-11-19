<?php
/**
 *
 * 
 * @package 	Matrícula
 * @subpackage 	Reportes
 * 
 */
class ci_rep_licencias_estudiantiles extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_licencias';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_licencias')->get_listado_reporte_licencias($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_propuesta = '';
			if ((isset($datos['propuesta']['valor']))) {
				$propuesta = toba::db()->quote($datos['propuesta']['valor']);
				$filtro_propuesta .= " AND $propuesta IN (
										SELECT 	sga_licencias_propuestas.propuesta
										FROM	sga_licencias_propuestas
										WHERE	sga_licencias_propuestas.licencia = sga_licencias.licencia
										)";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_propuesta";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>