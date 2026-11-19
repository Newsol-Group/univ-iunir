<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class cn_man_solicitud_constancias extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'solicitud_constancia';
	}

	function get_listado_preseleccion($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}

	function get_listado_solicitar_constancias($alumno)
	{
		$filtro = "sga_constancias_solicitud.alumno = $alumno AND sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_pendiente . "'";
		return toba::consulta_php('co_constancias')->get_listado_const_solicitadas($filtro);
	}
	
	function get_listado_solicitar_constancias_no_rechazadas($alumno)
	{
		$filtro = "sga_constancias_solicitud.alumno = $alumno AND 
				   sga_constancias_solicitud.estado != '" . constancias::estado_solicitud_rechazado."'";
		return toba::consulta_php('co_constancias')->get_listado_const_solicitadas($filtro);
	}
		
	//--------------- Validaciones --------------------------------------
	
	function validar_solicitud_alumno($persona, $alumno, $plan_version)
	{
		try {
			$this->validar_puntos_de_control_solicitud_alumno($persona, $alumno, $plan_version);
		} catch(guarani_error $e) {
			throw $e;
		}
	}
	
	function validar_puntos_de_control_solicitud_alumno($persona, $alumno, $plan_version)
	{
		$puntos_de_control = array( guarani_punto_de_control::alumno_entidad,
									guarani_punto_de_control::persona_operacion,
									guarani_punto_de_control::alumno_operacion,
									guarani_punto_de_control::alumno_anio_academico
									);
		
		//Traigo el último periodo de reinscripcion a la propuesta.
		$anio_academico = guarani::co('co_reinscripciones')->get_anio_academico_de_ultimo_periodo_reinscripcion($alumno, $plan_version, comunes::interfaz_gestion);		
		$parametros = array('persona' => $persona,
							'anio_academico' => $anio_academico,
							'fecha' => guarani_fecha::get_hoy(),
							'alumno' => $alumno,
							'plan_version' => $plan_version);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800CON_error_control_solicitud'));
		}
	}
	
	function validar_solicitud_constancia($alumno, $plan_version)
	{
		try {
			$datos = $this->entidad()->get_datos();
			$this->validar_puntos_de_control_solicitud_constancia($alumno, $plan_version, $datos['constancia']);
		} catch(guarani_error $e) {
			throw $e;
		}
	}
	
	function validar_puntos_de_control_solicitud_constancia($alumno, $plan_version, $constancia)
	{		
		$puntos_de_control = array( guarani_punto_de_control::alumno_constancia_certificado
									);
				
		$parametros = array('alumno' => $alumno,
							'plan_version' => $plan_version,
							'constancia' => $constancia,
							'fecha' => guarani_fecha::get_hoy());

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800CON_error_control_solicitud'));
		}
	}
}
?>