<?php
/**
*
*/
class test_alta_elementos_plan_cargado extends toba_test
{
	static function get_descripcion()
	{
		return "Carga de actividad y Agrega Actividad - Planes de Estudio";
	}
	
	function ttest_carga()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$manejador->cargar(1,true);	
			$plan = $manejador->entidad();
			$manejador->resetear();	
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	function ttest_asociar_actividad()
	{
		$plan_version = 1;
		try{
			$manejador = toba::cn('manejador_plan');
			$manejador->cargar($plan_version,true);			
			$plan = $manejador->entidad();
			$raiz = $plan->elementos()->elemento_raiz();
			// actividad del mismo plan 
			echo "--------------Modulo actividad mismo plan \n";
		    $m1 = $plan->elementos()->modulo_crear($raiz, 1, 'Modulo actividad mismo plan');
			$a1 = $plan->elementos()->elemento_asociar($m1, 1001);
		    // actividad de otro plan
		    echo "--------------Modulo actividad otro plan \n";
		    $m2 = $plan->elementos()->modulo_crear($raiz, 1, 'Modulo actividad otro plan');
			$a2 = $plan->elementos()->elemento_asociar($m2, 1012);
			echo "--------------Asocio actividades de otro y de este plan a la raiz \n";
			$a2 = $plan->elementos()->elemento_asociar($m2, 1014);
			$a2 = $plan->elementos()->elemento_asociar($m2, 1004);
			echo "--------------fin \n";
			$manejador->guardar();
			$manejador->resetear();			
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}
	
	
	function test_asociar_modulo()
	{
		$plan_version = 1;
		try{
			$manejador = toba::cn('manejador_plan');
			$manejador->cargar($plan_version,true);			
			$plan = $manejador->entidad();
			$raiz = $plan->elementos()->elemento_raiz();
	/*	    echo "--------------Modulo mismo plan a a un modulo nuevo \n";
		    $m1 = $plan->elementos()->modulo_crear($raiz, 1, 'Modulo - modulo mismo plan');
		    $m2 = $plan->elementos()->elemento_asociar($m1, 10);
		    echo "--------------Modulo 5to año de otro plan a la raiz sin actividades de este plan a la raiz \n";
			$m3 = $plan->elementos()->elemento_asociar($raiz, 105);
	*/	    echo "--------------Modulo 1er año de otro plan a la raiz con actividades de este plan a la raiz \n";
		    $m4 = $plan->elementos()->elemento_asociar($raiz, 12);
		  /*  echo "--------------Modulo 4to año de otro plan a la raiz con actividades y sin act de este plan a la raiz \n";
		    $m5 = $plan->elementos()->elemento_asociar($raiz, 104); */
		    echo "fin test \n";
		    $manejador->guardar();
			$manejador->resetear();			
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}
	

}

?>