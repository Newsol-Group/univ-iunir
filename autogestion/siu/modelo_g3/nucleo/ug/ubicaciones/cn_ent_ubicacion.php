<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */


class cn_ent_ubicacion extends cn_entidad
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
		$this->tabla('ubicaciones')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('ubicaciones')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['ubicacion'] = $this->tabla('ubicaciones')->get_columna('ubicacion');
		$this->validar_nombre_ubicacion($datos['nombre'], $datos['ubicacion']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Ubicacion ya existe.
	function validar_nombre_ubicacion($nombre, $ubicacion) 
	{
		if (! toba::consulta_php('co_ubicaciones')->validar_nombre_ubicacion($nombre, $ubicacion)) {
			$this->set_error('dato_existente', array('nombre', 'Ubicación'));
		}
	}	
}
?>