<?php
namespace siu\errores;
use SIU\Chulupi\ErrorKernel;

class error_procesar_datos_censales extends ErrorKernel
{
	protected $renglones = array();
	protected $error_base = 'Error procesando datos censales.';
	
	function agregar_mensaje($id, $msg)
	{
		$this->renglones[$id] = $msg;
		$this->actualizar_mensaje();
	}
	
	function get_renglones()
	{
		return $this->renglones;
	}
	
	function hay_errores()
	{
		return \guarani_arreglos::count($this->renglones) > 0;
	}
	
	private function actualizar_mensaje()
	{
		$msg = $this->error_base . "\n";
		foreach($this->get_renglones() as $id => $mensaje) {
			$msg .= "[$id]: ". $mensaje .".\n";
		}
		$this->message = $msg;
	}
}

?>