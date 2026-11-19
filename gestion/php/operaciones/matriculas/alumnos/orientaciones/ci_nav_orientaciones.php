<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class ci_nav_orientaciones extends ci_nav_sin_listado_inicial
{
	// Orientaciones para el plan-versión del alumno seleccioando.
	protected $s__orientaciones;

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__orientaciones = toba::consulta_php('co_planes')->get_plan_version_orientaciones($seleccion['plan_version']);
		if (empty($this->s__orientaciones)) {
			$mensaje = guarani_mensajes::get('800MAT_plan_sin_orientaciones', array($seleccion['apellido_y_nombre']));
			toba::notificacion()->agregar($mensaje);
		} else {
			$this->s__seleccion = $seleccion;
			$this->cn()->cargar(array('alumno' => $seleccion['alumno']));
			$this->set_pantalla('edicion');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_alumno_seleccionado()
	{
		return $this->s__seleccion;
	}

	function get_orientaciones()
	{
		return $this->s__orientaciones;
	}
}
?>