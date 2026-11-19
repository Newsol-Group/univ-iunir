<?php
/**
 * 
 * 
 * @package 	Comunicación
 * @subpackage 	-
 *
 */
class ci_edi_enviar_mensajes extends guarani_pers_ci
{
	protected $s__seleccion;
	protected $reset;

	function resetear()
	{
		$this->dep('editor_sel_comision')->resetear();
		$this->dep('editor_sel_mesa')->resetear();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones 
	//-----------------------------------------------------------------------------------

	function ini()
	{
		toba_ef_combo::set_maximo_descripcion(100);
	}

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

	//-----------------------------------------------------------------------------------
	//---- form_destinatarios -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_destinatarios(form_destinatarios $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos());
	}

	function evt__form_destinatarios__modificacion($datos)
	{
		$alcance = $this->cn()->entidad()->get_alcance();
		if ($datos['alcance'] != $alcance) {
			$this->reset = 1;
			$this->resetear_pantalla();
			$this->cn()->entidad()->resetear_relacion();
			if (($alcance == mensajes::alumnos_comision) || ($alcance == mensajes::docentes_comision)) {
				$this->dep('editor_sel_comision')->resetear();
			} elseif (($alcance == mensajes::alumnos_mesa_examen) || ($alcance == mensajes::docentes_mesa_examen)) {
				$this->dep('editor_sel_mesa')->resetear();
			}
		}
		$this->cn()->entidad()->set_datos($datos);
	}

	function get_alcance()
	{
		return $this->cn()->entidad()->get_alcance();
	}

	//-----------------------------------------------------------------------------------
	//---- form_grupo_destinatarios -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_grupo_destinatarios(toba_ei_formulario $form)
	{
		$form->set_datos(current($this->cn()->entidad()->get_dominio()));

		$form->ef('grupo')->set_estilo_etiqueta('ei-form-etiq-oblig');
		$etiqueta = $form->ef('grupo')->get_etiqueta() . ' (*)';
		$form->ef('grupo')->set_etiqueta($etiqueta);
	}

	function evt__form_grupo_destinatarios__modificacion($datos)
	{
		if (!$this->reset) {
			$this->cn()->entidad()->resetear_dominio();
			$this->cn()->entidad()->agregar_dominio($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_colacion ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_colacion(toba_ei_formulario $form)
	{
		$form->set_datos(current($this->cn()->entidad()->get_dominio()));

		/*$form->ef('grupo')->set_estilo_etiqueta('ei-form-etiq-oblig');
		$etiqueta = $form->ef('grupo')->get_etiqueta() . ' (*)';
		$form->ef('grupo')->set_etiqueta($etiqueta);*/
	}

	function evt__form_colacion__modificacion($datos)
	{
		if (!$this->reset) {
			$this->cn()->entidad()->resetear_dominio();
			$this->cn()->entidad()->agregar_dominio($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- CUADROS DE SELECCIÓN DIRECTA
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_ubicaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ubicaciones(cuadro_seleccion_multiple $cuadro)
	{
		$datos = toba::consulta_php('co_ubicaciones')->get_listado();
		$cuadro->set_datos($datos);
		$this->s__seleccion = $this->cn()->entidad()->get_dominio();
		if (empty($datos)) {
			$cuadro->set_eof_mensaje($this->get_msg_eof(guarani_etiquetas::get_tabla('sga_ubicaciones')));
		}
	}

	function evt__cuadro_ubicaciones__seleccion($datos)
	{
		$this->s__seleccion = $datos;
		$this->cn()->entidad()->set_dominio($datos);
	}

	function conf_evt__cuadro_ubicaciones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_ubicaciones')->get_datos();
		if (guarani_arreglos::count($datos) == 1) {
			$this->s__seleccion = $datos;
			$this->cn()->entidad()->set_dominio($datos);
			$evento->set_check_activo(true);
		} else {
			if (isset($this->s__seleccion)) {
				$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ubicaciones', $fila, 'ubicacion'));
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_ra --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ra(toba_ei_cuadro $cuadro)
	{
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro = "sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_ra__seleccion($datos)
	{
		$this->s__seleccion = $datos;
		$this->cn()->entidad()->set_dominio($datos);
	}

	function conf_evt__cuadro_ra__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_ra')->get_datos();
		if (guarani_arreglos::count($datos) == 1) {
			$this->s__seleccion = $datos;
			$this->cn()->entidad()->set_dominio($datos);
			$evento->set_check_activo(true);
		} else {
			if (isset($this->s__seleccion)) {
				$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ra', $fila, 'responsable_academica'));
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- MÉTODOS AUXILIARES
	//-----------------------------------------------------------------------------------

	function es_fila_seleccionada($dep_cuadro, $fila, $campo_clave)
	{
		$clave = $this->dep($dep_cuadro)->get_clave_fila_array($fila);
		$clave_fila = $clave[$campo_clave];
		foreach ($this->s__seleccion as $seleccion) {
			if (isset($seleccion[$campo_clave])) {
				if ($seleccion[$campo_clave] == $clave_fila) {
					return true;
				}
			}
		}
		return false;
	}

	function resetear_pantalla()
	{
		unset($this->s__seleccion);
	}
	
	function get_listado_alcances()
	{
		return $this->cn()->get_alcances();
	}
	
	function evt__enviar()
	{		
		$this->cn()->entidad()->pre_sincronizar();
		$this->cn()->entidad()->enviar_adeudan_requisitos();
		$this->controlador()->evt__cancelar();
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/*function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__enviar = function()
		{
			alert(this.dep('editor_alumnos_propuesta').dep('asignados')._filas);
			return true;
		}
		";
	}*/

}
?>