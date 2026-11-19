<?php
/**
 * Procesos
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */
class cn_proceso_nucleo extends guarani_pers_cn
{
	protected $msg_exito;
	
	function procesar($parametros = NULL, $transaccionar = true, $limpiar_memoria = true)
	{
		$ret = false;
		try {
			toba::db('guarani')->abrir_transaccion();
			$ret = $this->procesar_especifico($parametros);	
			toba::db('guarani')->cerrar_transaccion();
			$this->mostrar_mensaje_exito();
			return $ret;
		} catch (guarani_error $e ) {
			toba::db('guarani')->abortar_transaccion();
			toba::notificacion()->agregar($e->get_mensaje_log());
			return;
		}
	}

	function procesar_especifico($parametros){}
	
	function set_mensaje_exito($msg) 
	{
		$this->msg_exito = $msg;
	}
	
	function mostrar_mensaje_exito() 
	{
		if (isset($this->msg_exito)) {
			toba::notificacion()->info($this->msg_exito);
		}
	}		
	
}
?>