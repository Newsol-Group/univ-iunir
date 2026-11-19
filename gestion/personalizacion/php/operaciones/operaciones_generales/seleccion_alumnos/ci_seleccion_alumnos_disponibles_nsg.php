<?php
class ci_seleccion_alumnos_disponibles_nsg extends ci_seleccion_alumnos_disponibles
{
	protected $s__alumnos_agregados_control_morosidad;
	protected $s__evaluacion_control_morosidad;

	//-----------------------------------------------------------------------------------
	//---- comision_disponibles ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__comision_disponibles(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_alumnos_a_seleccionar'));

		$filtro = empty($this->s__filtro_comision) ? 'true' : $this->s__filtro_comision;
		$alumnos_asociados = $this->controlador->get_alumnos_asociados();
		
		if (!empty($alumnos_asociados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno', true);
			// Agrego filtro para que no traiga alumnos repetidos en otras propuestas
			$personas_filtro = guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno');
			$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
		}		
		$filtros = $this->controlador->get_filtros();		
		if ($this->controlador->es_rectificativa()) {
			$cuadro->eliminar_columnas(array('inscripto'));
			$alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursada_rectificada($filtros['evaluacion'], $filtros['acta'], $filtros['instancia'], $filtros['acta_original'], $filtro);
		} elseif ($this->aplica_control_morosidad()) {
			$cuadro->eliminar_columnas(array('inscripto', 'en_acta', 'invalidado'));
			$alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_eval($filtros['comision'], false, $filtro, true, $this->get_evaluacion_control_morosidad());
		} else {
			$cuadro->eliminar_columnas(array('en_acta', 'invalidado'));
			$alumnos = toba::consulta_php('co_evaluaciones')->get_detalles_acta($filtros['evaluacion'], $filtros['acta'], $filtros['instancia'], $filtro);
		}
		$cuadro->set_datos($alumnos);
	}

	function conf_evt__comision_disponibles__seleccionar(toba_evento_usuario $evento, $fila)
	{
		if ($this->controlador->es_rectificativa()) {
			$datos = $this->dep('comision_disponibles')->get_datos();
			if ($datos[$fila]['invalidado'] == comunes::si_desc) {
				$evento->anular();
			}
		}
	}

	function evt__comision_disponibles__seleccionar($seleccion)
	{
		$this->alumnos_seleccionados = $seleccion;
	}

	function evt__comision_disponibles__agregar()
	{
		if (!empty($this->alumnos_seleccionados)) {
			$this->validar_alumnos_no_morosos($this->alumnos_seleccionados);
			foreach ($this->alumnos_seleccionados as $alu) {
				$this->controlador->controlador->agregar_alumno_comision($alu);
				$this->registrar_alumno_agregado($alu);
			}
		}
	}

