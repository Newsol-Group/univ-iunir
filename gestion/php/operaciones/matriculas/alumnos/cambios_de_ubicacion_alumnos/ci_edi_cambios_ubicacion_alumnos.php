<?php
/**
 * Representa los diferentes cambios de ubicación sobre la propuesta a la que está inscripto un alumno.
 *
 *
 * @package Matricula
 * @subpackage Personas/Alumnos/Cambios de Ubicación de Alumnos
 */

class ci_edi_cambios_ubicacion_alumnos extends guarani_pers_ci
{
	protected $datos_temp_ubicacion;
	
	function conf__form_cambio_ubicacion(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_ubicacion)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_ubicacion;		
		} else {
			$datos = $this->cn()->entidad()->get_datos_cambio_ubicacion();
			//Se setea el ef oculto propuesta para obtener las ubicaciones posibles donde se oferta la misma.
			$alumno = $this->cn()->entidad()->get_datos_alumno();
			$datos['propuesta'] = $alumno['propuesta'];
			if ($this->controlador()->es_ubicacion_nueva()) {
				/**
				 * Se setea la Ubicación Actual que es ef maestro del ef ubicación, ya que la misma se descarta de las
				 * ubicaciones posibles para el cambio. Se obtiene su valor a partir de la ubicación del Alumno.
				 */
				$datos['ubicacion_actual'] = $alumno['ubicacion'];
				//En caso de estar agregando un nuevo cambio de ubicación el valor por defecto del campo Motivo Ubicación.
				$datos['motivo_ubicacion'] = alumno::motivo_ubicacion_alta;
			} else {
				/**
				 * Si se está en Edición entonces se elimina la Ubicación Actual como ef maestro de Ubicación (ya que no debe
				 * descartarse de las opciones del combo para poder mostrarla 
				 */
				$form->ef('ubicacion')->quitar_maestro('ubicacion_actual');
			}
		}			
		$form->set_datos_defecto($datos);
		if (!$this->controlador()->es_ubicacion_nueva()) {
			//Si se está en el caso de edición se setea el campo ubicacion como solo lectura.
			$form->ef('ubicacion')->set_solo_lectura();
		}
	}

	function evt__form_cambio_ubicacion__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_cambio_ubicacion($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp_ubicacion = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}

?>