<?php
class ci_rep_alumnos_relacionados_a_divisiones extends ci_rep_sin_listado_inicial
{
	protected $filtro_turno_preferido = '';

	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}

	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_reporte_alumnos_relacionados_a_divisiones($filtro, $this->filtro_turno_preferido);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('turno_preferido')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->filtro_turno_preferido = '';
			$filtro_sin_division = '';

			if (isset($datos['turno_preferido'])) {

				//Condición para el Turno Preferido del alumno
				$turno_preferido_nombre = toba::consulta_php('co_comisiones')->get_nombre_turno_cursada($datos['turno_preferido']['valor']);
				$this->filtro_turno_preferido = 'turno_preferido = ' . quote($turno_preferido_nombre);
				$this->dep('filtro')->columna('turno_preferido')->resetear_estado();
			}

			if (isset($datos['division']) && ($datos['division']['valor'] == 'N')) {
				// Condición para el caso de que se deseen traer alumnos sin división asignada.
				$filtro_sin_division .= $datos['division']['condicion'] == 'es_igual_a' ? " AND sga_alumnos.division IS NULL" : " AND sga_alumnos.division IS NOT NULL";
				$this->dep('filtro')->columna('division')->resetear_estado();
			}

			$this->s__filtro = $this->dep('filtro')->get_sql_where() . $filtro_sin_division;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		parent::evt__filtro__cancelar();
		$this->filtro_turno_preferido = '';
	}

}
?>