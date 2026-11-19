<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_usuario;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;

class reinscripcion_carrera extends transaccion_alumno
{
	protected $operacion = '800SIUMAT029';
	protected $anio_academico_reinscripcion;

	function puede_reinscribirse()
	{
		// Hay periodos de reinscripcion
		$periodo_reinscripcion = catalogo::consultar('matricula', 'periodos_reinscripcion', [
			'plan_version'	=> $this->plan_version, 
			'alumno'		=> $this->alumno
		]);

		if (!$periodo_reinscripcion) {
			throw new error_guarani_usuario(kernel::traductor()->trans('800MAT_sin_periodo_reinscripcion'), 1);
		}
		
		$this->anio_academico_reinscripcion = $periodo_reinscripcion['anio_academico'];

		// Propuestas a reinscribirse
		$datos = catalogo::consultar('matricula', 'carreras_a_reinscribirse', [
					'persona'						=> $this->persona,
					'anio_academico_reinscripcion'	=> $this->anio_academico_reinscripcion
		]);
		
		// La propuesta actual no esta dentro de las posibles a reinscribirse
		if (!isset($datos[$this->propuesta]) ) {
			throw new error_guarani_usuario(kernel::traductor()->trans('800MAT_reinscripcion_carrera_inexistente'), 2);
		}
		
		// No es activo en la propuesta
		if ($datos[$this->propuesta]['calidad'] != \alumno::calidad_activo) {
			throw new error_guarani_usuario(kernel::traductor()->trans('800MAT_reinscripcion_carrera_inactivo'), 3);
		}
		
		// Ya se encuentra reinscripto en la propuesta
		if ($datos[$this->propuesta]['reinscripto'] == 1) {
			throw new error_guarani_usuario(kernel::traductor()->trans('800MAT_reinscripcion_carrera_ya_reinscripto'), 4);
		}

		$reinscripcion_propuesta = parametro::get_valor('mat_reinscripcion_propuesta', $this->propuesta);

		if ($reinscripcion_propuesta == \comunes::no) {
			throw new error_guarani_usuario(kernel::traductor()->trans('800MAT_propuesta_sin_reinscripcion'), 5);
		}

		// Controlar si es reingresante y tiene que inscribirse
		$es_ingresante = catalogo::consultar('alumno', 'es_ingresante_carrera', [
			'alumno'			=> $this->alumno,
			'anio_academico'	=> $this->anio_academico_reinscripcion
		]);

		$reinscribir_ingresante = parametro::get_valor('mat_reinscripcion_ingresante', $this->propuesta);

		if ($es_ingresante == 1 && $reinscribir_ingresante == \comunes::no) {
			throw new error_guarani_usuario(kernel::traductor()->trans('800MAT_reinscripcion_ingresantes'), 6);
		}
	}

	protected function disparar_puntos_de_control($anio_academico)
	{
		$puntos_de_control = array(
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::alumno_anio_academico
		);

		$parametros = array(
			'persona'			=> $this->persona,
			'alumno'			=> $this->alumno,
			'plan_version'		=> $this->plan_version,
			'fecha'				=> \guarani_fecha::get_hoy(),
			'anio_academico'	=> $anio_academico,
		);

		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();

		return array($resultado_controles, $mensajes_controles);
	}

	function controles_reinscripcion()
	{
		// Disparar controles
		try {
			list($resultado_controles, $mensajes) = $this->disparar_puntos_de_control($this->anio_academico_reinscripcion);
		} catch (error_guarani $e) {
			$mensajes = $e->get_mensajes_usuario();
		}

		$mensajes_salida = array();
		foreach ($mensajes as $mensaje) {
			if ($mensaje['mtipo'] == "E") {
				throw new error_guarani_usuario($mensaje['mcuerpo']);
			}
			$mensajes_salida[] = $mensaje;
		}
		return $mensajes_salida;
	}

	function reinscribir()
	{
		$ok = false;
		$advertencias = "";
		$mensajes = "";
		try {
			$advertencias = $this->controles_reinscripcion();

			$ok = catalogo::consultar('matricula', 'reinscripcion', [
						'plan_version' => $this->plan_version,
						'alumno' => $this->alumno
			]);
		} catch (error_guarani_usuario $e) {
			throw new error_guarani_usuario($e->get_mensaje(), 7);
		}

		kernel::log()->add_debug('REINSCRIPCION', $ok);
		if (!$ok) {
			$mensajes = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('800MAT_reinscripcion_fallo_ctrl'));
		}
		return array('estado' => $ok, 'mensaje' => $mensajes, 'advertencia' => $advertencias);
	}

	function datos_comprobante($nro_trans)
	{

		return catalogo::consultar('matricula', 'datos_reinscripcion', ['nro_trans' => $nro_trans]);
		
	}

}
?>