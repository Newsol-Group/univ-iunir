<?php 
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 */
class ci_rep_inscripciones_examen extends ci_rep_sin_listado_inicial
{

	protected $s__filtro_requisito_no_cumplido;

	function get_tabla_principal()
	{
		return 'sga_insc_examen';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_examenes')->get_listado_reporte_insc_examen($filtro, $this->s__filtro_requisito_no_cumplido);
	}

	function get_requisitos_examen()
	{
		$accion_examen='2';//accion por examen
		return toba::consulta_php('co_requisitos_x_accion')->get_requisitos_x_accion_distinct($accion_examen);
	}
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		$filtro->columna('requisito_no_cumplido')->set_condicion_fija('es_igual_a');
	}
		/**
		* Redefinición del evento filtrar, para que arme una cadena WHERE acorde con la
		* nueva estructura de modalidades de cursada de comisión.
		*/
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro_requisito_no_cumplido = '';

			if (isset($datos['requisito_no_cumplido'])) {
				$this->s__filtro_requisito_no_cumplido = "AND vw_insc_examen.operacion = 'R' 
						AND EXISTS (SELECT 1 FROM sga_insc_examen_log_requisitos 
					WHERE inscripcion = vw_insc_examen.inscripcion AND sga_insc_examen_log_requisitos.requisito =" . $datos['requisito_no_cumplido']['valor']. ")";
			}

			$this->dep('filtro')->columna('requisito_no_cumplido')->resetear_estado();
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>