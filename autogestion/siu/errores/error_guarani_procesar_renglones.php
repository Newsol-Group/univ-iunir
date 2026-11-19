<?php
namespace siu\errores;

class error_guarani_procesar_renglones extends error_guarani
{
	const TIPO_GENERICO = 'error_generico';
	const TIPO_CONCURRENCIA = 'error_concurrencia';
	
	protected $renglones = array();
	protected $error_base = 'Error procesando renglones.';
	
	function add_renglon($id, $msg, $renglon=null, $tipo = self::TIPO_GENERICO)
	{
		$this->renglones[$id]['msg'] = $msg;
		$this->renglones[$id]['renglon'] = $renglon;
		$this->renglones[$id]['error_concurrencia'] = $tipo == self::TIPO_CONCURRENCIA;
		$this->actualizar_mensaje();
	}
	
	function get_renglones()
	{
		return $this->renglones;
	}
	
	function hay_renglones()
	{
		return \guarani_arreglos::count($this->renglones) > 0;
	}
	
	private function actualizar_mensaje()
	{
		$msg = $this->error_base . "\n";
		foreach($this->get_renglones() as $id => $renglon) {
			$msg .= "[$id]: ". $renglon['msg'] .".\n";
		}
		$this->message = $msg;
	}
}

?>