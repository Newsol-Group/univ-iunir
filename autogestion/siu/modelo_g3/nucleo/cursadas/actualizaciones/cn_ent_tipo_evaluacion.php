<?php
/**
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_ent_tipo_evaluacion extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('evaluaciones_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('evaluaciones_tipos')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{		
		$datos['evaluacion_tipo'] = $this->tabla('evaluaciones_tipos')->get_columna('evaluacion_tipo');
		$this->validar_nombre($datos['nombre'], $datos['evaluacion_tipo']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre($nombre, $id)
	{
		if (! toba::consulta_php('co_evaluaciones')->validar_nombre_tipo_evaluacion($nombre, $id)) {
			$this->set_error('dato_existente', array('nombre', 'tipo de evaluación'));
		}
	}

}
?>