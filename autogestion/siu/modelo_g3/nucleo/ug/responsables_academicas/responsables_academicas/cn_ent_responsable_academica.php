<?php
class cn_ent_responsables_academicas extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		if (isset($datos['localidad'])) {
			if (stripos($datos['localidad'],':')) {
				$claves = $datos['localidad'];
				$datos['codigo_postal'] = guarani_texto::extraer_subclave($claves,1);
				$datos['localidad'] = guarani_texto::extraer_subclave($claves);				
			}
		}
		$this->validar_datos($datos);
		$this->tabla('responsable_acad')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('responsable_acad')->get();
	}

	function get_g3entidad_subtipo() {
		return g3entidad::subtipo_ra;
	}

	function validar_datos($datos) 
	{
		$resp_acad = $this->tabla('responsable_acad')->get_columna('responsable_academica');
		$this->validar_codigo_responsable_academica($datos['institucion'],$datos['codigo'],$resp_acad);
		if($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		} 
	}
	
	function validar_codigo_responsable_academica($institucion,$codigo,$resp_acad=null) 
	{
		if (! toba::consulta_php('co_responsables_academicas')->validar_codigo_responsable_academica($institucion,$codigo,$resp_acad)) {
			$this->set_error('dato_duplicado',array('código','Institución'));
		}
	}
		
}

?>