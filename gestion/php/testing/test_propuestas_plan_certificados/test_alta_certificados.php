<?php

class test_alta_certificados extends toba_test
{
	static function get_descripcion()
	{
		return "Asociación de Certificados con Planes de Estudio";
	}
	
    function pre_run()
    {
    	parent::pre_run();
    }
	
    function post_run()
    {
    }

	function SetUp()
	{
	}

	function TearDown()
	{
	}

	//===============================================================

	function test_listado()
	{
	  try{
            $plan = toba::cn('manejador_certificados_plan');
            $plan->cargar(2);
			$datos = $plan->get_certificados_plan();
        	$this->pass();
		}catch(guarani_error $e){
			$this->fail("test_listado(): ".$e->getMessage());
        }
	}
	
	function ttest_asignar()
	{
	  try{
            $plan = toba::cn('manejador_certificados_plan');
            $plan->cargar(2);
			$plan->asignar_certificado(1, 16);//certif, modulo
        	$this->pass();
		}catch(guarani_error $e){
			$this->fail("test_asignar(): ".$e->getMessage());
        }
	}

	function ttest_desasignar_certificado()
	{
	  try{
            $plan = toba::cn('manejador_certificados_plan');
            $plan->cargar(2);
			$plan->desasignar_certificado(1);//certif
        	$this->pass();
		}catch(guarani_error $e){
			$this->fail("test_desasignar(): ".$e->getMessage());
        }
	}
	
	function test_desasignar_certificados_de_modulo()
	{
	  try{
            $plan = toba::cn('manejador_certificados_plan');
            $plan->cargar(4);
            $plan->desasignar_certificados_de_modulo_revision(9);//Revision
            $plan->desasignar_certificados_de_modulo_revision(7);//Revision
        	$this->pass();
		}catch(guarani_error $e){
			$this->fail("test_desasignar_certificados_de_modulo(): ".$e->getMessage());
        }
	}

	function ttest_desasignar_todo()
	{
	  try{
            $plan = toba::cn('manejador_certificados_plan');
            $plan->cargar(4);
			$plan->desasignar_todo();
        	$this->pass();
		}catch(guarani_error $e){
			$this->fail("test_desasignar_todo(): ".$e->getMessage());
        }
	}

}
?>