<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\modelo\datos\catalogo;
use siu\guarani;

class plan_estudios extends transaccion_alumno
{
	
	protected $operacion = 'mat000xxxx';
	protected $evento = '2';
	protected $detalle_plan;

	const CORRELATIVA_CURSAR = 'C';
	const CORRELATIVA_APROBAR = 'A';
	const REGLA_CORRELATIVA_CURSAR = 307;
	const REGLA_CORRELATIVA_APROBAR = 308;

	/**
	 * Retorna la composición del plan de estudios
	 *
	 * @return type
	 */
	function get_detalle_plan_estudios()
	{
		if (!isset($this->detalle_plan)) {
            $datos = catalogo::consultar('alumno', 'get_detalle_plan_estudios', array( 'plan_version' => $this->plan_version ));
			$this->detalle_plan['detalle'] = $datos['detalle'];
			$this->detalle_plan['datos_carrera']['plan'] = $this->plan;
			//$this->detalle_plan['promedios'] = $promedios;
		}
		return $this->detalle_plan;
	}

	/**
	 * Retorna la composición del plan de estudios con información adicional de la historia académica de cada materia del plan
	 *
	 * @return array
	 *
	 */
    function get_contenido_plan_version_con_historia_academica($params = []){

		if (!isset($this->detalle_plan)) {

			$this->detalle_plan = \alumno::get_contenido_plan_version_con_historia_academica($this->alumno, $this->plan_version, $params);
			kernel::log()->add_debug('=== plan_estudios/get_contenido_plan_version_con_historia_academica ===', $this->detalle_plan);
			
		}

		return $this->detalle_plan;
	}

	
	function get_correlativas($elemento)
	{
		$datos = catalogo::consultar('alumno', 'get_correlativas',
									array(
					'plan_version' => $this->plan_version,
					'elemento' => $elemento
						)
		);

		return $datos;
		
	}

	function get_optativas_generica($materia_generica)
	{

		$datos = catalogo::consultar('alumno', 'get_optativas_generica',
									array('carrera'=>kernel::persona()->get_id_propuesta_activa(),
					'plan' => $this->plan,
					'version' => $this->plan_version,
					'materia' => $materia_generica)
		);

		return $datos;
	}
	
	function get_optativas_genericas_alumno()
	{
		
		$materias_genericas_alumno = catalogo::consultar('alumno', 'get_materias_genericas_alumno', [
					'plan_version' => $this->plan_version
				]);
		
		$datos = [];
		foreach($materias_genericas_alumno as $dato){
			$datos[$dato['optativa_elemento']] = $dato['optativa_puntaje'];
		}
		
		return $datos;
	}
	
	// Obtiene las materias optativas de una genérica
	function get_optativas_generica_ajax($materia_generica)
	{	
		$optativas = \alumno::get_optativas_plan_version_con_historia_academica($this->alumno, $this->plan_version, $materia_generica);
		kernel::log()->add_debug('=== alumno/get_optativas_plan_version_con_historia_academica ===', $optativas);
		return $optativas;
	}

	function get_info_titulo()
	{
		$datos_titulo = catalogo::consultar(	'alumno', 'get_titulo_grado',
											array('carrera'=>kernel::persona()->get_id_propuesta_activa(),
											'plan'   => $this->plan)
		);
		return $datos_titulo;
	}

	/**
	 * Verificar las correlativas
	 *
	 * @param $tipo_correlativa
	 * @param $materia
	 */
    function verificar_correlativas($tipo_correlativa, $materia){

		//$tipo_correlativa A: aprobar | C: cursar
		switch ($tipo_correlativa) {
			case self::CORRELATIVA_APROBAR:
				$regla = self::REGLA_CORRELATIVA_APROBAR;
				break;
			case self::CORRELATIVA_CURSAR:
				$regla = self::REGLA_CORRELATIVA_CURSAR;
				break;
			default:
				throw new error_guarani(kernel::traductor()->trans("plan_estudio.error_verificando_correlativas"));
		}

		$regla = \guarani::regla($regla);
		$parametros_contexto['fecha'] = \guarani_fecha::get_hoy();
		$parametros_contexto['alumno'] = guarani::persona()->get_id_alumno_activo();
		$parametros_contexto['elemento'] = $materia;
		$parametros_contexto['plan_version'] = $this->plan_version;

		$regla->set_parametros_contexto($parametros_contexto);
		$cumple_regla = $regla->validar();
		$mensajes = $regla->get_mensaje_validacion();
		return array($cumple_regla, $mensajes);
	}

	/**
	 * Verificar las cumplimientos
	 *
	 * @param $regla
	 * @param $parametros
	 * @param $materia
	 */
	function verificar_cumplimientos($regla, $materia, $parametros)
	{

		$regla = \guarani::regla($regla);
		$parametros_contexto['fecha'] = \guarani_fecha::get_hoy();
		$parametros_contexto['alumno'] = guarani::persona()->get_id_alumno_activo();
		$parametros_contexto['elemento'] = $materia;
		$parametros_contexto['plan_version'] = $this->plan_version;
		$regla->set_parametros($parametros);
		$regla->set_parametros_contexto($parametros_contexto);
		$cumple_regla = $regla->validar();
		$mensajes = $regla->get_mensaje_validacion();
		return array($cumple_regla, $mensajes);
	}

}