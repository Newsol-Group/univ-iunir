<?php
/**
 *	Representa una Condición de Regularidad
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_ent_condicion_regularidad extends cn_entidad
{
	protected $instancias;
	protected $resultados;
	
	//-----------------------------------------------------------------------------------
	//---- sga_cond_regularidad ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar($datos);
		$this->tabla('cond_regularidad')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('cond_regularidad')->get();
	}
	

	//-----------------------------------------------------------------------------------
	//---- validaciones -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function validar($datos)
	{	
		$this->validar_nombre($datos);
		$this->validar_condicion_por_defecto($datos);
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	/**
	 * Valida si un nombre de Condición de Regularidad se encuentra ya en uso.
	 *
	 */
	function validar_nombre($datos)
	{		
		$cond_regularidad = $this->tabla('cond_regularidad')->get_columna('cond_regularidad');
		
		if (! toba::consulta_php('co_condiciones_regularidad')->validar_nombre_condicion_regularidad($datos['nombre'], $cond_regularidad)) {
			$this->set_error('dato_duplicado', array('nombre', 'Condición de Regularidad'));
		}
	}
	
	/**
	 * Valida que, en caso que se defina Es Condición por Defecto como SI, no haya otra otra condición 
	 * con el mismo valor para el mismo resultado
	 *
	 */
	function validar_condicion_por_defecto($datos)
	{
		if ($datos['es_condicion_defecto'] == comunes::si) {
			if (! toba::consulta_php('co_condiciones_regularidad')->validar_condicion_resultado($datos['resultado'])) {
				$this->set_error('800CUR_Resultado_Defecto_Repetido');
			}	
		}
	}
	
}
?>