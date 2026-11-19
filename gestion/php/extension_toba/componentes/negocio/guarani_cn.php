<?php
class guarani_cn extends toba_cn
{
	//--------------------------------------------------------------------------
	//---- Manejos generales de atributos de parámetros del sistema ------------
	//--------------------------------------------------------------------------
	
	function resetear_atributos_parametros($nivel = null)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->reset_atributos_operacion($operacion, $nivel);
		$this->resetear_dependencias_parametros();
	}
	
	function resetear_dependencias_parametros()
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->reset_parametros_no_usados($operacion);
	}
}
?>