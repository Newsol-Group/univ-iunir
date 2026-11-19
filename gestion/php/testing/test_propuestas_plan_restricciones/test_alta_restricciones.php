<?php

class test_alta_restricciones extends toba_test
{
	static function get_descripcion()
	{
		return "Alta de Restricciones en Planes de Estudio";
	}
	
	/**
	*	Esto se ejecuta UNA VEZ antes de todos los TEST
	*/
    function pre_run()
    {
    	parent::pre_run();
	/*	$sql[] = "DELETE FROM sga_restricciones_requisitos;";	
		$sql[] = "DELETE FROM sga_restricciones_grupos;";	
		$sql[] = "DELETE FROM sga_restricciones;";	
		guarani_db::ejecutar($sql);*/
    }
	
	/**
	*	Esto se ejecuta UNA VEZ despues de todos los TEST
	*/
    function post_run()
    {
    }

	/**
	*	Esto se ejecuta antes de cada TEST
	*/
	function SetUp()
	{
	}

	/**
	*	Esto se ejecuta despues de cada TEST
	*/
	function TearDown()
	{
	}

	//===============================================================
	
	
	protected function dumpdt ($msg,&$re)
	{
		echo "$msg";
		echo '-------------------------acciones-------------------------------------';
        print_r($re->dep('datos')->tabla('acciones')->get_filas());
        echo '-------------------------grupos-------------------------------------';
        //$id_accion = $re->get_id_interno_accion('C');
        //$re->dep('datos')->tabla('acciones')->set_cursor($id_accion);
        
        print_r($re->dep('datos')->tabla('grupos')->get_filas());
        echo '-------------------------requisitos-------------------------------------';
        print_r($re->dep('datos')->tabla('requisitos')->get_filas());
        echo '------------------------------------------------------------------------<br>';
	}


