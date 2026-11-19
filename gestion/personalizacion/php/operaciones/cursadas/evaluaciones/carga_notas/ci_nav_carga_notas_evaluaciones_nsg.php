<?php
/**
 * Personalización de la carga de notas de evaluaciones.
 * La validación al persistir se realiza en el CN, tanto al guardar como al cerrar.
 */
class ci_nav_carga_notas_evaluaciones_nsg extends ci_nav_carga_notas_evaluaciones
{
	function evt__cuadro_evaluacion__seleccion($seleccion)
	{
		parent::evt__cuadro_evaluacion__seleccion($seleccion);
		$this->get_selector_alumnos_disponibles()->set_evaluacion_control_morosidad($this->cn()->get_columna_evaluacion('evaluacion'));
	}

	function evt__guardar()
	{
		parent::evt__guardar();
		$this->get_selector_alumnos_disponibles()->resetear_control_morosidad();
	}

	function resetear_vars()
	{
		if (isset($this->s__evaluacion_seleccionada)) {
			$this->get_selector_alumnos_disponibles()->set_evaluacion_control_morosidad(null);
		}
		parent::resetear_vars();
	}

	protected function get_selector_alumnos_disponibles()
	{
		return $this->dep('selector_alumnos')
			->dep('alumnos')
			->dep('disponibles');
	}
}
?>
