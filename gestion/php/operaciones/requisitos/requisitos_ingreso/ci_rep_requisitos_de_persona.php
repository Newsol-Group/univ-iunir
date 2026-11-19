<?php 
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_rep_requisitos_de_persona extends ci_rep_sin_listado_inicial
{
	
	function get_tabla_principal()
	{
		return 'sga_requisitos_aspirante';
	}
	
	function get_datos($filtro)
	{		
		return toba::consulta_php('co_requisitos_ingreso')->get_listado_reporte($filtro);
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']['valor']);
				$filtro_ubicacion = " 
					AND EXISTS (
						SELECT 	1
						FROM	sga_alumnos
						WHERE	vw_personas.persona = sga_alumnos.persona
								AND sga_alumnos.ubicacion = $ubicacion
					) ";
				$this->dep('filtro')->columna('ubicacion')->resetear_estado();
			} else {
				$filtro_ubicacion = '';
			}
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
			$this->s__filtro .= $filtro_ubicacion;
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}
	
}
?>