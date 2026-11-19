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
		$this->add_js_vars();

		$facturacion = new nsg_g3w3_facturacion(true);

        // $pid = kernel::persona()->get_id();
		// if ($pid == 2) { 
		// 	//exit;
		// $ctactes = $facturacion->get_datosFacturacion(['alumno' => 1692]);
		// }else {
		// 	$ctactes = $facturacion->get_datosFacturacion([]);
		// }
		// //$

		
		$ctactes = $facturacion->get_datosFacturacion([]);

		kernel::log()->add_debug('CtaCte: ', $ctactes);

		$this->data['ctactes'] = $ctactes;
		$this->data['mcp_data'] = $facturacion->get_mcpData();

		$this->check_hubo_error();
	}

	function add_js_vars() 
	{
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
