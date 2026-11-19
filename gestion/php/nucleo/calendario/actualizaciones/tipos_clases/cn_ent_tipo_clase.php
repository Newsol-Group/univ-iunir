<?php
/**
 *
 *
 * @package 	Calendario
 * @subpackage 	Actualizaciones
 */
class cn_ent_tipo_clase extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('clases_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('clases_tipos')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$nombre = $datos['nombre'];
		$tipo_clase = $this->tabla('clases_tipos')->get_columna('tipo_clase');
		if (! toba::consulta_php('co_clases')->validar_nombre_tipo_clase($nombre, $tipo_clase)) {
			$this->set_error('dato_duplicado', array('nombre', 'Tipo de Clase'));
			throw guarani::error($this->errores);
		}
	}

}
?>