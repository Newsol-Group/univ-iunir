<?php
/**
 *
 *
 * @package 	Calendario
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_motivo_invalidacion_clase extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('clases_motivos_inval')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('clases_motivos_inval')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$nombre = $datos['nombre'];
		$motivo = $this->tabla('clases_motivos_inval')->get_columna('motivo_invalidacion');
		if (! toba::consulta_php('co_motivos_invalidacion_clases')->validar_nombre($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Invalidación de Clases'));
			throw guarani::error($this->errores);
		}
	}

}
?>