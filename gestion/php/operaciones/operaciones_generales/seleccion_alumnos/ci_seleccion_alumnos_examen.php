<?php
class ci_seleccion_alumnos_examen extends guarani_pers_ci
{
	protected $s__desahabilitar_eliminacion = false;
	
	//-----------------------------------------------------------------------------------
	//---- asociados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asociados(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_seleccion_alumnos'));
		$cuadro->set_datos($this->controlador()->get_alumnos_asociados());
	}

	function conf_evt__asociados__eliminar(toba_evento_usuario $evento, $fila)
	{
		// Si el parámetro exa_multiples_actas_examen = 'N', se deshabilita el botón 'Eliminar' de la lista de asociados para los alumnos de la mesa.
		$datos = $this->dep('asociados')->get_datos();
		if ($this->s__desahabilitar_eliminacion && $datos[$fila]['inscripto'] == comunes::si) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	function evt__asociados__eliminar($seleccion)
	{
		$this->controlador()->eliminar_alumno($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 *  Setea la Variable de sesión que determina si se habilita o no, el evento de eliminar
	 *  un alumno de la mesa asociado.
	 */
	function set_deshabilitar_eliminar_alumnos_mesa()
	{
		$this->s__desahabilitar_eliminacion = true;
	}

	function set_deshabilitar_eliminar_alumnos_disponibles()
	{
		$this->dependencia('alumnos_disponibles')->dependencia('mesa_disponibles')->evento('eliminar')->anular();
	}

	//Setea el mensaje de confirmación dado para el evento de eliminación de Alumnos asociados.
	function set_confirmacion_eliminacion_alumnos($msj)
	{
		$this->dependencia('asociados')->evento('eliminar')->set_msg_confirmacion($msj);
	}

	/**
	 * Elimina del cuadro de alumnos de la mesa disponibles para asociar, las columnas dadas.
	 * Como consecuencia también elimina las mismas columnas si es que están, del filtro de alumnos de la mesa.
	 * @param array	$columnas	ids de las columnas a eliminar.
	 */
	function eliminar_columnas_alumnos_mesa_disponibles($columnas)
	{
		$this->dependencia('alumnos_disponibles')->dependencia('mesa_disponibles')->eliminar_columnas($columnas);
		$columnas_filtro = $this->dependencia('alumnos_disponibles')->dependencia('filtro_mesa')->get_ids_columnas();
		$this->dependencia('alumnos_disponibles')->dependencia('filtro_mesa')->eliminar_columnas(array_intersect($columnas, $columnas_filtro));
	}

	/**
	 * Agrega la columna dada al cuadro de alumnos de la mesa disponibles para asociar.
	 * @param str	$clave	id de la columna a agregar.
	 * @param str	$titulo	título de la columna a mostrar.
	 */
	function agregar_columna_alumnos_mesa_disponibles($clave, $titulo)
	{
		$columnas = array(array('clave' => $clave, 'titulo' => $titulo));
		$this->dependencia('alumnos_disponibles')->dependencia('mesa_disponibles')->agregar_columnas($columnas);
	}

	function resetear_variables()
	{
		unset($this->s__desahabilitar_eliminacion);
		$this->dependencia('alumnos_disponibles')->resetear_variables();
	}

}
?>