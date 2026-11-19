<?php
/**
*	Caso de test del SIU-Guarani
*/
class test_guarani_reporte extends test_guarani
{
	protected $item;
	
	function SetUp()
	{
		$this->cargar_item($this->item);
	}

	function test_carga_cuadro()
	{
		try { 
			$ci = $this->solicitud->ci();
			$cuadro = $ci->dep('cuadro');
			$ci->conf__cuadro($cuadro);
			$this->pass();
		} catch (guarani_error $e ) {
			$this->fail($e->getMessage());
		}
	}

	
}

?>