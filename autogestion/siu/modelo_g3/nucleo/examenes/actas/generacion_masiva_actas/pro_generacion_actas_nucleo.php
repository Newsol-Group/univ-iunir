<?php
class pro_generacion_actas_nucleo extends guarani_procesos_bk
{
	protected $datos;

	const actas_x_mesa = 'M';
	const actas_x_instancia = 'I';
	const reporte_resultados = 'cantidad_actas';

	function set_datos($datos = array())
	{
		$this->datos = $datos['datos'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->datos);
		$cantidad_actas = 0;

		$origen = acta::origen_examen;
		$tipo_acta = acta::tipo_normal;

		foreach ($this->datos as $clave_llamado => $valor_llamado) {
			$composicion = $valor_llamado['composicion'];
			$renglones_folio = $valor_llamado['renglones_folio'];
			$digital = $valor_llamado['digital'];
			
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			
			$llamado_mesa = $valor_llamado['llamado_mesa'];
			$evaluacion = $this->get_evaluacion_automatica_mesa($llamado_mesa);
			$alumnos = $this->get_alumnos_de_evaluacion($evaluacion);

			$instancias = $this->get_instancias_llamado($llamado_mesa);

			if ($composicion == self::actas_x_instancia) {
				foreach ($instancias as $instancia) {
					$alumnos_instancia = $this->get_alumnos_instancia($alumnos, $instancia['instancia']);
					if (!empty($alumnos_instancia)) {
						try {
							if (!toba::consulta_php('co_actas')->existe_acta_no_anulada_llamado_mesa($llamado_mesa)) {
								toba::db()->abrir_transaccion();
								$nro_acta = $this->generar_nro_acta($origen, $tipo_acta, $llamado_mesa);
								$id_acta = guarani::act('act_actas')->insertar_acta_examen($nro_acta, $origen, $evaluacion, $llamado_mesa, $renglones_folio, $tipo_acta, '', null, null, $digital);
								guarani::act('act_actas')->insertar_acta_instancia($id_acta, $instancia['instancia'], $instancia['escala_nota']);
								foreach ($alumnos_instancia as $alumno_instancia) {
									guarani::act('act_actas')->actualizar_eval_detalle_examen($evaluacion, $alumno_instancia['alumno'], $id_acta);
								}
								toba::db()->cerrar_transaccion();
								$mensaje = array();
								$mensaje['id_acta'] = $id_acta;
								$mensaje['nro_acta'] = $nro_acta;
								$mensaje['llamado_mesa'] = $llamado_mesa;
								$this->log->set($mensaje, array('actas'));
								$cantidad_actas++;
							}
						} catch (toba_error $e) {
							toba::db()->abortar_transaccion();
							$this->log_estado->set_error();
						}
					}
				}
			} else {
				try {
					if (!toba::consulta_php('co_actas')->existe_acta_no_anulada_llamado_mesa($llamado_mesa)) {
						toba::db()->abrir_transaccion();
						$nro_acta = $this->generar_nro_acta($origen, $tipo_acta, $llamado_mesa);
						$id_acta = guarani::act('act_actas')->insertar_acta_examen($nro_acta, $origen, $evaluacion, $llamado_mesa, $renglones_folio, $tipo_acta, '', null, null, $digital);
						foreach ($instancias as $instancia) {
							guarani::act('act_actas')->insertar_acta_instancia($id_acta, $instancia['instancia'], $instancia['escala_nota']);
						}
						foreach ($alumnos as $alumno) {
							guarani::act('act_actas')->actualizar_eval_detalle_examen($evaluacion, $alumno['alumno'], $id_acta);
						}
						toba::db()->cerrar_transaccion();
						$mensaje = array();
						$mensaje['id_acta'] = $id_acta;
						$mensaje['nro_acta'] = $nro_acta;
						$mensaje['llamado_mesa'] = $llamado_mesa;
						$this->log->set($mensaje, array('actas'));
						$cantidad_actas++;
					}
				} catch (toba_error $e) {
					toba::db()->abortar_transaccion();
					$this->log_estado->set_error();
					// Se deja el registro del mensaje de error completo en el log
					ei_arbol($e->get_mensaje());
				}
			}

			$this->log_estado->incrementar_progreso($paso);
			$this->log->guardar();
		}

		$mensaje = array();
		$mensaje['cantidad_actas'] = $cantidad_actas;
		$this->log->set($mensaje, array('cantidad_actas'));
		$this->log->guardar();
	}

	function get_instancias_llamado($llamado_mesa)
	{
		$datos_mesa_examen = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($llamado_mesa);
		return toba::consulta_php('co_mesas_de_examen')->get_instancias_mesa_examen($datos_mesa_examen['mesa_examen']);
	}

	function get_evaluacion_automatica_mesa($llamado_mesa)
	{
		$datos_mesa_examen = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($llamado_mesa);
		$datos_evaluacion = toba::consulta_php('co_mesas_de_examen')->get_evaluacion_automatica_mesa($datos_mesa_examen['entidad']);
		return $datos_evaluacion['evaluacion'];
	}

	function generar_nro_acta($origen, $tipo_acta, $llamado_mesa)
	{
		$parametros['origen'] = $origen;
		$parametros['tipo_acta'] = $tipo_acta;
		$parametros['llamado_mesa'] = $llamado_mesa;

		return toba::consulta_php('co_actas')->generar_nro_acta($parametros);
	}

	function get_alumnos_de_evaluacion($evaluacion)
	{
		// Retorna los alumnos de la evaluación automática
		return toba::consulta_php('co_evaluaciones')->get_detalle_evaluacion_examen($evaluacion);
	}

	function get_alumnos_instancia($alumnos, $instancia)
	{
		foreach ($alumnos as $clave => $valor) {
			if ($valor['instancia'] != $instancia) {
				unset($alumnos[$clave]);
			}
		}
		return $alumnos;
	}
}
?>