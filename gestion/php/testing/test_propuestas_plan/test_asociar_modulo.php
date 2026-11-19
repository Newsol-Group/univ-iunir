<?php
/**
*	
	* No se puede agregar un hijo a un modulo que es hijo de el
	* No se puede tener dos veces el mismo hijo
	* Si asocio algo ya instanciado, no lo tengo que traer de la base

*/
class test_asociar_modulo extends toba_test
{
	protected $codigo = 0;
	
	static function get_descripcion()
	{
		return "Asociacion de Modulos en Planes de Estudio";
	}

	
	function ttest_asociar_en_plan_nuevo()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$plan = $manejador->nueva();

			//--- Info Basica ------------------------
			$plan->set_datos_cabecera( $this->get_datos_plan('Asociar modulos existentes') );
			$plan->set_datos_version( $this->get_datos_plan_version() );

			//--- Asocio un modulo existente a la raiz
			$raiz = $plan->elementos()->elemento_raiz();
			$m1 = $plan->elementos()->modulo_asociar($raiz, 6);	

			//--- Guardar PLAN --------------------
			$manejador->guardar();
			$manejador->resetear();
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	function ttest_asociar_en_plan_cargado()
	{
		try{
			$manejador = toba::cn('manejador_plan');
			$manejador->cargar(1);	
			$plan = $manejador->entidad();

			$raiz = $plan->elementos()->elemento_raiz();
			$m1 = $plan->elementos()->modulo_asociar($raiz, 6);	

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