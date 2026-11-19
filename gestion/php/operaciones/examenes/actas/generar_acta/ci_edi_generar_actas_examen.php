<?php
class ci_edi_generar_actas_examen extends guarani_pers_ci
{
	// Datos del formulario de acta
	protected $s__nro_acta;
	protected $s__observaciones;
	
	protected $datos_form_acta_temp;
	protected $s__parametro_multiples_acta_controlado;
	
	function ini()
	{
		if (!isset($this->s__parametro_multiples_acta_controlado)) {
			$this->s__parametro_multiples_acta_controlado = true;
			if (guarani::parametros()->get_valor('exa_multiples_actas_examen') == comunes::no) {
				$this->cn()->agregar_todos_los_alumnos_de_la_mesa();
				$this->dep('alumnos')->set_deshabilitar_eliminar_alumnos_mesa();
			}
		}
		$this->dep('alumnos')->set_deshabilitar_eliminar_alumnos_disponibles();
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/** 
	 * Función invocada desde el ci de navegación para limpiar la memoria de las 
	 * variables utilizadas en la edición.
	 */
	function resetear_variables()
	{
		unset($this->s__nro_acta);
		unset($this->s__observaciones);
		unset($this->s__parametro_multiples_acta_controlado);
		
		//Limpia las variables del selector de alumnos.
		$this->dependencia('alumnos')->resetear_variables();
	}

	/**
	 * Retorna la evaluacion automática del llamado mesa seleccionado
	 */
	function get_evaluacion()
	{
		return $this->cn()->get_evaluacion();
	}

	function get_llamado_mesa_entidad()
	{
		return $this->cn()->get_llamado_mesa_entidad();
	}

	function get_mesa_examen()
	{
		return $this->cn()->get_mesa_examen();
	}

	//--------------------------------------------------------------------------
	//---- alumnos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function agregar_alumno_mesa($alumno)
	{
		$this->cn()->agregar_alumno_mesa($alumno);
	}

    function set_instancia($instancia)
    {
        $this->cn()->set_inscripto_instancia($instancia);
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

	//--------------------------------------------------------------------------
	//---- Métodos que usa el selector de alumnos ------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna la clase de consulta, el metodo y los parámetros a hacer ejecutados por el selector de alumnos, para obtener los
	 * los alumnos disponibles a ser seleccionados. El selector agrega el filtro para restar los ya seleccionados.
	 * 
	 * @return array
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

	//--------------------------------------------------------------------------
	//---- Acta ----------------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_nro_acta()
	{
		if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen') == comunes::no) {
			$this->s__nro_acta = $this->cn()->generar_nro_acta(acta::tipo_normal);
		}
		return $this->s__nro_acta;
	}

	function get_observaciones()
	{
		return $this->s__observaciones;
	}

	//--------------------------------------------------------------------------
	//---- form_acta -----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Permite cambiar la configuración del formulario previo a la generación de la salida
	 * El formato del carga debe ser array(<campo> => <valor>, ...)
	 */
	function conf__form_acta($form)
	{
		if (isset($this->datos_form_acta_temp)) {
			$form->set_datos($this->datos_form_acta_temp);
		} else {
			$datos_form = array();

			// Número de Acta: deberá estar visible sólo si exa_carga_manual_nro_acta_examenes = S.
			if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen') == comunes::no) {
				$form->desactivar_efs(array('nro_acta'));
			} else {
				$datos_form['nro_acta'] = $this->s__nro_acta;
			}

			$datos_form['observaciones'] = $this->s__observaciones;

			$form->set_datos($datos_form);
		}
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_acta__modificacion($datos)
	{
		try {
			if (isset($datos['nro_acta'])) {
				$this->s__nro_acta = $datos['nro_acta'];
			}
			$this->s__observaciones = $datos['observaciones'];
		} catch (guarani_error $e) {
			$this->datos_form_acta_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}
?>