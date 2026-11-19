<?php 
/**
 *	Entidad Espacio
 *
 * @package Unidad de Gestión
 * @subpackage Espacios
 */
class cn_ent_espacio extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('espacios')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('espacios')->get();
	}

	//-------------------------------------------------------------------

	function puede_cambiar_tipo_ubicacion()
	{
		return toba::consulta_php('co_espacios')->puede_cambiar_tipo_ubicacion($this->tabla('espacios')->get_columna('espacio'));
	}

	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['espacio'] = $this->tabla('espacios')->get_columna('espacio');
		$this->validar_codigo_espacio($datos['codigo'],$datos['edificacion'],$datos['espacio']);
		$this->validar_nombre_espacio($datos['nombre'],$datos['edificacion'],$datos['espacio']);
		if($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Espacio ya existe para la Edificación seleccionada
	function validar_nombre_espacio($nombre,$edificacion,$espacio) 
	{
		if (! toba::consulta_php('co_espacios')->validar_nombre_espacio($nombre,$edificacion,$espacio)){
			$this->set_error('dato_duplicado',array('nombre','edificación'));
		}
	}		
	
	// Determinar si el código del Espacio ya existe para la Edificación seleccionada	
	function validar_codigo_espacio($codigo,$edificacion,$espacio) 
	{
		if (! isset($codigo)) return;
		if (! toba::consulta_php('co_espacios')->validar_codigo_espacio($codigo,$edificacion,$espacio)){
			$this->set_error('dato_duplicado',array('código','edificación'));
		}
	}	
	
}
?>