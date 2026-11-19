<?php
namespace siu\errores;
use SIU\Chulupi\ErrorKernel;

class error_guarani extends ErrorKernel
{
	
	protected $mensajes = array();
	
	function add_mensaje_usuario($mensaje)
	{
		$this->mensajes[] = $mensaje;
	}
	
	function add_mensajes_usuario($mensajes)
	{
		$this->mensajes = array_merge($this->mensajes, $mensajes);
	}
	
	function get_mensajes_usuario()
	{
		return $this->mensajes;
	}

	function get_id_error_generico()
	{
		return 'error_indefinido';
	}
}


?>