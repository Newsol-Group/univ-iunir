<?php 
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 */
class ci_rep_mesas_examen_sin_actas extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_mesas_examen';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_mesas_de_examen')->get_listado_reporte_mesas_examen_sin_acta_generada($filtro);
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
		$filtro->columna('instancia')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
	}

	/**
	 * Redefinición del evento filtrar, para que arme una cadena WHERE acorde con la
	 * nueva estructura de modalidades de cursada de comisión.
	 */
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro = '';
			
			if (isset($datos['instancia'])) {
				$filtro .= " AND EXISTS (SELECT * FROM sga_mesas_examen_instancias WHERE sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen AND sga_mesas_examen_instancias.instancia = '{$datos['instancia']['valor']}')";
				$this->dep('filtro')->columna('instancia')->resetear_estado();
			}
			
			if (isset($datos['propuesta'])) {
				$filtro .= " AND EXISTS (SELECT * FROM sga_mesas_examen_propuestas WHERE sga_mesas_examen_propuestas.mesa_examen = sga_mesas_examen.mesa_examen AND sga_mesas_examen_propuestas.propuesta = '{$datos['propuesta']['valor']}')";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>