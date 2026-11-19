<?php
/**
*	Caso de test del SIU-Guarani
*/

class test_guarani extends toba_test
{
	protected static $descripcion = "";
	protected $solicitud;
	static protected $clases = array(
			'test_guarani' 											=>	'test_guarani.php',
			'test_guarani_abm'										=>	'test_guarani_abm.php',
			'test_guarani_reporte'									=>	'test_guarani_reporte.php'
		);


	static function conf__inicial()
	{
		spl_autoload_register(array('test_guarani', 'cargador_clases'));
	}

	static function get_descripcion()
	{
		return self::$descripcion;
	}
	
	//---- Carga de ITEMS -------------------
	
	function cargar_item($item)
	{
		$proyecto = toba_proyecto::get_id();
		$this->solicitud = toba_constructor::get_runtime(array('proyecto'=>$proyecto, 'componente'=>$item), 'toba_item');
		$this->solicitud->inicializacion_pasiva();
	}

	//---- Cargador de Clases --------------

	static function cargador_clases($clase)
	{
		if(isset(self::$clases[$clase])) {
			require_once(self::$clases[$clase]);
		}
	}

}

?>