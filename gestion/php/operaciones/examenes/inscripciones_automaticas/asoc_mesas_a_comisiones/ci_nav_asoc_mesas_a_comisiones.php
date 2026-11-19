<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_asoc_mesas_a_comisiones extends ci_navegacion
{
	/**
	 * @ToDo: 	redefinir comportamiento del botón Volver y Guardar, distinguiendo
	 * 			desde donde se ha llegado a la operación.
	 * 
	 * Si se vino desde el menú principal, al finalizar se deberá mostrar el mensaje guardar_ok, 
	 * en cambio, si se vino desde el CU Inscripción Automática a Examen se deberá volver a la 
	 * pantalla correspondiente.
	 * 
	 * Lo mismo ocurre con el botón Volver que puede regresar al inicio de este CU o del otro.
	 * 
	 */
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		// Se resetean atributos de parámetros
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
		$filtro->columna('instancia')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('hora_inicio')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;

			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (	SELECT 	1
													FROM 	sga_mesas_examen_modalidad
													WHERE 	sga_mesas_examen_modalidad.mesa_examen = sga_mesas_examen.mesa_examen 
															AND sga_mesas_examen_modalidad.modalidad = '{$datos['modalidad']['valor']}') ";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}

			$filtro_instancia = '';
			if (isset($datos['instancia'])) {
				$filtro_instancia .= " AND EXISTS ( SELECT 	1
													FROM 	sga_mesas_examen_instancias
													WHERE 	sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen
															AND sga_mesas_examen_instancias.instancia = '{$datos['instancia']['valor']}') ";
				$this->dep('filtro')->columna('instancia')->resetear_estado();
			}

			$filtro_hora_inicio = '';
			if (isset($datos['hora_inicio'])) {
				$filtro_hora_inicio .= " AND to_char(CAST(sga_llamados_mesa.hora_inicio as time), '" . comunes::formato_hora_visual . "') = '{$datos['hora_inicio']['valor']}'";
				$this->dep('filtro')->columna('hora_inicio')->resetear_estado();
			}

			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad $filtro_instancia $filtro_hora_inicio";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof . '_sinfiltro');
			$cuadro->set_eof_mensaje($msg_eof);
		} else {
			parent::conf__cuadro($cuadro);
		}
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->cargar_atributos_parametros($seleccion['mesa_examen']);
		parent::evt__cuadro__seleccion($seleccion);
	}

}
?>