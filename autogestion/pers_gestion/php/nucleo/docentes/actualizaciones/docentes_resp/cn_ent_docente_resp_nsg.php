<?php
/**
 *	Representa una responsabilidad asignada a un docente
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_ent_docente_resp_nsg extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('docentes_resp')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('docentes_resp')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$this->validar_ya_existe_docente_resp($datos['docente'], $datos['responsabilidad'], $datos['fecha_desde'], $datos['fecha_hasta']);

		if ($this->hay_errores()) {
			//throw new guarani_error($this->get_mensaje_error());
			throw guarani::error($this->errores);
		}
	}
	
	function validar_ya_existe_docente_resp($docente, $responsabilidad, $fecha_desde, $fecha_hasta)
	{
		if (! toba::consulta_php('co_docentes')->validar_si_ya_existe_asignacion($docente, $responsabilidad, $fecha_desde, $fecha_hasta)) {
			$this->set_error('dato_duplicado', array('ya fue asignado'));
		}
	}


}
?>