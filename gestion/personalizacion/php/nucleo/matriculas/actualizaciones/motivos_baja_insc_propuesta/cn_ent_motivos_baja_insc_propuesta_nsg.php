<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_motivos_baja_insc_propuesta_nsg extends cn_entidad
{
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivo_baja')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivo_baja')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['motivo_baja'] = $this->tabla('motivo_baja')->get_columna('motivo_baja');
		
		$this->validar_nombre_motivo_baja_insc($datos['nombre'], $datos['motivo_baja']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_baja_insc($nombre, $motivo)
	{
		if (! toba::consulta_php('co_inscripciones')->validar_nombre_motivo_baja_insc($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Baja de Inscripciones a Propuestas'));
		}
	}
	
}

?>