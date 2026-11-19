<?php

namespace siu\operaciones\inicio_alumno;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\extension_kernel\qrcode;
use SIU\Chulupi\util\validador;
use siu\modelo\transacciones\solicitar_certificacion;

class controlador extends controlador_g3w2
{
	/**
	 *
	 * @return \siu\modelo\transacciones\inscripcion_cursada 
	 */
	function modelo()
	{
		return null;
	}

	function accion__index()
	{
		$this->vista()->agregar_a_contexto('persona', kernel::persona()->get_nombre());
		$requisitos_ingreso = $this->info_requisitos_ingreso();
		
		$band_boton = false;
		
		$fecha_actual = new \DateTime('today');
		$fecha_actual = \guarani_fecha::get_hoy();

		$this->vista()->agregar_a_contexto('url_requisito_pendiente', '');
		$this->vista()->agregar_a_contexto('requisitos_ingreso', '');
		$this->vista()->agregar_a_contexto('requisitos_pendientes', '');

		$this->vista()->agregar_a_contexto('estado_no_presentado', solicitar_certificacion::estado_no_presentado);
		$this->vista()->agregar_a_contexto('estado_pendiente_validacion', solicitar_certificacion::estado_pendiente_validacion);
		$this->vista()->agregar_a_contexto('estado_presentado', solicitar_certificacion::estado_presentado);
		$this->vista()->agregar_a_contexto('estado_vencido', solicitar_certificacion::estado_vencido);

		$requisitos_pendientes = [];
		if (!empty($requisitos_ingreso)) {
			foreach ($requisitos_ingreso as $key => $req) {
				// Si tiene 'Fecha límite presentación'
				if ($req['f_tope_presentacion']) {
					// Si 'Fecha límite presentación' es mayor o igual a hoy
					if ($req['esta_vigente_para_presentar']) {
						$band_boton = true;
						array_push($requisitos_pendientes, $req);
						unset($requisitos_ingreso[$key]);
					}
				}
			}
			if ($band_boton) {
				$this->vista()->agregar_a_contexto('url_requisito_pendiente', kernel::vinculador()->crear('requisitos_ingreso', 'index'));
				$this->vista()->agregar_a_contexto('requisitos_pendientes', $requisitos_pendientes);
			}
		}
		$this->vista()->agregar_a_contexto('requisitos_ingreso', $requisitos_ingreso);
		/* $parametros['persona_id'] = kernel::persona()->get_id();
		  $tiene_noticias = catalogo::consultar('noticias', 'tiene_noticias_por_persona_id', $parametros);
		  $this->vista()->agregar_a_contexto('tiene_noticias', $tiene_noticias); */
		$this->vista()->agregar_a_contexto('tiene_noticias', false);
		if (kernel::persona()->perfil()->get_operacion_activa('readmision')) {
			$this->vista()->agregar_a_contexto('operacion_readmision_activa', true);

			try {

				guarani::readmision()->puede_readmitirse();
				$this->vista()->agregar_a_contexto('debe_readmitirse', true);
				$this->vista()->agregar_a_contexto('alumno_activo', true);
				$this->vista()->agregar_a_contexto('url_readmision', kernel::vinculador()->crear('readmision', 'procesar_readmision'));
			} catch (\siu\errores\error_guarani_usuario $e) {

				$this->vista()->agregar_a_contexto('debe_readmitirse', false);
				$this->vista()->agregar_a_contexto('alumno_activo', false);
				$this->vista()->agregar_a_contexto('url_readmitirsion', '#');
			}
		} else {
			$this->vista()->agregar_a_contexto('operacion_readmision_activa', false);
		}
		try {
			guarani::reinscripcion_carerra()->puede_reinscribirse();
			$this->vista()->agregar_a_contexto('debe_reinscribirse', true);
			$this->vista()->agregar_a_contexto('alumno_activo', true);
			$this->vista()->agregar_a_contexto('url_reinscripcion', kernel::vinculador()->crear('reinscripcion', 'reinscribir'));
		} catch (\siu\errores\error_guarani_usuario $e) {
			$codigo = $e->getCode();
			// No es activo en la propuesta
			if ($codigo == 3) {
				$this->vista()->agregar_a_contexto('debe_reinscribirse', true);
				$this->vista()->agregar_a_contexto('alumno_activo', false);
				$this->vista()->agregar_a_contexto('url_reinscripcion', '#');
			}
		}
		if (kernel::sesion()->esta_seteada('mensaje_readmision2')) {
			$this->vista()->agregar_a_contexto('mensaje_readmision2', kernel::sesion()->get('mensaje_readmision2'));
			kernel::sesion()->borrar('mensaje_readmision2');
		}

		if (kernel::sesion()->esta_seteada('operacion_readmision_activa')) {
			$this->vista()->agregar_a_contexto('operacion_readmision_activa', kernel::sesion()->get('operacion_readmision_activa'));
			kernel::sesion()->borrar('operacion_readmision_activa');
		}
	}

	function info_periodos_lectivos()
	{
		$parametros = array(
			'plan_version' => guarani::persona()->get_id_plan_version_activo(),
		);
		return catalogo::consultar('unidad_academica', 'periodos_lectivos_con_fechas_insc', $parametros);
	}

	function info_turnos_examen()
	{
		// visualiza turnos de examen actuales y futuros.
		$parametros = array(
			'plan_version' => guarani::persona()->get_id_plan_version_activo(),
		);
		return catalogo::consultar('unidad_academica', 'turnos_examen_por_plan', $parametros);
	}

	function info_requisitos_ingreso()
	{
		$parametros = [
			'alumno' => kernel::persona()->get_id_alumno_activo(),
			'estado_req_digital' => [solicitar_certificacion::estado_no_presentado, solicitar_certificacion::estado_vencido]
		];
		return catalogo::consultar('alumno', 'get_requisitos_ingreso_propuesta', $parametros);
	}

	function info_encuestas_pendientes()
	{
		return kernel::persona()->get_encuestas_pendientes();
	}

	function info_lista_noticias()
	{
		$parametros['persona_id'] = kernel::persona()->get_id();
		return catalogo::consultar('noticias', 'noticias_por_persona_id', $parametros);
	}

	function accion__qr_periodo()
	{
		$periodos = $this->info_periodos_lectivos();

		$params = explode('$', $this->validate_param(0, 'get', validador::TIPO_TEXTO));
		$hash_periodo = $params[0];
		$periodo_insc_interno = $params[1];

		foreach ($periodos as $periodo) {
			if ($periodo['__ID__'] == $hash_periodo) {
				$title = $periodo["periodo_lectivo_nombre"];

				$periodo_insc_fecha = $periodo['periodos_insc'][$periodo_insc_interno];

				// Formateo 
				$datei = substr($periodo_insc_fecha["fecha_inicio_insc_sf"], 0, -3);
				$datei = str_replace("-", "", $datei);
				$datei = str_replace(" ", "T", $datei);
				$datei = str_replace(":", "", $datei);

				$datef = substr($periodo_insc_fecha["fecha_fin_insc_sf"], 0, -3);
				$datef = str_replace("-", "", $datef);
				$datef = str_replace(" ", "T", $datef);
				$datef = str_replace(":", "", $datef);

				$qr = new qrcode($title, $datei, $datef);
				$qr->get_code_periodos();
				$this->finalizar_request();
			}
		}
		return false;
	}

}