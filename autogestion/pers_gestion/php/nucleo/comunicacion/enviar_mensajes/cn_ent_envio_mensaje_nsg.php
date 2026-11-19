<?php
/**
 * 
 * 
 * @package 	Comunicación
 * @subpackage 	-
 *
 */
class cn_ent_envio_mensaje_nsg extends cn_ent_envio_mensaje
{
	
	function pre_sincronizar()
	{
		$alcance = $this->get_alcance();

		// Se asegura que no se pueda publicar en twitter y facebook si el alcance no es 'Todos los alumnos y docentes'.
		if (($alcance != mensajes::todos_alumnos_y_docentes)) {
			$datos = array();
			$datos['enviar_por_twitter'] = '0';
			$datos['enviar_por_facebook'] = '0';
			$datos['mensaje_twitter'] = null;
			$this->set_datos($datos);
		}

		// Validaciones generales.
		$this->validar();

		// Se valida, en función del alcance elegido, que haya al menos una selección.
		switch ($alcance) {
			case mensajes::alumnos_ra :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Responsable Académica'));
				}
				break;

			case mensajes::alumnos_ubicacion :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Ubicación'));
				}
				break;

			case mensajes::grupo_alumnos :
				$datos = $this->get_personas_asociadas();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('un Alumno'));
				}
				break;

			case mensajes::alumnos_propuesta :
			case mensajes::alumnos_extranjeros :
			case mensajes::alumnos_morosos :

			case mensajes::alumnos_adeudan_requisitos :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_propuesta_plan :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_propuesta_plan_ubicacion :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_ingresantes :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Propuesta'));
				}
				break;

			case mensajes::alumnos_comision :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Comisión'));
				} else {
					$dominio = current($datos);
					if (($dominio['insc_aceptadas'] == comunes::no) && ($dominio['insc_pendientes'] == comunes::no) && ($dominio['insc_rechazadas'] == comunes::no)) {
						$this->set_error('800_no_seleccion', array('un estado de Inscripción'));
					}
				}
				break;

			case mensajes::alumnos_mesa_examen :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Mesa de Examen'));
				} else {
					$dominio = current($datos);
					if (($dominio['insc_aceptadas'] == comunes::no) && ($dominio['insc_pendientes'] == comunes::no) && ($dominio['insc_rechazadas'] == comunes::no)) {
						$this->set_error('800_no_seleccion', array('un estado de Inscripción'));
					}
				}
				break;

			case mensajes::grupo_docentes :
				$datos = $this->get_personas_asociadas();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('un Docente'));
				}
				break;

			case mensajes::docentes_catedra :
				$datos = $this->get_dominio();
				if (empty($datos)) {
					$this->set_error('800_no_seleccion', array('una Cátedra'));
				}
				break;

			case mensajes::grupo_destinatarios :
				$datos = current($this->get_dominio());
				if (empty($datos['grupo'])) {
					$this->set_error('800_no_seleccion', array('un Grupo de Destinatarios'));
				}
				break;

			case mensajes::egresados_colacion :
				$datos = current($this->get_dominio());
				if (empty($datos['colacion'])) {
					$this->set_error('800_no_seleccion', array('una Colación'));
				}
				break;
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function get_destinatarios()
	{
		$alcance = $this->get_alcance();
		$dominio = $this->get_dominio();
		$personas = array();		
		switch ($alcance) {
			case mensajes::todos_alumnos_y_docentes :
				$personas = guarani::co('co_mensajes')->get_todos_alumnos_y_docentes();
				break;

			case mensajes::todos_alumnos :
				$personas = guarani::co('co_mensajes')->get_todos_alumnos();
				break;

			case mensajes::alumnos_ra :
				$personas = guarani::co('co_mensajes')->get_alumnos_ra($dominio);
				break;

			case mensajes::alumnos_ubicacion :
				$personas = guarani::co('co_mensajes')->get_alumnos_ubicacion($dominio);
				break;

			case mensajes::alumnos_propuesta :				
				$personas = guarani::co('co_mensajes')->get_alumnos_propuesta($dominio);
				break;

			case mensajes::alumnos_propuesta_plan :
				$personas = guarani::co('co_mensajes')->get_alumnos_propuesta_plan($dominio);
				break;

			case mensajes::alumnos_propuesta_plan_ubicacion :
				$personas = guarani::co('co_mensajes')->get_alumnos_propuesta_plan_ubicacion($dominio);
				break;

			case mensajes::alumnos_ingresantes :
				$personas = guarani::co('co_mensajes')->get_alumnos_ingresantes($dominio);
				break;

			case mensajes::alumnos_comision :
				$personas = guarani::co('co_mensajes')->get_alumnos_comision($dominio);
				break;

			case mensajes::alumnos_mesa_examen :
				$personas = guarani::co('co_mensajes')->get_alumnos_mesa_examen($dominio);
				break;

			case mensajes::todos_docentes :
				$personas = guarani::co('co_mensajes')->get_todos_docentes();
				break;

			case mensajes::docentes_mesa_examen :
				$personas = guarani::co('co_mensajes')->get_docentes_mesa_examen($dominio);
				break;

			case mensajes::docentes_comision :
				$personas = guarani::co('co_mensajes')->get_docentes_comision($dominio);
				break;

			case mensajes::docentes_catedra :
				$personas = guarani::co('co_mensajes')->get_docentes_catedra($dominio);
				break;

			case mensajes::grupo_destinatarios :
				$personas = guarani::co('co_mensajes')->get_grupo_destinatarios($dominio);
				break;

			case mensajes::alumnos_con_encuestas_pendientes :
				$personas = guarani::co('co_mensajes')->get_alumnos_con_encuestas_pendientes();
				break;
			
			case mensajes::grupo_alumnos :
			case mensajes::grupo_docentes :				
				$personas = $this->get_personas_asociadas();
				break;
			
			case mensajes::alumnos_extranjeros :
				$personas = guarani::co('co_mensajes')->get_alumnos_extranjeros($dominio);
				break;

			case mensajes::alumnos_morosos :
				$personas = guarani::co('co_mensajes')->get_alumnos_morosos($dominio);
				break;
			
			case mensajes::alumnos_adeudan_requisitos :				
				$personas = guarani::co('co_mensajes')->get_alumnos_adeudan_requisitos($dominio);
				break;

			case mensajes::egresados_colacion :				
				$personas = guarani::co('co_mensajes')->get_egresados_colacion($dominio);
				break;
		}
		return $personas;
	}

}
?>