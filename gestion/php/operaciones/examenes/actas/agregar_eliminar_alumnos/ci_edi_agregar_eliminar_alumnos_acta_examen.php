<?php
class ci_edi_agregar_eliminar_alumnos_acta_examen extends guarani_pers_ci
{
	//Indice del mensaje de confirmación de eliminación de Alumnos asociados al Acta.
	protected $indx_msg_confirmar_eliminar = '800_eliminar_alumno_acta';
	
	function ini()
	{
		//Se setea un mensaje de confirmación para el evento de eliminación de Alumnos asociados al Acta.
		$datos_acta = $this->controlador()->get_datos_acta_seleccionada();
		$msj_confirm = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($datos_acta['nro_acta']));
		$this->dependencia('alumnos')->set_confirmacion_eliminacion_alumnos($msj_confirm); 
		//Si no se permiten múltiples actas, entonces no se permiten eliminar alumnos inscriptos.
		if (guarani::parametros()->get_valor('exa_multiples_actas_examen') == comunes::no) {
			$this->dep('alumnos')->set_deshabilitar_eliminar_alumnos_mesa();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//Función invocada desde el ci de navegación para limpiar la memoria de las variables utilizadas en la edición.
	function resetear_variables()
	{
		//No tiene variables propias. Sólo limpia las variables de edición del selector de alumnos.
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

	function get_acta_seleccionada()
	{
		return $this->get_acta();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Métodos para los eventos del selector de alumnos -----------------------------
	//-----------------------------------------------------------------------------------

	function agregar_alumnos_mesa($alumnos)
	{
		$this->cn()->agregar_alumnos_mesa($alumnos);
	}
    
    function set_instancia($instancia)
    {
        $this->cn()->set_inscripto_instancia($instancia);
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
		$this->cn()->set_eliminado($alumno);
		$acta = $this->controlador()->get_datos_acta_seleccionada();
        $this->cn()->eliminar_alumno_disponible($acta, $alumno);	
    }

	//-----------------------------------------------------------------------------------
	//-- Métodos que invoca el Selector de Alumnos para configurar sus dependencias -----
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna la clase de consulta, el metodo y los parámetros a hacer ejecutados por el selector de alumnos, para obtener los
	 * los alumnos disponibles a ser seleccionados. El selector agrega el filtro para restar los ya seleccionados.
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