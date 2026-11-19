<?php
/**
 * Representa un elemento del plan de estudios de tipo MODULO RTF
 *
 * @package Propuestas
 * @subpackage Plan
 */
class plan_elemento_modulo_rtf_nucleo extends plan_elemento_modulo
{
	//----------------------------------------------------------------
	//-- DATOS del MODULO ---------------------------------
	//----------------------------------------------------------------

	//-- Inicializacion -----------------------------------------------------
	
	/** Redefine el método Ini de los módulos comunes, porque para RTF al crearlos se setean algunos valores 
	 *  diferentes, como el informe de créditos y además se copian los valóres de la cáscara del registro de
	 *  sga_modulos_rtf que se seleccionó.
	 */
	function ini($datos_modulo_rtf=array())
	{
		// Se agregan los datos para crear el elemento a la cáscara del módulo RTF y a los del subtipo 11.
		$elemento = $datos_modulo_rtf;
		$elemento['entidad_subtipo'] = $this->get_subtipo();
		$elemento['estado'] = elemento::estado_activo;
		$elemento['disponible_para'] = elemento::para_mesas_de_examen_y_comisiones;
		$elemento['compartible'] = comunes::no;
		
		// Se agregan los datos para crear el elemento revisión a la cáscara del módulo RTF.
		$revision = $datos_modulo_rtf;
		$revision['regla'] = elemento::regla_default;
		$revision['parametros'] = elemento::regla_parametros_default;
		$revision['origen_creditos'] = elemento::creditos_modulo;
		// Le suma el dato de créditos seteado en elementos_plan a la revisión ya que se valida que sea mayor 
		//  a 0 si origen_creditos es M.
		
		// Se agregan los datos de la cáscara del módulo RTF para crear el elemento plan con los créditos.
		$elemento_plan = $datos_modulo_rtf;
		
		$this->set_datos_elemento($elemento);
		$this->set_datos_revision($revision);		
		$this->set_creditos_elemento_plan($elemento_plan);
	}
	
	//-- API granular -----------------------------------------------------

	/** Se define esta función adicional a la set_datos_elementos_plan, ya que al setear los datos iniciales,
	 *  y créditos que se copian de la cáscara del módulo RTF, no deben ejecutarse las validaciones sobre 
	 *  créditos mínimos y máximos etc.
	 *  @param array $datos $datos datos de la cáscara del módulo rtf a copiar en el elemento plan (incluídos
	 *  los créditos).
	 */
	function set_creditos_elemento_plan($datos)
	{
		$this->control_edicion_no_bloqueada();
		$this->tabla_elementos_plan()->set($datos);
	}
	
	//----------------------------------------------------------------------
	
	function get_datos($id_padre=null)
	{
		$datos_elemento = $this->get_datos_elemento();
		$datos_revision = $this->get_datos_revision();
		$datos = array_merge($datos_elemento, $datos_revision);
		$datos_elementos_plan = $this->get_datos_elementos_plan();
		if (isset($datos_elementos_plan)) {
			$datos  = array_merge($datos_elementos_plan, $datos);
		}
		return $datos;
	}
}
?>