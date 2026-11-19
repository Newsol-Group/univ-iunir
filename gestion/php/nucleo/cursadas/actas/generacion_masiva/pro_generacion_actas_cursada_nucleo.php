<?php
class pro_generacion_actas_cursada_nucleo extends guarani_procesos_bk
{
	protected $comisiones;
	protected $origen;

	const reporte_actas_generadas = 1;
	const reporte_resultados = 2;
	const nombre = 'pro_generacion_actas_cursada';

	function set_datos($datos = array())
	{
		$this->comisiones = $datos['comisiones'];
		$this->origen = $datos['origen'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->comisiones);
		$cant_actas = 0;

		$origen = $this->origen;
		$tipo_acta = acta::tipo_normal;
		$instancia = ($origen == acta::origen_regulares) ? comision::inst_cursar : comision::inst_promocionar;

		$filtro = guarani_sql::armar_condicion_in($this->comisiones, 'sga_comisiones', 'comision');
		$datos_comisiones = guarani::co('co_comisiones')->get_comisiones_con_inscriptos_sin_acta_generada($origen, $filtro);
		$datos_comisiones = rs_convertir_asociativo_matriz($datos_comisiones, array('comision'));

		foreach ($this->comisiones as $comision) {
			$datos_comision = $datos_comisiones[$comision['comision']];
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}

			try {
				$nro_acta = $this->generar_nro_acta($origen, $tipo_acta, $comision['comision']);
				$datos_acta = $this->generar_acta($nro_acta, $comision['comision'], $instancia, $datos_comision['evaluacion']);

				$datos_comision['nro_acta'] = $nro_acta;
				$datos_comision['id_acta'] = $datos_acta['id_acta'];
				$datos_comision['cant_alumnos'] = $datos_acta['cant_alumnos'];
				$this->log->set($datos_comision, array(self::reporte_actas_generadas));
				$cant_actas++;
			} catch (toba_error $e) {
				$this->log_estado->set_error();
			}

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['actas_generadas'] = $cant_actas;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function generar_nro_acta($origen, $tipo_acta, $comision)
	{
		$parametros['origen'] = $origen;
		$parametros['tipo_acta'] = $tipo_acta;
		$parametros['comision'] = $comision;

		return toba::consulta_php('co_actas')->generar_nro_acta($parametros);
	}

	//------------------------------------------------------------------------
	//-- Generación de Acta
	//------------------------------------------------------------------------
	/**
	 * Crea un nuevo acta para la comisión.
	 *
	 * @param integer	$nro_acta
	 * @param integer	$comision	código de comisión a la cual pertenece el acta.
	 * @param integer	$instancia	instancia a la cual estará asociada (regularidad, promoción).
	 * @param integer	$evaluacion	evaluacion asociada.
	 * @return array	arreglo con los datos del acta: identificador y cantidad de alumnos asociados.
	 */
	function generar_acta($nro_acta, $comision, $instancia, $evaluacion)
	{
		try {
			toba::db()->abrir_transaccion();

			$id_acta = $this->generar_cabecera_acta($nro_acta, $comision, $instancia, $evaluacion);

			$this->generar_instancias_acta($id_acta, $comision, $instancia);
			$cant_alumnos = $this->asociar_detalles_acta($evaluacion, $id_acta, $instancia);

			toba::db()->cerrar_transaccion();

			return array('id_acta' => $id_acta, 'cant_alumnos' => $cant_alumnos);
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	/**
	 * Crea la cabecera del acta.
	 */
	function generar_cabecera_acta($nro_acta, $comision, $instancia, $evaluacion, $tipo_acta = acta::tipo_normal)
	{
		$atributos_propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		$atributos_ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($atributos_propuestas);
		
		// Datos por defecto para la creación de un acta.
		if ($instancia == comision::inst_cursar) {
			$lineas_folio = guarani::parametros()->get_valor('cur_lineas_folio_acta_regulares', $atributos_propuestas, false);
			$digital = guarani::parametros()->get_valor('cur_utiliza_acta_digital_regul', $atributos_ras, false);
			$origen = acta::origen_regulares;
		} else {
			$lineas_folio = guarani::parametros()->get_valor('cur_lineas_folio_acta_promocion', $atributos_propuestas, false);
			$digital = guarani::parametros()->get_valor('cur_utiliza_acta_digital_promo', $atributos_ras, false);
			$origen = acta::origen_promociones;
		}

		// Se inserta el nuevo acta.
		guarani::act('act_actas')->insertar_acta($nro_acta, $origen, $tipo_acta, $evaluacion, $comision, $lineas_folio, null, null, '', $digital);

		return toba::db()->recuperar_secuencia('sga_actas_seq');
	}

	/**
	 * Asocia un acta a la instancia correspondiente.
	 */
	function generar_instancias_acta($id_acta, $comision, $instancia)
	{
		// Se obtiene la escala de notas correspondiente a la instancia de la comisión.
		$where = "sga_comisiones_instancias.instancia = $instancia";
		$instancia_comision = current(toba::consulta_php('co_comisiones')->get_instancias_comision($comision, $where));
		$escala_nota = $instancia_comision['escala_nota'];

		// Se asocia al acta la instancia.
		guarani::act('act_actas')->insertar_acta_instancia($id_acta, $instancia, $escala_nota);
	}

	/**
	 * Vincula detalles de la evaluación a un acta de acuerdo a la instancia.
	 *
	 * @param integer	$evaluacion
	 * @param integer	$id_acta
	 * @param integer	$instancia
	 * @return integer	cantidad de alumnos vinculados al acta.
	 */
	function asociar_detalles_acta($evaluacion, $id_acta, $instancia)
	{
		$detalles = toba::consulta_php('co_evaluaciones')->get_detalles_acta($evaluacion, acta::id_acta_ficticia, $instancia);
		foreach ($detalles as $detalle) {
			// Se asocia al acta la instancia.
			guarani::act('act_actas')->actualizar_eval_detalle_cursada($evaluacion, $detalle['alumno'], $instancia, $id_acta);
		}
		return guarani_arreglos::count($detalles);
	}

}
?>