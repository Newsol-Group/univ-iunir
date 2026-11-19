<?php
class ci_seleccion_alumnos_asociados extends guarani_pers_ci
{
	// Filtros seteados desde las operaciones "consumidoras".
	protected $s__evaluacion;
	protected $s__acta;
	protected $s__instancia;
	protected $s__acta_original;
	protected $s__comision;
	
	// Lista de alumnos asociados, para cargar el cuadro.
	protected $s__alumnos_asociados;
	
	// Indica si es posible eliminar alumnos inscriptos de la lista de asociados.
	protected $s__eliminar_inscriptos = true;

	function set_filtros($evaluacion, $acta = null, $instancia = null, $acta_original = null, $comision = null)
	{
		$this->s__evaluacion = $evaluacion;
		$this->s__acta = $acta;
		$this->s__instancia = $instancia;
		$this->s__acta_original = $acta_original;
		$this->s__comision = $comision;
	}

	function get_filtros()
	{
		return array(
			'evaluacion' => $this->s__evaluacion,
			'acta' => $this->s__acta,
			'instancia' => $this->s__instancia,
			'acta_original' => $this->s__acta_original,
			'comision' => $this->s__comision
		);
	}

	function es_rectificativa()
	{
		return !empty($this->s__acta_original);
	}

	function hay_comision()
	{
		return !empty($this->s__comision);
	}

	function set_alumnos_asociados($alumnos_asociados)
	{
		$this->s__alumnos_asociados = $alumnos_asociados;
	}

	function get_alumnos_asociados()
	{
		return $this->s__alumnos_asociados;
	}

	/**
	 * Seteo del parámetro que indica si los alumnos inscriptos pueden quitarse de la lista de asociados.
	 */
	function set_eliminar_inscriptos($eliminar_inscriptos)
	{
		$this->s__eliminar_inscriptos = $eliminar_inscriptos;
	}

	function set_msg_confirmacion_eliminar_alumnos($msj)
	{
		$this->dep('asociados')->evento('eliminar')->set_msg_confirmacion($msj);
	}

	function set_deshabilitar_eliminar_alumnos_disponibles()
	{
		$this->dep('disponibles')->dep('comision_disponibles')->evento('eliminar')->anular();
	}

	//-----------------------------------------------------------------------------------
	//---- asociados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asociados(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_seleccion_alumnos'));
		$cuadro->set_datos($this->s__alumnos_asociados);
	}

	function conf_evt__asociados__eliminar(toba_evento_usuario $evento, $fila)
	{
		// Para algunas operaciones, si el parámetro cur_multiples_actas_comision = 'N' se deshabilita el botón 'Eliminar' de la lista de asociados para los alumnos inscriptos.
		$datos = $this->dep('asociados')->get_datos();
		if (!$this->s__eliminar_inscriptos && $datos[$fila]['inscripto'] == comunes::si) {
			$evento->anular();
		} else {
			$evento->activar();
		}
	}

	function evt__asociados__eliminar($seleccion)
	{
		$this->controlador()->eliminar_alumno($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->disparar_limpieza_memoria();
		$this->dep('disponibles')->evt__cancelar();
	}

}
?>