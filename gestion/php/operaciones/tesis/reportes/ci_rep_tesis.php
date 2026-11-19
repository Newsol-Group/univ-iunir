<?php
/**
 *
 * 
 * @package		Tesis
 * @subpackage 	Reportes
 * 
 */
class ci_rep_tesis extends ci_reporte
{
	//-----------------------------------------------------------------------------------
	//---- Funciones Auxiliares ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_tabla_principal()
	{
		return 'sga_tesis';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_tesis')->get_listado_con_roles($filtro);
	}

	function get_titulo()
	{
		return 'Reporte de Tésis';
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------

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
		/**
		 * Se maneja a mano el armado de las condiciones de los filtros para considerar alumnos, evaluadores y tutores de
		 * la tésis, ya que en la SQL utilizada para obtener el listado de tésis no se realiza el join con todas estas
		 * tablas, obteniendo los string de los nombres de las personas a partir de las funciones correspondientes.
		 */
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_alumno = '';
			$filtro_tutor = '';
			$filtro_evaluador = '';

			// Para poder utilizar el filtro por Rol de Evaluador debe haberse ingresado un valor para el filtro Evaluador.
			if (isset($datos['rol_evaluador']) && !isset($datos['evaluador'])) {
				$msj = guarani_mensajes::get('800_filtro_dep_obligatorio', array('Evaluador', 'Rol Evaluador'));
				throw new guarani_error($msj);
			}

			// Para poder utilizar el filtro por Rol de Tutor debe haberse ingresado un valor para el filtro Tutor.
			if (isset($datos['rol_tutor']) && !isset($datos['tutor'])) {
				$msj = guarani_mensajes::get('800_filtro_dep_obligatorio', array('Tutor', 'Rol Tutor'));
				throw new guarani_error($msj);
			}

			if (isset($datos['alumno'])) {
				$expresion_filtro_alumno_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['alumno']['valor'], $datos['alumno']['condicion_cs'], $datos['alumno']['condicion_as']);
				$filtro_alumno .= " AND sga_tesis.tesis IN (SELECT	sga_tesis_alumnos.tesis
															FROM	sga_tesis_alumnos, sga_alumnos, mdp_personas
															WHERE	sga_tesis_alumnos.tesis = sga_tesis.tesis 
															AND		sga_tesis_alumnos.alumno = sga_alumnos.alumno
															AND 	sga_alumnos.persona = mdp_personas.persona
															AND		$expresion_filtro_alumno_apellido)";

				$this->dep('filtro')->columna('alumno')->resetear_estado();
			}

			if (isset($datos['tutor'])) {
				$expresion_filtro_tutor_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['tutor']['valor'], $datos['tutor']['condicion_cs'], $datos['tutor']['condicion_as']);
				$filtro_tutor .= " AND sga_tesis.tesis IN (	SELECT	sga_tesis_tutores.tesis
															FROM	sga_tesis_tutores, sga_alumnos, mdp_personas
															WHERE	sga_tesis_tutores.tesis = sga_tesis.tesis 
															AND		sga_tesis_tutores.persona = mdp_personas.persona
															AND		$expresion_filtro_tutor_apellido";

				$this->dep('filtro')->columna('tutor')->resetear_estado();

				if (isset($datos['rol_tutor'])) {
					$rol = $datos['rol_tutor']['valor'];
					$filtro_tutor .= " AND sga_tesis_tutores.rol_tutor = $rol)";
					$this->dep('filtro')->columna('rol_tutor')->resetear_estado();
				} else {
					$filtro_tutor .= ')';
				}
			}

			if (isset($datos['evaluador'])) {
				$expresion_filtro_evaluador_apellido = guarani_ei_filtro::get_expresion('COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)', $datos['evaluador']['valor'], $datos['evaluador']['condicion_cs'], $datos['evaluador']['condicion_as']);
				$filtro_evaluador .= " AND sga_tesis.tesis IN (	SELECT	sga_tesis_evaluadores.tesis
																FROM	sga_tesis_evaluadores, sga_alumnos, mdp_personas
																WHERE	sga_tesis_evaluadores.tesis = sga_tesis.tesis 
																AND		sga_tesis_evaluadores.persona = mdp_personas.persona
																AND		$expresion_filtro_evaluador_apellido";

				$this->dep('filtro')->columna('evaluador')->resetear_estado();

				if (isset($datos['rol_evaluador'])) {
					$rol = $datos['rol_evaluador']['valor'];
					$filtro_evaluador .= " AND sga_tesis_evaluadores.rol_evaluador = $rol)";
					$this->dep('filtro')->columna('rol_evaluador')->resetear_estado();
				} else {
					$filtro_evaluador .= ')';
				}
			}

			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_alumno $filtro_tutor $filtro_evaluador";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

}