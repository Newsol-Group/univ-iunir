<?php
/**
 *
 *
 * @package 	Asistencia
 * @subpackage 	-
 * 
 */
class cn_ent_motivo_ausencia extends cn_entidad
{

	//-----------------------------------------------------------------------------------
	//---- sga_motivos_ausencias --------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('motivo_ausencia')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('motivo_ausencia')->get();
	}
	
	function validar_datos($datos)
	{
		$motivo_ausencia = $this->tabla('motivo_ausencia')->get_columna('motivo_ausencia');		
		$this->validar_nombre($datos['nombre'], $motivo_ausencia);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_nombre($nombre, $motivo)
	{
		if (!toba::consulta_php('co_motivos_ausencia')->validar_nombre_motivo_ausencia($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Ausencia'));
		}
	}
	
}
?>