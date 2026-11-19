<?php
/**
 *	Representa un Resultado de Aprendizaje
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class cn_ent_resultado_de_aprendizaje extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('result_aprendizaje')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('result_aprendizaje')->get();
	}
	
	//------ Validaciones -------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['resultado'] = $this->tabla('result_aprendizaje')->get_columna('resultado');
		$this->validar_datos_resultado_de_aprendizaje($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_datos_resultado_de_aprendizaje($datos)
	{
		if (! toba::consulta_php('co_resultados_de_aprendizaje')->validar_datos_resultado_de_aprendizaje($datos)) {
			$this->set_error('dato_duplicado', array('combinación (Tipo + Alcance + Resultado Alcanzado)', 'resultado de aprendizaje'));
		}
	}

	//------- COMPONENTES --------------------------------------------
	
	function set_datos_result_comp($datos)
	{		
		$this->tabla('result_comp')->procesar_filas($datos);		
	}
	
	function get_datos_result_comp()
	{
		return $this->tabla('result_comp')->get_filas();
	}	
	
	function eliminar_fila_result_comp($seleccion)
	{
		$this->tabla('result_comp')->eliminar_fila($seleccion);
	}
	
	function agregar_fila_result_comp($fila)
	{
		$this->tabla('result_comp')->nueva_fila($fila);
	}
}
?>