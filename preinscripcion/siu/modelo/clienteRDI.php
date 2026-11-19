<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\errores\error_preinscripcion;
use siu\extension_kernel\chulupi_logger_rdi;

class clienteRDI
{
	protected $clienteRdi;

	function __construct()
	{
		if (!kernel::proyecto()->usa_rdi()) {
			throw new error_preinscripcion('El sistema no está configurado para usar RDI.');
		}
		\RDIAutoload::registrar();
	}

	function get_cliente()
	{
		if (!isset($this->clienteRdi)) {
			$this->clienteRdi = $this->instanciar_cliente();
		}
		return $this->clienteRdi;
	}

	//--------------------------------------------------------------------------
	//---- Métodos auxiliares
	//--------------------------------------------------------------------------

	protected function instanciar_cliente()
	{
		$parametros = kernel::proyecto()->get_parametros_rdi();
		$rdi = new \RDICliente(
				$parametros['conector'], $parametros['repositorio'], $parametros['usuario'], $parametros['clave'], $parametros['proyecto'], $_SERVER['ID_PTO_ACCESO'] // Instalación
		);
		$log = new chulupi_logger_rdi();
		$log->set_activo(true);
		$rdi->asociarLog($log);
		return $rdi;
	}

}
?>