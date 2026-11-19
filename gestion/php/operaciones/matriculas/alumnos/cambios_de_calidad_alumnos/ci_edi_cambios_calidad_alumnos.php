<?php
/**
 * Representa los diferentes cambios de ubicación sobre la propuesta a la que está inscripto un alumno.
 *
 *
 * @package Matricula
 * @subpackage Personas/Alumnos/Cambios de Calidad de Alumnos
 */

class ci_edi_cambios_calidad_alumnos extends guarani_pers_ci
{
	protected $datos_temp_calidad;
	
	function conf__form_cambio_calidad(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_calidad)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_calidad;		
		} else {
			$datos = $this->cn()->entidad()->get_datos_cambio_calidad();
			if ($this->controlador()->es_calidad_nueva()) {
				/**
				 * Se setea la Calidad Actual que es ef maestro del ef calidad, ya que la misma se utiliza para obtener
				 * las calidades posibles para el cambio. Se obtiene su valor a partir de la calidad del Alumno.
				 */
				$alumno = $this->cn()->entidad()->get_datos_alumno();
				$datos['calidad_actual'] = $alumno['calidad'];
			} else {
				/**
				 * Si se está en Edición entonces se elimina la Calidad Actual como ef maestro de Calidad (ya que no debe
				 * descartarse de las opciones del combo para poder mostrarla 
				 */
				$form->ef('calidad')->quitar_maestro('calidad_actual');
			}
		}
		$form->set_datos_defecto($datos);
		if (!$this->controlador()->es_calidad_nueva()) {
			//Si se está en el caso de edición se setea el campo calidad como solo lectura.
			$form->ef('calidad')->set_solo_lectura();
		}
	}

	function evt__form_cambio_calidad__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_cambio_calidad($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp_calidad = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}

?>