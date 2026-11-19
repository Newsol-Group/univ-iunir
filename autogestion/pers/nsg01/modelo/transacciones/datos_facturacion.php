<?php

namespace nsg01\modelo\transacciones;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
//use siu\modelo\transacciones;
use siu\modelo_g3\gestion_rest;

class datos_facturacion extends \siu\modelo\transacciones\transaccion
{
	
	function info__get_datos_facturacion_alumno($alumno)
	{
		$datos = catalogo::consultar('datos_facturacion', 'get_datos_facturacion_alumno', array('alumno' => $alumno));
		return $datos;
	}
	
	function info__medios_pago()
	{
		$datos = catalogo::consultar('datos_facturacion', 'get_medios_pago');
		return $datos;
	}
	
	function info__formas_pago($alumno)
	{
		$datos = catalogo::consultar('datos_facturacion', 'get_formas_pago_alumno', $alumno);
		return $datos;
	}
	

	function info__descr_medio_pago($medio_pago)
	{
		$parametros = array('medio_pago' => $medio_pago);
		$datos = catalogo::consultar('datos_facturacion', 'get_descr_medio_pago', $parametros);
		return $datos;
	}

	function info__descr_forma_pago($forma_pago)
	{
		$parametros = array('forma_pago' => $forma_pago);
		$datos = catalogo::consultar('datos_facturacion', 'get_descr_forma_pago', $parametros);
		return $datos;
	}

	function info__descr_empresa($empresa)
	{
		$parametros = array('empresa' => $empresa);
		$datos = catalogo::consultar('datos_facturacion', 'get_descr_empresa', $parametros);
		return $datos;
	}

	function info__empresas()
	{
		$datos = catalogo::consultar('datos_facturacion', 'get_empresas');
		return $datos;
	}
		
	function evt__confirmar_datos_facturacion($estado, $datos_facturacion)
	{

		try{
			kernel::db()->abrir_transaccion();		
			$this->evt__guardar_datos_facturacion($estado, $datos_facturacion);
			kernel::db()->cerrar_transaccion();

		if (function_exists('apcu_clear_cache')) {
			apcu_clear_cache();
		} 
		return true;
		}
		catch (\Exception $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}

	function evt__guardar_datos_facturacion($estado, $datos_facturacion)
	{
		if ($estado == 'I') {
			return catalogo::consultar('datos_facturacion', 'registrar_datos_facturacion', array('datos_facturacion' => $datos_facturacion));
		} else {
			return catalogo::consultar('datos_facturacion', 'actualizar_datos_facturacion', array('datos_facturacion' => $datos_facturacion));
		}

		if (function_exists('apcu_clear_cache')) {
			apcu_clear_cache();
		} 		


	}

}
