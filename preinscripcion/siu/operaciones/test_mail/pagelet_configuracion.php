<?php

namespace siu\operaciones\test_mail;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_configuracion extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'configuracion';
	}

	function prepare()
	{
		$this->data['smtp'] = kernel::proyecto()->get_parametros_smtp();
		
		if (isset($this->data['smtp']['from_name'])) {
			// Se arregla problema de encoding con el campo 'from_name', dice 'SIU-PreinscripciÃ³n' en lugar de 'SIU-Preinscripción'.
			$valid_utf8 = mb_check_encoding($this->data['smtp']['from_name'], 'UTF-8');
			if ($valid_utf8) {
				$this->data['smtp']['from_name'] = utf8_decode($this->data['smtp']['from_name']);
			}
		}
		
		$this->data['es_default'] = $this->controlador->es_usuario_default();
	}

}