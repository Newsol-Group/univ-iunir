<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 */
class pro_vencimiento_regularidad_nucleo extends guarani_procesos_bk
{
	protected $datos = [];
	protected $datos_modificatoria = [];

	const reporte_modificados = 1;
	const reporte_no_modificados = 2;
	const reporte_resultados = 3;

	const nombre = 'pro_vencimiento_regularidad';
	
	function set_datos($datos = [])
	{
		$this->datos = $datos['datos'];
		$this->datos_modificatoria = $datos['datos_modificatoria'];
	}

	function procesar()
	{
		if (!empty($this->datos)) {
			$paso = 100 / guarani_arreglos::count($this->datos);
		} else {
			$paso = 100;
			$this->log_estado->incrementar_progreso($paso);
		}

		$cant_modificados = 0;
		$cant_no_modificados = 0;
		
		$fecha_vigencia_nuevo = ($this->datos_modificatoria['vencimiento'] == vencimiento_regularidad::fecha) ? $this->datos_modificatoria['fecha_vigencia_nuevo'] : null;
		
		foreach ($this->datos as $dato) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}			
			try {
				toba::db()->abrir_transaccion();

				$dato_reporte = array();
				$dato_reporte['alumno'] = $dato['apellido_y_nombre'];
				$dato_reporte['propuesta'] = $dato['propuesta_nombre'];
				$dato_reporte['actividad'] = $dato['actividad'];
				$dato_reporte['actividad_desc'] = $dato['actividad_codigo_y_nombre'];
				$dato_reporte['acta'] = $dato['acta_o_resolucion'];

				if (!is_null($fecha_vigencia_nuevo)) {
					$this->validar_fechas($dato['fecha'], $dato['fecha_vigencia'], $fecha_vigencia_nuevo);
				}

				// Se inserta la modificatoria.
				guarani::act('act_vencimientos_regularidad')->insertar_modificatoria($dato['id_acta'], $dato['alumno'], null, $this->datos_modificatoria['documento'], $dato['fecha_vigencia'], $fecha_vigencia_nuevo, $this->datos_modificatoria['observaciones']);
				// Se actualiza el detalle de acta.
				guarani::act('act_actas')->actualizar_fecha_vigencia_detalle($dato['id_acta'], $dato['alumno'], $fecha_vigencia_nuevo);				
				
				$dato_reporte['comision_desc'] = 'Comision: '.$dato['comision_nombre'];
				if (!empty($dato['turno_nombre'])) {
					$dato_reporte['comision_desc'] .= ' - Turno: '.$dato['turno_nombre'];
				}
				$dato_reporte['comision_desc'] .= ' - Ubicación: '.$dato['ubicacion_nombre'];
				$dato_reporte['comision'] = $dato['comision'];

				$dato_reporte['identificacion'] = $dato['documento_principal'];
				$dato_reporte['legajo'] = $dato['legajo'];

				$this->log->set($dato_reporte, array(self::reporte_modificados));
				$cant_modificados++;

				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {

				toba::db()->abortar_transaccion();
                                
				$this->log_estado->set_error();

				$dato_reporte['comision'] = $dato['comision_nombre'];
				$dato_reporte['motivo'] = $e->get_mensaje();

				$this->log->set($dato_reporte, array(self::reporte_no_modificados));
				$cant_no_modificados++;
			}

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['modificados'] = $cant_modificados;
		$mensaje_resultado['no_modificados'] = $cant_no_modificados;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function validar_fechas($fecha_regularidad, $fecha_vigencia_actual, $fecha_vigencia_nuevo)
	{
		// La fecha de vencimiento debe ser mayor a la fecha de aprobación de la cursada
		if (guarani_fecha::es_mayor($fecha_regularidad, $fecha_vigencia_nuevo)) {
			$mje = guarani_mensajes::get('800_control_fechas', array('de regularidad', 'mayor', 'de vigencia'));
			throw guarani::error($mje);
		}
		
		// La fecha de vencimiento debe ser distinta de la fecha de vencimiento de la regularidad actual del alumno.
		if (guarani_fecha::es_igual($fecha_vigencia_nuevo, $fecha_vigencia_actual)) {
			$mje = guarani_mensajes::get('800_control_fechas', array('de vigencia nueva', 'igual', 'de vigencia actual'));
			throw guarani::error($mje);
		}
	}
}