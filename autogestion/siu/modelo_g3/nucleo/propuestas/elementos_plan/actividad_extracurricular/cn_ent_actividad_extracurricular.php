<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_ent_actividad_extracurricular extends cn_ent_elemento
{
	function inicializar_estado()
	{
		$fila['entidad_tipo'] = elemento::tipo_actividad;
		$fila['entidad_subtipo'] = elemento::subtipo_actividad_extracurricular;
		$this->tabla('elemento')->nueva_fila($fila);
		$this->tabla('elemento')->set_cursor(0);
		
		$datos['requiere_cursada'] = 'N';
		$datos['requiere_examen'] = 'N';
		$datos['permite_promocion'] = 'N';
		$datos['permite_rendir_libre'] = 'N';
		$this->tabla('atributos')->nueva_fila($datos);
		$this->tabla('atributos')->set_cursor(0);
	}

	//---------- paquete de INFO default de MATERIA -------------

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('atributos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('atributos')->get();
	}
}

?>