<?php
/**
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Titulos Institucionales
 */
class cn_ent_titulo_institucional extends cn_entidad
{
	function get_datos($tabla=null)
	{
		return $this->tabla('titulos')->get();
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('titulos')->set($datos);
	}
	
	function validar_datos($datos)
	{
		$datos['titulo_persona'] = $this->tabla('titulos')->get_columna('titulo_persona');
		$this->validar_nombre_titulo($datos['nombre'], $datos['titulo_persona']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_nombre_titulo($nombre, $titulo) 
	{
		if (! toba::consulta_php('co_personas')->validar_nombre_persona_titulo($nombre, $titulo)) {
			$this->set_error('dato_existente', array('nombre', 'Título Institucional'));
		}
	}
}
?>