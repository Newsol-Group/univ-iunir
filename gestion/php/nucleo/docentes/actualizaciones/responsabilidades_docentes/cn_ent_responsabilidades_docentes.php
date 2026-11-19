<?php
/**
 *	Representa una responsabilidad de catedra
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class cn_ent_responsabilidades_docentes extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('resp_docente')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('resp_docente')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['responsabilidad'] = $this->tabla('resp_docente')->get_columna('responsabilidad');
		$this->validar_nombre_responsabilidad_de_catedra($datos['nombre'], $datos['responsabilidad']);
		if ($this->hay_errores()) {
			//throw new guarani_error($this->get_mensaje_error());
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_responsabilidad_de_catedra($nombre, $responsabilidad)
	{
		if (! toba::consulta_php('co_responsabilidades_docentes')->validar_nombre_responsabilidad_docente($nombre, $responsabilidad)) {
			$this->set_error('dato_duplicado', array('nombre de la responsabilidad docente'));
		}
	}

}
?>