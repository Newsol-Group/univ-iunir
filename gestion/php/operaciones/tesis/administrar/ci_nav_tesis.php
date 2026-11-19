<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage	-
 * 
 */
class ci_nav_tesis extends ci_nav_sin_listado_inicial
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN 
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('alumno')->set_condicion_fija('contiene');
		$filtro->columna('tutor')->set_condicion_fija('contiene');
		$filtro->columna('evaluador')->set_condicion_fija('contiene');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_alumno = '';
			$filtro_tutor = '';
			$filtro_evaluador = '';
			$filtro_prop = '';
			$filtro_leg = '';

			if (isset($datos['alumno'])) {
				$expresion_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['alumno']['valor'], $datos['alumno']['condicion_cs'], $datos['alumno']['condicion_as']);
				$filtro_alumno .= " AND sga_tesis.tesis IN (SELECT	sga_tesis_alumnos.tesis
															FROM	sga_tesis_alumnos, sga_alumnos, mdp_personas
															WHERE	sga_tesis_alumnos.tesis = sga_tesis.tesis 
															AND		sga_tesis_alumnos.alumno = sga_alumnos.alumno
															AND 	sga_alumnos.persona = mdp_personas.persona
															AND		$expresion_apellido)";

				$this->dep('filtro')->columna('alumno')->resetear_estado();
			}

			if (isset($datos['tutor'])) {
				$expresion_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['tutor']['valor'], $datos['tutor']['condicion_cs'], $datos['tutor']['condicion_as']);
				$filtro_tutor .= " AND sga_tesis.tesis IN (	SELECT	sga_tesis_tutores.tesis
															FROM	sga_tesis_tutores, sga_alumnos, mdp_personas
															WHERE	sga_tesis_tutores.tesis = sga_tesis.tesis 
															AND		sga_tesis_tutores.persona = mdp_personas.persona
															AND		$expresion_apellido)";

				$this->dep('filtro')->columna('tutor')->resetear_estado();
			}

			if (isset($datos['evaluador'])) {
				$expresion_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['evaluador']['valor'], $datos['evaluador']['condicion_cs'], $datos['evaluador']['condicion_as']);
				$filtro_evaluador .= " AND sga_tesis.tesis IN (	SELECT	sga_tesis_evaluadores.tesis
																FROM	sga_tesis_evaluadores, sga_alumnos, mdp_personas
																WHERE	sga_tesis_evaluadores.tesis = sga_tesis.tesis 
																AND		sga_tesis_evaluadores.persona = mdp_personas.persona
																AND		$expresion_apellido)";

				$this->dep('filtro')->columna('evaluador')->resetear_estado();
			}

			if (isset($datos['propuesta'])) {
				$expresion_prop = $this->dep('filtro')->columna('propuesta')->get_sql_where();
				$filtro_prop .= " AND sga_tesis.tesis IN (	SELECT	sga_tesis_alumnos.tesis            
															FROM	sga_tesis_alumnos, sga_alumnos, sga_propuestas
															WHERE	sga_tesis_alumnos.alumno = sga_alumnos.alumno
															AND		sga_alumnos.propuesta = sga_propuestas.propuesta
															AND		$expresion_prop)";

				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}

			if (isset($datos['legajo'])) {
				$expresion_leg = $this->dep('filtro')->columna('legajo')->get_sql_where();
				$filtro_leg .= " AND sga_tesis.tesis IN (	SELECT	sga_tesis_alumnos.tesis            
															FROM	sga_tesis_alumnos, sga_alumnos
															WHERE	sga_tesis_alumnos.alumno = sga_alumnos.alumno
															AND		$expresion_leg)";

				$this->dep('filtro')->columna('legajo')->resetear_estado();
			}

			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_alumno $filtro_tutor $filtro_evaluador $filtro_prop $filtro_leg";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION 
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$hay_cambios = $this->hay_cambios || $this->dep('editor')->get_datos_cambio_estado();

		if ($hay_cambios) {
			$this->set_hubo_cambios();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		$datos_cambio_estado = $this->dep('editor')->get_datos_cambio_estado();
		$this->cn()->entidad()->validar_pre_guardar($datos_cambio_estado);
		$cambio_estado = false;

		if ($datos_cambio_estado) {
			$this->cn()->entidad()->set_datos($datos_cambio_estado);
			$this->cn()->entidad()->set_datos_cambio_estado($datos_cambio_estado);
			$cambio_estado = true;
		}

		$this->dep('editor')->reset_vars();

		// Lo copio del padre (método 'evt__guardar' de la clase 'ci_navegacion')...(*)
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e) {
			throw $e;
		}

		// Si cambio el estado
		if ($cambio_estado) {
			$datos = $this->cn()->entidad()->get_datos();
			// Invoco al notificador para que envíe mensaje/email según corresponda.
			$parametros = ['id_tesis' => $datos['tesis']];
			guarani::notificador()->enviar(notificador::circuito_tesis, $parametros);
		}

		// ...(*) porque necesito resetear al final de todo.
		$this->cn()->resetear();
	}

	function evt__cancelar()
	{
		$this->dep('editor')->reset_vars();
		parent::evt__cancelar();
	}

	function evt__eliminar()
	{
		$this->dep('editor')->reset_vars();
		parent::evt__eliminar();
	}

}