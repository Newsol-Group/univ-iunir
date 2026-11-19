<?php

namespace siu\extension_kernel;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\log;

/**
 * Mantiene una serie de sucesos generados durante la interaccion con el ECM  no visibles al usuario y los almacena para el posterior analisis
 * Los sucesos tienen una categoria (debug, info, error, etc.) y el proyecto que la produjo
 * 
 * @package Debug
 */
class chulupi_logger_rdi implements \RDILog {
	
	/** @var log */
	private $log;
	private $_activo = false;
	
	function __construct()
	{
		$this->log = kernel::log();
	}
	
	function set_activo($activo)
	{
		$this->_activo = $activo;
	}	
	
	function add_debug($etiqueta, $valor_a_dumpear, $tabla=false)
	{
		
		if (! $this->_activo) {
			return;
		}

		$this->log->add_debug('DEBUG RDI', $etiqueta);
		if (is_array($valor_a_dumpear)) {
			if (!$tabla) {
				$this->log->add_debug('DEBUG RDI', $valor_a_dumpear);
			} else {
				$this->log->add_debug('DEBUG RDI', var_export($valor_a_dumpear, true));
			}
		} else {
			if(is_object($valor_a_dumpear)) {
				$this->log->add_debug('DEBUG RDI', var_export($valor_a_dumpear, true));
			} else {
				$this->log->add_debug('DEBUG RDI', $valor_a_dumpear);
			}
		}
	}
	
	function add_error($excepcion)
	{
		if (! $this->_activo){
			return;
		}
		$this->log->add_error($excepcion);
	}
}