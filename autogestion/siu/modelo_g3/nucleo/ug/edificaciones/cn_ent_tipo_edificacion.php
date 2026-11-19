<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Ubicaciones
 */

class cn_ent_tipo_edificacion extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('edificaciones_tipos')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('edificaciones_tipos')->get();
	}

	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['edificacion_tipo'] = $this->tabla('edificaciones_tipos')->get_columna('edificacion_tipo');
		$this->validar_nombre_tipo_edificacion($datos['nombre'], $datos['edificacion_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el nombre del Tipo de Edificacion ya existe.
	function validar_nombre_tipo_edificacion($nombre, $tipo_edif)
	{
		if (! toba::consulta_php('co_edificaciones')->validar_nombre_tipo_edificacion($nombre, $tipo_edif)) {
			$this->set_error('dato_existente', array('nombre', 'Tipo de edificación'));
		}
	}
}

?>