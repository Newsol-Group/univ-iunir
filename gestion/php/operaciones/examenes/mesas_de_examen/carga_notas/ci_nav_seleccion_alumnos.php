<?php
class ci_nav_seleccion_alumnos extends guarani_pers_ci
{
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función invocada desde el ci de navegación para limpiar la memoria de las 
	 * variables utilizadas en la edición.
	 */
	function resetear_variables()
	{
		// Limpia las variables del selector de alumnos.
		$this->dependencia('alumnos')->resetear_variables();
	}

	function get_llamado_mesa_entidad()
	{
		return $this->cn()->get_llamado_mesa_entidad();
	}

	function get_mesa_examen()
	{
		return $this->cn()->get_mesa_examen();
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
    function set_instancia($instancia)
    {
        $this->cn()->set_inscripto_instancia($instancia);
    }
    
	function agregar_alumno_mesa($alumno)
	{
		$this->cn()->agregar_alumno_mesa($alumno);
	}

	function agregar_alumnos_mesa($alumnos)
	{
		$this->cn()->agregar_alumnos_mesa($alumnos);
	}

	function agregar_alumno_otros($alumno)
	{
		$this->cn()->agregar_alumno_otros($alumno);
	}

	function eliminar_alumno($alumno)
	{
		$this->cn()->eliminar_alumno($alumno);
	}

	function eliminar_alumno_disponible($alumno)
    {	
        $this->cn()->eliminar_alumno_disponible($alumno);	
    }
	
	//-----------------------------------------------------------------------------------
	//---- Métodos que usa el selector de Alumnos ---------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Retorna la clase de consulta, el metodo y los parámetros a hacer ejecutados por el 
	 * selector de alumnos, para obtener los alumnos disponibles a ser seleccionados. El 
	 * selector agrega el filtro para restar los ya seleccionados.
	 */
	function get_alumnos_mesa_disponibles()
	{
		return $this->cn()->get_metodo_alumnos_mesa_disponibles();
	}

	function get_alumnos_otros_disponibles()
	{
		return $this->cn()->get_metodo_alumnos_otros_disponibles();
	}

	function get_alumnos_asociados()
	{
		return $this->cn()->get_alumnos_asociados();
	}

}
?>