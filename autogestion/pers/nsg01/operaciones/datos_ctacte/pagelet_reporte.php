<?php

namespace nsg01\operaciones\datos_ctacte;

use PlusPagos\AESEncrypter;
use PlusPagos\SHA256Encript;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

use nsg01\modelo\facturacion\nsg_g3w3_facturacion;

class pagelet_reporte extends pagelet
{
	protected $facturacion;

	function get_nombre()
	{
		return 'reporte';
	}

	/**
	 * Shortcut al modelo
	 * 
	 * @return \siu\modelo\transacciones\datos_ctacte
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	function prepare()
	{
		$this->data['ctactes'] = [];
		$this->data['mensaje_contexto'] = null;
		$this->data['propuestas_cursos'] = [];
		$this->data['propuesta'] = null;
		$this->data['url_consultar'] = kernel::vinculador()->crear('datos_ctacte', 'index');
		if (kernel::persona()->es_perfil_cursos()) {
			$this->preparar_propuesta_cursos();
		}
		$facturacion = new nsg_g3w3_facturacion(true);
		if (is_null($this->data['mensaje_contexto'])) {
			$ctactes = $facturacion->get_datosFacturacion(['propuesta' => $this->data['propuesta']]);
			if (is_null($ctactes)) {
				$this->data['mensaje_contexto'] = kernel::traductor()->trans('datos_ctacte.sin_alumno');
			} else {
				$this->data['ctactes'] = $ctactes;
				if (!empty($ctactes)) {
					$this->data['mcp_data'] = $facturacion->get_mcpData();
				}
			}
		}
		$this->add_js_vars();
		$this->check_hubo_error();
	}

	protected function preparar_propuesta_cursos()
	{
		$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', [
			'persona' => kernel::persona()->get_id()
		]);
		$this->data['propuestas_cursos'] = $propuestas;
		if (empty($propuestas)) {
			$this->data['mensaje_contexto'] = kernel::traductor()->trans('datos_ctacte.sin_alumno');
			return;
		}
		$seleccionada = $this->controlador->get_propuesta_ctacte();
		if (is_null($seleccionada)) {
			// La propuesta de Inscripcion a Cursos puede ser una oferta sin inscripcion.
			if (kernel::sesion()->esta_seteada('__propuesta')) {
				$anterior = kernel::sesion()->get('__propuesta');
				foreach ($propuestas as $propuesta) {
					if ((string) $propuesta['propuesta'] === (string) $anterior) {
						$seleccionada = $propuesta['propuesta'];
						break;
					}
				}
			}
			if (is_null($seleccionada) && count($propuestas) == 1) {
				$unica = current($propuestas);
				$seleccionada = $unica['propuesta'];
			}
		}
		foreach ($propuestas as $propuesta) {
			if ((string) $propuesta['propuesta'] === (string) $seleccionada) {
				$this->data['propuesta'] = $propuesta['propuesta'];
				return;
			}
		}
		$mensaje = is_null($seleccionada) ? 'datos_ctacte.seleccionar_propuesta' : 'datos_ctacte.propuesta_invalida';
		$this->data['mensaje_contexto'] = kernel::traductor()->trans($mensaje);
	}

	function add_js_vars() 
	{
		$this->add_var_js('propuesta', $this->data['propuesta']);
		$this->add_var_js('error_pago', kernel::traductor()->trans('datos_ctacte.error_preparar_pago'));
		$this->add_var_js('url_pagar_mcp', kernel::vinculador()->crear('datos_ctacte', 'pagar_mcp'));
		$this->add_var_js('url_pagar_mp', kernel::vinculador()->crear('datos_ctacte', 'pagar_mp'));
		$this->add_var_js('msj_confirmar_pagar_mcp', kernel::traductor()->trans('datos_ctacte.msj_confirmar_pagar_mcp'));
		$this->add_var_js('msj_confirmar_pagar_mp', kernel::traductor()->trans('datos_ctacte.msj_confirmar_pagar_mp'));
		
	}

	function check_hubo_error()
	{
		if (kernel::sesion()->esta_seteada('hubo_error')) {

			$this->data['mostrar_mensaje'] = true;

			$hubo_error = kernel::sesion()->get('hubo_error');

			// Si hubo error
			if ($hubo_error) {
				$this->data['tipo_mensaje'] = static::TIPO_MENSAJE_ERROR;
				$this->data['mensaje']		= kernel::traductor()->trans('pagar_con_mp.error_confirmar_pago');
			} // si no hubo error 
			else {
				$this->data['tipo_mensaje'] = static::TIPO_MENSAJE_EXITO;
				$this->data['mensaje']		= kernel::traductor()->trans('pagar_con_mp.pago_exitoso');
			}

			kernel::sesion()->borrar('hubo_error');
		}
	}


	function send_ui_message($mtype, $msg)
	{
		$message['mensajes'][] = [
			'mtipo' => $mtype,
			'mcuerpo' => kernel::traductor()->trans($msg)
		];

		$this->data['resultado'] = $message;
	}
}
