<?php
/**
 *
 *
 * @package		Matriculas
 * @subpackage	Inscripciones
 * 
 */
class cn_ent_insc_prop extends cn_entidad
{
	function sincronizar()
	{
		try {
			$this->dep('datos')->sincronizar();
			// Verificar si es necesario actualizar estado de inscripción
			preinscripcion::actualizar_estado_inscripcion($this->get_columna_prop_asp('propuesta_aspira'));
		} catch (toba_error $e) {
			throw $e;
		}
	}

	function cargar_datos($clave)
	{
		$this->dep('datos')->cargar($clave);
	}

	//--------------------------------------------------------------------------
	//---- Tabla: sga_propuestas_aspira (prop_asp) -----------------------------
	//--------------------------------------------------------------------------

	function set_datos_prop_asp($datos)
	{
		$this->tabla('prop_asp')->set($datos);
	}

	function get_datos_prop_asp()
	{
		return $this->tabla('prop_asp')->get_filas();
	}

	function cargar_datos_prop_asp($condiciones)
	{
		$this->dep('datos')->tabla('prop_asp')->cargar($condiciones);
	}

	function agregar_prop_asp($datos)
	{
		$this->tabla('prop_asp')->nueva_fila($datos);
	}

	function get_prop_asp($fila)
	{
		$this->dep('datos')->tabla('prop_asp')->set_cursor($fila);
		return $this->dep('datos')->tabla('prop_asp')->get();
	}

	function set_prop_asp($datos, $fila)
	{
		$this->dep('datos')->tabla('prop_asp')->set_cursor($fila);
		$this->dep('datos')->tabla('prop_asp')->set($datos);
	}

	function get_columna_prop_asp($columna)
	{
		return $this->tabla('prop_asp')->get_columna($columna);
	}

	//--------------------------------------------------------------------------
	//---- Tabla: sga_requisitos_aspirantes (req_asp) --------------------------
	//--------------------------------------------------------------------------

	function set_requisitos_aspirante()
	{
		$this->tabla('req_asp')->resetear();

		// Se agregan los requisitos de aspirante que corresponden para la inscripción actual
		$datos_prop_aspira = current($this->get_datos_prop_asp());
		$datos_per_insc = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc_fechas($datos_prop_aspira['periodo_insc']);
		$periodo_inscripcion = $datos_per_insc['periodo_inscripcion'];
		$datos = toba::consulta_php('co_requisitos')->get_requisito_apl_pres($datos_prop_aspira['plan_version'], $datos_prop_aspira['persona'], $periodo_inscripcion);
		foreach ($datos as $valor) {
			$ra = array();
			$ra['requisito'] = $valor['requisito'];
			$ra['restrictivo'] = $valor['restrictivo'];
			$ra['parametros'] = $valor['parametros'];
			$ra['exceptuado'] = comunes::no;
			$this->set_datos_requisito($ra);
		}
	}

	function set_datos_requisito($datos, $fila = null)
	{
		if (is_null($fila)) {
			$this->dep('datos')->tabla('req_asp')->nueva_fila($datos);
		} else {
			$this->dep('datos')->tabla('req_asp')->set_cursor($fila['cursor']);
			$this->dep('datos')->tabla('req_asp')->set($datos);
		}
	}
}