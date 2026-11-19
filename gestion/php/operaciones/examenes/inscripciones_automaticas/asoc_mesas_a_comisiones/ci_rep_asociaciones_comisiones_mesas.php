<?php
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 * 
 */
class ci_rep_asociaciones_comisiones_mesas extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_insc_auto_examen';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_comisiones')->get_comisiones_asociadas_a_mesas($filtro);
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		
		$filtro->columna('instancia')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (	SELECT 	1
													FROM 	sga_mesas_examen_modalidad
													WHERE 	sga_mesas_examen_modalidad.mesa_examen = sga_mesas_examen.mesa_examen 
															AND sga_mesas_examen_modalidad.modalidad = '{$datos['modalidad']['valor']}') ";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			
			$filtro_instancia = '';
			if (isset($datos['instancia'])) {
				$filtro_instancia .= " AND EXISTS ( SELECT 	1
													FROM 	sga_mesas_examen_instancias
													WHERE 	sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen
															AND sga_mesas_examen_instancias.instancia = '{$datos['instancia']['valor']}') ";
				$this->dep('filtro')->columna('instancia')->resetear_estado();
			}
			
			$filtro_propuesta = '';
			if (isset($datos['propuesta'])) {
				$filtro_propuesta .= " AND EXISTS (	SELECT 	1 
													FROM 	sga_comisiones_propuestas 
													WHERE 	sga_comisiones_propuestas.comision = sga_comisiones.comision 
															AND sga_comisiones_propuestas.propuesta = '{$datos['propuesta']['valor']}')";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad $filtro_instancia $filtro_propuesta";
			
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>