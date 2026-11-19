<?php

namespace nsg01\operaciones\datos_facturacion;

use nsg01\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\modelo\datos\catalogo;

class controlador extends controlador_g3w2
{

	/**
	 * @return \siu\modelo\transacciones\datos_facturacion
	 */
	function modelo()
	{
		return guarani::datos_facturacion();
	}

	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'cargar_datos_facturacion':
				return 'vista_cargar_datos_facturacion';
			default:
				return 'vista';
		}
	}

	static function send_ui_message($mtype, $msg, $pagelet) {
        $message['mensajes'][] = [
            'mtipo' => $mtype,
            'mcuerpo' => kernel::traductor()->trans($msg)
        ];
		$pagelet->data['resultado'] = $message;
    }

	static function set_js_error($pagelet_cdf, $error, $msg) {
		$pagelet_cdf->add_var_js('error', $error);
		if ($error) { 
			$pagelet_cdf->add_mensaje_js('error_msj', kernel::traductor()->trans($msg));
		}
	}

	function accion__index() {}

	static function set_pagelet_cdf_default($alumno, $pagelet_cdf) {
		$pagelet_cdf->data['alumno'] = $alumno;
		$pagelet_cdf->data['porcentaje_facturacion_alumno'] = 100;
		$pagelet_cdf->data['forma_pago'] = null;
		$pagelet_cdf->data['medio_pago_alumno_mat'] = 3;
		$pagelet_cdf->data['nro_tarjeta_alumno_mat'] = null;
		$pagelet_cdf->data['cbu_alumno_mat'] = null;
		$pagelet_cdf->data['medio_pago_alumno_cuota'] = 3;
		$pagelet_cdf->data['nro_tarjeta_alumno_cuota'] = null;
		$pagelet_cdf->data['cbu_alumno_cuota'] = null;
		$pagelet_cdf->data['pago_a_cargo_empresa'] = 'N';
		$pagelet_cdf->data['porcentaje_facturacion_empresa'] = null;
		$pagelet_cdf->data['medio_pago_empresa_mat'] = null;
		$pagelet_cdf->data['nro_tarjeta_empresa_mat'] = null;
		$pagelet_cdf->data['cbu_empresa_mat'] = null;
		$pagelet_cdf->data['medio_pago_empresa_cuota'] = null;
		$pagelet_cdf->data['nro_tarjeta_empresa_cuota'] = null;
		$pagelet_cdf->data['cbu_empresa_cuota'] = null;
		$pagelet_cdf->data['empresa'] = null;
		$pagelet_cdf->data['email_empresa'] = null;
		$pagelet_cdf->data['observaciones'] = null;
		$pagelet_cdf->data['porcentajes_invalidos'] = false;
		$pagelet_cdf->data['forma_pago_vacia'] = false;
		$pagelet_cdf->data['medio_pago_vacio'] = false;
	}

	function accion__cargar_datos_facturacion()
	{
		// $parametros['persona'] = kernel::persona()->get_id();
		// $es_perfil_cursos = kernel::persona()->es_perfil_cursos();

		// if (!$es_perfil_cursos) {
		// 	$alumno = kernel::persona()->get_id_alumno_activo();
		// 	$parametros['alumno'] = array('alumno' => $alumno);
		// } else {
		// 	$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', array('persona' => $parametros['persona']));

		// 	foreach ($propuestas as &$propuesta) {
		// 		$propuesta['cursos'] = catalogo::consultar('cursos', 'get_cursos_academica', array('persona' => $parametros['persona'], 'propuesta' => $propuesta['propuesta']));
		// 	}
		// 	$parametros['propuesta'] = $propuesta['propuesta'];

		// 	$alumno = catalogo::consultar('alumno', 'get_alumno', array('persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']));
		// 	$parametros['alumno'] = $alumno;
		// 	$alumno = $parametros['alumno']['alumno'];
		// }

				$parametros['persona'] = kernel::persona()->get_id();
		$es_perfil_cursos = kernel::persona()->es_perfil_cursos();

		if (!$es_perfil_cursos) {
			$alumno = kernel::persona()->get_id_alumno_activo();
			$parametros['alumno'] = array('alumno' => $alumno);
		}
		else {

			$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', array('persona' => $parametros['persona']));
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];
			//var_dump($propuestas);
			foreach($propuestas as &$propuesta) {
				$propuesta['cursos'] = catalogo::consultar('cursos', 'get_cursos_academica', $pers_prop_param);
			}

			$parametros['propuesta'] = $propuesta['propuesta'];
			///var_dump($propuesta['propuesta']);

			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];

			$alumno = catalogo::consultar('alumno', 'get_alumno', $pers_prop_param);
			$parametros['alumno'] = $alumno;
		}

		$datos_facturacion = catalogo::consultar('datos_facturacion', 'get_datos_facturacion_alumno', $parametros['alumno']);
		$pagelet_cdf = $this->vista()->pagelet('pagelet');
		static::set_pagelet_cdf_default($alumno, $pagelet_cdf);

		// Si estoy guardando
		if (kernel::request()->isPost()) {
			try {
				$errores_validacion = false;

				$forma_pago	= $this->validate_param('forma_pago', 'post', validador::TIPO_INT, array('default' => null));
				$porcentaje_facturacion_alumno = $this->validate_param('porcentaje_facturacion_alumno', 'post', validador::TIPO_INT, array('default' => 100));
				$medio_pago_alumno_mat = $this->validate_param('medio_pago_alumno_mat', 'post', validador::TIPO_INT, array('default' => 3));
				$nro_tarjeta_alumno_mat	= $this->validate_param('nro_tarjeta_alumno_mat', 'post', validador::TIPO_TEXTO, array('default' => null));
				$medio_pago_alumno_cuota = $this->validate_param('medio_pago_alumno_cuota', 'post', validador::TIPO_INT, array('default' => 3));
				$nro_tarjeta_alumno_cuota = $this->validate_param('nro_tarjeta_alumno_cuota', 'post', validador::TIPO_TEXTO, array('default' => null));
				$cbu_alumno_mat	= $this->validate_param('cbu_alumno_mat', 'post', validador::TIPO_TEXTO, array('default' => null));
				$cbu_alumno_cuota = $this->validate_param('cbu_alumno_cuota', 'post', validador::TIPO_TEXTO, array('default' => null));
				$observaciones = $this->validate_param('observaciones', 'post', validador::TIPO_TEXTO, array('default' => null));
				
				kernel::log()->add_debug('=== forma_pago ===', $forma_pago);
				kernel::log()->add_debug('=== medio_pago_alumno_mat ===', $medio_pago_alumno_mat);
				kernel::log()->add_debug('=== medio_pago_alumno_cuota ===', $medio_pago_alumno_cuota);

				static::set_js_error($pagelet_cdf, false, '');

				$pagelet_cdf->data['alumno'] = $alumno;
				$pagelet_cdf->data['porcentaje_facturacion_alumno'] = $porcentaje_facturacion_alumno;
				$pagelet_cdf->data['forma_pago'] = $forma_pago;
				$pagelet_cdf->data['medio_pago_alumno_mat'] = $medio_pago_alumno_mat;
				$pagelet_cdf->data['nro_tarjeta_alumno_mat'] = $nro_tarjeta_alumno_mat;
				$pagelet_cdf->data['cbu_alumno_mat'] = $cbu_alumno_mat;
				$pagelet_cdf->data['medio_pago_alumno_cuota'] = $medio_pago_alumno_cuota;
				$pagelet_cdf->data['nro_tarjeta_alumno_cuota'] = $nro_tarjeta_alumno_cuota;
				$pagelet_cdf->data['cbu_alumno_cuota'] = $cbu_alumno_cuota;
				$pagelet_cdf->data['observaciones'] = $observaciones;

				if ($medio_pago_alumno_cuota == 2 && (empty($cbu_alumno_cuota) || strlen($cbu_alumno_cuota) != 22)) {
					static::send_ui_message('E', 'datos_facturacion.cbu.invalido', $pagelet_cdf);
					$errores_validacion = true;
				}
				
				if ($medio_pago_alumno_mat == 2 && (empty($cbu_alumno_mat) || strlen($cbu_alumno_mat) != 22)) {
					static::send_ui_message('E', 'datos_facturacion.cbu.invalido', $pagelet_cdf);
					$errores_validacion = true;
				}

				if ($medio_pago_alumno_cuota == 1 && (empty($nro_tarjeta_alumno_cuota) || !$this->validateCreditCard($nro_tarjeta_alumno_cuota))) {
					static::send_ui_message('E', 'datos_facturacion.tarjeta.invalida', $pagelet_cdf);
					$errores_validacion = true;
				} 

				if ($medio_pago_alumno_mat == 1 && (empty($nro_tarjeta_alumno_mat) || !$this->validateCreditCard($nro_tarjeta_alumno_mat))) {
					static::send_ui_message('E', 'datos_facturacion.tarjeta.invalida', $pagelet_cdf);
					$errores_validacion = true;
				} 

				if ($porcentaje_facturacion_alumno != 100) {
					static::set_js_error($pagelet_cdf, true, 'datos_facturacion.error_suma_no_coincide');
					$errores_validacion = true;
				}

				if (is_null($medio_pago_alumno_mat) && is_null($medio_pago_alumno_cuota)) {
					static::set_js_error($pagelet_cdf, true, 'datos_facturacion.error_al_procesar_solicitud');
					$pagelet_cdf->data['medio_pago_vacio'] = true;
					$errores_validacion = true;
				}

				if (is_null($forma_pago)) {
					static::set_js_error($pagelet_cdf, true, 'datos_facturacion.error_al_procesar_solicitud');
					$pagelet_cdf->data['forma_pago_vacia'] = true;
					$errores_validacion = true;
				}

				$this->verificar_cumplimiento_condiciones($errores_validacion, $pagelet_cdf, $datos_facturacion);	
			} 
			catch (\Exception $e) {
				// Si es una redirecci�n la relanzo
				if ($e instanceof NuevoRequest) {
					throw $e;
				}
				kernel::log()->add_error($e);
				static::set_js_error($pagelet_cdf, true, 'datos_facturacion.error_al_procesar_solicitud');
			}
		}
	}

	function verificar_cumplimiento_condiciones($errores_validacion, $pagelet_cdf, $datos_facturacion) {
		if (!$errores_validacion) {
			$operacion = kernel::ruteador()->get_id_operacion();
			$estado = (empty($datos_facturacion)) ? 'I' : 'U';
			$cumple_condiciones = $this->modelo()->evt__confirmar_datos_facturacion($estado, $pagelet_cdf->data);

			if ($cumple_condiciones) {
				kernel::sesion()->set('guardado_ok', true);
				$this->nuevo_request($operacion, 'index');
			} 
			else 
			{ 
				static::set_js_error($pagelet_cdf, true, 'datos_facturacion.no_cumplis_condiciones');
			}
		}
	}

	function validateCreditCard($number)
	{
		// Remove any non-digit characters (e.g., spaces, hyphens)
		$number = preg_replace('/\D/', '', $number);

		if (strlen($number) < 13 || strlen($number) > 19) {
			return false;
		}

		$sum = 0;
		$numDigits = strlen($number);
		$parity = $numDigits % 2;

		for ($i = 0; $i < $numDigits; $i++) {
			$digit = (int)$number[$i];

			if ($i % 2 === $parity) {
				$digit *= 2;

				if ($digit > 9) {
					$digit -= 9;
				}
			}
			$sum += $digit;
		}

		return ($sum % 10) === 0;
	}
}
