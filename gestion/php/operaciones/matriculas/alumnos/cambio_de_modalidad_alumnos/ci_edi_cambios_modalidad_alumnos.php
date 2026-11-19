<?php
/**
 * Representa los diferentes cambios de modalidad sobre la propuesta a la que está inscripto un alumno.
 *
 *
 * @package Matricula
 * @subpackage Personas/Alumnos/Cambios de Modalidad de Alumnos
 */

class ci_edi_cambios_modalidad_alumnos extends guarani_pers_ci
{
	protected $datos_temp_modalidad;
	
	function conf__form_cambio_modalidad(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_modalidad)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_modalidad;		
		} else {
			$datos = $this->cn()->entidad()->get_datos_cambio_modalidad();
			//Se setea el ef oculto plan_version_alumno para obtener las modalidades posibles de cursada del plan correspondiente.
			$alumno = $this->cn()->entidad()->get_datos_alumno();
			$datos['plan_version_alumno'] = $alumno['plan_version'];
			if ($this->controlador()->es_modalidad_nueva()) {
				/**
				 * Se setea la Modalidad Actual que es ef maestro del ef modalidad, ya que la misma se utiliza para 
				 * obtener las modalidades posibles para el cambio (se excluye de las posibilidades). Se obtiene su 
				 * valor a partir de la modalidad del Alumno.
				 */
				$datos['modalidad_actual'] = $alumno['modalidad'];
			} else {
				/**
				 * Si se está en Edición entonces se elimina la Modalidad Actual como ef maestro de Modalidad (ya que 
				 * no debe descartarse de las opciones del combo para poder mostrarla).
				 */
				$form->ef('modalidad')->quitar_maestro('modalidad_actual');
			}
		}
		$form->set_datos_defecto($datos);
		if (!$this->controlador()->es_modalidad_nueva()) {
			//Si se está en el caso de edición se setea el campo modalidad como solo lectura.
			$form->ef('modalidad')->set_solo_lectura();
		}
	}

	function evt__form_cambio_modalidad__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_cambio_modalidad($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp_modalidad = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}

?>