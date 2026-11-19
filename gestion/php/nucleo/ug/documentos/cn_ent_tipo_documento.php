<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */

class cn_ent_tipo_documento extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('tipos_documentos')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('tipos_documentos')->get();
	}

	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['documento_tipo'] = $this->tabla('tipos_documentos')->get_columna('documento_tipo');
		$this->validar_nombre_tipo_documento($datos['nombre'], $datos['documento_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre del Tipo de Documento Respaldatorio ya existe.
	function validar_nombre_tipo_documento($nombre, $tipo_doc)
	{
		if (! toba::consulta_php('co_documentos')->validar_nombre_tipo_documento($nombre, $tipo_doc)) {
			$this->set_error('dato_existente', array('nombre', 'Tipo de documento'));
		}
	}
}

?>