	protected function agregar_grupo_requisito(&$restricciones_plan)
    {
        $id_grupo = $restricciones_plan->elemento()->agregar_grupo('C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1001);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1002);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');

        $id_grupo = $restricciones_plan->elemento()->agregar_grupo('C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1000);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1002);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');

        $id_grupo = $restricciones_plan->elemento()->agregar_grupo('A');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1019);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1020);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1000);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'A');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1001);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'A');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,2001);

        $id_grupo = $restricciones_plan->elemento()->agregar_grupo('A');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1003);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1005);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1001);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'A');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,1002);
        $restricciones_plan->elemento()->modificar_requisito($id_requisito,'A');
        $id_requisito = $restricciones_plan->elemento()->agregar_requisito($id_grupo,2000);
    }
	
    
    protected function recuperar_arbol($re)
    {
        $arbol = Array(); 
        $acciones = $re->get_acciones();
        foreach ($acciones as $accion)
        {
            $grupos = $re->get_grupos($accion['restriccion_accion']);
            $arbol['accion_'.$accion['restriccion_accion']]=$accion;
            foreach ($grupos as $grupo)
            {
                $requisitos = $re->get_requisitos($grupo['indice']);
                $arbol['accion_'.$accion['restriccion_accion']]['grupos'][$grupo['orden']]= $grupo;
                foreach ($requisitos as $requisito)
                {
                	$arbol['accion_'.$accion['restriccion_accion']]['grupos'][$grupo['orden']]['requisitos'][$requisito['orden']]= $requisito;
                }
            }
        }

     return $arbol;
    }
	

	function ttest_alta_acciones()
	{
        try{
        	
            $plan_version_id = 3;
            $elemento = 1003;
            
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            
            $restricciones_plan->seleccionar_elemento($elemento);
            $restricciones_plan->elemento()->agregar_accion('C');
            $restricciones_plan->elemento()->agregar_accion('A');
            $restricciones_plan->elemento()->guardar();
            $this->pass();
			}catch(guarani_error $e){
                $this->fail("test_alta_acciones(): ".$e->getMessage());
		}
	}
	
	
	function ttest_excep_alta_acciones()
	{
	  try{
            
            $plan_version_id = 3;
            $elemento = 1003;
            
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->seleccionar_elemento($elemento);
            $restricciones_plan->elemento()->agregar_accion('C');
            $restricciones_plan->elemento()->agregar_accion('A');
            $restricciones_plan->elemento()->guardar();
            $this->fail('test_excep_alta_acciones() no salto la excepcion');
            }catch(guarani_error $e){
                $this->pass();
        }
	
	}
	
    function ttest_alta_grupos()
    {
        try{
            $plan_version_id = 3;
            $elemento = 1003;
            
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->seleccionar_elemento($elemento);
            $this->agregar_grupo_requisito($restricciones_plan);  
        	//$this->dumpdt('ss',$re);
            //TODO guardar un array por elemento con las restricciones de cada uno para borrar despues
            $restricciones_plan->elemento()->guardar();
            $this->pass();
        }catch(guarani_error $e){
            $this->fail("test_alta_grupos()   " . $e->getMessage());
        }
    }

    function Ttest_alta_requisito()
    {
        try{
            $plan_version_id = 3;
            $elemento = 1003;
            
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->seleccionar_elemento($elemento);
            $id_requisito = $restricciones_plan->elemento()->agregar_requisito(2,1010);
            $restricciones_plan->elemento()->modificar_requisito($id_requisito,'C');
            $restricciones_plan->elemento()->guardar();
            $this->pass();
            }catch(guarani_error $e){
                $this->fail("test_alta_grupos()   " . $e->getMessage());
            }
    	
    }
    
	
	function ttest_eliminar_requisito()
	{
		try{
        
            $plan_version_id = 3;
            $elemento = 1003;
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->seleccionar_elemento($elemento);
			$restricciones_plan->elemento()->eliminar_requisito_ctrl_grupo(0);
           	$restricciones_plan->elemento()->eliminar_requisito_ctrl_grupo(1);
            $restricciones_plan->elemento()->guardar();
		    }catch (guarani_error $e){
		    	 $this->fail("test_eliminar_requisito()   " . $e->getMessage());
		    }
            				
	}
	
	function ttest_eliminar_grupo()
	{
		try{
		    $plan_version_id = 3;
            $elemento = 1003;
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->seleccionar_elemento($elemento);
            //$restricciones_plan->elemento()->eliminar_grupo(1);
            //$restricciones_plan->elemento()->eliminar_grupo_ctrl_accion(1);
            $restricciones_plan->elemento()->eliminar_grupo_ctrl_accion(20);
          //  $restricciones_plan->elemento()->guardar();
		    }catch(guarani_error $e){
                $this->fail("test_eliminar_grupo()   " . $e->getMessage());
        }
	}
	
    function ttest_eliminar_accion()
    {
        try{
            $plan_version_id = 3;
            $elemento = 1003;
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->seleccionar_elemento($elemento);
            $restricciones_plan->elemento()->eliminar_accion('A');
            $restricciones_plan->elemento()->guardar();
            }catch(guarani_error $e){
                $this->fail("test_eliminar_accion()   " . $e->getMessage());
        }
    }
    
    function ttest_modificar_requisito()
    {
        try{
            $plan_version_id = 3;
            $elemento = 1003;
            $restricciones_plan = toba::cn('restriccionesPlan');
            $restricciones_plan->cargar_plan($plan_version_id);
            $restricciones_plan->get_restricciones_elemento($elemento);
            $restricciones_plan->elemento()->modificar_requisito(2,'C');
            $restricciones_plan->elemento()->guardar();
        }catch (guarani_error $e){
            $this->fail("test_modificar_requisito()   " . $e->getMessage());
        	
        }
    }
    
    function test_eliminar_elemento()
    {
    	 $plan_version_id = 1000;
         $elemento = 20003;
         $restricciones_plan = toba::cn('restriccionesPlan');
         $restricciones_plan->cargar($plan_version_id);
		 $restricciones_plan->eliminar_elemento($elemento);
		
    }
    
      
    

/*
	function test_carga_elementos()
	{
		try{
			$plan = toba::cn('restriccionesPlan');
			$plan->cargar_plan(3);

			$elemento = 1001;

			$re = $plan->get_restricciones_elemento($elemento);	

			toba::logger()->notice( $re->get_acciones() );

			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}

	}


/*	
	function test_alta_acciones()
	{
		try{
			
			$plan = toba::cn('restriccionesPlan');
			$plan->cargar_plan(3);
			$actividades = $plan->get_lista_actividades();
			foreach($actividades as $a) {
				$re = $plan->get_restriccion_elemento($a);	
				$re->agregar_accion('A');
				for($a=0;$a<3;$a++) {
					$id_grupo = $re->agregar_grupo();
				}
				$re->guardar();
			}
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}
*/
/*

	function test_alta_acciones_controlar_acciones duplicadas()
	{
		try{
			
			$plan = toba::cn('restriccionesPlan');
			$plan->cargar_plan(3);
			$actividades = $plan->get_lista_actividades();
			foreach($actividades as $a) {
				$re = $plan->get_restriccion_elemento($a);	
				$re->agregar_accion('A');
				$re->guardar();
			}
			$this->fail('No se atrapo el error!');
		}catch(guarani_error $e){
			$this->pass();
		}
	}
*/

	//------------------------------------------


}