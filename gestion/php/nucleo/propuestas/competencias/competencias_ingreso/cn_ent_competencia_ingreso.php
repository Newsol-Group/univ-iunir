<?php
/**
 * 
 * @package Propuestas Formativas
 * @subpackage Competencias
 */
class cn_ent_competencia_ingreso extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('competencias_ingreso')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('competencias_ingreso')->get();
	}	

	//------------------------------------------------------------------------
	//----- validaciones -----------------------------------------------------
	//------------------------------------------------------------------------
	
	function validar_datos($datos)
	{				
		$datos['competencia'] = $this->tabla('competencias_ingreso')->get_columna('competencia');
		$this-> validar_nombre_competencia($datos['nombre'], $datos['competencia']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}		
	}
	
	function validar_nombre_competencia($nombre, $competencia)
	{		
		if (! toba::consulta_php('co_competencias')->validar_nombre_competencia($nombre, $competencia)) {
			$this->set_error('dato_existente', array('nombre', 'competencia de ingreso'));
		}
	}

}
?>