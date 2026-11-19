<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage
 * 
 */
class cn_ent_grupo_destinatarios extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('grupos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('grupos')->get();
	}

	function set_personas_asociadas()
	{
		$this->tabla('grupos_personas')->nueva_fila($datos);
	}
	
	function get_personas_asociadas()
	{
		return $this->tabla('grupos_personas')->get_filas();
	}
	
	/**
	 * Agrega varias Personas (alumnos ó docentes) al Grupo de Destinatarios cargado
	 *
	 * @param array $personas Array con los ids de las personas a agregar
	 */
	function agregar_personas_grupo($personas)
	{
		if (!empty($personas)) {
			foreach ($personas as $persona) {
				$this->tabla('grupos_personas')->nueva_fila($persona);
			}
		}
	}
	
	/**
	 * Elimina la persona del Grupo de Destinatarios cargado
	 *
	 * @param integer $persona id de la persona a eliminar
	 */
	function eliminar_asociado($persona)
	{
		$this->tabla('grupos_personas')->eliminar_fila($persona);
	}
}
?>