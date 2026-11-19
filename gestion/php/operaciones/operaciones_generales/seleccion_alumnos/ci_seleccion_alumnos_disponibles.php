<?php
class ci_seleccion_alumnos_disponibles extends guarani_pers_ci
{
	protected $alumnos_seleccionados;
	
	// Filtro "Alumnos de la Comisión".
	protected $s__filtro_comision;
	protected $s__datos_filtro_comision;
	
	// Filtro "Otros Alumnos".
	protected $s__filtro_otros;
	protected $s__datos_filtro_otros;

	function conf()
	{
		// Si el parámetro cur_agregar_alumnos_no_inscriptos = 'N', se oculta la solapa 'Otros Alumnos'.
		if (guarani::parametros()->get_valor('cur_agregar_alumnos_no_inscriptos') == comunes::no) {
			$this->pantalla()->eliminar_tab('pant_otros');
		}
		if ($this->controlador->hay_comision()) {
			$this->pantalla()->set_tipo_navegacion(toba_ei_pantalla::NAVEGACION_BASICA);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_comision --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_comision(guarani_ei_filtro $filtro)
	{
		$filtro->columna('con_nota_evaluacion_parcial')->set_condicion_fija('es_igual_a');		
		
		if (guarani::get_id_operacion() != comision::cod_op_cargar_notas_comisiones) {
			$filtro->eliminar_columnas(array('con_nota_cursada', 'con_nota_promocion'));
		} else {
			$filtro->columna('con_nota_cursada')->set_condicion_fija('es_igual_a');
			$filtro->columna('con_nota_promocion')->set_condicion_fija('es_igual_a');
		}
		if (!$this->controlador->hay_comision()) {
			$filtro->eliminar_columnas(array('evaluacion_anterior', 'resultado_evaluacion_anterior'));
		} else {
			$filtro->columna('evaluacion_anterior')->set_condicion_fija('es_igual_a');
		}
		if (isset($this->s__datos_filtro_comision)) {
			$filtro->set_datos($this->s__datos_filtro_comision);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_comision__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_comision = $datos;
			$filtro_evaluaciones = '';
			$filtro_resultado = '';
			if (isset($datos['evaluacion_anterior'])) {
				$filtro_evaluaciones = " AND sga_insc_cursada.alumno IN (	
											SELECT	sga_eval_detalle.alumno
											FROM	sga_eval_detalle
											WHERE	sga_eval_detalle.evaluacion = {$datos['evaluacion_anterior']['valor']}";
				if (isset($datos['resultado_evaluacion_anterior'])) {
					$operador = ($datos['resultado_evaluacion_anterior']['condicion'] == 'es_igual_a') ? '=' : '<>';
					$filtro_resultado = " AND sga_eval_detalle.resultado $operador '{$datos['resultado_evaluacion_anterior']['valor']}'";
					$this->dep('filtro_comision')->columna('resultado_evaluacion_anterior')->resetear_estado();
				}
				$filtro_resultado .= ')';
				$this->dep('filtro_comision')->columna('evaluacion_anterior')->resetear_estado();
			}
			$filtro_nota_cursada = '';
			if (isset($datos['con_nota_cursada'])) {
				if ($datos['con_nota_cursada']['valor'] == comunes::si) {
					$filtro_nota_cursada .= " AND sga_eval_detalle_cursadas.nota_cursada IS NOT NULL";
				} else {
					$filtro_nota_cursada .= " AND sga_eval_detalle_cursadas.nota_cursada IS NULL";
				}
				$this->dep('filtro_comision')->columna('con_nota_cursada')->resetear_estado();
			}
			$filtro_nota_promocion = '';
			if (isset($datos['con_nota_promocion'])) {
				if ($datos['con_nota_promocion']['valor'] == comunes::si) {
					$filtro_nota_promocion .= " AND sga_eval_detalle_cursadas.nota_promocion IS NOT NULL";
				} else {
					$filtro_nota_promocion .= " AND sga_eval_detalle_cursadas.nota_promocion IS NULL";
				}
				$this->dep('filtro_comision')->columna('con_nota_promocion')->resetear_estado();
			}
			$filtro_evaluacion_parcial = '';			
			if (isset($datos['con_nota_evaluacion_parcial'])) {
				$filtros = $this->controlador->get_filtros();
				if ($datos['con_nota_evaluacion_parcial']['valor'] == comunes::si) {					
					$filtro_evaluacion_parcial = " AND sga_alumnos.alumno IN (
														SELECT	sga_eval_detalle.alumno
														FROM	sga_eval_detalle
														WHERE	sga_eval_detalle.evaluacion = {$filtros['evaluacion']} AND
																sga_eval_detalle.nota IS NOT NULL)";
				} else {
					$filtro_evaluacion_parcial = " AND sga_alumnos.alumno NOT IN (
														SELECT	sga_eval_detalle.alumno
														FROM	sga_eval_detalle
														WHERE	sga_eval_detalle.evaluacion = {$filtros['evaluacion']} AND
																sga_eval_detalle.nota IS NOT NULL)";
				}
				$this->dep('filtro_comision')->columna('con_nota_evaluacion_parcial')->resetear_estado();
			}
			$this->s__filtro_comision = "{$this->dep('filtro_comision')->get_sql_where()} $filtro_evaluaciones $filtro_resultado $filtro_nota_cursada $filtro_nota_promocion $filtro_evaluacion_parcial";
		} else {
			unset($this->s__datos_filtro_comision);
			$this->s__filtro_comision = '';
		}
	}

	function evt__filtro_comision__cancelar()
	{
		unset($this->s__datos_filtro_comision);
		$this->s__filtro_comision = '';
	}

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
		} elseif ($this->controlador->hay_comision()) {
			$cuadro->eliminar_columnas(array('inscripto', 'en_acta', 'invalidado'));
			$alumnos = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision($filtros['comision'], false, $filtro);
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
			foreach ($this->alumnos_seleccionados as $alu) {
				$this->controlador->controlador->agregar_alumno_comision($alu);
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

			$filtros = $this->controlador->get_filtros();
			$alumnos = toba::consulta_php('co_alumnos')->get_otros_alumnos_acta_cursada($filtros['evaluacion'], $filtros['acta'], $filtros['instancia'], $filtro);
			$cuadro->set_datos($alumnos);
		}
	}

	function evt__otros_disponibles__seleccion($seleccion)
	{
		$this->controlador->controlador->agregar_alumno($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->disparar_limpieza_memoria();
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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