<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Alumnos
 * 
 */
class cn_ent_motivo_licencia extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_nombre($datos);
		$this->tabla('licencias_motivos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('licencias_motivos')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_nombre($datos)
	{		
		$motivo = $this->tabla('licencias_motivos')->get_columna('motivo_licencia');
		$nombre = $datos['nombre'];
		
		if (!toba::consulta_php('co_motivos_licencia')->validar_nombre($nombre, $motivo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Motivo de Licencia Estudiantil'));
			throw guarani::error($this->errores);
		}
	}
}
?>