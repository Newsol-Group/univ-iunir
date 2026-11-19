<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Inscripciones
 */

class cn_baja_insc_propuesta extends cn_manejador_entidad
{
	function eliminar_inscripcion_a_propuesta($propuesta_aspira, $fecha_inscripcion, $persona, $propuesta, $alumno, $desactivar_transaccion=false)
	{
		
		if (! alumno::tiene_historia_academica($alumno)) { // Si el alumno no tiene historia académica...
			try {
				
				if(!$desactivar_transaccion) {
					toba::db()->abrir_transaccion();
				}
				
				if (! $this->existe_inscripcion_a_propuesta($propuesta_aspira, $persona, $propuesta)) { // Si la persona no tiene otra inscripción a la propuesta... 
					// Se eliminan todos los cambios de calidad, plan, modalidad y ubicación del alumno.
					$this->eliminar_historicos_alumno($alumno);
	
					// Se elimina el alumno.
					guarani::act('act_alumnos')->eliminar_alumno($alumno);
				} else {
					// Se eliminan los cambios de calidad, modalidad, plan y ubicación del alumno con fecha igual o posterior a la inscripción a borrar.
					$this->eliminar_historicos_alumno($alumno, $fecha_inscripcion);
	
					// Se actualizan la calidad, modalidad, plan y ubicación del alumno.
					$this->actualizar_datos_alumno($alumno);
				}
	
				// Se eliminan la inscripción a propuesta y sus requisitos.
				guarani::act('act_requisitos_aspirante')->eliminar_requisitos_aspirante($propuesta_aspira);
				guarani::act('act_inscripciones')->eliminar_inscripcion_propuesta($propuesta_aspira);
				
				/**
                                 * Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
				 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoBaja_Iscripcion_Propuesta Caso de uso.
				 */
				cobro::post_suscripciones_novedades_rest_hooks(cobro::TIPO_POSGRADO, $propuesta_aspira, cobro::TIPO_NOVEDAD_BAJA, inscripcion::operacion_baja);
                                
				if (!$desactivar_transaccion) {
					toba::db()->cerrar_transaccion();
				}
				
				return true;
			} catch (toba_error $e) {
				if (!$desactivar_transaccion) {
					toba::db()->abortar_transaccion();
				}
				throw $e;
			}
		}
		return false;
	}

	/**
	 * Verifica si una persona tiene otra inscripción a propuesta diferente a la enviada como parámetro.
	 *
	 * @param integer $propuesta_aspira
	 * @param integer $persona
	 * @param integer $propuesta
	 * @return boolean
	 */
	function existe_inscripcion_a_propuesta($propuesta_aspira, $persona, $propuesta)
	{
		$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $propuesta, $propuesta_aspira);
		return ($cant_inscripciones == 0) ? false : true;
	}

	/**
	 * Elimina los cambios de calidad, plan, modalidad y ubicación de un alumno.
	 * Si se indica una fecha de inscripción, tiene en cuenta sólo los cambios iguales o posteriores a esa fecha.
	 *
	 * @param integer $alumno
	 * @param date $fecha_inscripcion
	 */
	function eliminar_historicos_alumno($alumno, $fecha_inscripcion=null)
	{
		guarani::act('act_alumnos')->eliminar_cambios_calidad($alumno, $fecha_inscripcion);
		guarani::act('act_alumnos')->eliminar_cambios_plan($alumno, $fecha_inscripcion);
		guarani::act('act_alumnos')->eliminar_cambios_modalidad($alumno, $fecha_inscripcion);
		guarani::act('act_alumnos')->eliminar_cambios_ubicacion($alumno, $fecha_inscripcion);
	}

	/**
	 * Actualiza la calidad, plan, modalidad y ubicación de un alumno
	 * de acuerdo a los últimos cambios registrados en las tablas históricas.
	 *
	 * @param integer $alumno
	 */
	function actualizar_datos_alumno($alumno)
	{
		$ultimo_cambio_calidad = toba::consulta_php('co_alumnos')->get_ultimo_cambio_calidad($alumno);
		guarani::act('act_alumnos')->actualizar_calidad_alumno($alumno, $ultimo_cambio_calidad['calidad']);

		$ultimo_cambio_plan = toba::consulta_php('co_alumnos')->get_ultimo_cambio_plan($alumno);
		guarani::act('act_alumnos')->actualizar_plan_alumno($alumno, $ultimo_cambio_plan['plan_version']);

		$ultimo_cambio_modalidad = toba::consulta_php('co_alumnos')->get_ultimo_cambio_modalidad($alumno);
		guarani::act('act_alumnos')->actualizar_modalidad_alumno($alumno, $ultimo_cambio_modalidad['modalidad']);

		$ultimo_cambio_ubicacion = toba::consulta_php('co_alumnos')->get_ultimo_cambio_ubicacion($alumno);
		guarani::act('act_alumnos')->actualizar_ubicacion_alumno($alumno, $ultimo_cambio_ubicacion['ubicacion']);
	}
}