	function conf_evt__comision_disponibles__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('comision_disponibles')->get_datos();
		if (isset($datos[$fila]['inscripto']) && $datos[$fila]['inscripto'] == comunes::si_desc) {
			$evento->anular();
		}
		if (isset($datos[$fila]['en_acta']) && $datos[$fila]['en_acta'] == comunes::si_desc) {
			$evento->anular();
		}
		$evento->set_msg_confirmacion(toba::mensajes()->get('800CUR_sincronizar_eliminar'));
	}

	function evt__comision_disponibles__eliminar($seleccion)
	{
        $this->controlador->controlador->eliminar_alumno_disponible($seleccion['alumno']);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_otros -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_otros(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_otros)) {
			$filtro->set_datos($this->s__datos_filtro_otros);
		}
	}

	function evt__filtro_otros__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_otros = $datos;
			$this->s__filtro_otros = $this->dep('filtro_otros')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_otros);
			$this->s__filtro_otros = '';
		}
	}

	function evt__filtro_otros__cancelar()
	{
		unset($this->s__datos_filtro_otros);
		$this->s__filtro_otros = '';
	}

	//-----------------------------------------------------------------------------------
	//---- otros_disponibles ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * @todo Al conjunto de "otros alumnos" se restan aquellos asociados a otro acta (no anulada) de la comisión.
	 */
	function conf__otros_disponibles(toba_ei_cuadro $cuadro)
	{
		if (empty($this->s__filtro_otros)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_sinfiltro'));
		} else {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_alumnos_a_seleccionar'));

			$filtro = empty($this->s__filtro_otros) ? 'true' : $this->s__filtro_otros;
			// Al conjunto de "otros alumnos" se restan los asociados y los alumnos de la comisión (porque ya se muestran en el otro cuadro).
			$alumnos_asociados = $this->controlador->get_alumnos_asociados();
			if (!empty($alumnos_asociados)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno', true);
				// Agrego filtro para que no traiga alumnos repetidos en otras propuestas
				$personas_filtro = guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno');
				$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
			}
			$alumnos_comision = $this->dep('comision_disponibles')->get_datos();
			if (!empty($alumnos_comision)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_comision, 'sga_alumnos', 'alumno', 'alumno', true);
				// Agrego filtro para que no traiga alumnos repetidos en otras propuestas
				$personas_filtro = guarani_sql::armar_condicion_in($alumnos_comision, 'sga_alumnos', 'alumno', 'alumno');
				$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
			}
			// Sólo para actas rectificativas se permite agregar alumnos en calidad "Pasivo".
			if (!$this->controlador->es_rectificativa()){
				$filtro .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."'";
			}
			if ($this->aplica_control_morosidad()) {
				$filtro .= ' AND ' . toba::consulta_php('co_cobros')->get_condicion_habilitados_evaluacion($this->get_evaluacion_control_morosidad());
			}

			$filtros = $this->controlador->get_filtros();
			$alumnos = toba::consulta_php('co_alumnos')->get_otros_alumnos_acta_cursada($filtros['evaluacion'], $filtros['acta'], $filtros['instancia'], $filtro);
			$cuadro->set_datos($alumnos);
		}
	}

	function evt__otros_disponibles__seleccion($seleccion)
	{
		$this->validar_alumnos_no_morosos(array($seleccion));
		if ($this->get_evaluacion_control_morosidad() !== null) {
			$this->controlador->controlador->agregar_alumno_comision($seleccion);
		} else {
			$this->controlador->controlador->agregar_alumno($seleccion);
		}
		$this->registrar_alumno_agregado($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->set_evaluacion_control_morosidad(null);
		$this->disparar_limpieza_memoria();
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Carga de notas de evaluaciones informa comision, sin acta ni instancia.
	 * Las actas y la carga de notas de cursada no llevan este control de deuda.
	 */
	protected function aplica_control_morosidad()
	{
		$filtros = $this->controlador->get_filtros();
		return $this->controlador->hay_comision()
			&& empty($filtros['acta']) && empty($filtros['instancia'])
			&& !$this->controlador->es_rectificativa();
	}

	/**
	 * Habilitada expresamente por la navegacion de carga de notas.
	 * La excepcion por pertenencia previa solo corresponde a evaluaciones.
	 */
	function set_evaluacion_control_morosidad($evaluacion)
	{
		$this->resetear_control_morosidad();
		$this->s__evaluacion_control_morosidad = $evaluacion;
	}

	protected function get_evaluacion_control_morosidad()
	{
		$filtros = $this->controlador->get_filtros();
		if ($this->aplica_control_morosidad()
			&& isset($this->s__evaluacion_control_morosidad, $filtros['evaluacion'])
			&& (string) $this->s__evaluacion_control_morosidad === (string) $filtros['evaluacion']) {
			return $this->s__evaluacion_control_morosidad;
		}
		return null;
	}

	function validar_alumnos_no_morosos($alumnos)
	{
		if (!$this->aplica_control_morosidad()) {
			return;
		}

		$morosos = toba::consulta_php('co_cobros')->get_morosos_para_incorporar_evaluacion($alumnos, $this->get_evaluacion_control_morosidad());
		if (empty($morosos)) {
			return;
		}

		$nombres = array();
		foreach ($morosos as $moroso) {
			$nombre = isset($moroso['alumno_nombre']) ? $moroso['alumno_nombre'] : $moroso['alumno'];
			$nombres[$nombre] = $nombre;
		}

		$mensaje = 'No se pueden agregar a la evaluación los siguientes alumnos porque se encuentran bloqueados por deuda: '
			. implode(', ', $nombres) . '.';
		throw guarani::error($mensaje);
	}

	function get_alumnos_agregados_control_morosidad()
	{
		return isset($this->s__alumnos_agregados_control_morosidad)
			? array_values($this->s__alumnos_agregados_control_morosidad)
			: array();
	}

	function resetear_control_morosidad()
	{
		unset($this->s__alumnos_agregados_control_morosidad);
	}

	protected function registrar_alumno_agregado($alumno)
	{
		if (!isset($alumno['alumno'])) {
			return;
		}
		if (!isset($this->s__alumnos_agregados_control_morosidad)) {
			$this->s__alumnos_agregados_control_morosidad = array();
		}
		$this->s__alumnos_agregados_control_morosidad[$alumno['alumno']] = $alumno['alumno'];
	}

	function get_evaluaciones_anteriores()
	{
		$filtros = $this->controlador->get_filtros();
		return toba::consulta_php('co_evaluaciones')->get_evaluaciones_anteriores($filtros['evaluacion']);
	}

	function get_resultados_evaluaciones_anteriores($evaluacion)
	{
		// Se usa una cascada para simular que el resultado no puede ingresarse hasta haber seleccionado una evaluación anterior.
		return toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados();
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_datos');

		// Advertencia de pérdida de datos en el cuadro multiselección de alumnos de la comisión al filtrar.
		if ($this->get_id_pantalla() == 'pant_comision') {
			$id_js_filtro = $this->dependencia('filtro_comision')->get_id_objeto_js();
			$id_js_cuadro = $this->dependencia('comision_disponibles')->get_id_objeto_js();

			echo "
				{$id_js_filtro}.evt__filtrar = function() {
					// La advertencia sólo debe emitirse en caso de que el cuadro tenga filas seleccionadas.
					filas_seleccionadas = {$id_js_cuadro}.get_ids_seleccionados('seleccion');
					if (filas_seleccionadas.length > 0) {
						return confirmar_filtrado();
					}
				}

				function confirmar_filtrado(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_filtrar');
						if (! resp) {
							return false;
						}
					}
					return true;
				}
			";
		}
	}

}
?>
