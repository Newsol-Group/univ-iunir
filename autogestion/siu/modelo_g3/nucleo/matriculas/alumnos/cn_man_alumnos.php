<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Personas/Alumnos
 */

class cn_man_alumnos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'alumno';
	}

	/**
	 * Permite obtener los datos a cargar en el cuadro de preseleccion para la operación de Cambio de Ubicación, Calidad, Modalidad y Plan/Plan-Versión de un Alumno,
	 * donde se muestran todos los alumnos (combinación de personas inscriptos en propuestas) según el filtro dado.
	 */
	function get_listado($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($filtro);
	}

	/**
	 * Verifica que el alumno seleccionado para el Cambio de Modalidad cumpla con las condiciones necesarias para poder
	 * agregarle un nuevo Cambio de Modalidad. En otro caso, no tiene sentido seleccionar el Alumno, mostrar su historial 
	 * de modalidades e ir a la pantalla de edición de agregar o editar cambios de modalidad.
	 * Para esto se determina si el plan_version que el alumno tiene asociado se dicta en màs de una modalidad de cursada. 
	 * @param array $seleccion Alumno seleccionado desde el cuadro inicial.
	 */
	function validar_seleccion_alumno_modalidad($seleccion)
	{
		$alumno = toba::db()->quote(current($seleccion));
		$datos = toba::consulta_php('co_alumnos')->get_listado("sga_alumnos.alumno = $alumno");
		$datos_alumno = current($datos);
		$modalidades_plan = toba::consulta_php('co_planes')->get_modalidades_plan_version($datos_alumno['plan_version']);
		if (guarani_arreglos::count($modalidades_plan) == 1) {
			$nombre_alumno = $datos_alumno['apellido'] . ', ' . $datos_alumno['nombres'];
			$msj_error = guarani_mensajes::get('800MAT_alumno_modalidad_unica', array($nombre_alumno));
			throw guarani::error($msj_error);
		}	
	}
	
	function baja_suscripcion_novedad_SQ(){
		
		try{
			
			$alumno = $this->entidad()->get_datos_alumno();
			
			// Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
			// Si el alumno cambia de calidad modifico la suscripcion novedad de SQ
			if($this->entidad()->hay_cambio_calidad()){

				/**
                                 * Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
				 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoCambios_Calidad_alumnos Caso de uso.
				 */
				$propuesta_aspira = guarani::co('co_inscripciones')->get_id_ultima_propuesta_aspira($alumno['persona'], $alumno['propuesta']);
				
				if ($alumno['calidad'] == alumno::calidad_pasivo) {
					$tipo_novedad = cobro::TIPO_NOVEDAD_INACTIVA;
					$operacion = inscripcion::operacion_cambio_calidad_pasivo;
				} else {
					$tipo_novedad = cobro::TIPO_NOVEDAD_ACTIVA;
					$operacion = inscripcion::operacion_cambio_calidad_activo;
				}
				cobro::post_suscripciones_novedades_rest_hooks(cobro::TIPO_POSGRADO, $propuesta_aspira, $tipo_novedad, $operacion);
				
			}
		
		}
		catch (Exception $e) {
			// Reseteo el 'datos tabla' para que luego reconozca que hubo un cambio
			$this->resetear();
			$this->cargar(array('alumno' => $alumno['alumno']));
			throw new guarani_error($e->getMessage());
		}
		
	}
	
}