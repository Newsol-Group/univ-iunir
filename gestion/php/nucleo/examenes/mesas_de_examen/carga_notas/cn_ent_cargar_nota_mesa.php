<?php
class cn_ent_cargar_nota_mesa extends cn_ent_acta_examen
{

	/**
	 * Se extiende el metodo que obtiene el filtro de la consulta que obtiene los alumnos disponibles para la mesa
	 *
	 * @return <type>
	 */
	function filtro_alumnos_disponibles()
	{
		$filtro = '';
		return $filtro;
	}

	function set_carga_notas_eval_autom($datos)
	{
		$this->validar_carga_notas_eval_autom($datos);
		$this->procesar_detalles($datos);
	}

	function set_carga_notas_eval_manual($datos)
	{
		$this->validar_carga_nota_eval_manual($datos);
		$this->procesar_detalles_eval_manual($datos);
	}

	function set_carga_nota_final($alumno, $datos)
	{
		$this->validar_carga_nota_final($datos);
		$this->modificar_detalle($alumno[apex_datos_clave_fila], $datos);
	}


	/**
	 *  Determina si hay cambios en la seleccion de alumnos
	 *
	 * @return <type>
	 */
	function hay_cambios_seleccion_alumnos()
	{
		$datos = $this->get_ids_detalle(array('agregado' => true));
		return !empty($datos);
	}

	/**
	 *  Determina si hay cambios en las notas de las evaluaciones manuales
	 *
	 * @return <type>
	 */


	function hay_cambios_carga_notas()
	{
		return ($this->tabla('eval_detalle')->hay_cambios() || $this->tabla('eval_detalle_examenes')->hay_cambios());
	}

	
	//--------------------------------------------------------------------------------------------------------------------
	//											Validaciones Carga Notas
	//--------------------------------------------------------------------------------------------------------------------

	function validar_carga_notas_eval_autom($datos)
	{
		foreach ($datos as $detalle) {
			// Se validan la nota y el resultado de cursada.
			if ($this->validar_nota($detalle['nota'], $detalle['escala_nota'], $detalle['apellido_y_nombre'])) {
				$this->validar_resultado($detalle['nota'], $detalle['escala_nota'], $detalle['resultado'], $detalle['apellido_y_nombre']);
			}
			$this->validar_fecha($detalle['fecha'], $detalle['apellido_y_nombre']);
		}
		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	function validar_carga_nota_eval_manual($datos)
	{
		foreach ($datos as $detalle) {
			//Se valida la nota de la evaluación
			if ($this->validar_nota($detalle['nota'], $detalle['escala_nota'], "la evaluación ".$detalle['nombre_y_tipo_eval'])) {
				$this->validar_resultado($detalle['nota'], $detalle['escala_nota'], $detalle['resultado'], "la evaluación ".$detalle['nombre_y_tipo_eval']);
			}
		}
		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());

		}
	}

	
	function validar_carga_nota_final($datos)
	{
		//Se valida la nota de la evaluación
		$this->validar_nota($datos['nota'], $datos['escala_nota'], 'la Nota Final');
		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	
	/**
	 * Verifica que la nota ingresada sea válida de acuerdo a la escala de notas de la instancia correspondiente al alumno.
	 * retorna true si la nota es valida / false si es invalida, esto es para luego sabeer si debo validar el resultado
	 */
	function validar_nota($nota, $escala_nota, $sujeto_del_mensaje)
	{
		$validador = escalas_notas::validador_escalas_notas();
		if (! $validador->es_nota_valida($nota, $escala_nota)) {
				$this->set_error('800_nota_invalida', array($sujeto_del_mensaje));
				return false;
			}
		return true;
	}

	/**
	 * Verifica que el resultado seteado para el alumno sea el que se corresponde con la escala de notas asignada al 
	 * mismo (según su instancia) y la nota que se seteó para él, o en su defecto que el resultado sea Ausente y al 
	 * alumno no se le haya seteado nota.
	 * Además se verifica que si hay cargado un resultado debe haberse cargado una nota y viceversa.
	 */
	function validar_resultado($nota, $escala_nota, $resultado, $apellido_nombre_alumno)
	{
		$validador = escalas_notas::validador_escalas_notas();
		if (!empty($resultado)) {
			if ($resultado == escalas_notas::resultado_ausente && isset($nota)) {
				$this->set_error('800EXA_error_nota_ausente', array($apellido_nombre_alumno));
			} elseif (isset($nota) && !$validador->es_resultado_valido($nota, $escala_nota, $resultado)) {
				$this->set_error('800EXA_error_nota_resultado', array($apellido_nombre_alumno));
			} elseif (!isset($nota) && ($resultado != escalas_notas::resultado_ausente)) {
				$this->set_error('800EXA_error_nota_faltante', array($apellido_nombre_alumno));
			}
		} elseif (isset($nota)) {
			$this->set_error('800EXA_error_nota_resultado', array($apellido_nombre_alumno));
		}
	}

	/**
	 * Verifica que la fecha de la carga de nota para el alumno sea válida, es decir, mayor o igual a la fecha del 
	 * llamado-mesa y menor o igual a la actual.
	 */
	function validar_fecha($fecha_nota, $apellido_nombre_alumno)
	{
		if (!empty($fecha_nota) && !toba::consulta_php('co_mesas_de_examen')->es_fecha_valida_de_carga_nota_examen($fecha_nota, $this->get_llamado_mesa())) {
			$this->set_error('800_control_fecha_entre', array('cargada para el alumno ' . $apellido_nombre_alumno, 'fecha del llamado de la mesa', 'fecha actual'));
		}
	}



	
	/**
	 * Verifica los datos del autcompletar, si se quiere autocompletar el dato nota que se haya ingresado, que corresponda
	 * a la instancia seleccionada, y que haya siempre una instancia elegida.
	 */
	function validar_datos_a_autocompletar($datos)
	{
		if (empty($datos['instancia'])) {
			throw guarani::error(guarani_mensajes::get('dato_faltante', array('instancia')));
		}
		if ($datos['campo_completar'] == mesa_de_examen::autocompletar_campo_nota) {
			if (empty($datos['nota'])) {
				throw guarani::error(guarani_mensajes::get('dato_faltante', array('nota')));
			} elseif (!toba::consulta_php('co_escalas_notas')->es_nota_valida_para_instancia_examen($this->get_mesa_examen(), $datos['nota'], $datos['instancia'])) {
				throw guarani::error(guarani_mensajes::get('800_dato_dep_invalido', array('nota', 'la instancia y su escala asociada')));
			}
		}
	}

	function eliminar_alumno_disponible($alumno)
    {
		guarani::act('act_actas')->eliminar_eval_detalle_examen($this->get_evaluacion(), $alumno);
    }

}

?>