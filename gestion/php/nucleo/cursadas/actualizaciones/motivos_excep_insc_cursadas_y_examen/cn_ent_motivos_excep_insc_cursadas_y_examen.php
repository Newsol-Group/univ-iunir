<?php
/**
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_motivos_excep_insc_cursadas_y_examen extends cn_entidad
{
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivo_excepcion')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivo_excepcion')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['motivo_excepcion'] = $this->tabla('motivo_excepcion')->get_columna('motivo_excepcion');
		
		$this->validar_nombre_motivo_excepcion_insc($datos['nombre'], $datos['motivo_excepcion']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_excepcion_insc($nombre, $motivo)
	{
		if (! toba::consulta_php('co_inscripciones')->validar_nombre_motivo_excepcion_insc($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Excepción de Inscripciones a Cursadas y Examen'));
		}
	}
	
	function motivo_en_uso()
	{
		$motivo_excepcion = toba::db()->quote($this->tabla('motivo_excepcion')->get_columna('motivo_excepcion'));
		$where = "sga_insc_cursada.motivo_excepcion = $motivo_excepcion";
		$insc_cursadas_log = toba::consulta_php('co_inscripciones_cursadas')->get_insc_cursada($where);
		if (guarani_arreglos::count($insc_cursadas_log) > 0) {
			// Motivo usado en insc. cursadas
			return true;
		} else {
			// Si no es usado en insc. cursadas, chequear en insc. examen
			$where = "sga_insc_examen.motivo_excepcion = $motivo_excepcion";
			$insc_examen_log = toba::consulta_php('co_inscripciones_examenes')->get_insc_examen($where);
			return guarani_arreglos::count($insc_examen_log) > 0;
		}
	}
	
	
}
?>