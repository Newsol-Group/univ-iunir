<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_ent_actividad_materia extends cn_ent_elemento
{
	function inicializar_estado()
	{
		$fila['entidad_tipo'] = elemento::tipo_actividad;
		$fila['entidad_subtipo'] = elemento::subtipo_materia;
		$this->tabla('elemento')->nueva_fila($fila);
		$this->tabla('elemento')->set_cursor(0);
	}

	//---------- paquete de INFO default de MATERIA -------------

	function set_datos_materia($datos)
	{
		$this->tabla('atributos')->set($datos);
	}
	
	function get_datos_materia()
	{
		return $this->tabla('atributos')->get();
	}
}

?>