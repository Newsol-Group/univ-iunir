<?php
/**
*
	Validaciones: 
		- controlar los tipos validos de contenedor/hijo
		- Controlar las multiples instanciaciones

*/
class test_alta_elementos_plan_nuevo extends toba_test
{
	protected $codigo = 0;
	
	static function get_descripcion()
	{
		return "Alta de Planes de Estudio";
	}
	
	function ttest_plan_sin_elementos()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$plan = $manejador->nueva();

			//--- Info Basica ------------------------
			$plan->set_datos_cabecera( $this->get_datos_plan('Sin elementos') );
			$plan->set_datos_version( $this->get_datos_plan_version() );

			//--- Guardar PLAN --------------------
			$manejador->guardar();
			$manejador->resetear();
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	function ttest_plan_con_modulos()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$plan = $manejador->nueva();

			//--- Info Basica ------------------------
			$plan->set_datos_cabecera( $this->get_datos_plan('Modulos') );
			$plan->set_datos_version( $this->get_datos_plan_version());

			//--- Elementos -----------------------
			$raiz = $plan->elementos()->elemento_raiz();
			$m1 = $plan->elementos()->modulo_crear($raiz, 1, '1er Año');
			$m1a = $plan->elementos()->modulo_crear($m1, 1, '1er Cuatrimestre');
			$m1b = $plan->elementos()->modulo_crear($m1, 1, '2do Cuatrimestre');
			$m2 = $plan->elementos()->modulo_crear($raiz, 1, '2do Año');
			$m2a = $plan->elementos()->modulo_crear($m2, 1, '1er Cuatrimestre');
			$m2b = $plan->elementos()->modulo_crear($m2, 1, '2do Cuatrimestre');

			//--- Guardar PLAN --------------------
			$manejador->guardar();
			$manejador->resetear();

			$this->pass();

		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	function ttest_plan_con_modulos_y_actividades()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$plan = $manejador->nueva();

			//--- Info Basica ------------------------
			$plan->set_datos_cabecera( $this->get_datos_plan('Modulos y Actividades') );
			$plan->set_datos_version( $this->get_datos_plan_version());

			//--- Elementos -----------------------
			$raiz = $plan->elementos()->elemento_raiz();
			$m1 = $plan->elementos()->modulo_crear($raiz, 1, '1er Año');
			$a1 = $plan->elementos()->actividad_asociar($m1, 1001);
			$a2 = $plan->elementos()->actividad_asociar($m1, 1002);
			$m2 = $plan->elementos()->modulo_crear($raiz, 1, '2do Año');
			$a3 = $plan->elementos()->actividad_asociar($m2, 1003);
			$a4 = $plan->elementos()->actividad_asociar($m2, 1004);

			//--- Guardar PLAN --------------------
			$manejador->guardar();
			$manejador->resetear();

			$this->pass();

		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	function ttest_plan_con_modulos_y_actividades_con_varios_padres()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$plan = $manejador->nueva();

			//--- Info Basica ------------------------
			$plan->set_datos_cabecera( $this->get_datos_plan('Modulos y Actividades 2') );
			$plan->set_datos_version( $this->get_datos_plan_version());

			//--- Elementos -----------------------
			$raiz = $plan->elementos()->elemento_raiz();
			$m1 = $plan->elementos()->modulo_crear($raiz, 1, '1er Año');
			$a1 = $plan->elementos()->actividad_asociar($m1, 1001);
			$a2 = $plan->elementos()->actividad_asociar($m1, 1002);
			$m1a = $plan->elementos()->modulo_crear($m1, 1, '1er Cuatrimestre');
			$a3 = $plan->elementos()->actividad_asociar($m1a, 1003);
			$m1b = $plan->elementos()->modulo_crear($m1, 1, '2do Cuatrimestre');
			$a4 = $plan->elementos()->actividad_asociar($m1b, 1004);
			$m2 = $plan->elementos()->modulo_crear($raiz, 1, '2do Año');
			$a5 = $plan->elementos()->actividad_asociar($m2, 1005);
			$a6 = $plan->elementos()->actividad_asociar($m2, 1006);
			$m2a = $plan->elementos()->modulo_crear($m2, 1, '1er Cuatrimestre');
			$a7 = $plan->elementos()->actividad_asociar($m2a, 1007);
			$m2b = $plan->elementos()->modulo_crear($m2, 1, '2do Cuatrimestre');
			$a8 = $plan->elementos()->actividad_asociar($m2b, 1008);

			// Reinstancia elementos
			$a1_2 = $plan->elementos()->actividad_asociar($m1a, 1001);
			$a2_2 = $plan->elementos()->actividad_asociar($m1a, 1002);
			$m1_2 = $plan->elementos()->modulo_reasociar($m2b, $m1);

			// Controlo que las actividades que se asociaron varias veces sean las mismas
			$this->assertReference($a1, $a1_2);
			$this->assertReference($a2, $a2_2);
			$this->assertReference($m1, $m1_2);

			//--- Guardar PLAN --------------------
			$manejador->guardar();
			$manejador->resetear();

			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	//------------------------------------------------------------------------------
	//--- DATOS
	//------------------------------------------------------------------------------
	
	function get_datos_plan($sufijo_nombre)
	{
		return	array (
						  'propuesta' => '1',
						  'nombre' => 'Plan TEST -' . $sufijo_nombre,
						  'codigo' => $this->get_proximo_codigo(),
						  'estado' => 'A',
						  'inscripcion_habilitada' => 'N',
						  'modalidad' => 'P',
						  'documento_alta' => NULL,
						  'documento_baja' => NULL,
						  'tope_creditos_cursado' => NULL,
						  'tope_creditos_reg_vigentes' => NULL,
						  'version_actual' => NULL,
						  'vigencia_minima' => NULL,
						);
	}
	
	function get_datos_plan_version()
	{	
		return array (
						  'version' => '222',
						  'nombre' => NULL,
						  'duracion_en_anios' => NULL,
						  'duracion_en_meses' => NULL,
						  'duracion_teorica' => NULL,
						  'cnt_idiomas' => NULL,
						  'cnt_materias' => NULL,
						  'cnt_optativas' => NULL,
						);		
	}
	
	function get_proximo_codigo()
	{
		return 'test_' . $this->codigo++;
	}
}

?>