<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class cn_ent_carga_notas_evaluaciones extends cn_entidad
{
	function cargar_atributos_parametros($comision)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}
	
	function entidad_cargada()
	{
		return $this->dep('datos')->esta_cargada();
	}

	//--------------------------------------------------------------------------
	//---- Control de Cambios
	//--------------------------------------------------------------------------

	function hay_cambios()
	{
		return $this->dep('datos')->hay_cambios();
	}

	function hay_cambios_seleccion_alumnos()
	{
		$datos = $this->get_detalles(array('agregado' => true));
		return !empty($datos);
	}

	//--------------------------------------------------------------------------
	//---- Evaluación
	//--------------------------------------------------------------------------

	function set_datos_evaluacion($datos)
	{
		$this->tabla('evaluaciones')->set($datos);
	}

	function get_datos_evaluacion()
	{
		return $this->tabla('evaluaciones')->get();
	}

	function get_columna_evaluacion($columna)
	{
		return $this->tabla('evaluaciones')->get_columna($columna);
	}

	//--------------------------------------------------------------------------
	//---- Detalles de la Evaluación
	//--------------------------------------------------------------------------

	function get_detalles($condiciones = null, $columna_ordenamiento = null)
	{
		$detalles = $this->tabla('eval_detalle')->get_filas($condiciones);
		return empty($columna_ordenamiento) ? rs_ordenar_por_columna($detalles, 'apellido_y_nombre') : rs_ordenar_por_columna($detalles, $columna_ordenamiento);
	}

	function get_detalle($id)
	{
		return $this->tabla('eval_detalle')->get_fila($id);
	}

	function get_id_detalle($condiciones)
	{
		return current($this->tabla('eval_detalle')->get_id_fila_condicion($condiciones));
	}

	function existe_detalle($condiciones)
	{
		return $this->tabla('eval_detalle')->existe_fila_condicion($condiciones);
	}

	function agregar_detalle($datos)
	{
		$this->tabla('eval_detalle')->nueva_fila($datos);
	}

	function eliminar_detalle($datos)
	{
		$this->tabla('eval_detalle')->eliminar_fila($datos);
	}

	function modificar_detalle($id, $datos)
	{
		$this->tabla('eval_detalle')->modificar_fila($id, $datos);
	}

	function modificar_columna_detalle($id, $columna, $valor)
	{
		$this->tabla('eval_detalle')->set_fila_columna_valor($id, $columna, $valor);
	}

	function procesar_detalles($datos)
	{
		$this->validar_datos($datos);
		$this->tabla('eval_detalle')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------
	//---- Cierre de Evaluación
	//--------------------------------------------------------------------------

	function get_cantidades_alumnos_no_agregados()
	{
		$cant_con_resultado = 0;
		$cant_sin_resultado = 0;
		$alumnos_agregados = aplanar_matriz($this->get_detalles(array('agregado' => true)), 'alumno');
		$datos = $this->get_detalles();
		foreach ($datos as $detalle) {
			if (!in_array($detalle['alumno'], $alumnos_agregados)) {
				if (empty($detalle['resultado'])) {
					$cant_sin_resultado++;
				} else {
					$cant_con_resultado++;
				}
			}
		}
		return array('cant_con_resultado' => $cant_con_resultado, 'cant_sin_resultado' => $cant_sin_resultado);
	}

	function eliminar_alumnos_sin_resultado()
	{
		$datos = $this->get_detalles();
		foreach ($datos as $detalle) {
			if (empty($detalle['resultado'])) {
				$this->eliminar_detalle($detalle);
			}
		}
	}

	function cerrar_evaluacion()
	{
		$datos = array('estado' => comunes::estado_cerrado, 'fecha_cierre' => guarani_fecha::get_hoy());
		$this->set_datos_evaluacion($datos);

		$this->dep('datos')->sincronizar();
	}

	//--------------------------------------------------------------------------
	//---- Validaciones
	//--------------------------------------------------------------------------

	function validar_datos($datos)
	{
		$escala_nota = $this->get_columna_evaluacion('escala_nota');
		foreach ($datos as $detalle) {
			$this->validar_nota($detalle['nota'], $escala_nota, $detalle['apellido_y_nombre']);
		}
		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	/**
	 * Verifica que la nota ingresada sea válida de acuerdo a la escala de notas.
	 */
	function validar_nota($nota, $escala_nota, $apellido_nombre_alumno)
	{
		if (!is_null($nota) && !empty($escala_nota) && !toba::consulta_php('co_escalas_notas')->existe_nota_en_escala($nota, $escala_nota)) {
			$this->set_error('800_nota_invalida', array("$apellido_nombre_alumno."));
		}
	}

}
?>