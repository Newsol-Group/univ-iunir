<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Instituciones
 */

class cn_ent_institucion extends cn_entidad
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
		$datos['email'] = strtolower($datos['email']);
		$this->tabla('instituciones')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('instituciones')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['institucion'] = $this->tabla('instituciones')->get_columna('institucion');
		$this->validar_nombre_institucion($datos['nombre'],$datos['institucion']);
		if ($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre de la Ubicacion ya existe.
	function validar_nombre_institucion($nombre,$institucion) 
	{
		if (! toba::consulta_php('co_instituciones')->validar_nombre_institucion($nombre,$institucion)){
			$this->set_error('dato_existente',array('nombre', 'Institución'));
		}
	}	
	
}
?>