<?php
/**
 *
 * @package 	Propuestas 
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_nivel_formacion extends cn_entidad
{
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('nivel_formacion')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('nivel_formacion')->get();
	}
	
	
	//-------------------------------------------------------------------
	//---- Validaciones -------------------------------------------------
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['nivel_formacion'] = $this->tabla('nivel_formacion')->get_columna('nivel_formacion');
		
		$this->validar_nombre_nivel_formacion($datos['nombre'], $datos['nivel_formacion']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_nivel_formacion($nombre, $nivel)
	{
		if (! toba::consulta_php('co_planes')->validar_nombre_nivel_formacion($nombre, $nivel)) {
			$this->set_error('dato_duplicado', array('nombre', 'Nivel de Formación'));
		}
	}
	
}

?>