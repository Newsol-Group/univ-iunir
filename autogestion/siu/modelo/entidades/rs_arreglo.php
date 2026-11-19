<?php

namespace siu\modelo\entidades;
use \SIU\Chulupi\util\modelo;

class rs_arreglo extends modelo\RsBase
{
	public function __construct($datos)
	{
		parent::__construct();
		$this->datos = $datos;
	}
	
}

?>
