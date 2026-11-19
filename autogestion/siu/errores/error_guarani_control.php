<?php
namespace siu\errores;

class error_guarani_control extends error_guarani
{
	protected $error_reinscripcion = false;
	
	function set_mensajes($mensajes)
	{
		$this->mensajes = $mensajes;
	}
	
	function get_mensajes()
	{
		return $this->mensajes;
	}
	
	//--------------------------------------------------
	
	function set_error_reinscripcion($estado=true)
	{
		$this->error_reinscripcion = $estado;
	}
	
	function requiere_reinscripcion()
	{
		return $this->error_reinscripcion;
	}

	//--------------------------------------------------
	
	function requiere_datos_censales()
	{
		return false;
	}
}

?>