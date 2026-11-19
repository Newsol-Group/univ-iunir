<?php
/**
 * 
 * 
 * @package 	Comunicación
 * @subpackage 	-
 *
 */
class ci_edi_enviar_mensajes_nsg extends ci_edi_enviar_mensajes
{
	
	function conf()
	{
		if (!$this->cn()->entidad()->es_nueva()) {
			$datos = $this->cn()->entidad()->get_datos();
			// Si no se registraron errores en el envío de mail, se elimina la pantalla correspondiente.
			if (empty($datos['error_envio_mail'])) {
				$this->pantalla()->eliminar_tab('errores');
			}

			$this->dep('form_destinatarios')->set_solo_lectura();
			$alcance = $this->cn()->entidad()->get_alcance();

			switch ($alcance) {
				case mensajes::alumnos_ra :
					$this->dep('cuadro_ra')->evento('seleccion')->desactivar();
					break;

				case mensajes::alumnos_ubicacion :
					$this->dep('cuadro_ubicaciones')->evento('seleccion')->desactivar();
					break;

				case mensajes::alumnos_propuesta :
				case mensajes::alumnos_extranjeros :
					$this->dep('editor_alumnos_propuesta')->set_solo_lectura();
					break;
				case mensajes::alumnos_morosos :
					$this->dep('editor_alumnos_propuesta')->set_solo_lectura();
					break;
				
				case mensajes::alumnos_adeudan_requisitos :
					$this->dep('editor_alumnos_propuesta')->set_solo_lectura();
					$this->pantalla()->set_tipo_navegacion('basica');
					break;

				case mensajes::alumnos_propuesta_plan :
					$this->dep('editor_alumnos_propuesta_plan')->set_solo_lectura();
					break;

				case mensajes::alumnos_propuesta_plan_ubicacion :
					$this->dep('editor_alumnos_propuesta_plan_ubicacion')->set_solo_lectura();
					break;

				case mensajes::grupo_alumnos :
					$this->dep('editor_grupo_alumnos')->set_solo_lectura();
					break;

				case mensajes::alumnos_ingresantes :
					$this->dep('editor_alumnos_ingresantes')->set_solo_lectura();
					break;

				case mensajes::alumnos_comision :
					$this->dep('editor_sel_comision')->set_solo_lectura();
					break;

				case mensajes::alumnos_mesa_examen :
					$this->dep('editor_sel_mesa')->set_solo_lectura();
					break;

				case mensajes::grupo_docentes :
					$this->dep('editor_grupo_docentes')->set_solo_lectura();
					break;

				case mensajes::docentes_mesa_examen :
					$this->dep('editor_sel_mesa')->set_solo_lectura();
					break;

				case mensajes::docentes_comision :
					$this->dep('editor_sel_comision')->set_solo_lectura();
					break;

				case mensajes::docentes_catedra :
					$this->dep('editor_docentes_catedra')->set_solo_lectura();
					break;

				case mensajes::grupo_destinatarios :
					$this->dep('form_grupo_destinatarios')->set_solo_lectura();
					break;

				case mensajes::egresados_colacion :
					$this->dep('form_colacion')->set_solo_lectura();
					break;
			}
			if ($this->pantalla()->existe_evento('enviar')) {
				$this->pantalla()->evento('enviar')->anular();
			}
		} else {
			// Si la entidad es nueva, se elimina la pantalla de errores en el envío de mail	
			$this->pantalla()->eliminar_tab('errores');
			$alcance = $this->get_alcance();
			if ($this->pantalla()->existe_evento('enviar')) {
				$this->pantalla()->evento('enviar')->anular();
			}
			if ($alcance == mensajes::alumnos_adeudan_requisitos) {
				$this->pantalla()->evento('enviar')->restituir();
				$this->pantalla()->set_tipo_navegacion('basica');
			}			
		}
	}

	function conf__destinatarios(toba_ei_pantalla $pantalla)
	{
		$alcance = $this->cn()->entidad()->get_alcance();

		/*
		 * Tomo la lista de dependencia y las quito hasta no saber cual es el alcance.
		 * Solo se que tengo que dejar form_destinatarios, que contiene el combo de alcances.
		 */
		$dependencias = $pantalla->get_lista_dependencias();
		$quitar = array_search('form_destinatarios', $dependencias);
		unset($dependencias[$quitar]);
		foreach($dependencias as $dependencia) {
			$pantalla->eliminar_dep($dependencia);
		}
		
		if (!empty($alcance)) {
			// Configuración de las dependencias
			$dep = '';
			switch ($alcance) {
				case mensajes::alumnos_ra :
					$dep = 'cuadro_ra';					
					break;

				case mensajes::alumnos_ubicacion :
					$dep = 'cuadro_ubicaciones';
					break;

				case mensajes::alumnos_propuesta :
				case mensajes::alumnos_extranjeros :
				case mensajes::alumnos_morosos :
				case mensajes::alumnos_adeudan_requisitos :
					$dep = 'editor_alumnos_propuesta';
					break;

				case mensajes::alumnos_propuesta_plan :
					$dep = 'editor_alumnos_propuesta_plan';
					break;

				case mensajes::alumnos_propuesta_plan_ubicacion :
					$dep = 'editor_alumnos_propuesta_plan_ubicacion';
					break;

				case mensajes::grupo_alumnos :
					$dep = 'editor_grupo_alumnos';
					break;

				case mensajes::alumnos_ingresantes :
					$dep = 'editor_alumnos_ingresantes';
					break;

				case mensajes::alumnos_comision :
					$dep = 'editor_sel_comision';
					break;

				case mensajes::alumnos_mesa_examen :
					$dep = 'editor_sel_mesa';
					break;

				case mensajes::grupo_docentes :
					$dep = 'editor_grupo_docentes';
					break;

				case mensajes::docentes_mesa_examen :
					$dep = 'editor_sel_mesa';
					break;

				case mensajes::docentes_comision :
					$dep = 'editor_sel_comision';
					break;

				case mensajes::docentes_catedra :
					$dep = 'editor_docentes_catedra';
					break;

				case mensajes::grupo_destinatarios :
					$dep = 'form_grupo_destinatarios';
					break;

				case mensajes::egresados_colacion :
					$dep = 'form_colacion';
					break;
			}
			//Sabiendo cual es la dependencia la agrego en el pantalla siempre 
			//y cuando se tenga alguna asociada			
			if($dep != '') {
				$pantalla->agregar_dep($dep);
				/*
				 * Si es un mensaje nuevo y la dependencia es un ci, limpio la memoria
				 * porque sino queda la selección utilizada anteriormente.
				 */
				if ($this->cn()->entidad()->es_nueva() && $this->dep($dep) instanceof guarani_pers_ci) {
					$this->dep($dep)->limpiar_memoria();				
				}
			}			
		}
	}

}
?>