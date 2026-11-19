<?php
/**
*	
	* Siembre se revisiona algo que ya existe en la base:
		- porque fua asociado
		- porque fue cargado de entrada
		
		- Si el

*/
class test_revisionar_modulo extends toba_test
{
	static function get_descripcion()
	{
		return "Crear revisiones nuevas de Modulos existentes en Planes de Estudio";
	}
	
	function ttest_asociar_en_plan_nuevo()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$manejador->cargar(3);	
			$plan = $manejador->entidad();

			$modulo = $plan->elementos()->revisionar_modulo(9);

			//--- Guardar PLAN --------------------
			$manejador->guardar();
			$manejador->resetear();
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}
	
}

?>