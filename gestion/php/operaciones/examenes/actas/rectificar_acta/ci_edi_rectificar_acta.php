<?php
class ci_edi_rectificar_acta extends guarani_pers_ci
{
	protected $s__nro_acta;
	protected $s__documento;
	protected $s__observaciones;

	function ini()
	{
		// Datos del acta.
		$this->s__id_acta = $this->controlador()->get_acta_seleccionada();
		$this->s__datos_acta = $this->controlador()->get_datos_acta();
		// Se invoca al método del selector de alumnos que elimina columnas para los alumnos disponibles de la mesa.
		$this->dependencia('alumnos')->eliminar_columnas_alumnos_mesa_disponibles(array('inscripto'));
		// Se invoca al método del selector de alumnos que agrega la columna 'En Acta' para los alumnos disponibles de la mesa.
		$this->dependencia('alumnos')->agregar_columna_alumnos_mesa_disponibles('en_acta', 'En Acta');
		// Se invoca al método del selector de alumnos que agrega la columna 'Invalidado' para los alumnos disponibles de la mesa.
		$this->dependencia('alumnos')->agregar_columna_alumnos_mesa_disponibles('invalidado', 'Invalidado');
		$this->dependencia('alumnos')->set_deshabilitar_eliminar_alumnos_disponibles();
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función invocada desde el ci de navegación para limpiar la memoria de las 
	 * variables utilizadas en la edición.
	 */
	function resetear_variables()
	{
		unset($this->s__nro_acta);
		unset($this->s__documento);
		unset($this->s__observaciones);
		//Limpia las variables del selector de alumnos.
		$this->dependencia('alumnos')->resetear_variables();
	}

	function get_llamado_mesa_entidad()
	{
		return $this->controlador()->get_llamado_mesa();
	}

	function get_mesa_examen()
	{
		return $this->controlador()->get_mesa_acta_seleccionada();
	}

	//-----------------------------------------------------------------------------------
	//---- Métodos para los eventos del selector de alumnos -----------------------------
	//-----------------------------------------------------------------------------------

    function set_instancia($instancia)
    {
        $this->cn()->set_inscripto_instancia($instancia);
    }
    
	function agregar_alumnos_mesa($alumnos)
	{
		$this->cn()->agregar_alumnos_mesa_llamado($alumnos, $this->controlador()->get_llamado());
	}

	function agregar_alumno_otros($alumno)
	{
		$this->cn()->agregar_alumno_otros($alumno);
	}

	function eliminar_alumno($alumno)
	{
		$this->cn()->eliminar_alumno($alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- Métodos que invoca el Selector de Alumnos para configurar sus dependencias ---
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

	//-----------------------------------------------------------------------------------
	//---- Métodos auxiliares para datos de la Rectificación del Acta -------------------
	//-----------------------------------------------------------------------------------

	function get_nro_acta()
	{
		if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen') == comunes::no) {
			$this->s__nro_acta = $this->cn()->generar_nro_acta(acta::tipo_rectificativa);
		}
		return $this->s__nro_acta;
	}

	function get_documento()
	{
		if (isset($this->s__documento)) {
			return $this->s__documento;
		}
	}

	function get_observaciones()
	{
		return $this->s__observaciones;
	}

	//-----------------------------------------------------------------------------------
	//---- form_acta --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Permite cambiar la configuración del formulario previo a la generación de la salida
	 * El formato del carga debe ser array(<campo> => <valor>, ...)
	 */
	function conf__form_acta(form_acta $form)
	{
		$datos_form = array();

		// Número de Acta: deberá estar visible sólo si exa_carga_manual_nro_acta_examenes = S.
		if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen') == comunes::no) {
			$form->desactivar_efs(array('nro_acta'));
		} else {
			$datos_form['nro_acta'] = $this->s__nro_acta;
		}

		if (isset($this->s__documento)) {
			$datos_form['documento'] = $this->s__documento;
		}
		$datos_form['observaciones'] = $this->s__observaciones;

		$form->set_datos($datos_form);
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * 
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_acta__modificacion($datos)
	{
		if (isset($datos['nro_acta'])) {
			$this->s__nro_acta = $datos['nro_acta'];
		}
		if (isset($datos['documento'])) {
			$this->s__documento = $datos['documento'];
		}
		$this->s__observaciones = $datos['observaciones'];
	}

}
?>