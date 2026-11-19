<?php
class cn_ent_responsable_academica_tipo extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('resp_acad_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('resp_acad_tipos')->get();
	}	
	
	//------Validaciones------//
	
	function validar_datos($datos)
	{
		$datos['responsable_academica_tipo'] = $this->tabla('resp_acad_tipos')->get_columna('responsable_academica_tipo');
		$this->validar_nombre_resp_acad_tipo($datos['nombre'], $datos['responsable_academica_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_resp_acad_tipo($nombre, $responsable_academica_tipo)
	{
		if (! toba::consulta_php('co_responsables_academicas')->validar_nombre_resp_acad_tipo($nombre, $responsable_academica_tipo)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de responsable académica'));
		}
	}
	
}

?>