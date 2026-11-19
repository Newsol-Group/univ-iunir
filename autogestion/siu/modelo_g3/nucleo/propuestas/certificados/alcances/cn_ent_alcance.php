<?php
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage 	Certificados
 * 
 */
class cn_ent_alcance extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('alcance')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('alcance')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$alcance = $this->tabla('alcance')->get_columna('alcance');
		
		if (! toba::consulta_php('co_certificados')->validar_nombre_alcance($datos['nombre'], $alcance)) {
			$this->set_error('dato_duplicado', array('nombre', 'Alcance'));
			throw guarani::error($this->get_mensaje_error());
		}
	}
}
?>