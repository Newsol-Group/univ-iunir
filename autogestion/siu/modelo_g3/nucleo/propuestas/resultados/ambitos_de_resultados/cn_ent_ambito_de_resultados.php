<?php
/**
 *	Representa un Ambito de Resultados
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class cn_ent_ambito_de_resultados extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('ambitos_resultados')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('ambitos_resultados')->get();
	}

	//---- Validaciones ----------------------------
	
	function validar_datos($datos)
	{
		$datos['ambito'] = $this->tabla('ambitos_resultados')->get_columna('ambito');
		$this->validar_nombre_ambito_de_resultados($datos['nombre'], $datos['ambito']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_ambito_de_resultados($nombre, $ambito)
	{
		if (! toba::consulta_php('co_ambitos_de_resultados')->validar_nombre_ambito_de_resultados($nombre, $ambito)) {
			$this->set_error('dato_duplicado', array('nombre', 'ambito'));
		}
	}	
	
	//---- Funciones Auxiliares --------------------
	
	function tiene_areas_tematicas_asociadas($ambito)
	{
		$areas_tem = toba::consulta_php('co_ambitos_de_resultados')->get_areas_tematicas_asociadas($ambito);
		if (!empty($areas_tem)) {
			return true;
		}
		return false;
	}
	
	function tiene_certificados_asociados($ambito)
	{
		$certificados = toba::consulta_php('co_ambitos_de_resultados')->get_certificados_asociados($ambito);
		if (!empty($certificados)) {
			return true;
		}
		return false;
	}
	
	function tiene_actividades_asociadas($ambito)
	{
		$actividades = toba::consulta_php('co_ambitos_de_resultados')->get_actividades_asociadas($ambito);
		if (!empty($actividades)) {
			return true;
		}
		return false;
	}

}
?>