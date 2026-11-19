<?php
/**
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_motivo_rechazo_insc_cursadas_y_examen extends cn_entidad
{
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivo_rechazo')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivo_rechazo')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['motivo_rechazo'] = $this->tabla('motivo_rechazo')->get_columna('motivo_rechazo');
		
		$this->validar_nombre_motivo_rechazo_insc($datos['nombre'], $datos['motivo_rechazo']);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre_motivo_rechazo_insc($nombre, $motivo)
	{
		if (! toba::consulta_php('co_inscripciones')->validar_nombre_motivo_rechazo_insc($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Rechazo de Inscripciones a Cursadas y Examen'));
		}
	}
	
	function motivo_en_uso()
	{
		$motivo_rechazo = toba::db()->quote($this->tabla('motivo_rechazo')->get_columna('motivo_rechazo'));
		$where = "sga_insc_cursada_log.motivo_rechazo = $motivo_rechazo";
		$insc_cursadas_log = toba::consulta_php('co_inscripciones_cursadas')->get_insc_cursadas_log($where);
		if (guarani_arreglos::count($insc_cursadas_log) > 0) {
			// Motivo usado en insc. cursadas
			return true;
		} else {
			// Si no es usado en los logs de insc. cursadas, chequear en los logs de insc. examen
			$where = "sga_insc_examen_log.motivo_rechazo = $motivo_rechazo";
			$insc_examen_log = toba::consulta_php('co_inscripciones_examenes')->get_insc_examen_log($where);
			return guarani_arreglos::count($insc_examen_log) > 0;
		}
	}
}

?>